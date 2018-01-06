<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = $this;
$installer->startSetup();

function addMHubAttributegroupTable ($installer, $model, $description)
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
        ->addColumn ($table, 'attribute_set_id', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'   => 11,
            'unsigned' => true,
            'nullable' => false,
            'comment'  => 'Attribute Set ID',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'group_name', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'comment'  => 'Group Name',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'attribute_codes', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'nullable' => false,
            'comment'  => 'Attribute Codes',
        ));
}

addMHubAttributegroupTable ($installer, Epicom_MHub_Helper_Data::ATTRIBUTE_GROUP_TABLE, 'Epicom MHub Attribute Group');

$installer->endSetup();

