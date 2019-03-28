<?php
/*
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2019 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = new Mage_Sales_Model_Resource_Setup ('mhub_setup');
$installer->startSetup ();

/**
 * Quote Item & Order Item
 */
$entities = array(
    'quote_item',
    'order_item',
);

$options = array(
    'type'     => Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'length'   => 255,
    'visible'  => true,
    'required' => false
);

foreach ($entities as $entity)
{
    $installer->addAttribute ($entity, Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_MANUFACTURER, $options);
}

$installer->endSetup ();

