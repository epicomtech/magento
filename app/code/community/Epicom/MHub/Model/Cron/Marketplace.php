<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cron_Marketplace extends Epicom_MHub_Model_Cron_Abstract
{
    const MARKETPLACES_METHOD = 'marketplaces';

    public function run ()
    {
        $collection = Mage::getModel ('core/config_data')->getCollection ()
            ->addFieldToFilter ('path', array('eq' => Epicom_MHub_Helper_Data::XML_PATH_MHUB_SETTINGS_ACTIVE))
            ->addValueFilter (1)
        ;

        $scopeIds = array ();

        foreach ($collection as $config)
        {
            $scopeIds [] = $config->getScopeId ();
        }

        $collection = Mage::getModel ('core/config_data')->getCollection ()
            ->addFieldToFilter ('scope_id', array ('in' => $scopeIds))
            ->addFieldToFilter ('path',  array ('eq' => Epicom_MHub_Helper_Data::XML_PATH_MHUB_SETTINGS_MODE))
            ->addFieldToFilter ('value', array ('eq' => Epicom_MHub_Helper_Data::API_MODE_PROVIDER))
        ;

        foreach ($collection as $config)
        {
            $scopeId = $config->getScopeId ();

            $storeId = Mage::helper ('mhub')->getStoreConfig ('store_view', $scopeId);

            $websiteId = Mage::app ()->getStore ($storeId)->getWebsite ()->getId ();

            try
            {
                $result = $this->getHelper ()->api (self::MARKETPLACES_METHOD, null, null, $scopeId);

                foreach ($result as $item)
                {
                    $marketplace = Mage::getModel ('mhub/marketplace')->load ($item->id, 'external_id');

                    $marketplace->setExternalId ($item->id)
                        ->setWebsiteId ($websiteId)
                        ->setStoreId ($storeId)
                        ->setScopeId ($scopeId)
                        ->setCode ($item->codigo)
                        ->setName ($item->nome)
                        ->setFantasyName ($item->nomeFantasia)
                        ->setUseCategories ($item->usaCategorias)
                        ->setUpdatedAt (date ('c'))
                        ->save ()
                    ;
                }
            }
            catch (Exception $e)
            {
                throw Mage::exception ('Epicom_MHub', $e->getMessage (), $e->getCode ());
            }
        }

        return true;
    }
}

