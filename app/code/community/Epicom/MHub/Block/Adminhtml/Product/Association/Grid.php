<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Product_Association_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('productAssociationsGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('mhub/product_association')->getCollection ();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header' => Mage::helper('mhub')->__('ID'),
            'align'  =>'right',
            'width'  => '50px',
            'type'   => 'number',
            'index'  => 'entity_id',
        ));
        $this->addColumn('website_id', array(
            'header'  => Mage::helper('mhub')->__('Website'),
            'index'   => 'website_id',
            'type'    => 'options',
            'options' => Mage::getSingleton ('adminhtml/system_store')->getWebsiteOptionHash (true),
        ));
        $this->addColumn('store_id', array(
            'header'  => Mage::helper('mhub')->__('Store'),
            'index'   => 'store_id',
            'type'    => 'options',
            'options' => Mage::getSingleton ('adminhtml/system_store')->getStoreOptionHash (true),
        ));
        $this->addColumn('parent_sku', array(
            'header' => Mage::helper('mhub')->__('Parent SKU'),
            'index'  => 'parent_sku',
        ));
        $this->addColumn('sku', array(
            'header' => Mage::helper('mhub')->__('SKU'),
            'index'  => 'sku',
        ));
        $this->addColumn('is_modified', array(
            'header'  => Mage::helper('mhub')->__('Is Modified'),
            'index'   => 'is_modified',
            'type'    => 'options',
            'options' => Mage::getModel ('adminhtml/system_config_source_yesno')->toArray (),
        ));

        $this->addExportType ('*/*/exportCsv', Mage::helper ('mhub')->__('CSV'));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        // return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}

