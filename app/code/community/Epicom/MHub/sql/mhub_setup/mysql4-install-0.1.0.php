<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = $this;
$installer->startSetup();

function addMHubCategoriesTable ($installer, $model, $description)
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
        ->addColumn ($table, 'category_id', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'   => 11,
            'unsigned' => true,
            'nullable' => false,
            'comment'  => 'Category ID',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'attribute_set_id', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'   => 11,
            'unsigned' => true,
            'nullable' => false,
            'comment'  => 'Attribute Set ID',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'associable', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
            'nullable' => false,
            'comment'  => 'Associable',
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
            'type'     => Varien_Db_Ddl_Table::TYPE_DATETIME,
            'nullable' => false,
            'comment'  => 'Updated At',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'synced_at', array(
            'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
            'nullable' => true,
            'comment' => 'Synced At'
        ));
}

addMHubCategoriesTable ($installer, Epicom_MHub_Helper_Data::CATEGORY_TABLE, 'Epicom MHub Category');

$installer->addAttribute('catalog_category', Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_SET,  array(
    'type'             => 'int',
    'label'            => Mage::helper('mhub')->__('Category Attribute Set'),
    'input'            => 'select',
    'source'           => 'mhub/eav_entity_attribute_source_category_attributeSets',
    'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'          => true,
    'required'         => false,
    'user_defined'     => true,
    'searchable'       => false,
    'filterable'       => false,
    'comparable'       => false,
    'visible_on_front' => false,
    'unique'           => false,
    'group'            => Mage::helper('mhub')->__('Epicom')
));

$installer->addAttribute ('catalog_category', Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_ISACTIVE,  array(
    'type'             => 'int',
    'label'            => Mage::helper ('mhub')->__('Category Is Active'),
    'input'            => 'select',
    // 'input_renderer'   => 'mhub/catalog_category_helper_form_boolean',
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
    // 'default'          => '1',
    'group'            => Mage::helper ('mhub')->__('Epicom')
));

$installer->addAttribute ('catalog_category', Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_SENDPRODUCTS,  array(
    'type'             => 'int',
    'label'            => Mage::helper ('mhub')->__('Category Send Products'),
    'input'            => 'select',
    // 'input_renderer'   => 'mhub/catalog_category_helper_form_boolean',
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
    // 'default'          => '1',
    'group'            => Mage::helper ('mhub')->__('Epicom')
));

$installer->endSetup();

