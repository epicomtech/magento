<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Adminhtml_Product_AllowedController extends Mage_Adminhtml_Controller_Action
{
    public const PRODUCT_ALLOWED_QTY = 1000000;

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('epicom/mhub/product_allowed');
    }

    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('epicom/mhub/product_allowed')
            ->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Products Allowed Manager'),
                Mage::helper('adminhtml')->__('Products Allowed Manager')
            )
        ;

        return $this;
    }

    public function indexAction()
    {
        $this->_title($this->__('MHub'));
        $this->_title($this->__('Manage Products Allowed'));

        $this->_initAction();

        $this->renderLayout();
    }

    public function newAction ()
    {
        $this->_title(Mage::helper ('mhub')->__('MHub'));
        $this->_title(Mage::helper ('mhub')->__('Manage Products Allowed'));
        $this->_title(Mage::helper ('mhub')->__('Upload New CSV'));

        $this->_initAction ();

        $this->_addLeft ($this->getLayout ()->createBlock ('mhub/adminhtml_product_allowed_new_tabs'));
        $this->_addContent ($this->getLayout ()->createBlock ('mhub/adminhtml_product_allowed_new'));

        $this->renderLayout ();
    }

    public function saveAction()
    {
        try
        {
            if (array_key_exists('csv', $_FILES))
            {
                $tmpName = $_FILES['csv']['tmp_name'];

                if (($handle = fopen($tmpName, 'r')) !== false)
                {
                    /*
                    $write = Mage::getSingleton('core/resource')->getConnection('core_write');

                    $write->delete(Mage::getSingleton('core/resource')->getTableName(Epicom_MHub_Helper_Data::PRODUCT_ALLOWED_TABLE));
                    */
                    $rows = 0;

                    while (($data = fgetcsv ($handle, self::PRODUCT_ALLOWED_QTY, ',')) !== false)
                    {
                        $productCode = $data[0];
                        $productSku  = $data[1];

                        if (!strcmp(strtolower($productCode), 'productcode')
                            && !strcmp(strtolower($productSku), 'productsku'))
                        {
                            continue;
                        }

                        Mage::getModel('mhub/product_allowed')
                            ->setCode($productCode)
                            ->setSku($productSku)
                            ->setCreatedAt(date('c'))
                            ->save();

                        $rows ++;
                    }

                    Mage::getSingleton ('adminhtml/session')->addSuccess (Mage::helper ('mhub')->__('%s rows imported.', $rows));

                    fclose ($handle);
                }
            }
        }
        catch (Exception $e)
        {
            Mage::getSingleton ('adminhtml/session')->addError ($e->getMessage ());
        }

        return $this->_redirect('*/*/index');
    }

    /**
     * Export customer grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName   = 'productalloweds.csv';
        $content    = $this->getLayout()
            ->createBlock('mhub/adminhtml_product_allowed_grid')
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
                $model = Mage::getModel('mhub/product_allowed');
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

