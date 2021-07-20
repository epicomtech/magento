<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Product_Allowed_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('productAllowedGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('mhub/product_allowed')->getCollection ();

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
        $this->addColumn('code', array(
            'header' => Mage::helper('mhub')->__('Code'),
            'index'  => 'code',
        ));
        $this->addColumn('sku', array(
            'header' => Mage::helper('mhub')->__('SKU'),
            'index'  => 'sku',
        ));
        $this->addColumn('created_at', array(
            'header'  => Mage::helper('mhub')->__('Created At'),
            'index'   => 'created_at',
            'type'    => 'datetime',
        ));

        $this->addExportType ('*/*/exportCsv', Mage::helper ('mhub')->__('CSV'));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        // return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

	protected function _prepareMassaction ()
	{
		$this->setMassactionIdField ('entity_id');
		$this->getMassactionBlock ()->setFormFieldName ('entity_ids')
		    ->setUseSelectAll (true)
		    ->addItem ('remove_product_allowed', array(
				 'label'   => Mage::helper ('mhub')->__('Remove Products Allowed'),
				 'url'     => $this->getUrl ('*/adminhtml_product_allowed/massRemove'),
				 'confirm' => Mage::helper ('mhub')->__('Are you sure?')
			))
        ;

		return $this;
	}
}

