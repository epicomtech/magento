<?php
/*
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Quote_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct ()
	{
		parent::__construct ();

		$this->setId ('mhubQuoteGrid');
		$this->setDefaultSort ('entity_id');
		$this->setDefaultDir ('DESC');
		$this->setSaveParametersInSession (true);
	}

	protected function _prepareCollection ()
	{
		$collection = Mage::getModel ('mhub/quote')->getCollection ();

        $collection->getSelect()->joinLeft(
            array ('customer' => Mage::getSingleton ('core/resource')->getTableName ('customer_entity')),
            'main_table.customer_id = customer.entity_id AND customer.is_active = 1',
            array(
                'customer_email' => 'customer.email',
            )
        );

		$this->setCollection ($collection);

		return parent::_prepareCollection ();
	}

	protected function _prepareColumns ()
	{
        $store = $this->_getStore();

		$this->addColumn ('entity_id', array(
		    'header' => Mage::helper ('mhub')->__('ID'),
		    'align'  => 'right',
		    'width'  => '50px',
	        'type'   => 'number',
		    'index'  => 'entity_id',
		));
		$this->addColumn ('store_id', array(
		    'header'  => Mage::helper ('mhub')->__('Store'),
		    'index'   => 'store_id',
            'type'    => 'store',
            'filter_index' => 'main_table.store_id',
		));
		$this->addColumn ('customer_email', array(
		    'header'  => Mage::helper ('mhub')->__('Customer Email'),
		    'index'   => 'customer_email',
            'filter_index' => 'customer.email',
		));
		$this->addColumn ('quote_id', array(
		    'header'  => Mage::helper ('mhub')->__('Quote ID'),
		    'index'   => 'quote_id',
            'type'    => 'number',
		));
		$this->addColumn ('postcode', array(
		    'header' => Mage::helper ('mhub')->__('Postcode'),
		    'index'  => 'postcode',
		));
		$this->addColumn ('sku', array(
		    'header' => Mage::helper ('mhub')->__('SKU'),
		    'index'  => 'sku',
		));
		$this->addColumn ('method', array(
		    'header' => Mage::helper ('mhub')->__('Method'),
		    'index'  => 'method',
		));
		$this->addColumn ('title', array(
		    'header' => Mage::helper ('mhub')->__('Title'),
		    'index'  => 'title',
		));
		$this->addColumn ('price', array(
		    'header' => Mage::helper ('mhub')->__('Price'),
		    'index'  => 'price',
            'type'   => 'price',
            'currency_code' => $store->getBaseCurrency()->getCode(),
		));
		$this->addColumn ('days', array(
		    'header' => Mage::helper ('mhub')->__('Days'),
		    'index'  => 'days',
            'type'   => 'number',
		));
		$this->addColumn ('created_at', array(
			'header' => Mage::helper ('mhub')->__('Created At'),
			'index'  => 'created_at',
            'type'   => 'datetime',
            'width'  => '100px',
            'filter_index' => 'main_table.created_at',
		));

        $this->addExportType ('*/*/exportCsv', Mage::helper ('mhub')->__('CSV'));

		return parent::_prepareColumns ();
	}

	public function getRowUrl ($row)
	{
        // nothing here
	}

    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);

        return Mage::app()->getStore($storeId);
    }
}

