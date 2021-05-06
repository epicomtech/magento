<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Order_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('ordersGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('mhub/order')->getCollection();
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
        $this->addColumn('scope_id', array(
            'header'  => Mage::helper('mhub')->__('Scope'),
            'index'   => 'scope_id',
            'type'    => 'options',
            'options' => Mage::getSingleton ('adminhtml/system_store')->getStoreOptionHash (true),
        ));
        $this->addColumn('order_id', array(
            'header' => Mage::helper('mhub')->__('Order ID'),
            'index'  => 'order_id',
            'type'   => 'number',
        ));
        $this->addColumn('order_increment_id', array(
            'header' => Mage::helper('mhub')->__('Order Inc. ID'),
            'index'  => 'order_increment_id',
        ));
        $this->addColumn('order_external_id', array(
            'header' => Mage::helper('mhub')->__('Order Ext. ID'),
            'index'  => 'order_external_id',
        ));
        $this->addColumn('operation', array(
            'header'  => Mage::helper('mhub')->__('Operation'),
            'index'   => 'operation',
            'type'    => 'options',
            'options' => Mage::getModel ('mhub/adminhtml_system_config_source_operation')->toArray (),
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
            'getter'  => 'getOrderId',
            'filter'   => false,
            'sortable' => false,
            'index'    => 'stores',
            'actions' => array(
                array(
                    'caption' => Mage::helper('mhub')->__('Edit'),
                    'url'     => array(
                        'base'   => 'adminhtml/sales_order/view',
                        'params' => array('store'=>$this->getRequest()->getParam('store'))
                    ),
                    'field'   => 'order_id'
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
        $this->getMassactionBlock()->addItem('remove_orders', array(
            'label'   => Mage::helper('mhub')->__('Remove Orders'),
            'url'     => $this->getUrl('*/adminhtml_order/massRemove'),
            'confirm' => Mage::helper('mhub')->__('Are you sure?')
        ));

        return $this;
    }
}

