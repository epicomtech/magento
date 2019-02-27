<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2019 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cron_Product_Availability extends Epicom_MHub_Model_Cron_Product_Input
{
    const DEFAULT_QUEUE_LIMIT = 300;

    protected $_methods = array(
        Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_PRICE,
        Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_STOCK,
        Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_AVAILABILITY,
    );

    protected $_entityTypeId = null;

    protected $_statusAttribute       = null;
    protected $_priceAttribute        = null;
    protected $_priceSpecialAttribute = null;

    public function _construct ()
    {
        $this->_entityTypeId = Mage::getModel ('eav/entity')->setType (Mage_Catalog_Model_Product::ENTITY)->getTypeId ();

        $this->_statusAttribute        = Mage::getModel ('eav/entity_attribute')->loadByCode ($this->_entityTypeId, 'status');
        $this->_priceAttribute         = Mage::getModel ('eav/entity_attribute')->loadByCode ($this->_entityTypeId, 'price');
        $this->_priceSpecialAttribute  = Mage::getModel ('eav/entity_attribute')->loadByCode ($this->_entityTypeId, 'special_price');
    }


    protected function updateMHubProduct (Epicom_MHub_Model_Product $product)
    {
        $productId  = $product->getExternalId ();
        $productSku = $product->getExternalSku ();

        /**
         * Load
         */
        $mageProduct = Mage::getModel ('catalog/product')->loadByAttribute (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID, $productSku, null);

        if (!$mageProduct || !$mageProduct->getId ())
        {
            $this->_fault ('product_not_exists');
        }

        $productsAvailabilityMethod = str_replace (array ('{productId}', '{productSku}'), array ($productId, $productSku), self::PRODUCTS_AVAILABILITY_METHOD);
        $productsAvailabilityResult = $this->getHelper ()->api ($productsAvailabilityMethod);

        if (empty ($productsAvailabilityResult))
        {
            throw Mage::exception ('Epicom_MHub', Mage::helper ('mhub')->__('Empty SKU Availability! SKU %s', $productSku), 9999);
        }

        $resource = Mage::getSingleton ('core/resource');
        $write    = $resource->getConnection ('core_write');

        /**
         * Parse
         */
        switch ($product->getMethod ())
        {
            case Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_PRICE:
            case Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_STOCK:
            case Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_AVAILABILITY:
            {
                // price
                $table = $resource->getTableName ('catalog_product_entity_' . $this->_priceAttribute->getBackendType ());

                $productPriceFrom = $productsAvailabilityResult->precoDe;
                $productPriceTo = $productsAvailabilityResult->preco;

                if (!empty ($productPriceFrom))
                {
                    $mageProduct->setPrice ($productPriceFrom);
                    $mageProduct->setSpecialPrice ($productPriceTo);

                    $write->insertOnDuplicate ($table, array (
                        'entity_type_id' => $this->_entityTypeId,
                        'attribute_id'   => $this->_priceAttribute->getId (),
                        'store_id'       => Mage_Core_Model_App::ADMIN_STORE_ID,
                        'entity_id'      => $mageProduct->getId (),
                        'value'          => $productPriceFrom,
                    ));

                    $write->insertOnDuplicate ($table, array (
                        'entity_type_id' => $this->_entityTypeId,
                        'attribute_id'   => $this->_priceSpecialAttribute->getId (),
                        'store_id'       => Mage_Core_Model_App::ADMIN_STORE_ID,
                        'entity_id'      => $mageProduct->getId (),
                        'value'          => $productPriceTo,
                    ));
                }
                else
                {
                    $mageProduct->setPrice ($productPriceTo ? $productPriceTo : 999999);
                    $mageProduct->setSpecialPrice (null);

                    $write->insertOnDuplicate ($table, array (
                        'entity_type_id' => $this->_entityTypeId,
                        'attribute_id'   => $this->_priceAttribute->getId (),
                        'store_id'       => Mage_Core_Model_App::ADMIN_STORE_ID,
                        'entity_id'      => $mageProduct->getId (),
                        'value'          => $productPriceTo ? $productPriceTo : 999999,
                    ));

                    $write->insertOnDuplicate ($table, array (
                        'entity_type_id' => $this->_entityTypeId,
                        'attribute_id'   => $this->_priceSpecialAttribute->getId (),
                        'store_id'       => Mage_Core_Model_App::ADMIN_STORE_ID,
                        'entity_id'      => $mageProduct->getId (),
                        'value'          => new Zend_Db_Expr ('NULL'),
                    ));
                }
                /*
                $mageProduct->save ();
                */

                // parent
                $parentProduct = Mage::getModel ('catalog/product')->loadByAttribute (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID, $productId);
                if ($parentProduct && intval ($parentProduct->getId ()) > 0)
                {
                    if ($mageProduct->getPrice () < $parentProduct->getPrice () /* && $mageProduct->isSalable () */)
                    {
                        /*
                        $parentProduct->setPrice ($mageProduct->getPrice ())
                            ->setSpecialPrice ($mageProduct->getSpecialPrice ())
                            ->save ()
                        ;
                        */

                        $write->insertOnDuplicate ($table, array (
                            'entity_type_id' => $this->_entityTypeId,
                            'attribute_id'   => $this->_priceAttribute->getId (),
                            'store_id'       => Mage_Core_Model_App::ADMIN_STORE_ID,
                            'entity_id'      => $parentProduct->getId (),
                            'value'          => $mageProduct->getPrice (),
                        ));

                        $write->insertOnDuplicate ($table, array (
                            'entity_type_id' => $this->_entityTypeId,
                            'attribute_id'   => $this->_priceSpecialAttribute->getId (),
                            'store_id'       => Mage_Core_Model_App::ADMIN_STORE_ID,
                            'entity_id'      => $parentProduct->getId (),
                            'value'          => $mageProduct->getSpecialPrice (),
                        ));
                    }
                }

                // status
                $table = $resource->getTableName ('catalog_product_entity_' . $this->_statusAttribute->getBackendType ());

                $productStatus = $productsAvailabilityResult->disponivel
                    ? Mage_Catalog_Model_Product_Status::STATUS_ENABLED
                    : Mage_Catalog_Model_Product_Status::STATUS_DISABLED
                ;

                $write->insertOnDuplicate ($table, array (
                    'entity_type_id' => $this->_entityTypeId,
                    'attribute_id'   => $this->_statusAttribute->getId (),
                    'store_id'       => Mage_Core_Model_App::ADMIN_STORE_ID,
                    'entity_id'      => $mageProduct->getId (),
                    'value'          => $productStatus,
                ));

                // stock
                $setIsInStock = true; // $productsAvailabilityResult->disponivel;
                $setQty = $productsAvailabilityResult->estoque;

                $stockItem = Mage::getModel ('cataloginventory/stock_item')
                    ->assignProduct ($mageProduct)
                    ->setStockId (Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID)
                    ->setUseConfigManageStock (true)
                    ->setManageStock (true)
                    ->setIsInStock ($setIsInStock)
                    ->setStockStatusChangedAuto (true)
                    ->setQty ($setQty)
                    ->save ()
                ;
            }
        }

        return $mageProduct->getId ();
    }
}

