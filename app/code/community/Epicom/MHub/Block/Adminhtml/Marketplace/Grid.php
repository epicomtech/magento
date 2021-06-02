<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Marketplace_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct ()
	{
		parent::__construct ();

		$this->setId ('mhubMarketplaceGrid');
		$this->setDefaultSort ('entity_id');
		$this->setDefaultDir ('DESC');
		$this->setSaveParametersInSession (true);
	}

	protected function _prepareCollection ()
	{
		$collection = Mage::getModel ('mhub/marketplace')->getCollection ();
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
        $this->addColumn('scope_id', array(
            'header'  => Mage::helper('mhub')->__('Scope'),
            'index'   => 'scope_id',
            'type'    => 'options',
            'options' => Mage::getSingleton ('adminhtml/system_store')->getStoreOptionHash (true),
        ));
		$this->addColumn ('external_id', array(
		    'header'  => Mage::helper ('mhub')->__('External ID'),
		    'index'   => 'external_id',
            'type'    => 'number',
		));
		$this->addColumn ('code', array(
		    'header'  => Mage::helper ('mhub')->__('Code'),
		    'index'   => 'code',
		));
		$this->addColumn ('name', array(
		    'header' => Mage::helper ('mhub')->__('Name'),
		    'index'  => 'name',
		));
		$this->addColumn ('fantasy_name', array(
		    'header' => Mage::helper ('mhub')->__('Fantasy Name'),
		    'index'  => 'fantasy_name',
		));
		$this->addColumn ('use_categories', array(
		    'header'  => Mage::helper ('mhub')->__('Use Categories'),
		    'index'   => 'use_categories',
            'type'    => 'options',
            'options' => Mage::getModel ('adminhtml/system_config_source_yesno')->toArray (),
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
		    ->addItem ('remove_marketplace', array(
				 'label'   => Mage::helper ('mhub')->__('Remove Marketplaces'),
				 'url'     => $this->getUrl ('*/adminhtml_marketplace/massRemove'),
				 'confirm' => Mage::helper ('mhub')->__('Are you sure?')
			))
        ;

		return $this;
	}
}

