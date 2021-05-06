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

        $collection->getSelect()->joinLeft(
            array ('product' => Mage::getSingleton ('core/resource')->getTableName ('catalog_product_entity')),
            'main_table.product_id = product.entity_id',
            array(
                'type_id' => 'product.type_id',
            )
        );

        $entityTypeId = Mage::getModel ('eav/entity')
            ->setType (Mage_Catalog_Model_Product::ENTITY)
            ->getTypeId ()
        ;

        $manufacturerAttribute = Mage::getModel ('eav/entity_attribute')->loadByCode (
            $entityTypeId, Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_MANUFACTURER
        );

        $resource = Mage::getSingleton ('core/resource');
        $write    = $resource->getConnection ('core_write');
        $table    = $resource->getTableName ('catalog_product_entity_' . $manufacturerAttribute->getBackendType ());

        $collection->getSelect()->joinLeft(
            array ('m' => $table),
            "product.entity_id = m.entity_id AND m.attribute_id = {$manufacturerAttribute->getAttributeId ()}",
            array(
                'manufacturer' => 'm.value',
            )
        );

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
            'filter_index' => 'main_table.entity_id',
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
        $this->addColumn('type_id', array(
            'header' => Mage::helper('mhub')->__('Type'),
            'index'  => 'type_id',
            'type'    => 'options',
            'options' => Mage::getModel ('catalog/product_type')->getOptionArray (),
        ));
        $this->addColumn('product_id', array(
            'header' => Mage::helper('mhub')->__('Product ID'),
            'index'  => 'product_id',
            'type'   => 'number',
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
        $this->addColumn('manufacturer', array(
            'header' => Mage::helper('mhub')->__('Manufacturer'),
            'index'  => 'manufacturer',
            'type'    => 'options',
            'options' => Mage::getModel ('mhub/adminhtml_system_config_source_product_manufacturer')->toArray (),
            'filter_index' => 'm.value',
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
            'filter_index' => 'main_table.updated_at',
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

