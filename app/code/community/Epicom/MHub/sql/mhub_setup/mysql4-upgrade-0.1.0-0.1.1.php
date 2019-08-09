<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = $this;
$installer->startSetup();

function addMHubProductsTable ($installer, $model, $description)
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
        ->addColumn ($table, 'product_id', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'   => 11,
            'unsigned' => true,
            'nullable' => false,
            'comment'  => 'Product ID',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'external_id', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'   => 11,
            'unsigned' => true,
            'nullable' => false,
            'comment'  => 'External ID',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'external_code', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'comment'  => 'External Code',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'external_sku', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'comment'  => 'External SKU',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'operation', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'comment'  => 'Operation',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'method', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'comment'  => 'Method',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'send_date', array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 20,
            'nullable' => false,
            'comment'  => 'Send Date',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'parameters', array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'nullable' => false,
            'comment'  => 'Parameters',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'status', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'comment' => 'Status'
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'message', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'nullable' => true,
            'comment' => 'Message'
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'updated_at', array(
            'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
            'nullable' => false,
            'comment'  => 'Updated At',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'synced_at', array(
            'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
            'nullable' => true,
            'comment'  => 'Synced At'
        ));
}

addMHubProductsTable ($installer, Epicom_MHub_Helper_Data::PRODUCT_TABLE, 'Epicom MHub Product');

$installer->addAttribute ('catalog_product', Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID,  array(
    'type'             => 'varchar',
    'label'            => Mage::helper ('mhub')->__('Product ID'),
    'input'            => 'text',
    'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'          => false,
    'required'         => false,
    'user_defined'     => true,
    'searchable'       => false,
    'filterable'       => false,
    'comparable'       => false,
    'visible_on_front' => false,
    'unique'           => false,
    'group'            => Mage::helper ('mhub')->__('Epicom'),
    'used_in_product_listing' => true,
));

$installer->addAttribute ('catalog_product', Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_CODE,  array(
    'type'             => 'varchar',
    'label'            => Mage::helper ('mhub')->__('Product Code'),
    'input'            => 'text',
    'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'          => true,
    'required'         => false,
    'user_defined'     => true,
    'searchable'       => false,
    'filterable'       => false,
    'comparable'       => false,
    'visible_on_front' => false,
    'unique'           => false,
    'group'            => Mage::helper ('mhub')->__('Epicom')
));

$installer->addAttribute ('catalog_product', Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_BRAND,  array(
    'type'             => 'int',
    'label'            => Mage::helper ('mhub')->__('Product Brand'),
    'input'            => 'select',
    'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'          => true,
    'required'         => false,
    'user_defined'     => true,
    'searchable'       => false,
    'filterable'       => false,
    'comparable'       => false,
    'visible_on_front' => false,
    'unique'           => false,
    'group'            => Mage::helper ('mhub')->__('Epicom')
));

$installer->addAttribute ('catalog_product', Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_EAN,  array(
    'type'             => 'varchar',
    'label'            => Mage::helper ('mhub')->__('Product EAN'),
    'input'            => 'text',
    'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'          => true,
    'required'         => false,
    'user_defined'     => true,
    'searchable'       => false,
    'filterable'       => false,
    'comparable'       => false,
    'visible_on_front' => true,
    'unique'           => true,
    'apply_to'         => implode (',', array(
        Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
        Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL,
        Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
    )),
    'group'            => Mage::helper ('mhub')->__('Epicom')
));

$installer->addAttribute ('catalog_product', Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_SKU,  array(
    'type'             => 'varchar',
    'label'            => Mage::helper ('mhub')->__('Product SKU'),
    'input'            => 'text',
    'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'          => true,
    'required'         => false,
    'user_defined'     => true,
    'searchable'       => false,
    'filterable'       => false,
    'comparable'       => false,
    'visible_on_front' => false,
    'unique'           => false,
    'group'            => Mage::helper ('mhub')->__('Epicom')
));

$installer->addAttribute ('catalog_product', Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_SUMMARY,  array(
    'type'             => 'text',
    'label'            => Mage::helper ('mhub')->__('Product Summary'),
    'input'            => 'textarea',
    'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'          => true,
    'required'         => false,
    'user_defined'     => true,
    'searchable'       => false,
    'filterable'       => false,
    'comparable'       => false,
    'visible_on_front' => true,
    'unique'           => false,
    'wysiwyg_enabled'  => true,
    'group'            => Mage::helper ('mhub')->__('Epicom')
));

