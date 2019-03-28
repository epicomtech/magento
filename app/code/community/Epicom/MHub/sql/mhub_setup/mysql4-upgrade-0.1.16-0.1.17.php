<?php
/*
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2019 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = new Mage_Sales_Model_Resource_Setup ('mhub_setup');
$installer->startSetup ();

/**
 * Quote Table
 */
$installer->getConnection ()->addColumn(
    $installer->getTable ('mhub/quote'),
    'provider',
    'varchar(255) DEFAULT NULL AFTER sku'
);

$installer->endSetup ();

