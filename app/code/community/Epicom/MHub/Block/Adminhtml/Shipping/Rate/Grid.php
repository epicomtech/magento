<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Shipping_Rate_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('shippingRatesGrid');
        $this->setDefaultSort('updated_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('mhub/sales_quote_address_rate_collection')
            // ->addFieldToFilter('carrier', array('eq' => Epicom_MHub_Model_Shipping_Carrier_Epicom::CODE))
        ;

        $collection->getSelect()->join(
            array('sfqa'=> 'sales_flat_quote_address'),
            'sfqa.address_id = main_table.address_id',
            array(
                'sfqa.email', 'sfqa.postcode',
            )
        );

        $collection->getSelect()->join(
            array('sfq' => 'sales_flat_quote'),
            'sfq.entity_id = sfqa.quote_id',
            array(
                'store_id', 'customer_email',
                'quote_id' => 'sfq.entity_id',
	        )
        );

        $collection->getSelect()->joinLeft(
            array('sfqi' => 'sales_flat_quote_item'),
            'sfqi.quote_id = sfq.entity_id',
            array()
        );

        $collection->getSelect()
            ->group('rate_id')
            ->columns(array("GROUP_CONCAT(sku SEPARATOR ' ') AS skus", "GROUP_CONCAT(qty SEPARATOR ' ') AS qtys"))
        ;

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $store = $this->_getStore();

        $this->addColumn('rate_id', array(
            'header' => Mage::helper('mhub')->__('ID'),
            'align'  =>'right',
            'width'  => '50px',
            'type'   => 'number',
            'index'  => 'rate_id',
        ));
        $this->addColumn('address_id', array(
            'header' => Mage::helper('mhub')->__('Address ID'),
            'align'  => 'right',
            'width'  => '50px',
            'type'   => 'number',
            'index'  => 'address_id',
            'filter_index' => 'main_table.address_id',
        ));
        $this->addColumn('quote_id', array(
            'header' => Mage::helper('mhub')->__('Quote ID'),
            'align'  => 'right',
            'width'  => '50px',
            'type'   => 'number',
            'index'  => 'quote_id',
            'filter_index' => 'sfq.entity_id',
        ));
        $this->addColumn ('store_id', array(
            'header' => Mage::helper ('mhub')->__('Store'),
            'index'  => 'store_id',
            'type'   => 'options',
            'options' => Mage::getSingleton ('adminhtml/system_store')->getStoreOptionHash (true),
            'filter_index' => 'sfq.store_id',
        ));
        $this->addColumn('customer_email', array(
            'header' => Mage::helper('mhub')->__('Customer E-mail'),
            'index'  => 'customer_email',
        ));
        $this->addColumn('skus', array(
            'header' => Mage::helper('mhub')->__('SKUs'),
            'index'  => 'skus',
            'filter_index' => 'sfqi.sku',
        ));
        $this->addColumn('qtys', array(
            'header' => Mage::helper('mhub')->__('Qtys'),
            'index'  => 'qtys',
            'filter_index' => 'sfqi.qty',
        ));
        /*
        $this->addColumn('firstname', array(
            'header' => Mage::helper('mhub')->__('Firstname'),
            'index'  => 'firstname',
        ));
        $this->addColumn('city', array(
            'header' => Mage::helper('mhub')->__('City'),
            'index'  => 'city',
        ));
        $this->addColumn('region', array(
            'header' => Mage::helper('mhub')->__('Region'),
            'index'  => 'region',
        ));
        */
        $this->addColumn('postcode', array(
            'header' => Mage::helper('mhub')->__('Postcode'),
            'index'  => 'postcode',
        ));
        $this->addColumn('code', array(
            'header' => Mage::helper('mhub')->__('Code'),
            'index'  => 'code',
        ));
        $this->addColumn('method', array(
            'header' => Mage::helper('mhub')->__('Method'),
            'index'  => 'method',
        ));
        /*
        $this->addColumn('method_description', array(
            'header'  => Mage::helper('mhub')->__('Method Description'),
            'index'   => 'method_description',
        ));
        */
        $this->addColumn('method_title', array(
            'header'  => Mage::helper('mhub')->__('Method Title'),
            'index'   => 'method_title',
        ));
        $this->addColumn('price', array(
            'header'  => Mage::helper('mhub')->__('Price'),
            'index'   => 'price',
            'type'   => 'price',
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'filter_index' => 'main_table.price',
        ));
        $this->addColumn('error_message', array(
            'header' => Mage::helper('mhub')->__('Error Message'),
            'index'  => 'error_message',
        ));
        $this->addColumn('created_at', array(
            'header' => Mage::helper('mhub')->__('Created At'),
            'index'  => 'created_at',
            'type'   => 'datetime',
            'width'  => '100px',
            'filter_index' => 'main_table.created_at',
        ));
        $this->addColumn('updated_at', array(
            'header' => Mage::helper('mhub')->__('Updated At'),
            'index'  => 'updated_at',
            'type'   => 'datetime',
            'width'  => '100px',
            'filter_index' => 'main_table.updated_at',
        ));
/*
        $this->addColumn('action', array(
            'header'  => Mage::helper('mhub')->__('Edit'),
            'width'   => '50px',
            'type'    => 'action',
            'getter'  => 'getCustomerId',
            'filter'   => false,
            'sortable' => false,
            'index'    => 'stores',
            'actions' => array(
                array(
                    'caption' => Mage::helper('mhub')->__('Edit'),
                    'url'     => array(
                        'base'   => 'adminhtml/customer/edit',
                        'params' => array('store'=>$this->getRequest()->getParam('store'))
                    ),
                    'field'   => 'id'
                )
            ),
        ));
*/
        $this->addExportType ('*/*/exportCsv', Mage::helper ('mhub')->__('CSV'));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        // return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);

        return Mage::app()->getStore($storeId);
    }
}

