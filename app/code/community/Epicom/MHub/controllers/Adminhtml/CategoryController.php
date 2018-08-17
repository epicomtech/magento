<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Adminhtml_CategoryController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('mhub/category');
    }

    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('epicom/mhub/category')->_addBreadcrumb(Mage::helper('adminhtml')->__('Categories Manager'),Mage::helper('adminhtml')->__('Categories Manager'));

        return $this;
    }

    public function indexAction()
    {
        $this->_title($this->__('MHub'));
        $this->_title($this->__('Manage Categories'));

        $this->_initAction();
        $this->renderLayout();
    }

    public function massRemoveAction()
    {
        try
        {
            $ids = $this->getRequest()->getPost('entity_ids', array());
            foreach ($ids as $id)
            {
                $model = Mage::getModel('mhub/category');
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

