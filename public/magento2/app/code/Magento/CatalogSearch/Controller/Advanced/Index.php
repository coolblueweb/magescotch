<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogSearch\Controller\Advanced;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @return void
     */
    public function executeInternal()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
