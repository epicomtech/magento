<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Shipment_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('shipmentsGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('mhub/shipment')->getCollection();
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
        $this->addColumn('order_id', array(
            'header' => Mage::helper('mhub')->__('Order ID'),
            'index'  => 'order_id',
        ));
        $this->addColumn('order_increment_id', array(
            'header' => Mage::helper('mhub')->__('Order Inc. ID'),
            'index'  => 'order_increment_id',
        ));
        $this->addColumn('external_order_id', array(
            'header' => Mage::helper('mhub')->__('Ext. Order ID'),
            'index'  => 'external_order_id',
        ));
        $this->addColumn('shipment_id', array(
            'header' => Mage::helper('mhub')->__('Shipment ID'),
            'index'  => 'shipment_id',
        ));
        $this->addColumn('shipment_increment_id', array(
            'header' => Mage::helper('mhub')->__('Shipment Inc. ID'),
            'index'  => 'shipment_increment_id',
        ));
        $this->addColumn('external_shipment_id', array(
            'header' => Mage::helper('mhub')->__('Ext. Shipment ID'),
            'index'  => 'external_shipment_id',
        ));
        $this->addColumn('external_event_id', array(
            'header' => Mage::helper('mhub')->__('Ext. Event ID'),
            'index'  => 'external_event_id',
        ));
/*
        $this->addColumn('external_provider_id', array(
            'header' => Mage::helper('mhub')->__('Ext. Provider ID'),
            'index'  => 'external_provider_id',
        ));
*/
        $this->addColumn('event', array(
            'header'  => Mage::helper('mhub')->__('Event'),
            'index'   => 'event',
            'type'    => 'options',
            'options' => Mage::getModel ('mhub/adminhtml_system_config_source_event')->toArray (),
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
        $this->getMassactionBlock()->addItem('remove_shipments', array(
            'label'   => Mage::helper('mhub')->__('Remove Shipments'),
            'url'     => $this->getUrl('*/adminhtml_shipment/massRemove'),
            'confirm' => Mage::helper('mhub')->__('Are you sure?')
        ));

        return $this;
    }
}

