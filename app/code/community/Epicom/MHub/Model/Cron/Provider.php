<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cron_Provider extends Epicom_MHub_Model_Cron_Abstract
{
    const XML_PATH_MHUB_SETTINGS_KEY = 'mhub/settings/key';

    const PROVIDERS_METHOD = 'fornecedores';

    public function run ()
    {
        if (!$this->getStoreConfig ('active') || !$this->getHelper ()->isMarketplace ())
        {
            return false;
        }

        $collection = Mage::getModel ('core/config_data')->getCollection ()
            ->addFieldToFilter ('path',  array ('eq'      => self::XML_PATH_MHUB_SETTINGS_KEY))
            ->addFieldToFilter ('value', array ('notnull' => true))
        ;

        foreach ($collection as $config)
        {
            if (!strcmp ($config->getScope (), 'websites'))
            {
                $websiteId = $config->getScopeId ();
                $storeId   = Mage::app ()->getWebsite ($websiteId)->getDefaultStore ()->getId ();
            }
            else if (!strcmp ($config->getScope (), 'stores'))
            {
                $storeId   = $config->getScopeId ();
                $websiteId = Mage::app ()->getStore ($storeId)->getWebsite ()->getId ();
            }

        try
        {
            $result = $this->getHelper ()->api (self::PROVIDERS_METHOD, null, null, $storeId);

            foreach ($result as $item)
            {
                $provider = Mage::getModel ('mhub/provider')->load ($item->id, 'external_id');

                $provider->setExternalId ($item->id)
                    ->setWebsiteId ($websiteId)
                    ->setStoreId ($storeId)
                    ->setCode ($item->codigo)
                    ->setName ($item->nomeFantasia)
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

        } // foreach

        return true;
    }
}

