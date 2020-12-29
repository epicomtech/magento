<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Order_Api extends Epicom_MHub_Model_Api_Resource_Abstract
{
    const CUSTOMER_MODE_REGISTER = Mage_Checkout_Model_Api_Resource_Customer::MODE_REGISTER;

    /**
     * field_1: carrier
     * field_2: shipping
     */
    const SHIPPING_DESCRIPTION_REGEX = '/(.*)([\d])(.*)/';

    public function __construct ()
    {
        Mage::app ()->setCurrentStore (Mage_Core_Model_App::ADMIN_STORE_ID);
    }

    public function create ($marketplace, $orderCode, $createdAt, $items, $recipient, $shipping, $discount, $fee)
    {
        if (empty ($marketplace) || empty ($orderCode) || empty ($createdAt)
            || empty ($items) || empty ($recipient) || empty ($shipping)
            || !isset ($discount) || !isset ($fee))
        {
            $this->_fault ('invalid_request_param');
        }

        $mhubOrder = Mage::getModel ('mhub/order')->setOrderExternalId ($orderCode)
            ->setOperation (Epicom_MHub_Helper_Data::OPERATION_IN)
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_PENDING)
            ->setUpdatedAt (date ('c'))
            ->save ()
        ;

        $mageOrder = Mage::getModel ('sales/order')->loadByAttribute (
            Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_EXT_ORDER_ID, $orderCode
        );

        if (!empty ($mageOrder) && intval ($mageOrder->getId ()) > 0)
        {
            return $this->_error ($mhubOrder, Mage::helper ('mhub')->__('Order already exists'), null /* order_already_exists */, 200);
        }

        $productCodeAttribute = Mage::getStoreConfig ('mhub/product/code');
        // $productSkuAttribute  = Mage::getStoreConfig ('mhub/product/sku');

        $storeId = intval (Mage::getStoreConfig ('mhub/quote/store_view'));

        $quoteId = Mage::getModel ('checkout/cart_api')->create ($storeId);

        $quote = Mage::getModel ('sales/quote')->load ($quoteId)
            ->setIsSuperMode (true)
        ;

        foreach ($items as $_item)
        {
            $productCode  = strval   ($_item ['codigo']);
            $productPrice = floatval ($_item ['precoProduto']);
            $productSku   = strval   ($_item ['nomeSku']);
            $productQty   = intval   ($_item ['quantidade']);

            $mageProduct = Mage::getModel ('catalog/product')->loadByAttribute ($productCodeAttribute, $productCode);

            if (!$mageProduct || !$mageProduct->getId ())
            {
                return $this->_error ($mhubOrder, Mage::helper ('mhub')->__('Product not exists: %s', $productCode), null /* product_not_exists */);
            }
/*
            if (strcmp ($productSku, $mageProduct->getData ($productSkuAttribute)))
            {
                return $this->_error ($mhubOrder, Mage::helper ('mhub')->__('Invalid Product SKU: %s', $productSku), null / * invalid_product_sku * /);
            }

            if ($productPrice != $mageProduct->getFinalPrice ())
            {
                return $this->_error ($mhubOrder, Mage::helper ('mhub')->__('Invalid Product Price: %s', $productPrice), null / * invalid_product_price * /);
            }
*/
            $stockItem = Mage::getModel ('cataloginventory/stock_item')->assignProduct ($mageProduct);

            if ($productQty > $stockItem->getQty () || !$stockItem->getIsInStock ())
            {
                return $this->_error ($mhubOrder, Mage::helper ('mhub')->__('Invalid Product Qty: %s', $productQty), null /* invalid_product_qty */);
            }

            $productByItem = $this->_getProduct ($mageProduct->getId (), $storeId, 'id');

            $productRequest = new Varien_Object ();
            $productRequest->setQty ($productQty);

            try
            {
/*
                Mage::getModel ('checkout/cart_product_api')->add (
                    $quoteId,
                    array(
                        array(
                            'product_id' => $mageProduct->getId (),
                            'qty'        => $productQty
                        )
                    ),
                    $storeId
                );
*/
                $result = $quote->addProductAdvanced ($productByItem, $productRequest, Mage_Catalog_Model_Product_Type_Abstract::PROCESS_MODE_LITE);

                if (is_string ($result))
                {
                    throw Mage::exception ('Epicom_MHub', $result);
                }
                {
                    $result
                        ->setIsSuperMode(true)
                        ->setOriginalCustomPrice ($productPrice)
                        ->setCustomPrice ($productPrice)
                        ->setQty ($productQty)
                        ->save ()
                    ;
                }

                // $quote->collectTotals ()->save ();
            }
            catch (Exception $e)
            {
                return $this->_error ($mhubOrder, Mage::helper ('mhub')->__('Product was not added: %s', $e->getMessage ()), null /* add_product_fault */);
            }
        }

        $defaultStoreId = Mage::app ()->getWebsite ()->getDefaultGroup ()->getDefaultStoreId ();
        $defaultStore   = Mage::getModel ('core/store')->load ($storeId ? $storeId : $defaultStoreId);

        $customerMode   = Mage::getStoreConfig ('mhub/quote/customer_mode');

        $customerName   = $recipient ['nomeDestinatario'];
        $_customerPos   = strpos ($customerName, " ");
        $customerEmail  = $recipient ['emailDestinatario'];
        $customerTaxvat = $recipient ['cpfCnpjDestinatario'];
/*
        $mageCustomer = Mage::getModel ('customer/customer')
            ->setWebsiteId ($defaultStore->getWebsiteId ())
            ->loadByEmail ($customerEmail)
        ;
*/
        $taxvatSuffix  = Mage::getStoreConfig (Epicom_MHub_Helper_Data::XML_PATH_MHUB_QUOTE_TAXVAT_SUFFIX);

        $mageCustomer = Mage::getModel ('customer/customer')
            ->getCollection ()
            ->addAttributeToSelect ('*')
            ->addAttributeToFilter ('taxvat',  $customerTaxvat . $taxvatSuffix)
            ->getFirstItem ()
        ;

        if (!$mageCustomer || !$mageCustomer->getId ())
        {
            $mageCustomer = Mage::getModel ('customer/customer')
                ->setEmail ($customerEmail)
            ;
        }

        $customerGroup = Mage::getStoreConfig (Epicom_MHub_Helper_Data::XML_PATH_MHUB_CUSTOMER_GROUP);

        $mageCustomer->setTaxvat ($customerTaxvat . $taxvatSuffix)
            ->setGroupId ($customerGroup)
            ->setFirstname (substr ($customerName, 0, $_customerPos))
            ->setLastname  (substr ($customerName, $_customerPos + 1))
        ;

        $mageCustomer->setMode ($customerMode);

        if (!strcmp ($mageCustomer->getMode (), self::CUSTOMER_MODE_REGISTER))
        {
            $customerPassword = $mageCustomer->generatePassword ();

            $mageCustomer->setPassword ($customerPassword)
                ->setPasswordConfirmation ($customerPassword)
                ->save ()
            ;
        }

        $customerId = $mageCustomer->getId ();

        if (intval ($customerId) > 0)
        {

        Mage::getModel ('checkout/cart_customer_api')->set ($quoteId, array(
            'mode'        => Mage_Checkout_Model_Api_Resource_Customer::MODE_CUSTOMER,
            'customer_id' => $customerId,
        ));

        }
        else
        {

        Mage::getModel ('checkout/cart_customer_api')->set ($quoteId, $mageCustomer->getData (), $storeId);

        }

        $customerAddress = Mage::getModel ('customer/address')->setData (array (
            'firstname'  => substr ($customerName, 0, $_customerPos),
            'lastname'   => substr ($customerName, $_customerPos + 1),
            'company'    => $shipping ['referenciaEntrega'],
            'street'     => array(
                $shipping ['logradouroEntrega'],
                $shipping ['numeroEntrega'],
                $shipping ['complementoEntrega'],
                $shipping ['bairroEntrega'],
            ),
            'city'       => $shipping ['cidadeEntrega'],
            'region'     => $this->_getRegionByCode ($shipping ['estadoEntrega'], 'name'),
            'region_id'  => $this->_getRegionByCode ($shipping ['estadoEntrega'], 'region_id'),
            'postcode'   => $shipping ['cepEntrega'],
            'country_id' => 'BR',
            'telephone'  => $shipping ['telefoneEntrega'],
        ));

        $customerAddressId = null;

        if (intval ($customerId) > 0)
        {
            $customerAddressId = Mage::getModel ('customer/address_api')->create ($customerId, $customerAddress->getData ());
        }

        $customerAddressModes = array(
            Mage_Checkout_Model_Api_Resource_Customer::ADDRESS_BILLING,
            Mage_Checkout_Model_Api_Resource_Customer::ADDRESS_SHIPPING
        );

        foreach ($customerAddressModes as $mode)
        {
            if (intval ($customerId) > 0)
            {

            Mage::getModel ('checkout/cart_customer_api')->setAddresses ($quoteId, array(
                array(
                    'mode'       => $mode,
            		'address_id' => $customerAddressId
                )
            ), $storeId);

            }
            else
            {

            $customerAddress->setMode ($mode);

            Mage::getModel ('checkout/cart_customer_api')->setAddresses ($quoteId, array ($customerAddress->getData ()), $storeId);

            }
        }

        Mage::getModel ('checkout/cart_shipping_api')->setShippingMethod (
            $quoteId, str_repeat (Epicom_MHub_Model_Shipping_Carrier_Epicom::CODE . '_', 2) . 'provider', $storeId
        );

        Mage::getModel ('checkout/cart_payment_api')->setPaymentMethod (
            $quoteId, array ('method' => Epicom_MHub_Model_Payment_Method_Epicom::CODE), $storeId
        );

        try
        {
            $incrementId = Mage::getModel ('checkout/cart_api')->createOrder ($quoteId, $storeId);
        }
        catch (Exception $e)
        {
            $errorMessage = $e->getCustomMessage () ? $e->getCustomMessage () : $e->getMessage();

            return $this->_error ($mhubOrder, $errorMessage, null /* others */);
        }

        $mageOrder = Mage::getModel ('sales/order')->loadByIncrementId ($incrementId)
            ->setCreatedAt ($createdAt)
            ->setData (Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_EXT_ORDER_ID, $orderCode)
            ->setCustomerGroupId ($customerGroup)
            ->save ()
        ;

        $mhubOrder->setOrderIncrementId ($incrementId)
            ->setOrderId ($mageOrder->getId ())
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
            ->setMessage (Mage::helper ('mhub')->__('Order successfully created'))
            ->setSyncedAt (date ('c'))
            ->save ()
        ;

        if ($mageCustomer && $mageCustomer->getId ())
        {
            $resource = Mage::getSingleton ('core/resource');
            $write    = $resource->getConnection ('core_write');
            $table    = $resource->getTableName ('customer/entity');

            $bind = array(
                'group'    => $customerGroup,
                'customer' => $mageCustomer->getId (),
            );

            $write->query (sprintf ('UPDATE %s SET group_id = :group WHERE entity_id = :customer', $table), $bind);
        }

        $result = array(
            'codigoDoPedido'      => $incrementId,
            'parametrosExtras'    => null,
            'diasParaEntrega'     => 0,
            'valorDoFrete'        => 0,
            'valorTotalDoPedido'  => $mageOrder->getBaseGrandTotal (),
            'produtosPedido'      => array (),
            'requestLogs'         => null,
            'cancelarPedidoCanal' => false,
            'mensagem'            => Mage::helper ('mhub')->__('Order successfully created'),
        );

        foreach ($items as $_item)
        {
            $result ['produtosPedido'][] = array(
                'codigoProduto'     => $_item ['codigo'],
                'quantidadeProduto' => $_item ['quantidade'],
            );

            /**
             * Freight
             */
            if (empty ($_shipping)) continue;

            preg_match (self::SHIPPING_DESCRIPTION_REGEX, $_item ['formaEntrega'], $_shipping);

            $_daysForDelivery = preg_replace ('[\D]', "", $_shipping [2]);

            if (intval ($_daysForDelivery) > $result ['diasParaEntrega'])
            {
                $result ['diasParaEntrega'] = $_daysForDelivery;
            }

            if (floatval ($_item ['precoFrete']) > $result ['valorDoFrete'])
            {
                $result ['valorFrete'] = $_item ['precoFrete'];
            }
        }

        return $result;
    }

    protected function _getProduct ($productId, $storeId = null, $identifierType = null)
    {
        $product = Mage::helper ('catalog/product')->getProduct(
            $productId,
            $storeId,
            $identifierType
        );

        return $product;
    }

    public function _getRegionByCode ($regionCode, $attribute, $countryId = 'BR')
    {
        $collection = Mage::getModel ('directory/region')->getResourceCollection ()->addCountryFilter ($countryId);
        $collection->getSelect ()->where ("main_table.code = '{$regionCode}'");

        $result = $collection->getFirstItem ()->getData ($attribute);

        return $result;
    }

    protected function _error ($model, $message, $fault = null, $response = 400)
    {
        parent::_log ($model, $message, $fault, $response);

        $orderAutoCancel = Mage::getStoreConfigFlag ('mhub/order/auto_cancel');

        $result = array(
            'codigoDoPedido' => $model->getOrderExternalId (),
            'mensagem'     => $message,
            'cancelamentoAutomatico' => $orderAutoCancel
        );

        return $result;
    }
}

