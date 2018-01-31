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

    private function readMHubOrdersMagento ()
    {
        $orderStatus = Mage::getStoreConfig ('mhub/order/reserve_filter');

        $collection = Mage::getModel ('sales/order')->getCollection ();

        if ($this->_orderId)
        {
            $collection->addAttributeToFilter ('entity_id', array ('eq' => $this->_orderId));
        }
        else
        {
            $collection->addAttributeToFilter ('main_table.status', array ('eq' => $orderStatus));
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

            $mhubOrder = Mage::getModel ('mhub/order')->load ($orderId, 'order_id');
            $mhubOrder->setOrderId ($orderId)
                ->setOrderIncrementId ($order->getIncrementId())
                ->setOrderExternalId ($order->getExtOrderId())
                ->setUpdatedAt (date ('c'))
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
                    throw new Exception (__('Order saving error: %s', $order->getOrderIncrementId ()));
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

        $billingAddress  = Mage::getModel('sales/order_address')->load($mageOrder->getBillingAddressId ());
        $shippingAddress = Mage::getModel('sales/order_address')->load($mageOrder->getShippingAddressId ());

        $post = array(
            'codigoPedido' => $mageOrder->getIncrementId(),
            'dataPedido'   => date ('c', strtotime ($mageOrder->getCreatedAt())),
            'valorTotal'   => $mageOrder->getBaseGrandTotal(),
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
                'cep'    => $billingAddress->getPostcode(),
                'cidade' => $billingAddress->getCity(),
                'complemento' => $billingAddress->getStreet3(),
                'estado'      => $this->getRegionName ($billingAddress->getRegionId(), $billingAddress->getCountryId()),
                'logradouro'  => $billingAddress->getStreet1(),
                'numero'      => $billingAddress->getStreet2(),
                'telefone'    => $billingAddress->getTelephone(),
                'referencia'  => null,
            ),
        );

        $productIdAttribute = Mage::getStoreConfig ('mhub/product/id');

        $mageOrderItems = Mage::getResourceModel ('sales/order_item_collection')
            ->setOrderFilter ($mageOrder)
            ->addFieldToFilter ($productIdAttribute, array ('notnull' => true))
        ;

        $productSkuAttribute = Mage::getStoreConfig ('mhub/product/sku');
        $itemsPos = $itemsCount = $mageOrderItems->count ();

        foreach ($mageOrderItems as $id => $item)
        {
            $productSku = $item->getData($productSkuAttribute);

            $post ['itens'][] = array(
                'id'           => $productSku,
                'quantidade'   => intval ($item->getQtyOrdered()),
                'valor'        => $item->getBasePrice(),
                'valorFrete'   => $itemsPos % $itemsCount == 0 ? $mageOrder->getBaseShippingAmount() : 0,
                'formaEntrega' => $itemsPos % $itemsCount == 0 ? $mageOrder->getShippingDescription() : 0,
                'prazoEntrega' => null,
            );

            -- $itemsPos;
        }

        $extOrderId = true;

        try
        {
            $result = $this->getHelper ()->api (self::ORDERS_POST_METHOD, $post);

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

