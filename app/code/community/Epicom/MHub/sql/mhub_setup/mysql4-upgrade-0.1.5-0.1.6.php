<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = $this;
$installer->startSetup();

function updateMHubNFTable ($installer, $model, $description)
{
    $table = $installer->getTable ($model);
/*
    $sqlBlock = <<< SQLBLOCK
CREATE TABLE IF NOT EXISTS {$table}
(
    entity_id int(11) unsigned NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 COMMENT='{$description}';
SQLBLOCK;

    $installer->run ($sqlBlock);
*/
    $installer->getConnection ()
        ->addColumn ($table, 'cfop', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'   => 11,
            'unsigned' => true,
            'nullable' => true,
            'comment'  => 'CFOP',
            'after'    => 'access_key',
        ));
}

updateMHubNFTable ($installer, Epicom_MHub_Helper_Data::NF_TABLE, 'Epicom MHub NF');

$installer->endSetup();

