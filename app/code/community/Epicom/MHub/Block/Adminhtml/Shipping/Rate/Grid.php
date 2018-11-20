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
        $this->setDefaultSort('rate_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('sales/quote_address_rate')->getCollection()
            ->addFieldToFilter('carrier', array('eq' => Epicom_MHub_Model_Shipping_Carrier_Epicom::CODE))
        ;

        $collection->getSelect()->join(
            array('sfqa'=> 'sales_flat_quote_address'),
            'sfqa.address_id = main_table.address_id',
            array('sfqa.*')
        );

        $collection->addFieldToFilter ('address_type', array ('eq' => 'shipping'));

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('rate_id', array(
            'header' => Mage::helper('mhub')->__('ID'),
            'align'  =>'right',
            'width'  => '50px',
            'type'   => 'number',
            'index'  => 'rate_id',
        ));
        /*
        $this->addColumn('address_id', array(
            'header' => Mage::helper('mhub')->__('Address ID'),
            'index'  => 'address_id',
        ));
        */
        $this->addColumn('email', array(
            'header' => Mage::helper('mhub')->__('E-mail'),
            'index'  => 'email',
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
        ));
        $this->addColumn('updated_at', array(
            'header' => Mage::helper('mhub')->__('Updated At'),
            'index'  => 'updated_at',
            'type'   => 'datetime',
            'width'  => '100px',
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
        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        // return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}

