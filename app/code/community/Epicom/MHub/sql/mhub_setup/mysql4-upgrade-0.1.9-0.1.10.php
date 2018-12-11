<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = $this;
$installer->startSetup();

function updateSalesOrderItemTable ($installer, $model)
{
    $table = $installer->getTable ($model);

    $installer->getConnection ()
        ->addColumn ($table, 'qty_delivered', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'length'   => '12,4',
            'unsigned' => true,
            'nullable' => true,
            'comment'  => 'Qty Delivered',
            'after'    => 'qty_shipped',
        ));
}

updateSalesOrderItemTable ($installer, 'sales/order_item');

$installer->endSetup();

