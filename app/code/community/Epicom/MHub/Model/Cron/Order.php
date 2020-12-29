<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cron_Order extends Epicom_MHub_Model_Cron_Abstract
{
    const ORDERS_POST_METHOD = 'pedidos';

    protected $_orderId = null;

    protected $_errorMessage = 'An error occurred saving the order.';

    protected $_providers = array ();

    private function readMHubOrdersMagento ()
    {
        /*
        $orderStatus = Mage::getStoreConfig ('mhub/order/reserve_filter');
        */
        $reserveFilter = Mage::getStoreConfig ('mhub/order/reserve_filter'); // bank_slip
        $confirmFilter = Mage::getStoreConfig ('mhub/order/confirm_filter'); // creditcard
        $erpFilter     = Mage::getStoreConfig ('mhub/order/erp_filter'); // ERP

        $collection = Mage::getModel ('sales/order')->getCollection ();

        if ($this->_orderId)
        {
            $collection->addAttributeToFilter ('main_table.entity_id', array ('eq' => $this->_orderId));
        }
        else
        {
            /*
            $collection->addAttributeToFilter ('main_table.status', array ('eq' => $orderStatus));
            */
            $collection->addAttributeToFilter ('main_table.status', array ('in' => array ($reserveFilter, $confirmFilter, $erpFilter)));
            $collection->addAttributeToFilter (Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_IS_EPICOM, array ('notnull' => true));
        }

        $select = $collection->getSelect ()
            ->joinLeft(
                array ('mhub' => Epicom_MHub_Helper_Data::ORDER_TABLE),
                'main_table.entity_id = mhub.order_id',
                array('mhub_updated_at' => 'mhub.updated_at', 'mhub_synced_at' => 'mhub.synced_at')
            )->where ('main_table.created_at > mhub.synced_at OR mhub.synced_at IS NULL');

        foreach ($collection as $order)
        {
            $orderId = $order->getId();

            $websiteId = Mage::app ()->getStore ($order->getStoreId ())->getWebsiteId ();

            $mhubOrder = Mage::getModel ('mhub/order')->load ($orderId, 'order_id');
            $mhubOrder->setOrderId ($orderId)
                ->setWebsiteId ($websiteId)
                ->setStoreId ($order->getStoreId ())
                ->setOrderIncrementId ($order->getIncrementId())
                ->setOrderExternalId ($order->getExtOrderId())
                ->setUpdatedAt (date ('c'))
                ->setOperation (Epicom_MHub_Helper_Data::OPERATION_OUT)
                ->setStatus (Epicom_MHub_Helper_Data::STATUS_PENDING)
                ->setMessage (new Zend_Db_Expr ('NULL'))
                ->save ()
            ;
        }

        return true;
    }

    private function readMHubOrdersCollection ()
    {
        $collection = Mage::getModel ('mhub/order')->getCollection ();
        $select = $collection->getSelect ()->where ('synced_at < updated_at OR synced_at IS NULL');

        if ($this->_orderId)
        {
            $collection->addFieldToFilter ('order_id', array ('eq' => $this->_orderId));
        }

        return $collection;
    }

    private function updateOrders ($collection)
    {
        foreach ($collection as $order)
        {
            $externalOrderId = null;

            try
            {
                $externalOrderId = $this->updateMHubOrder ($order);
            }
            catch (Exception $e)
            {
                $this->logMHubOrder ($order, $e->getMessage ());

                self::logException ($e);

                if ($this->_orderId)
                {
                    try
                    {
                        throw Mage::exception ('Epicom_MHub', $e->getMessage (), $e->getCode ());
                    }
                    catch (Exception $ex)
                    {

                    $errorMessage = Mage::getStoreConfig ('mhub/checkout/error_message');

                    throw new Exception (__($errorMessage ? $errorMessage : $this->_errorMessage));

                    }
                }
            }

            if (!empty ($externalOrderId)) $this->cleanupMHubOrder ($order, $externalOrderId);
        }

        return true;
    }

    private function updateMHubOrder (Epicom_MHub_Model_Order $order)
    {
        $orderId = $order->getOrderId ();

        $mageOrder = Mage::getModel ('sales/order');
        $loaded = $mageOrder->load ($orderId);
        if (!$loaded || !$loaded->getId ())
        {
            return false;
        }
        else
        {
            $mageOrder = $loaded;
        }

        /**
         * Order Info
         */
        $billingAddress  = Mage::getModel('sales/order_address')->load($mageOrder->getBillingAddressId ());
        $shippingAddress = Mage::getModel('sales/order_address')->load($mageOrder->getShippingAddressId ());

		$rawPostcode = $billingAddress->getPostcode();
		$postCode    = Mage::helper ('mhub')->validatePostcode ($rawPostcode);

        $number = explode ('/', $billingAddress->getStreet2 ());

        $post = array(
            'codigoPedido' => $order->getOrderIncrementId (), // $mageOrder->getIncrementId(),
            'dataPedido'   => date ('c', strtotime ($mageOrder->getCreatedAt())),
            'valorTotal'   => null, // $mageOrder->getBaseGrandTotal(),
            'itens'        => array(),
            'destinatario' => array(
                'cpfCnpj'           => preg_replace ('[\D]', "", $mageOrder->getCustomerTaxvat ()),
                'inscricaoEstadual' => null,
                'nome'              => sprintf ("%s %s", $shippingAddress->getFirstname(), $shippingAddress->getLastname()),
                'email'             => $mageOrder->getCustomerEmail(),
                'telefone'          => $shippingAddress->getTelephone(),
            ),
            'endereco' => array(
                'bairro' => $billingAddress->getStreet4(),
                'cep'    => $postCode,
                'cidade' => $billingAddress->getCity(),
                'complemento' => $billingAddress->getStreet3(),
                'estado'      => $this->getRegionName ($billingAddress->getRegionId(), $billingAddress->getCountryId()),
                'logradouro'  => $billingAddress->getStreet1(),
                'numero'      => $number [0], // $billingAddress->getStreet2(),
                'telefone'    => $billingAddress->getTelephone(),
                'referencia'  => null,
            ),
        );

        $freightSplit = Mage::getStoreConfigFlag ('mhub/order/freight_split');

        /**
         * 0: magento_carrier
         * 1: epicom_item_id
         * 2: epicom_carrier
         * 3: epicom_modality
         */
        $shippingMethod = explode ('_', $mageOrder->getShippingMethod ());

        /**
         * Quote Collection
         */
        $direction = Mage::getStoreConfigFlag ('mhub/cart/best_price') ? 'ASC' : 'DESC';

        $mhubQuoteCollection = Mage::getModel ('mhub/quote')->getCollection ()
            ->addFieldToFilter ('store_id', array ('eq' => $mageOrder->getStoreId ()))
            ->addFieldToFilter ('quote_id', array ('eq' => $mageOrder->getQuoteId ()))
        ;

        $mhubQuoteCollection->getSelect ()->order (sprintf ("price %s", $direction));

        /**
         * Quote Items
         */
        $mhubQuoteItems = Mage::getModel ('mhub/quote')->getCollection ();

        $mhubQuoteItems->getSelect ()->reset (Zend_Db_Select::FROM)
            ->from ($mhubQuoteCollection->getSelect ())
            ->group ('sku')
            ->reset (Zend_Db_Select::COLUMNS)
            ->columns ('t.*')
        ;

        if (!$mhubQuoteItems->count () && $freightSplit)
        {
            throw Mage::exception ('Epicom_MHub', Mage::helper ('mhub')->__('Internal Error! No quote item was found. Store %s Quote %s Order %s',
                $mageOrder->getStoreId (), $mageOrder->getQuoteId (), $mageOrder->getIncrementId ()
            ), 9999);
        }

        /**
         * Order Items
         */
        $uniqueShipping = Mage::getStoreConfigFlag ('mhub/cart/unique_shipping');

        $productIdAttribute = Mage::getStoreConfig ('mhub/product/id');

        $mageOrderItems = Mage::getResourceModel ('sales/order_item_collection')
            ->setOrderFilter ($mageOrder)
            ->addFieldToFilter ($productIdAttribute, array ('notnull' => true))
            ->filterByTypes (array (
                Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
                Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL,
                Mage_Catalog_Model_Product_Type::TYPE_GROUPED
            ))
        ;

        if (!$mageOrderItems->count ())
        {
            throw Mage::exception ('Epicom_MHub', Mage::helper ('mhub')->__('Internal Error! No order item was found. Store %s Quote %s Order %s',
                $mageOrder->getStoreId (), $mageOrder->getQuoteId (), $mageOrder->getIncrementId ()
            ), 9999);
        }

        $itemsAmount = 0; // floatval ($mageOrder->getBaseShippingAmount ());

        $itemsPos = $itemsCount = $mageOrderItems->count ();

        $this->_providers = array (); // empty

        foreach ($mageOrderItems as $id => $item)
        {
            $productId = $item->getData ($productIdAttribute);

            $itemBasePrice = $item->getBasePrice ();

            $parentItem = Mage::getModel ('sales/order_item')->load ($item->getParentItemId ());
            if ($parentItem && intval ($parentItem->getId ()) > 0)
            {
                $itemBasePrice = $parentItem->getBasePrice ();
            }

            /*
            // 1: epicom_item_id
            $shippingAmount      = $productId == $shippingMethod [1] ? $mageOrder->getBaseShippingAmount () : 0;
            $shippingDescription = $productId == $shippingMethod [1] ? $mageOrder->getShippingDescription () : null;
            */

            $itemQuote = $mhubQuoteItems->getItemByColumnValue ('sku', $productId);

            if ((!$itemQuote || !$itemQuote->getId ()) && $freightSplit)
            {
                throw Mage::exception ('Epicom_MHub', Mage::helper ('mhub')->__('Internal Error! No quote item INFORMATION was found. Store %s Quote %s Order %s',
                    $mageOrder->getStoreId (), $mageOrder->getQuoteId (), $mageOrder->getIncrementId ()
                ), 9999);
            }

            if ($freightSplit)
            {

            $shippingAmount      = $itemQuote->getPrice ();
            $shippingDescription = $itemQuote->getTitle ();

            if ($uniqueShipping && in_array ($itemQuote->getProvider (), $this->_providers))
            {
                $shippingAmount = 0;
            }
            else
            {
                $this->_providers [] = $itemQuote->getProvider ();
            }

            }
            else
            {

                $shippingAmount      = $itemsPos % $itemsCount == 0 ? $mageOrder->getBaseShippingAmount() : 0;
                $shippingDescription = $itemsPos % $itemsCount == 0 ? $mageOrder->getShippingDescription() : 0;

            } // freightSplit

            $itemsAmount += floatval ($shippingAmount);

            $post ['itens'][] = array(
                'id'           => $productId,
                'quantidade'   => intval ($item->getQtyOrdered()),
                'valor'        => $itemBasePrice,
                /*
                'valorFrete'   => $itemsPos % $itemsCount == 0 ? $mageOrder->getBaseShippingAmount() : 0,
                'formaEntrega' => $itemsPos % $itemsCount == 0 ? $mageOrder->getShippingDescription() : 0,
                */
                'valorFrete'   => $shippingAmount,
                'formaEntrega' => $shippingDescription,
                'prazoEntrega' => null,
            );

            $itemsAmount += floatval ($itemBasePrice) * intval ($item->getQtyOrdered ());

            -- $itemsPos;
        }

        $post ['valorJuros'] = $mageOrder->getBaseFeeAmount ();

        $baseDiscountAmount = abs ($mageOrder->getBaseDiscountAmount ());

        $post ['valorDesconto'] = $baseDiscountAmount;

        $post ['valorTotal'] = round ($itemsAmount - $baseDiscountAmount, 4);

        $extOrderId = true;

        try
        {
            $result = $this->getHelper ()->api (self::ORDERS_POST_METHOD, $post, null, $order->getStoreId ());

            $extOrderId = $result->id;

            $mageOrder->setExtOrderId ($extOrderId)->save (); // for status cron
        }
        catch (Exception $e)
        {
            if ($e->getCode () != 409 /* Resource Exists */)
            {
                throw Mage::exception ('Epicom_MHub', $e->getMessage (), $e->getCode ());
            }
        }

        return $extOrderId;
    }

    private function cleanupMHubOrder (Epicom_MHub_Model_Order $order, $externalOrderId = null)
    {
        if ($externalOrderId !== null && $externalOrderId !== true)
        {
            $order->setOrderExternalId ($externalOrderId);
        }

        $order->setSyncedAt (date ('c'))
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
            ->setMessage (new Zend_Db_Expr ('NULL'))
            ->save ();

        return true;
    }

    private function logMHubOrder (Epicom_MHub_Model_Order $order, $message = null)
    {
        $order->setStatus (Epicom_MHub_Helper_Data::STATUS_ERROR)->setMessage ($message)->save ();
    }

    public function setOrderId ($id)
    {
        $this->_orderId = $id;

        return $this;
    }

    public function run ()
    {
        if (!$this->getStoreConfig ('active') || !$this->getHelper ()->isMarketplace ())
        {
            return false;
        }

        $result = $this->readMHubOrdersMagento ();
        if (!$result) return false;

        $collection = $this->readMHubOrdersCollection ();
        $length = $collection->count ();
        if (!$length) return false;

        $this->updateOrders ($collection);

        return true;
    }
}

