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
        ->addColumn ($table, 'fee_amount', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'length'   => '12,4',
            'unsigned' => true,
            'nullable' => true,
            'comment'  => 'Fee Amount',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'base_fee_amount', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'length'   => '12,4',
            'unsigned' => true,
            'nullable' => true,
            'comment'  => 'Base Fee Amount',
        ));
}

updateSalesTable ($installer, 'sales/quote_address');

updateSalesTable ($installer, 'sales/order');
updateSalesTable ($installer, 'sales/invoice');
updateSalesTable ($installer, 'sales/creditmemo');

function updateSalesInvoicedTable ($installer, $model)
{
    $table = $installer->getTable ($model);

    $installer->getConnection ()
        ->addColumn ($table, 'fee_amount_invoiced', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'length'   => '12,4',
            'unsigned' => true,
            'nullable' => true,
            'comment'  => 'Fee Amount Invoiced',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'base_fee_amount_invoiced', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'length'   => '12,4',
            'unsigned' => true,
            'nullable' => true,
            'comment'  => 'Base Fee Amount Invoiced',
        ));
}

updateSalesInvoicedTable ($installer, 'sales/order');

function updateSalesRefundedTable ($installer, $model)
{
    $table = $installer->getTable ($model);

    $installer->getConnection ()
        ->addColumn ($table, 'fee_amount_refunded', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'length'   => '12,4',
            'unsigned' => true,
            'nullable' => true,
            'comment'  => 'Fee Amount Refunded',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'base_fee_amount_refunded', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'length'   => '12,4',
            'unsigned' => true,
            'nullable' => true,
            'comment'  => 'Base Fee Amount Refunded',
        ));
}

updateSalesRefundedTable ($installer, 'sales/order');

$installer->endSetup ();