$installer->addAttribute ('catalog_product', Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_URL,  array(
    'type'             => 'varchar',
    'label'            => Mage::helper ('mhub')->__('Product URL'),
    'input'            => 'text',
    'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'          => true,
    'required'         => false,
    'user_defined'     => true,
    'searchable'       => false,
    'filterable'       => false,
    'comparable'       => false,
    'visible_on_front' => false,
    'unique'           => false,
    'group'            => Mage::helper ('mhub')->__('Epicom')
));

$installer->addAttribute ('catalog_product', Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_OUT_OF_LINE,  array(
    'type'             => 'int',
    'label'            => Mage::helper ('mhub')->__('Product Out Of Line'),
    'input'            => 'select',
    'source'           => 'eav/entity_attribute_source_boolean',
    'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'          => true,
    'required'         => false,
    'user_defined'     => true,
    'searchable'       => false,
    'filterable'       => false,
    'comparable'       => false,
    'visible_on_front' => false,
    'unique'           => false,
    'group'            => Mage::helper ('mhub')->__('Epicom')
));

$installer->addAttribute ('catalog_product', Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_MANUFACTURER,  array(
    'type'             => 'varchar',
    'label'            => Mage::helper ('mhub')->__('Product Manufacturer'),
    'input'            => 'text',
    'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'          => false,
    'required'         => false,
    'user_defined'     => true,
    'searchable'       => false,
    'filterable'       => false,
    'comparable'       => false,
    'visible_on_front' => false,
    'unique'           => false,
    'group'            => Mage::helper ('mhub')->__('Epicom')
));

$installer->addAttribute ('catalog_product', Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_MODEL,  array(
    'type'             => 'int',
    'label'            => Mage::helper ('mhub')->__('Product Model'),
    'input'            => 'select',
    'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'          => true,
    'required'         => false,
    'user_defined'     => true,
    'searchable'       => false,
    'filterable'       => false,
    'comparable'       => false,
    'visible_on_front' => false,
    'unique'           => false,
    'group'            => Mage::helper ('mhub')->__('Epicom')
));

$installer->addAttribute ('catalog_product', Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_OFFER_TITLE,  array(
    'type'             => 'varchar',
    'label'            => Mage::helper ('mhub')->__('Product Offer Title'),
    'input'            => 'text',
    'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'          => true,
    'required'         => false,
    'user_defined'     => true,
    'searchable'       => false,
    'filterable'       => false,
    'comparable'       => false,
    'visible_on_front' => false,
    'unique'           => false,
    'group'            => Mage::helper ('mhub')->__('Epicom')
));

$installer->addAttribute ('catalog_product', Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_HEIGHT,  array(
    'type'             => 'varchar',
    'label'            => Mage::helper ('mhub')->__('Product Height'),
    'input'            => 'text',
    'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'          => true,
    'required'         => false,
    'user_defined'     => true,
    'searchable'       => false,
    'filterable'       => false,
    'comparable'       => false,
    'visible_on_front' => false,
    'unique'           => false,
    'group'            => Mage::helper ('mhub')->__('Epicom')
));

$installer->addAttribute ('catalog_product', Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_WIDTH,  array(
    'type'             => 'varchar',
    'label'            => Mage::helper ('mhub')->__('Product Width'),
    'input'            => 'text',
    'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'          => true,
    'required'         => false,
    'user_defined'     => true,
    'searchable'       => false,
    'filterable'       => false,
    'comparable'       => false,
    'visible_on_front' => false,
    'unique'           => false,
    'group'            => Mage::helper ('mhub')->__('Epicom')
));

$installer->addAttribute ('catalog_product', Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_LENGTH,  array(
    'type'             => 'varchar',
    'label'            => Mage::helper ('mhub')->__('Product Length'),
    'input'            => 'text',
    'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'          => true,
    'required'         => false,
    'user_defined'     => true,
    'searchable'       => false,
    'filterable'       => false,
    'comparable'       => false,
    'visible_on_front' => false,
    'unique'           => false,
    'group'            => Mage::helper ('mhub')->__('Epicom')
));

$defaultAttributeSetId = Mage::getModel ('catalog/product')->getDefaultAttributeSetId ();

$coreConfig = Mage::getModel ('core/config');
$coreConfig->saveConfig ('mhub/attributes_set/product', $defaultAttributeSetId);

$installer->endSetup();

