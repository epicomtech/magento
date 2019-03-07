<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = $this;
$installer->startSetup ();

function addMHubProductAssociations ($installer, $model_name)
{
    $sqlBlock = <<< SQLBLOCK
CREATE TABLE IF NOT EXISTS {$installer->getTable ($model_name)}
(
    entity_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 COMMENT='Epicom MHUb - Product Associations';
SQLBLOCK;

    $installer->run ($sqlBlock);

    $table = $installer->getTable ($model_name);

    $installer->getConnection ()
        ->addColumn ($table, 'parent_sku', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'comment'  => 'Parent SKU',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'sku', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'comment'  => 'SKU',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'is_modified', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
            'unsigned' => true,
            'nullable' => true,
            'comment'  => 'Is Modified',
        ));

    $installer->getConnection ()->addKey ($table, 'FK_EPICOM_MHUB_PRODUCT_ASSOCIATION_PARENT_SKU', 'parent_sku', Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX);
    $installer->getConnection ()->addKey ($table, 'FK_EPICOM_MHUB_PRODUCT_ASSOCIATION_SKU',        'sku',        Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX);
}

addMHubProductAssociations ($installer, 'epicom_mhub_product_association');

$installer->endSetup ();

