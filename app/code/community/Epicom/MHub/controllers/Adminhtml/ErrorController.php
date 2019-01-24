<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2019 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Adminhtml_ErrorController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('epicom/mhub/error');
    }

    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('epicom/mhub/error')->_addBreadcrumb(
            Mage::helper('adminhtml')->__('Errors Manager'),
            Mage::helper('adminhtml')->__('Errors Manager')
        );

        return $this;
    }

    public function indexAction()
    {
        $this->_title($this->__('MHub'));
        $this->_title($this->__('Manage Errors'));

        $this->_initAction();
        $this->renderLayout();
    }

    /**
     * Export customer grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName   = 'errors.csv';
        $content    = $this->getLayout()
            ->createBlock('mhub/adminhtml_error_grid')
            ->getCsvFile()
        ;

        $this->_prepareDownloadResponse ($fileName, $content);
    }

    public function massRemoveAction()
    {
        try
        {
            $ids = $this->getRequest()->getPost('entity_ids', array());
            foreach ($ids as $id)
            {
                $model = Mage::getModel('mhub/error');
                $model->setId($id)->delete();
            }

            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Item(s) was successfully removed'));
        }
        catch (Exception $e)
        {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');
    }
}

