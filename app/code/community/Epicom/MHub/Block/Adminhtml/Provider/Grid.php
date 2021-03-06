<?php
/*
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Provider_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct ()
	{
		parent::__construct ();

		$this->setId ('mhubProviderGrid');
		$this->setDefaultSort ('entity_id');
		$this->setDefaultDir ('DESC');
		$this->setSaveParametersInSession (true);
	}

	protected function _prepareCollection ()
	{
		$collection = Mage::getModel ('mhub/provider')->getCollection ();
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
        $this->addColumn('customer_id', array(
            'header'  => Mage::helper('mhub')->__('Customer'),
            'index'   => 'customer_id',
            'type'    => 'options',
            'options' => $this->_getCustomers (),
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
		$this->addColumn ('use_categories', array(
		    'header'  => Mage::helper ('mhub')->__('Use Categories'),
		    'index'   => 'use_categories',
            'type'    => 'options',
            'options' => Mage::getModel ('adminhtml/system_config_source_yesno')->toArray (),
		));
		$this->addColumn ('is_service', array(
		    'header'  => Mage::helper ('mhub')->__('Is Service'),
		    'index'   => 'is_service',
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
		    ->addItem ('remove_provider', array(
				 'label'   => Mage::helper ('mhub')->__('Remove Providers'),
				 'url'     => $this->getUrl ('*/adminhtml_provider/massRemove'),
				 'coproviderirm' => Mage::helper ('mhub')->__('Are you sure?')
			))
        ;

		return $this;
	}

    public function _getCustomers ($websiteId = null)
    {
        $groupId = Mage::getStoreConfig (Epicom_MHub_Helper_Data::XML_PATH_MHUB_CUSTOMER_GROUP);

        $collection = Mage::getModel ('customer/customer')->getCollection ()
            ->addFieldToFilter ('group_id', array ('eq' => $groupId))
        ;

        if (!empty ($websiteId))
        {
            $collection->addFieldToFilter ('website_id', $websiteId);
        }

        $collection->getSelect ()->reset (Zend_Db_Select::COLUMNS)
            ->columns (array ('id' => 'e.entity_id', 'name' => 'e.email'))
        ;

        return $collection->toOptionHash ();
    }
}

