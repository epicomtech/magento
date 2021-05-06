<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cron_Order_Status extends Epicom_MHub_Model_Cron_Abstract
{
    const ORDERS_CONFIRMATION_POST_METHOD = 'pedidos/{orderId}';

    private $_confirmFilter = null;
    private $_erpFilter     = null;
    private $_cancelFilter  = null;
    private $_sentFilter    = null;

    public function _construct ()
    {
        $this->_confirmFilter = Mage::getStoreConfig ('mhub/order/confirm_filter');
        $this->_erpFilter     = Mage::getStoreConfig ('mhub/order/erp_filter');
        $this->_cancelFilter  = Mage::getStoreConfig ('mhub/order/cancel_filter');
        $this->_sentFilter    = Mage::getStoreConfig ('mhub/order/sent_filter');
    }

    private function readMHubOrdersStatusesMagento ($scopeId = null)
    {
        $storeId = $this->getStoreConfig ('store_view', $scopeId);

        $collection = Mage::getModel ('sales/order')->getCollection ()
            ->addFieldToFilter ('main_table.store_id', array ('eq' => $storeId))
            ->addAttributeToFilter ('main_table.status', array ('in' => array (
                $this->_confirmFilter, $this->_erpFilter, $this->_cancelFilter, $this->_sentFilter,
            )))
            ->addAttributeToFilter (Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_EXT_ORDER_ID, array ('notnull' => true))
            ->addAttributeToFilter (Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_IS_EPICOM,    array ('notnull' => true))
        ;

        $collection->getSelect ()
            ->joinLeft(
                array ('mhub' => Epicom_MHub_Helper_Data::ORDER_STATUS_TABLE),
                'main_table.entity_id = mhub.order_id',
                array ('mhub_updated_at' => 'mhub.updated_at', 'mhub_synced_at' => 'mhub.synced_at')
            )->where ('main_table.created_at > mhub.synced_at OR mhub.synced_at IS NULL');

        foreach($collection as $order)
        {
            $orderId = $order->getId();

            $websiteId = Mage::app ()->getStore ($order->getStoreId ())->getWebsiteId ();

            $mhubOrder = Mage::getModel ('mhub/order_status')->load ($orderId, 'order_id');
            $mhubOrder->setOrderId ($orderId)
                ->setWebsiteId ($websiteId)
                ->setStoreId ($order->getStoreId ())
                ->setScopeId ($scopeId)
                ->setOrderIncrementId ($order->getIncrementId())
                ->setOrderExternalId ($order->getExtOrderId())
                ->setUpdatedAt (date ('c'))
                ->setOperation (Epicom_MHub_Helper_Data::OPERATION_OUT)
                ->setStatus (Epicom_MHub_Helper_Data::STATUS_PENDING)
                ->setMessage (new Zend_Db_Expr ('NULL'))
                ->save ();
        }

        return true;
    }

    private function readMHubOrdersStatusesCollection ($scopeId = null)
    {
        $storeId = $this->getStoreConfig ('store_view', $scopeId);

        $collection = Mage::getModel ('mhub/order_status')->getCollection ()
            ->addFieldToFilter ('store_id', array ('eq' => $storeId))
        ;

        $collection->getSelect ()
            ->where ('synced_at < updated_at OR synced_at IS NULL')
               // ->group ('order_id')
               // ->order ('updated_at DESC')
        ;

        return $collection;
    }

    private function updateOrdersStatuses ($collection)
    {
        foreach ($collection as $order)
        {
            try
            {
                $result = $this->updateMHubOrderStatus ($order);
            }
            catch (Exception $e)
            {
                $this->logMHubOrderStatus ($order, $e->getMessage ());

                self::logException ($e);
            }

            if (!empty ($result)) $this->cleanupMHubOrderStatus ($order);
        }

        return true;
    }

    private function updateMHubOrderStatus (Epicom_MHub_Model_Order_Status $order_status)
    {
        $orderId = $order_status->getOrderId ();

        $mageOrder = Mage::getModel ('sales/order')->load ($orderId);

        if (!$mageOrder || !$mageOrder->getId ())
        {
            return false;
        }

        $billingAddress  = Mage::getModel('sales/order_address')->load($mageOrder->getBillingAddressId ());
        $shippingAddress = Mage::getModel('sales/order_address')->load($mageOrder->getShippingAddressId ());

		$rawPostcode = $billingAddress->getPostcode();
		$postCode    = Mage::helper ('mhub')->validatePostcode ($rawPostcode);

        $post = array(
            'status'   => !strcmp ($mageOrder->getStatus (), $this->_cancelFilter) ? 'Cancelado' : 'Confirmado',
            'endereco' => array(
                'bairro'      => $billingAddress->getStreet4(),
                'cep'         => $postCode,
                'cidade'      => $billingAddress->getCity(),
                'complemento' => $billingAddress->getStreet3(),
                'estado'      => $this->getRegionName ($billingAddress->getRegionId(), $billingAddress->getCountryId()),
                'logradouro'  => $billingAddress->getStreet1(),
                'numero'      => $billingAddress->getStreet2(),
                'telefone'    => $billingAddress->getTelephone(),
                'referencia'  => null,
            ),
            'formaEntrega' => $mageOrder->getShippingDescription(),
            'cpfCnpj'      => preg_replace ('[\D]', "", $mageOrder->getCustomerTaxvat()),
            'destinatario' => array(
                'cpfCnpj'           => preg_replace ('[\D]', "", $mageOrder->getCustomerTaxvat()),
                'inscricaoEstadual' => null,
                'nome'              => sprintf ("%s %s", $shippingAddress->getFirstname(), $shippingAddress->getLastname()),
                'email'             => $mageOrder->getCustomerEmail(),
                'telefone'          => $shippingAddress->getTelephone(),
            ),
            'telefone'               => $billingAddress->getTelephone(),
            'dataCancelamentoPedido' => !strcmp ($mageOrder->getStatus (), $this->_cancelFilter) ? $mageOrder->getUpdatedAt () : null,
            'erro'                   => null,
            'prazoEntrega'           => null,
        );

        $ordersPatchMethod = str_replace ('{orderId}', $order_status->getOrderExternalId (), self::ORDERS_CONFIRMATION_POST_METHOD);

        $result = $this->getHelper ()->api ($ordersPatchMethod, $post, 'PATCH', $order_status->getScopeId ());

        return true;
    }

    private function cleanupMHubOrderStatus (Epicom_MHub_Model_Order_Status $order_status)
    {
        $order_status->setSyncedAt (date ('c'))
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
            ->setMessage (new Zend_Db_Expr ('NULL'))
            ->save ();

        return true;
    }

    private function logMHubOrderStatus (Epicom_MHub_Model_Order_Status $order_status, $message = null)
    {
        $order_status->setStatus (Epicom_MHub_Helper_Data::STATUS_ERROR)->setMessage ($message)->save ();
    }

    public function run ()
    {
        $collection = Mage::getModel ('core/config_data')->getCollection ()
            ->addFieldToFilter ('path', array('eq' => Epicom_MHub_Helper_Data::XML_PATH_MHUB_SETTINGS_ACTIVE))
            ->addValueFilter(1)
        ;

        foreach ($collection as $config)
        {
            $scopeId = $config->getScopeId ();

            if (!$this->getHelper ()->isMarketplace ($scopeId))
            {
                continue;
            }

            $result = $this->readMHubOrdersStatusesMagento ($scopeId);

            if (!$result) continue;

            $collection = $this->readMHubOrdersStatusesCollection ($scopeId);

            if (!$collection->getSize ()) continue;

            $this->updateOrdersStatuses ($collection);
        }

        return true;
    }
}

