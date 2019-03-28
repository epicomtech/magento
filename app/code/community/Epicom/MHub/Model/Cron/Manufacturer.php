<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2019 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cron_Manufacturer extends Epicom_MHub_Model_Cron_Abstract
{
    public function run ()
    {
        if (!$this->getStoreConfig ('active') || !$this->getHelper ()->isMarketplace ())
        {
            return false;
        }

        try
        {
            $entityTypeId = Mage::getModel ('eav/entity')
                ->setType (Mage_Catalog_Model_Product::ENTITY)
                ->getTypeId ()
            ;

            $manufacturerAttribute = Mage::getModel ('eav/entity_attribute')->loadByCode (
                $entityTypeId, Mage::getStoreConfig ('mhub/product/manufacturer')
            );

            $resource = Mage::getSingleton ('core/resource');
            $write    = $resource->getConnection ('core_write');
            $table    = $resource->getTableName ('catalog_product_entity_' . $manufacturerAttribute->getBackendType ());

            $providerCollection = Mage::getModel ('mhub/provider')->getCollection ()
                // ->addFieldToFilter ('customer_id', array ('gt' => 0))
            ;

            foreach ($providerCollection as $provider)
            {
                $productCollection = Mage::getModel ('catalog/product')->getCollection ()
                    ->addAttributeToFilter (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_MANUFACTURER, array ('eq' => $provider->getCode ()))
                ;

                foreach ($productCollection as $product)
                {
                    $productManufacturerOptionId = Mage::getModel ('mhub/config')->addAttributeOptionValue (
                        $manufacturerAttribute->getId (),
                        array(
                            'order' => '0',
                            'label' => array (
                                array ('store_code' => 'admin', 'value' => $provider->getName ())
                            ),
                        )
                    );

                    $write->insertOnDuplicate ($table, array (
                        'entity_type_id' => $entityTypeId,
                        'attribute_id'   => $manufacturerAttribute->getId (),
                        'store_id'       => Mage_Core_Model_App::ADMIN_STORE_ID,
                        'entity_id'      => $product->getId (),
                        'value'          => $productManufacturerOptionId,
                    ));
                }
            }
        }
        catch (Exception $e)
        {
            throw Mage::exception ('Epicom_MHub', $e->getMessage (), $e->getCode ());
        }

        return true;
    }
}

