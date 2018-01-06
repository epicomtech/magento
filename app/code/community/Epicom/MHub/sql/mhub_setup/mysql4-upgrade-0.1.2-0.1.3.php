<?php
/*
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = new Mage_Sales_Model_Resource_Setup ('mhub_setup');
$installer->startSetup ();

function addMHubBrandTable ($installer, $model, $description)
{
    $table = $installer->getTable ($model);

    $sqlBlock = <<< SQLBLOCK
CREATE TABLE IF NOT EXISTS {$table}
(
    entity_id int(11) unsigned NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 COMMENT='{$description}';
SQLBLOCK;

    $installer->run ($sqlBlock);

    $installer->getConnection ()
        ->addColumn ($table, 'external_id', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'   => 11,
            'unsigned' => true,
            'nullable' => false,
            'comment'  => 'External ID',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'option_id', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'   => 11,
            'unsigned' => true,
            'nullable' => false,
            'comment'  => 'Option ID',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'name', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'comment'  => 'Name',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'status', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'comment' => 'Status'
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'message', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'nullable' => true,
            'comment' => 'Message'
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'updated_at', array(
            'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
            'nullable' => false,
            'comment'  => 'Updated At',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'synced_at', array(
            'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
            'nullable' => true,
            'comment'  => 'Synced At'
        ));
}

addMHubBrandTable ($installer, Epicom_MHub_Helper_Data::BRAND_TABLE, 'Epicom MHub Brand');

$installer->endSetup ();

