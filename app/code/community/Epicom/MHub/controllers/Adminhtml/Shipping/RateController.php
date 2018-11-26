<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Adminhtml_Shipping_RateController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('epicom/mhub/shipping_rate');
    }

    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('epicom/mhub/shipping_rate')
            ->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Shipping Rates Manager'),
                Mage::helper('adminhtml')->__('Shipping Rates Manager')
            )
        ;

        return $this;
    }

    public function indexAction()
    {
        $this->_title($this->__('MHub'));
        $this->_title($this->__('Manage Shipping Rates'));

        $this->_initAction();

        $this->renderLayout();
    }

    /**
     * Export customer grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName   = 'shippingrates.csv';
        $content    = $this->getLayout()
            ->createBlock('mhub/adminhtml_shipping_rate_grid')
            ->getCsvFile()
        ;

        $this->_prepareDownloadResponse ($fileName, $content);
    }
}

