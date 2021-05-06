<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = $this;
$installer->startSetup ();

function updateMHubWebsiteStoreTable ($installer, $model)
{
    $table = $installer->getTable ($model);

    $installer->getConnection ()
        ->addColumn ($table, 'scope_id', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'   => 11,
            'unsigned' => false,
            'nullable' => true,
            'comment'  => 'Scope ID',
            'after'    => 'store_id',
        ))
    ;
}

updateMHubWebsiteStoreTable ($installer, Epicom_MHub_Helper_Data::CATEGORY_TABLE);
updateMHubWebsiteStoreTable ($installer, Epicom_MHub_Helper_Data::ERROR_TABLE);
updateMHubWebsiteStoreTable ($installer, Epicom_MHub_Helper_Data::PRODUCT_TABLE);
updateMHubWebsiteStoreTable ($installer, Epicom_MHub_Helper_Data::PROVIDER_TABLE);
updateMHubWebsiteStoreTable ($installer, Epicom_MHub_Helper_Data::ORDER_TABLE);
updateMHubWebsiteStoreTable ($installer, Epicom_MHub_Helper_Data::ORDER_STATUS_TABLE);
updateMHubWebsiteStoreTable ($installer, Epicom_MHub_Helper_Data::SHIPMENT_TABLE);
updateMHubWebsiteStoreTable ($installer, Epicom_MHub_Helper_Data::NF_TABLE);
updateMHubWebsiteStoreTable ($installer, Epicom_MHub_Helper_Data::QUOTE_TABLE);

$installer->endSetup ();

