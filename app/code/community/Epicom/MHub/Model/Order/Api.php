<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Order_Api extends Epicom_MHub_Model_Api_Resource_Abstract
{
    public function create ($marketplace, $orderCode, $createdAt, $items, $recipient, $shipping)
    {
        if (empty ($marketplace) || empty ($orderCode) || empty ($createdAt)
            || empty ($items) || empty ($recipient) || empty ($shipping))
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
            return $this->_error ($mhubOrder, Mage::helper ('mhub')->__('Order already exists'), null /* order_already_exists */);
        }

        $productCodeAttribute = Mage::getStoreConfig ('mhub/product/code');
        // $productSkuAttribute  = Mage::getStoreConfig ('mhub/product/sku');

        $quoteId = Mage::getModel ('checkout/cart_api')->create ();

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

            try
            {
                Mage::getModel ('checkout/cart_product_api')->add ($quoteId, array(
                    array(
                        'product_id' => $mageProduct->getId (),
                        'sku'        => $productSku,
                        'qty'        => $productQty
                    )
                ));
            }
            catch (Exception $e)
            {
                return $this->_error ($mhubOrder, Mage::helper ('mhub')->__('Product was not added: %s', $e->getMessage ()), null /* add_product_fault */);
            }
        }

        $defaultStoreId = Mage::app ()->getWebsite ()->getDefaultGroup ()->getDefaultStoreId ();
        $defaultStore   = Mage::getModel ('core/store')->load ($defaultStoreId);

        $customerName   = explode (chr (32), $recipient ['nomeDestinatario']);
        $customerEmail  = $recipient ['emailDestinatario'];
        $customerTaxvat = $recipient ['cpfCnpjDestinatario'];

        $mageCustomer = Mage::getModel ('customer/customer')
            ->setWebsiteId ($defaultStore->getWebsiteId ())
            ->loadByEmail ($customerEmail)
        ;

        if (!$mageCustomer || !$mageCustomer->getId ())
        {
            $mageCustomer = Mage::getModel ('customer/customer')
                ->setEmail ($customerEmail)
                ->setFirstname ($customerName [0])
                ->setLastname  ($customerName [1])
                ->setTaxvat ($customerTaxvat)
                ->save ()
            ;
        }

        $customerId = $mageCustomer->getId ();

        Mage::getModel ('checkout/cart_customer_api')->set ($quoteId, array(
            'mode'        => Mage_Checkout_Model_Api_Resource_Customer::MODE_CUSTOMER,
            'customer_id' => $customerId,
        ));

        $customerAddressId = Mage::getModel ('customer/address_api')->create ($customerId, array(
            'firstname'  => $customerName [0],
            'lastname'   => $customerName [1],
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

        $customerAddressModes = array(
            Mage_Checkout_Model_Api_Resource_Customer::ADDRESS_BILLING,
            Mage_Checkout_Model_Api_Resource_Customer::ADDRESS_SHIPPING
        );

        foreach ($customerAddressModes as $mode)
        {
            Mage::getModel ('checkout/cart_customer_api')->setAddresses ($quoteId, array(
                array(
                    'mode'       => $mode,
            		'address_id' => $customerAddressId
                )
            ));
        }

        Mage::getModel ('checkout/cart_shipping_api')->setShippingMethod ($quoteId,
            str_repeat (Epicom_MHub_Model_Shipping_Carrier_Epicom::CODE . '_', 2) . 'provider'
        );

        Mage::getModel ('checkout/cart_payment_api')->setPaymentMethod ($quoteId, array(
            'method' => Epicom_MHub_Model_Payment_Method_Epicom::CODE
        ));

        $incrementId = Mage::getModel ('checkout/cart_api')->createOrder ($quoteId);

        $mageOrder = Mage::getModel ('sales/order')->loadByIncrementId ($incrementId)
            ->setCreatedAt ($createdAt)
            ->setData (Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_EXT_ORDER_ID, $orderCode)
            ->save ()
        ;

        $mhubOrder->setOrderIncrementId ($incrementId)
            ->setOrderId ($mageOrder->getId ())
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
            ->setMessage (Mage::helper ('mhub')->__('Order successfully created'))
            ->setSyncedAt (date ('c'))
            ->save ()
        ;

        return array ('codigoPedido' => $incrementId);
    }

    public function _getRegionByCode ($regionCode, $attribute, $countryId = 'BR')
    {
        $collection = Mage::getModel ('directory/region')->getResourceCollection ()->addCountryFilter ($countryId);
        $collection->getSelect ()->where ("main_table.code = '{$regionCode}'");

        $result = $collection->getFirstItem ()->getData ($attribute);

        return $result;
    }

    protected function _error ($model, $message, $fault = null)
    {
        parent::_log ($model, $message, $fault);

        $orderAutoCancel = Mage::getStoreConfigFlag ('mhub/order/auto_cancel');

        $result = array(
            'codigoPedido' => $model->getOrderExternalId (),
            'mensagem'     => $message,
            'cancelamentoAutomatico' => $orderAutoCancel
        );

        return $result;
    }
}

