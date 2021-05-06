<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Category_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('categoriesGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('mhub/category')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $categories = array ();
        foreach (Mage::getModel ('catalog/category')->getCollection ()->addNameToResult () as $category)
        {
            $categories [$category->getId ()] = str_repeat (' - ', intval ($category->getLevel ())) . $category->getName ();
        }

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
        $this->addColumn('category_id', array(
            'header'  => Mage::helper('mhub')->__('Category ID'),
            'index'   => 'category_id',
            'width'  => '50px',
            'type'   => 'number',
        ));
        $this->addColumn('category', array(
            'header'  => Mage::helper('mhub')->__('Category'),
            'index'   => 'category_id',
            'type'    => 'options',
            'options' => $categories,
        ));
        $this->addColumn('attribute_set_id', array(
            'header'  => Mage::helper('mhub')->__('Attribute Set'),
            'index'   => 'attribute_set_id',
            'type'    => 'options',
            'options' => Mage::getModel ('mhub/adminhtml_system_config_source_attributes_set')->toArray (),
        ));
        $this->addColumn('associable', array(
            'header'  => Mage::helper('mhub')->__('Associable'),
            'index'   => 'associable',
            'type'    => 'options',
            'options' => Mage::getModel ('adminhtml/system_config_source_yesno')->toArray (),
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
            'getter'  => 'getCategoryId',
            'filter'   => false,
            'sortable' => false,
            'index'    => 'stores',
            'actions' => array(
                array(
                    'caption' => Mage::helper('catalog')->__('Edit'),
                    'url'     => array(
                        'base'   => 'adminhtml/catalog_category/edit',
                        'params' => array('store'=>$this->getRequest()->getParam('store'), 'clear' => true)
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
        $this->getMassactionBlock()->addItem('pending_categories', array(
            'label'   => Mage::helper('mhub')->__('Pending Categories'),
            'url'     => $this->getUrl('*/adminhtml_category/massPending'),
            'confirm' => Mage::helper('mhub')->__('Are you sure?')
        ));
        $this->getMassactionBlock()->addItem('remove_categories', array(
            'label'   => Mage::helper('mhub')->__('Remove Categories'),
            'url'     => $this->getUrl('*/adminhtml_category/massRemove'),
            'confirm' => Mage::helper('mhub')->__('Are you sure?')
        ));

        return $this;
    }
}

