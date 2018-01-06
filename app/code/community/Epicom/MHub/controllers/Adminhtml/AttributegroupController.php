<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Adminhtml_AttributegroupController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('mhub/attributegroups');
    }

    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('epicom/mhub/attributegroups')->_addBreadcrumb(Mage::helper('adminhtml')->__('Attribute Groups Manager'),Mage::helper('adminhtml')->__('Attribute Groups Manager'));

        return $this;
    }

    public function indexAction()
    {
        $this->_title($this->__('MHub'));
        $this->_title($this->__('Manage Attribute Groups'));

        $this->_initAction();
        $this->renderLayout();
    }

    public function newAction ()
    {
        $this->_title(Mage::helper ('mhub')->__('MHub'));
        $this->_title(Mage::helper ('mhub')->__('Manage Attribute Groups'));
        $this->_title(Mage::helper ('mhub')->__('Add New'));

        $this->_initAction ();

        $this->_addLeft ($this->getLayout ()->createBlock ('mhub/adminhtml_attributegroup_new_tabs'));
        $this->_addContent ($this->getLayout ()->createBlock ('mhub/adminhtml_attributegroup_new'));

        $this->renderLayout ();
    }

    public function editAction ()
    {
        $this->_title(Mage::helper ('mhub')->__('MHub'));
        $this->_title(Mage::helper ('mhub')->__('Manage Attribute Groups'));
        $this->_title(Mage::helper ('mhub')->__('Edit Item'));

        $id = $this->getRequest ()->getParam ('id');
        if (!empty ($id))
        {
            $attributeGroup = Mage::getModel ('mhub/attributegroup')->load ($id);
            if ($attributeGroup && $attributeGroup->getId ())
            {
                Mage::register ('current_attributegroup', $attributeGroup);
            }
            else
            {
                Mage::getSingleton ('adminhtml/session')->addError (Mage::helper ('mhub')->__('Attribute group does not exist.'));

                return $this->_redirect ('*/*/index');
            }
        }

        $this->_title($this->__('MHub'));
        $this->_title($this->__('Manage Attribute Groups'));

        $this->_initAction ();

        $this->_setActiveMenu ('mhub/attributegroup');
        $this->_addLeft ($this->getLayout ()->createBlock ('mhub/adminhtml_attributegroup_edit_tabs'));
        $this->_addContent ($this->getLayout ()->createBlock ('mhub/adminhtml_attributegroup_edit'));

        $this->renderLayout ();
    }

    public function saveAction ()
    {
        $post = $this->getRequest ()->getPost ();
        if ($post)
        {
            try
            {
                $post ['attribute_codes'] = implode (',', $post ['attribute_codes']);

                $attributeGroup = Mage::getModel ('mhub/attributegroup')
                    ->setId ($this->getRequest ()->getParam ('id'))
                    ->addData ($post)
                    ->save()
                ;
            }
            catch (Exception $e)
            {
                Mage::getSingleton ('adminhtml/session')->addError ($e->getMessage ());

                return $this->_redirect ('*/*/edit', array ('id' => $this->getRequest ()->getParam ('id')));
            }
        }

        Mage::getSingleton ('adminhtml/session')->addSuccess (Mage::helper ('mhub')->__('Attribute group was successfully saved'));

        $this->_redirect ('*/*/index');
    }

    public function deleteAction()
    {
        $id = $this->getRequest ()->getParam ('id');
        if ($id)
        {
            try
            {
                $attributeGroup = Mage::getModel ('mhub/attributegroup')
                    ->setId ($id)
                    ->delete ()
                ;
            } 
            catch (Exception $e)
            {
                Mage::getSingleton ('adminhtml/session')->addError ($e->getMessage ());

                return $this->_redirect ('*/*/edit', array ('id' => $id));
            }
        }

        Mage::getSingleton ('adminhtml/session')->addSuccess (Mage::helper ('mhub')->__('Attribute group was successfully deleted'));

        $this->_redirect ('*/*/index');
    }

    public function massRemoveAction()
    {
        try
        {
            $ids = $this->getRequest()->getPost('entity_ids', array());
            foreach ($ids as $id)
            {
                $model = Mage::getModel('mhub/attributegroup');
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

