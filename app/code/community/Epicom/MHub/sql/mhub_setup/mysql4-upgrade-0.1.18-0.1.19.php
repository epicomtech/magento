<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2019 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = $this;
$installer->startSetup ();

function updateMHubProviderTable ($installer, $model)
{
    $table = $installer->getTable ($model);

    $installer->getConnection ()
        ->addColumn ($table, 'is_service', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
            'unsigned' => false,
            'nullable' => false,
            'comment'  => 'Is Service',
            'after'   => 'use_categories',
        ))
    ;
}

updateMHubProviderTable ($installer, Epicom_MHub_Helper_Data::PROVIDER_TABLE);

$installer->endSetup ();

