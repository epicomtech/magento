<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cron_Brand extends Epicom_MHub_Model_Cron_Abstract
{
    const BRANDS_POST_METHOD = '/fornecedor/marcas';

    private function readMHubBrandsMagento ()
    {
        $attributeCode = Mage::getStoreConfig ('mhub/product/brand');
        $attribute     = Mage::getModel ('eav/entity_attribute')->loadByCode ('catalog_product', $attributeCode);

        $collection = Mage::getResourceModel ('eav/entity_attribute_option_collection')
            ->setAttributeFilter ($attribute->getId())
            ->setStoreFilter (0)
        ;

        $select = $collection->getSelect ()
            ->joinLeft(
                array ('mhub' => Epicom_MHub_Helper_Data::BRAND_TABLE),
                'main_table.option_id = mhub.option_id',
                array('mhub_updated_at' => 'mhub.updated_at', 'mhub_synced_at' => 'mhub.synced_at')
            )->where ('mhub.synced_at IS NULL')
        ;

        foreach ($collection as $option)
        {
            $optionId    = $option->getId ();
            $optionValue = $option->getValue ();

            $mhubBrand = Mage::getModel ('mhub/brand')->load ($optionId, 'option_id');
            $mhubBrand->setOptionId ($optionId)
                ->setName ($optionValue)
                ->setStatus (Epicom_MHub_Helper_Data::STATUS_PENDING)
                ->setUpdatedAt (date ('c'))
                ->save ();
        }

        return true;
    }

    private function readMHubBrandsCollection ()
    {
        $collection = Mage::getModel ('mhub/brand')->getCollection ();

        $select = $collection->getSelect ();
        $select->where ('synced_at < updated_at OR synced_at IS NULL')
               ->group ('option_id')
               ->order ('updated_at DESC')
        ;

        return $collection;
    }

    private function updateBrands ($collection)
    {
        foreach ($collection as $brand)
        {
            $result = null;

            try
            {
                $result = $this->updateMHubBrand ($brand);
            }
            catch (Exception $e)
            {
                $this->logMHubBrand ($brand, $e->getMessage ());

                Mage::logException ($e);
            }

            if (!empty ($result)) $this->cleanupMHubBrand ($brand);
        }

        return true;
    }

    private function updateMHubBrand (Epicom_MHub_Model_Brand $brand)
    {
        $post = array(
            'codigo'       => $brand->getOptionId (),
            'nome'         => $brand->getName (),
        );

        try
        {
            $this->getHelper ()->api (self::BRANDS_POST_METHOD, $post);
        }
        catch (Exception $e)
        {
            if ($e->getCode () != 409 /* Resource Exists */)
            {
                throw Mage::exception ('Epicom_MHub', $e->getMessage (), $e->getCode ());
            }
        }

        return true;
    }

    private function cleanupMHubBrand (Epicom_MHub_Model_Brand $brand)
    {
        $brand->setSyncedAt (date ('c'))
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
            ->setMessage (new Zend_Db_Expr ('NULL'))
            ->save ();

        return true;
    }

    private function logMHubBrand (Epicom_MHub_Model_Brand $brand, $message = null)
    {
        $brand->setStatus (Epicom_MHub_Helper_Data::STATUS_ERROR)->setMessage ($message)->save ();
    }

    public function run ()
    {
        if (!$this->getStoreConfig ('active')) return false;

        $result = $this->readMHubBrandsMagento ();
        if (!$result) return false;

        $collection = $this->readMHubBrandsCollection ();
        $length = $collection->count ();
        if (!$length) return false;

        $this->updateBrands ($collection);

        return true;
    }
}

