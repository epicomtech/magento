<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_NotificationController extends Epicom_MHub_Controller_Action
{
    public function indexAction ()
    {
        $rawData = $this->getRequest ()->getRawBody ();
        if (empty ($rawData)) die (__('Data not specified'));

        $jsonData = json_decode ($rawData, true);

        $type       = $jsonData ['tipo'];
        $sendDate   = $jsonData ['dataEnvio'];
        $parameters = $jsonData ['parametros'];

        $result = null;

        switch ($type)
        {
            case Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_SKU:
            case Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_PRICE:
            case Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_STOCK:
            case Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_AVAILABILITY:
            case Epicom_MHub_Helper_Data::API_PRODUCT_ASSOCIATED_SKU:
            case Epicom_MHub_Helper_Data::API_PRODUCT_DISASSOCIATED_SKU:
            {
                $result = Mage::getModel ('mhub/product_api')->manage ($type, $sendDate, $parameters);

                break;
            }
            case Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_CREATED:
            {
                $result = Mage::getModel ('mhub/shipment_api')->manage ($type, $sendDate, $parameters);

                break;
            }
            default:
            {
                Mage::throwException (__('Invalid notification type'));
            }
        }

        $this->getResponse ()->setBody ($result);
    }
}

