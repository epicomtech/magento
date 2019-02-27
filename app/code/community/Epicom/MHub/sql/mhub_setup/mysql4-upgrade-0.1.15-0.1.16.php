<?php
/*
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2019 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = new Mage_Sales_Model_Resource_Setup ('mhub_setup');
$installer->startSetup ();

/**
 * Order
 */
$entities = array(
    'order',
);

$options = array(
    'type'     => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
    'visible'  => true,
    'required' => false
);

foreach ($entities as $entity)
{
    $installer->addAttribute ($entity, Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_SYNCED_IN,  $options);
    $installer->addAttribute ($entity, Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_SYNCED_OUT, $options);
}

$installer->endSetup ();

