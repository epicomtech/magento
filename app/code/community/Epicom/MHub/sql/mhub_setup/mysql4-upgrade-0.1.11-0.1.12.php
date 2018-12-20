<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = $this;
$installer->startSetup ();

function updateMHubQuoteTable ($installer, $model)
{
    $table = $installer->getTable ($model);

    $installer->getConnection ()
        ->addColumn ($table, 'postcode', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'comment'  => 'Postcode',
            'after'   => 'quote_id',
        ));
}

updateMHubQuoteTable ($installer, Epicom_MHub_Helper_Data::QUOTE_TABLE);

$installer->endSetup ();

