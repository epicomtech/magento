<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cron_Provider extends Epicom_MHub_Model_Cron_Abstract
{
    const PROVIDERS_METHOD = 'fornecedores';

    public function run ()
    {
        if (!$this->getStoreConfig ('active') || !$this->getHelper ()->isMarketplace ())
        {
            return false;
        }

        try
        {
            $result = $this->getHelper ()->api (self::PROVIDERS_METHOD);

            foreach ($result as $item)
            {
                $provider = Mage::getModel ('mhub/provider')->load ($item->id, 'external_id');

                $provider->setExternalId ($item->id)
                    ->setCode ($item->code)
                    ->setName ($item->nome)
                    ->setUseCategories ($item->usaCategorias)
                ;
            }
        }
        catch (Exception $e)
        {
            throw Mage::exception ('Epicom_MHub', $e->getMessage (), $e->getCode ());
        }

        return true;
    }
}

