<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\Console;

use Magento\Framework\Filesystem\Driver\File;
use Symfony\Component\Console\Application as SymfonyApplication;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\Shell\ComplexParameter;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * Magento2 CLI Application. This is the hood for all command line tools supported by Magento.
 *
 * {@inheritdoc}
 */
class Cli extends SymfonyApplication
{
    /**
     * Name of input option
     */
    const INPUT_KEY_BOOTSTRAP = 'bootstrap';

    /** @var \Zend\ServiceManager\ServiceManager */
    private $serviceManager;

    /**
     * @param string $name    The name of the application
     * @param string $version The version of the application
     */
    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        $this->serviceManager = \Zend\Mvc\Application::init(require BP . '/setup/config/application.config.php')
            ->getServiceManager();
        /**
         * Temporary workaround until the compiler is able to clear the generation directory. (MAGETWO-44493)
         */
        if (class_exists('Magento\Setup\Console\CompilerPreparation')) {
            (new \Magento\Setup\Console\CompilerPreparation($this->serviceManager, new ArgvInput(), new File()))
                ->handleCompilerEnvironment();
        }

        parent::__construct($name, $version);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        return array_merge(parent::getDefaultCommands(), $this->getApplicationCommands());
    }

    /**
     * Gets application commands
     *
     * @return array
     */
    protected function getApplicationCommands()
    {
        $setupCommands   = [];
        $modulesCommands = [];

        $bootstrapParam = new ComplexParameter(self::INPUT_KEY_BOOTSTRAP);
        $params = $bootstrapParam->mergeFromArgv($_SERVER, $_SERVER);
        $params[Bootstrap::PARAM_REQUIRE_MAINTENANCE] = null;
        $bootstrap = Bootstrap::create(BP, $params);
        $objectManager = $bootstrap->getObjectManager();
        /** @var \Magento\Setup\Model\ObjectManagerProvider $omProvider */
        $omProvider = $this->serviceManager->get('Magento\Setup\Model\ObjectManagerProvider');
        $omProvider->setObjectManager($objectManager);

        if (class_exists('Magento\Setup\Console\CommandList')) {
            $setupCommandList = new \Magento\Setup\Console\CommandList($this->serviceManager);
            $setupCommands = $setupCommandList->getCommands();
        }

        if ($objectManager->get('Magento\Framework\App\DeploymentConfig')->isAvailable()) {
            /** @var \Magento\Framework\Console\CommandList $commandList */
            $commandList = $objectManager->create('Magento\Framework\Console\CommandList');
            $modulesCommands = $commandList->getCommands();
        }

        $vendorCommands = $this->getVendorCommands($objectManager);

        $commandsList = array_merge(
            $setupCommands,
            $modulesCommands,
            $vendorCommands
        );

        return $commandsList;
    }

    /**
     * Gets vendor commands
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @return array
     */
    protected function getVendorCommands($objectManager)
    {
        $commands = [];
        foreach (CommandLocator::getCommands() as $commandListClass) {
            if (class_exists($commandListClass)) {
                $commands = array_merge(
                    $commands,
                    $objectManager->create($commandListClass)->getCommands()
                );
            }
        }
        return $commands;
    }
}
