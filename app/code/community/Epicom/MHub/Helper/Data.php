<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Helper_Data extends Mage_Core_Helper_Abstract
{
    const ATTRIBUTE_GROUP_TABLE = 'epicom_mhub_attribute_group';
    const BRAND_TABLE           = 'epicom_mhub_brand';
    const CATEGORY_TABLE        = 'epicom_mhub_category';
    const PRODUCT_TABLE         = 'epicom_mhub_product';
    const ORDER_TABLE           = 'epicom_mhub_order';
    const ORDER_STATUS_TABLE    = 'epicom_mhub_order_status';
    const SHIPMENT_TABLE        = 'epicom_mhub_shipment';
    const NF_TABLE              = 'epicom_mhub_nf';
    const PROVIDER_TABLE        = 'epicom_mhub_provider';
    const QUOTE_TABLE           = 'epicom_mhub_quote';
    const ERROR_TABLE           = 'epicom_mhub_error';

    const CATEGORY_ATTRIBUTE_SET          = 'mhub_category_attributeset';
    const CATEGORY_ATTRIBUTE_ISACTIVE     = 'mhub_category_isactive';
    const CATEGORY_ATTRIBUTE_SENDPRODUCTS = 'mhub_category_sendproducts';

    const PRODUCT_ATTRIBUTE_ID            = 'mhub_product_id';
    const PRODUCT_ATTRIBUTE_SKU           = 'mhub_product_sku';
    const PRODUCT_ATTRIBUTE_CODE          = 'mhub_product_code';
    const PRODUCT_ATTRIBUTE_BRAND         = 'mhub_product_brand';
    const PRODUCT_ATTRIBUTE_EAN           = 'mhub_product_ean';
    const PRODUCT_ATTRIBUTE_URL           = 'mhub_product_url';
    const PRODUCT_ATTRIBUTE_HEIGHT        = 'mhub_product_height';
    const PRODUCT_ATTRIBUTE_WIDTH         = 'mhub_product_width';
    const PRODUCT_ATTRIBUTE_LENGTH        = 'mhub_product_length';
    const PRODUCT_ATTRIBUTE_SUMMARY       = 'mhub_product_summary';
    const PRODUCT_ATTRIBUTE_OFFER_TITLE   = 'mhub_product_offer_title';
    const PRODUCT_ATTRIBUTE_MANUFACTURER  = 'mhub_product_manufacturer';
    const PRODUCT_ATTRIBUTE_MODEL         = 'mhub_product_model';
    const PRODUCT_ATTRIBUTE_OUT_OF_LINE   = 'mhub_product_out_of_line';

    const PRODUCT_WEIGHT_GRAM = 'gram';
    const PRODUCT_WEIGHT_KILO = 'kilo';

    const ORDER_ATTRIBUTE_IS_EPICOM    = 'is_epicom';
    const ORDER_ATTRIBUTE_EXT_ORDER_ID = 'ext_order_id';

    const SHIPMENT_ATTRIBUTE_IS_EPICOM       = 'is_epicom';
    const SHIPMENT_ATTRIBUTE_EXT_SHIPMENT_ID = 'ext_shipment_id';

    const PRODUCT_FIXED_GROUP_NAME = 'Grupo fixo';

    const API_ENVIRONMENT_URL_SANDBOX    = 'https://sandboxmhubapi.epicom.com.br/v1/';
    const API_ENVIRONMENT_URL_PRODUCTION = 'https://mhubapi.epicom.com.br/v1/';

    const API_MODE_MARKETPLACE = 'marketplace';
    const API_MODE_PROVIDER    = 'fornecedor';

    const API_PRODUCT_UPDATED_SKU          = 'alteracao_sku';
    const API_PRODUCT_UPDATED_PRICE        = 'preco_alterado';
    const API_PRODUCT_UPDATED_STOCK        = 'estoque_alterado';
    const API_PRODUCT_UPDATED_AVAILABILITY = 'disponibilidade_alterada';
    const API_PRODUCT_ASSOCIATED_SKU       = 'sku_associado';
    const API_PRODUCT_DISASSOCIATED_SKU    = 'sku_desassociado';

    const API_SHIPMENT_EVENT_CREATED  = 'criacao_evento_entrega';

    const API_SHIPMENT_EVENT_NF        = 'nota_fiscal_emitida';
    const API_SHIPMENT_EVENT_SENT      = 'despachada';
    const API_SHIPMENT_EVENT_DELIVERED = 'entregue';
    const API_SHIPMENT_EVENT_FAILED    = 'nao_entregue';
    const API_SHIPMENT_EVENT_PARCIAL   = 'parcialmente_entregue';
    const API_SHIPMENT_EVENT_CANCELED  = 'cancelada';

    const QUEUE_LIMIT_30  = 30;
    const QUEUE_LIMIT_60  = 60;
    const QUEUE_LIMIT_90  = 90;
    const QUEUE_LIMIT_120 = 120;
    const QUEUE_LIMIT_150 = 150;
    const QUEUE_LIMIT_180 = 180;
    const QUEUE_LIMIT_210 = 210;
    const QUEUE_LIMIT_240 = 240;
    const QUEUE_LIMIT_270 = 270;
    const QUEUE_LIMIT_300 = 300;

    const OPERATION_IN   = 'in';
    const OPERATION_OUT  = 'out';
    const OPERATION_BOTH = 'both';

    const STATUS_PENDING = 'pending';
    const STATUS_OKAY    = 'okay';
    const STATUS_ERROR   = 'error';

    const LOG = 'epicom_mhub.log';

    public function api ($method, $post = null, $request = null)
    {
        $timeout = $this->getStoreConfig ('timeout');
        $url = $this->getStoreConfig ('url');
        $mode = $this->getStoreconfig ('mode');
        $key = $this->getStoreConfig ('key');
        $token = $this->getStoreConfig ('token');
        $ssl = intval ($this->getStoreConfig ('ssl'));

        $curl = curl_init ();

        $uniqid = md5 (uniqid (rand (), true));

        curl_setopt ($curl, CURLOPT_CONNECTTIMEOUT, 0); // indefinitely
        curl_setopt ($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt ($curl, CURLOPT_URL, $url . $mode . '/' . $method . '?ts=' . time ());
        curl_setopt ($curl, CURLOPT_USERPWD, "{$key}:{$token}");
        curl_setopt ($curl, CURLOPT_HTTPHEADER, array ('Content-Type: application/json', "X-Trace-Id: {$uniqid}", "Cache-Control: no-cache"));
        curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
        // SSL off?
        curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, $ssl);
        curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, $ssl ? 2 : 0);

        if ($post != null)
        {
            if (empty ($request)) $request = 'POST';

            curl_setopt ($curl, CURLOPT_POST, 1);
            curl_setopt ($curl, CURLOPT_POSTFIELDS, json_encode ($post));
        }

        if ($request != null)
        {
            curl_setopt ($curl, CURLOPT_CUSTOMREQUEST, $request);
        }

        curl_setopt ($curl, CURLOPT_FAILONERROR, true);

        $result = curl_exec ($curl);
        $response = json_decode ($result);
        $info = curl_getinfo ($curl);

        $message = null;

        switch ($httpCode = $info ['http_code'])
        {
            case 400: { $message = 'Invalid Request';      break; }
            case 401: { $message = 'Authentication Error'; break; }
            case 403: { $message = 'Permission Denied';    break; }
            case 404: { $message = 'Invalid URL';          break; }
            case 405: { $message = 'Method Not Allowed';   break; }
            case 409: { $message = 'Resource Exists';      break; }
            case 500: { $message = 'Internal Error';       break; }
            case 200: { $message = null; /* Success! */    break; }
        }

        if ($error = curl_error ($curl))
        {
            $message = $error;
        }

        if ($this->getStoreConfig ('debug'))
        {
            $text = implode (' : ' , array ($request, $method, json_encode ($post), $message, $result, $uniqid));

            Mage::log ($text, null, self::LOG, true);
        }

        if (!empty ($message))
        {
            $message = implode (' : ' , array ($request, $method, json_encode ($post), $message, $result, $uniqid));

            throw Mage::exception ('Epicom_MHub', $message, $httpCode);
        }

        curl_close ($curl);

        return $response;
    }

    public function formatShippingTime ($deliveryTime)
    {
        $plural = $deliveryTime > 1 ? 's' : chr (0);

        return chr (32) . $this->__("Delivered within %d day%s", $deliveryTime, $plural) . chr (32);
    }

    public function getConfig ()
    {
        return Mage::getModel ('mhub/config');
    }

    public function getEntityTypeId ($entityType)
    {
        return $this->getConfig()->getEntityTypeId ($entityType);
    }

    public function getStoreConfig ($key)
    {
        return Mage::getStoreConfig ("mhub/settings/{$key}");
    }

    public function isMarketplace ()
    {
        return !strcmp ($this->getStoreConfig ('mode'), self::API_MODE_MARKETPLACE);
    }

    public function isWebhook ()
    {
        $appRequest = Mage::app ()->getRequest ();
        if (!strcmp ($appRequest->getControllerModule (), 'Epicom_MHub')
            && in_array ($appRequest->getControllerName (), array ('provider', 'marketplace')))
        {
            return true;
        }
    }

    public function validatePostcode ($rawPostcode)
    {
        $postCode = preg_replace ('[\D]', '', $rawPostcode);
        if (empty ($postCode) || strlen ($postCode) != 8 || !is_numeric ($postCode))
        {
            // Mage::throwException (Mage::helper ('mhub')->__('Please enter a valid ZIP code.'));

            return false;
        }

        return $postCode;
    }
}

