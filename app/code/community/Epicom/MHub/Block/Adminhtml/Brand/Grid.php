<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Brand_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('brandsGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('mhub/brand')->getCollection();
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
        $this->addColumn('attribute_id', array(
            'header' => Mage::helper('mhub')->__('Attribute'),
            'index'  => 'attribute_id',
            'type'    => 'options',
            'options' => Mage::getModel ('mhub/adminhtml_system_config_source_attributes_product')->toArray (),
        ));
        $this->addColumn('option_id', array(
            'header' => Mage::helper('mhub')->__('Option ID'),
            'index'  => 'option_id',
            'type'   => 'number',
        ));
        $this->addColumn('name', array(
            'header' => Mage::helper('mhub')->__('Name'),
            'index'  => 'name',
        ));
        $this->addColumn('status', array(
            'header'  => Mage::helper('mhub')->__('Status'),
            'index'   => 'status',
            'type'    => 'options',
            'options' => Mage::getModel ('mhub/adminhtml_system_config_source_status')->toArray (),
        ));
        $this->addColumn('message', array(
            'header' => Mage::helper('mhub')->__('Message'),
            'index'  => 'message',
        ));
        $this->addColumn('updated_at', array(
            'header' => Mage::helper('mhub')->__('Updated At'),
            'index'  => 'updated_at',
            'type'   => 'datetime',
            'width'  => '100px',
        ));
        $this->addColumn('synced_at', array(
            'header' => Mage::helper('mhub')->__('Synced At'),
            'index'  => 'synced_at',
            'type'   => 'datetime',
            'width'  => '100px',
        ));

        $this->addColumn('action', array(
            'header'  => Mage::helper('mhub')->__('Action'),
            'width'   => '50px',
            'type'    => 'action',
            'getter'  => 'getAttributeId',
            'filter'   => false,
            'sortable' => false,
            'index'    => 'stores',
            'actions' => array(
                array(
                    'caption' => Mage::helper('catalog')->__('Edit'),
                    'url'     => array(
                        'base'   => 'adminhtml/catalog_product_attribute/edit',
                        'params' => array('store'=>$this->getRequest()->getParam('store'))
                    ),
                    'field'   => 'attribute_id'
                )
            ),
        ));

        $this->addExportType ('*/*/exportCsv', Mage::helper ('mhub')->__('CSV'));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        // return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('entity_ids');
        $this->getMassactionBlock()->setUseSelectAll(true);
        $this->getMassactionBlock()->addItem('remove_brands', array(
            'label'   => Mage::helper('mhub')->__('Remove Brands'),
            'url'     => $this->getUrl('*/adminhtml_brand/massRemove'),
            'confirm' => Mage::helper('mhub')->__('Are you sure?')
        ));

        return $this;
    }
}

