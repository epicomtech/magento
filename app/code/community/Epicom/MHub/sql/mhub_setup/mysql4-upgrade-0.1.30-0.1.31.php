<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = new Mage_Sales_Model_Resource_Setup ('mhub_setup');
$installer->startSetup ();

/**
 * Quote Item & Order Item
 */
$entities = array(
    'sales/quote_item',
    'sales/order_item',
);

foreach ($entities as $entity)
{
    $installer->run ("ALTER TABLE {$this->getTable ($entity)} MODIFY mhub_product_id VARCHAR(255) DEFAULT NULL COMMENT 'MHub Product ID'");
    $installer->run ("ALTER TABLE {$this->getTable ($entity)} MODIFY mhub_product_sku VARCHAR(255) DEFAULT NULL COMMENT 'MHub Product SKU'");
    $installer->run ("ALTER TABLE {$this->getTable ($entity)} MODIFY mhub_product_code VARCHAR(255) DEFAULT NULL COMMENT 'MHub Product Code'");
}

$installer->endSetup ();

