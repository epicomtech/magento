<?php
/*
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2019 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = new Mage_Sales_Model_Resource_Setup ('mhub_setup');
$installer->startSetup ();

/**
 * Order Grid
 */
$installer->getConnection ()->addColumn(
    $installer->getTable ('sales/order_grid'),
    'is_epicom',
    'tinyint(1) UNSIGNED DEFAULT NULL'
);

$this->getConnection ()->addKey(
    $this->getTable ('sales/order_grid'),
    'is_epicom',
    'is_epicom'
);

$installer->getConnection ()->addColumn(
    $installer->getTable ('sales/order_grid'),
    'ext_order_id',
    'varchar(255) DEFAULT NULL'
);

$select = $this->getConnection ()->select ();

$select->join(
    array ('order' => $this->getTable ('sales/order')),
    'order.entity_id = grid.entity_id',
    array ('is_epicom', 'ext_order_id')
);

$this->getConnection()->query(
    $select->crossUpdateFromSelect(
        array ('grid' => $this->getTable ('sales/order_grid'))
    )
);

$installer->endSetup ();

