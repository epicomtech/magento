<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = new Mage_Catalog_Model_Resource_Setup ('mhub_setup');
$installer->startSetup ();

/**
 * Create table 'mhub/product_attribute_marketplace_price'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('mhub/product_attribute_marketplace_price'))
    ->addColumn('value_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Value ID')
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
        ), 'Entity ID')
    ->addColumn('all_groups', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '1',
        ), 'Is Applicable To All Customer Groups')
    ->addColumn('customer_group_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
        ), 'Customer Group ID')
    ->addColumn('value', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        'nullable'  => false,
        'default'   => '0.0000',
        ), 'Value')
    ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        ), 'Website ID')
    ->addIndex(
        $installer->getIdxName(
            'mhub/product_attribute_marketplace_price',
            array('entity_id', 'all_groups', 'customer_group_id', 'website_id', 'marketplace_id'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        array('entity_id', 'all_groups', 'customer_group_id', 'website_id', 'marketplace_id'),
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
    ->addIndex($installer->getIdxName('mhub/product_attribute_marketplace_price', array('entity_id')),
        array('entity_id'))
    ->addIndex($installer->getIdxName('mhub/product_attribute_marketplace_price', array('customer_group_id')),
        array('customer_group_id'))
    ->addIndex($installer->getIdxName('mhub/product_attribute_marketplace_price', array('website_id')),
        array('website_id'))
    ->addForeignKey(
        $installer->getFkName(
            'mhub/product_attribute_marketplace_price',
            'customer_group_id',
            'customer/customer_group',
            'customer_group_id'
        ),
        'customer_group_id', $installer->getTable('customer/customer_group'), 'customer_group_id',
         Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->addForeignKey(
        $installer->getFkName(
            'mhub/product_attribute_marketplace_price',
            'entity_id',
            'catalog/product',
            'entity_id'
        ),
        'entity_id', $installer->getTable('catalog/product'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->addForeignKey(
        $installer->getFkName(
            'mhub/product_attribute_marketplace_price',
            'website_id',
            'core/website',
            'website_id'
        ),
        'website_id', $installer->getTable('core/website'), 'website_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    /* marketplace */
    ->addColumn('marketplace_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
        ), 'Marketplace ID')
    ->addColumn('special_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,2', array(
        'unsigned'  => true,
        'nullable'  => true,
        'default'   => null,
        ), 'Special Price')
    ->addColumn('is_active', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '1',
        ), 'Is Active')
    ->addIndex($installer->getIdxName('mhub/product_attribute_marketplace_price', array('marketplace_id')),
        array('marketplace_id'))
    ->addForeignKey(
        $installer->getFkName(
            'mhub/product_attribute_marketplace_price',
            'marketplace_id',
            'mhub/marketplace',
            'external_id'
        ),
        'marketplace_id', $installer->getTable('mhub/marketplace'), 'external_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->setComment('Catalog Product Group Price Attribute Backend Table');
$installer->getConnection()->createTable($table);

$installer->addAttribute('catalog_product', 'marketplace_price', array(
    'type'                       => 'decimal',
    'label'                      => Mage::helper ('mhub')->__('Marketplace Price'),
    'input'                      => 'text',
    'backend'                    => 'mhub/catalog_product_attribute_backend_marketplaceprice',
    'required'                   => false,
    'sort_order'                 => 7,
    'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'apply_to'                   => 'simple,grouped,configurable,virtual,bundle,downloadable',
    'group'                      => Mage::helper ('mhub')->__('Prices'),
));

/**
 * Create table 'mhub/product_index_marketplace_price'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('mhub/product_index_marketplace_price'))
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Entity ID')
    ->addColumn('customer_group_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Customer Group ID')
    ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Website ID')
    ->addColumn('price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Min Price')
    ->addIndex($installer->getIdxName('mhub/product_index_marketplace_price', array('customer_group_id')),
        array('customer_group_id'))
    ->addIndex($installer->getIdxName('mhub/product_index_marketplace_price', array('website_id')),
        array('website_id'))
    ->addForeignKey(
        $installer->getFkName(
            'mhub/product_index_marketplace_price',
            'customer_group_id',
            'customer/customer_group',
            'customer_group_id'
        ),
        'customer_group_id', $installer->getTable('customer/customer_group'), 'customer_group_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->addForeignKey(
        $installer->getFkName(
            'mhub/product_index_marketplace_price',
            'entity_id',
            'catalog/product',
            'entity_id'
        ),
        'entity_id', $installer->getTable('catalog/product'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->addForeignKey(
        $installer->getFkName(
            'mhub/product_index_marketplace_price',
            'website_id',
            'core/website',
            'website_id'
         ),
        'website_id', $installer->getTable('core/website'), 'website_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    /* marketplace */
    ->addColumn('marketplace_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
        ), 'Marketplace ID')
    ->addColumn('special_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,2', array(
        'unsigned'  => true,
        'nullable'  => true,
        'default'   => null,
        ), 'Special Price')
    ->addColumn('is_active', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '1',
        ), 'Is Active')
    ->addIndex($installer->getIdxName('mhub/product_index_marketplace_price', array('marketplace_id')),
        array('marketplace_id'))
    ->addForeignKey(
        $installer->getFkName(
            'mhub/product_index_marketplace_price',
            'marketplace_id',
            'mhub/marketplace',
            'external_id'
         ),
        'marketplace_id', $installer->getTable('mhub/marketplace'), 'external_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->setComment('Catalog Product Marketplace Price Index Table');
$installer->getConnection()->createTable($table);
/*
$finalPriceIndexerTables = array(
    'catalog/product_price_indexer_final_idx',
    'catalog/product_price_indexer_final_tmp',
);

$priceIndexerTables =  array(
    'catalog/product_price_indexer_option_aggregate_idx',
    'catalog/product_price_indexer_option_aggregate_tmp',
    'catalog/product_price_indexer_option_idx',
    'catalog/product_price_indexer_option_tmp',
    'catalog/product_price_indexer_idx',
    'catalog/product_price_indexer_tmp',
    'catalog/product_price_indexer_cfg_option_aggregate_idx',
    'catalog/product_price_indexer_cfg_option_aggregate_tmp',
    'catalog/product_price_indexer_cfg_option_idx',
    'catalog/product_price_indexer_cfg_option_tmp',
    'catalog/product_index_price',
);

foreach ($finalPriceIndexerTables as $table) {
    $installer->getConnection()->addColumn($installer->getTable($table), 'marketplace_price', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_DECIMAL,
        'length'    => '12,4',
        'comment'   => 'Marketplace Price',
    ));
    $installer->getConnection()->addColumn($installer->getTable($table), 'base_marketplace_price', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_DECIMAL,
        'length'    => '12,4',
        'comment'   => 'Base Marketplace Price',
    ));
}

foreach ($priceIndexerTables as $table) {
    $installer->getConnection()->addColumn($installer->getTable($table), 'marketplace_price', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_DECIMAL,
        'length'    => '12,4',
        'comment'   => 'Marketplace Price',
    ));
}
*/
$installer->endSetup ();

