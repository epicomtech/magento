<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = $this;
$installer->startSetup ();

function addMHubNFTable ($installer, $model, $description)
{
    $table = $installer->getTable ($model);

    $sqlBlock = <<< SQLBLOCK
CREATE TABLE IF NOT EXISTS {$table}
(
    entity_id int (11) unsigned NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 COMMENT='{$description}';
SQLBLOCK;

    $installer->run ($sqlBlock);

    $installer->getConnection ()
        ->addColumn ($table, 'order_increment_id', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'comment'  => 'Order Increment ID',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'number', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'   => 11,
            'unsigend' => true,
            'nullable' => false,
            'comment'  => 'Number',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'series', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'   => 11,
            'unsigned' => true,
            'nullable' => false,
            'comment'  => 'Series',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'access_key', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 44,
            'nullable' => false,
            'comment'  => 'Access Key',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'link', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'nullable' => false,
            'comment'  => 'Link',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'issued_at', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_DATE,
            'nullable' => false,
            'comment'  => 'Issued At',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'operation', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'comment'  => 'Operation',
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
            'type'     => Varien_Db_Ddl_Table::TYPE_DATE,
            'nullable' => true,
            'comment'  => 'Updated At',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'synced_at', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_DATE,
            'nullable' => true,
            'comment'  => 'Synced At',
        ));
}

addMHubNFTable ($installer, Epicom_MHub_Helper_Data::NF_TABLE, 'Epicom MHub NF');

$installer->endSetup();

