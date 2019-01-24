<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2019 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Error_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('errorsGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('mhub/error')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header' => Mage::helper('mhub')->__('ID'),
            'align'  =>'right',
            'width'  => '100px',
            'type'   => 'number',
            'index'  => 'entity_id',
        ));
        $this->addColumn('url', array(
            'header'  => Mage::helper('mhub')->__('URL'),
            'index'   => 'url',
        ));
        $this->addColumn('code', array(
            'header'  => Mage::helper('mhub')->__('Code'),
            'index'   => 'code',
            'type'    => 'number',
        ));
        $this->addColumn('message', array(
            'header'  => Mage::helper('mhub')->__('Message'),
            'index'   => 'message',
        ));
        $this->addColumn('created_at', array(
            'header' => Mage::helper('mhub')->__('Created At'),
            'index'  => 'created_at',
            'type'   => 'datetime',
            'width'  => '100px',
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
        $this->getMassactionBlock()->addItem('remove_errors', array(
            'label'   => Mage::helper('mhub')->__('Remove Errors'),
            'url'     => $this->getUrl('*/adminhtml_error/massRemove'),
            'confirm' => Mage::helper('mhub')->__('Are you sure?')
        ));

        return $this;
    }
}

