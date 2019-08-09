<?php
/*
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_NF_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct ()
	{
		parent::__construct ();

		$this->setId ('mhubNFGrid');
		$this->setDefaultSort ('entity_id');
		$this->setDefaultDir ('DESC');
		$this->setSaveParametersInSession (true);
	}

	protected function _prepareCollection ()
	{
		$collection = Mage::getModel ('mhub/nf')->getCollection ();
		$this->setCollection ($collection);

		return parent::_prepareCollection ();
	}

	protected function _prepareColumns ()
	{
		$this->addColumn ('entity_id', array(
		    'header' => Mage::helper ('mhub')->__('ID'),
		    'align'  => 'right',
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
		$this->addColumn ('order_increment_id', array(
		    'header'  => Mage::helper ('mhub')->__('Order Inc. ID'),
		    'index'   => 'order_increment_id',
		));
		$this->addColumn ('skus', array(
		    'header'  => Mage::helper ('mhub')->__('SKUs'),
		    'index'   => 'skus',
		));
		$this->addColumn ('number', array(
		    'header'  => Mage::helper ('mhub')->__('Number'),
		    'index'   => 'number',
		));
		$this->addColumn ('series', array(
		    'header' => Mage::helper ('mhub')->__('Series'),
		    'index'  => 'series',
		));
		$this->addColumn ('access_key', array(
		    'header' => Mage::helper ('mhub')->__('Access Key'),
		    'index'  => 'access_key',
		));
        $this->addColumn ('cfop', array(
            'header' => Mage::helper ('mhub')->__('CFOP'),
            'index'  => 'cfop',
        ));
		$this->addColumn ('link', array(
		    'header' => Mage::helper ('mhub')->__('Link'),
		    'index'  => 'link',
		));
		$this->addColumn ('issued_at', array(
			'header' => Mage::helper ('mhub')->__('Issued At'),
			'index'  => 'issued_at',
            'type'   => 'date',
            'width'  => '100px',
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
		$this->addColumn ('updated_at', array(
			'header' => Mage::helper ('mhub')->__('Updated At'),
			'index'  => 'updated_at',
            'type'   => 'datetime',
            'width'  => '100px',
		));
		$this->addColumn ('synced_at', array(
			'header' => Mage::helper ('mhub')->__('Synced At'),
			'index'  => 'synced_at',
            'type'   => 'datetime',
            'width'  => '100px',
		));

        $this->addColumn ('action',
            array(
                'header'    =>  Mage::helper ('mhub')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
                'actions'   => array(
                    array(
                        'caption' => Mage::helper ('mhub')->__('Edit'),
                        'url'     => array ('base' => '*/*/edit'),
                        'field'   => 'id'
                    )
                ),
        ));

        $this->addExportType ('*/*/exportCsv', Mage::helper ('mhub')->__('CSV'));

		return parent::_prepareColumns ();
	}

	public function getRowUrl ($row)
	{
        // nothing here
	}

	protected function _prepareMassaction ()
	{
		$this->setMassactionIdField ('entity_id');
		$this->getMassactionBlock ()->setFormFieldName ('entity_ids')
		    ->setUseSelectAll (true)
		    ->addItem ('remove_nf', array(
				 'label'   => Mage::helper ('mhub')->__('Remove NFs'),
				 'url'     => $this->getUrl ('*/adminhtml_nf/massRemove'),
				 'confirm' => Mage::helper ('mhub')->__('Are you sure?')
			))
        ;

		return $this;
	}
}

