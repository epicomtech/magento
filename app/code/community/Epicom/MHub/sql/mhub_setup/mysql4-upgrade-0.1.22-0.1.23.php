<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2020 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = $this;
$installer->startSetup ();

function updateSalesTable ($installer, $model)
{
    $table = $installer->getTable ($model);

    $installer->getConnection ()
        ->addColumn ($table, 'discount_amount', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'length'   => '12,4',
            'unsigned' => true,
            'nullable' => true,
            'comment'  => 'Discount Amount',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'base_discount_amount', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'length'   => '12,4',
            'unsigned' => true,
            'nullable' => true,
            'comment'  => 'Base Discount Amount',
        ));
}

updateSalesTable ($installer, 'sales/quote');

function updateSalesInvoicedTable ($installer, $model)
{
    $table = $installer->getTable ($model);

    $installer->getConnection ()
        ->addColumn ($table, 'discount_amount_invoiced', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'length'   => '12,4',
            'unsigned' => true,
            'nullable' => true,
            'comment'  => 'Discount Amount Invoiced',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'base_discount_amount_invoiced', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'length'   => '12,4',
            'unsigned' => true,
            'nullable' => true,
            'comment'  => 'Base Discount Amount Invoiced',
        ));
}

updateSalesInvoicedTable ($installer, 'sales/order');

function updateSalesRefundedTable ($installer, $model)
{
    $table = $installer->getTable ($model);

    $installer->getConnection ()
        ->addColumn ($table, 'discount_amount_refunded', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'length'   => '12,4',
            'unsigned' => true,
            'nullable' => true,
            'comment'  => 'Discount Amount Refunded',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'base_discount_amount_refunded', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'length'   => '12,4',
            'unsigned' => true,
            'nullable' => true,
            'comment'  => 'Base Discount Amount Refunded',
        ));
}

updateSalesRefundedTable ($installer, 'sales/order');

$installer->endSetup ();

