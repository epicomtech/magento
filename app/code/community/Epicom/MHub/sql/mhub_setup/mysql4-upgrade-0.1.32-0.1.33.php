<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = $this;
$installer->startSetup ();

function addMHubProductsAllowed ($installer, $model)
{
    $sqlBlock = <<< SQLBLOCK
CREATE TABLE IF NOT EXISTS {$installer->getTable ($model)}
(
    entity_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 COMMENT='Epicom MHUb - Products Allowed';
SQLBLOCK;

    $installer->run ($sqlBlock);

    $table = $installer->getTable ($model);

    $installer->getConnection ()
        ->addColumn ($table, 'code', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'comment'  => 'Code',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'sku', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'comment'  => 'SKU',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'created_at', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_DATETIME,
            'nullable' => false,
            'comment'  => 'Created At',
        ));

    $installer->getConnection ()->addKey ($table, 'FK_EPICOM_MHUB_PRODUCT_ALLOWED_CODE', 'code', Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX);
    $installer->getConnection ()->addKey ($table, 'FK_EPICOM_MHUB_PRODUCT_ALLOWED_SKU',  'sku',  Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX);
}

addMHubProductsAllowed ($installer, Epicom_MHub_Helper_Data::PRODUCT_ALLOWED_TABLE);

$installer->endSetup ();

