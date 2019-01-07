<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Product_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('productsGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('mhub/product')->getCollection();
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
        $this->addColumn('product_id', array(
            'header' => Mage::helper('mhub')->__('Product ID'),
            'index'  => 'product_id',
        ));
        $this->addColumn('external_id', array(
            'header' => Mage::helper('mhub')->__('External ID'),
            'index'  => 'external_id',
        ));
        $this->addColumn('external_code', array(
            'header' => Mage::helper('mhub')->__('External Code'),
            'index'  => 'external_code',
        ));
        $this->addColumn('external_sku', array(
            'header' => Mage::helper('mhub')->__('External SKU'),
            'index'  => 'external_sku',
        ));
        $this->addColumn('operation', array(
            'header' => Mage::helper('mhub')->__('Operation'),
            'index'  => 'operation',
            'type'    => 'options',
            'options' => Mage::getModel ('mhub/adminhtml_system_config_source_operation')->toArray (),
        ));
        $this->addColumn('method', array(
            'header' => Mage::helper('mhub')->__('Method'),
            'index'  => 'method',
        ));
        $this->addColumn('send_date', array(
            'header' => Mage::helper('mhub')->__('Send Date'),
            'index'  => 'send_date',
        ));
        $this->addColumn('parameters', array(
            'header' => Mage::helper('mhub')->__('Parameters'),
            'index'  => 'parameters',
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
            'getter'  => 'getProductId',
            'filter'   => false,
            'sortable' => false,
            'index'    => 'stores',
            'actions' => array(
                array(
                    'caption' => Mage::helper('catalog')->__('Edit'),
                    'url'     => array(
                        'base'   => 'adminhtml/catalog_product/edit',
                        'params' => array('store'=>$this->getRequest()->getParam('store'))
                    ),
                    'field'   => 'id'
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

        $this->getMassactionBlock()->addItem('pending_products', array(
            'label'   => Mage::helper('mhub')->__('Pending Product(s)'),
            'url'     => $this->getUrl('*/adminhtml_product/massPending'),
            'confirm' => Mage::helper('mhub')->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('remove_products', array(
            'label'   => Mage::helper('mhub')->__('Remove Product(s)'),
            'url'     => $this->getUrl('*/adminhtml_product/massRemove'),
            'confirm' => Mage::helper('mhub')->__('Are you sure?')
        ));

        return $this;
    }
}

