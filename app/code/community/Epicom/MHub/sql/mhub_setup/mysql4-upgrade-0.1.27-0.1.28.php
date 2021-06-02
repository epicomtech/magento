<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = $this;
$installer->startSetup ();

function updateMHubOrdersTable ($installer, $model, $description)
{
    $table = $installer->getTable ($model);

    $installer->getConnection ()
        ->addColumn ($table, 'marketplace', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'comment'  => 'Marketplace',
            'after'    => 'operation',
        ));
}

updateMHubOrdersTable ($installer, Epicom_MHub_Helper_Data::ORDER_TABLE, 'Epicom MHub Order');

$installer->endSetup ();

