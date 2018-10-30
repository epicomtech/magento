<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Shipping_Carrier_Epicom extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    const CODE = 'epicom';

    protected $_code = self::CODE;

	public function collectRates (Mage_Shipping_Model_Rate_Request $request)
	{
		if (!$this->getConfigFlag ('active')) return false;

		$result = Mage::getModel ('shipping/rate_result');

		$rawPostcode = $request->getDestPostcode ();
		$postCode = Mage::helper ('mhub')->validatePostcode ($rawPostcode);
		if (empty ($postCode)) return $result;

        /**
         * via Order API
         */
        $jsonData = null;

        $appRequest = Mage::app ()->getRequest ();
        if (Mage::helper ('mhub')->isWebhook ())
        {
            $jsonData = json_decode ($appRequest->getRawBody (), true);
        }

        if (is_array ($jsonData) && !strcmp ($appRequest->getActionName (), 'criado'))
        {
            $totalFreight = 0;

            foreach ($jsonData ['itens'] as $item)
            {
                $totalFreight += floatval ($item ['precoFrete']);
            }

            $method = Mage::getModel ('shipping/rate_result_method')
                ->setCarrier ($this->_code)
                ->setCarrierTitle ($this->getConfigData ('title'))
                ->setMethod ($this->_code . '_provider')
                ->setMethodTitle (Mage::helper ('mhub')->__('Provider'))
                ->setPrice ($totalFreight)
                ->setCost (0)
            ;

            $result->append ($method);

            return $result;
        }

        if (!Mage::helper ('mhub')->isMarketplace ()) return false;

        /**
         * via Magento Cart
         */
        $unique = true;

        if (is_array ($jsonData) && !strcmp ($appRequest->getActionName (), 'calcularCarrinho'))
        {
            $unique = $jsonData ['entregaUnica'];
        }

        try
        {
		    $shipping = Mage::getModel ('mhub/config')->getShippingPrices ($postCode, $unique);
        }
        catch (Exception $e)
        {
            $error = $this->_getError ($e->getMessage ());

            // $result->append ($error);

            return $error;
        }

		if (is_array ($shipping) && count ($shipping) > 0)
		{
            foreach ($shipping as $item)
            {
                if (strcmp ($item->status, 'ok'))
                {
                    $ids = array ();

                    foreach ($item->itens as $_item) $ids [] = $_item->id;

                    $error = $this->_getError (sprintf ('%s: %s', $item->status, implode (',', $ids)));

                    // $result->append ($error);

                    return $error;
                }

                if (is_array ($item->fretes) && count ($item->fretes) > 0)
                {
                    foreach ($item->fretes as $freight)
                    {
			            $carrier  = $freight->transportadora;
			            $modality = $freight->formaEntrega;
                        $time     = $freight->diasParaEntrega;
                        $price    = $freight->valorTotalFrete;

                        $formatedTime = Mage::helper ('mhub')->formatShippingTime ($time);

			            $method = Mage::getModel ('shipping/rate_result_method')
		                    ->setCarrier ($this->_code)
			                ->setCarrierTitle ($this->getConfigData ('title'))
			                ->setMethod ($this->_code . '_' . preg_replace ('[\W]', "", $modality))
			                ->setMethodTitle ($modality . ' - ' . $formatedTime)
			                ->setPrice ($price)
			                ->setCost (0)
                        ;

			            $result->append ($method);
                    }
                }
            }
		}

		return $result;
	}

	public function getAllowedMethods ()
	{
		return array ($this->_code => $this->getConfigData ('name'));
	}

	public function isTrackingAvailable ()
	{
		return true;
	}

	public function getTrackingInfo ($tracking)
	{
	    $result = $this->getTracking ($tracking);

        if ($result instanceof Mage_Shipping_Model_Tracking_Result)
        {
            if ($trackings = $result->getAllTrackings ())
            {
                return $trackings [0];
            }
        }
        elseif (!empty ($result) && is_string ($result))
        {
            return $result;
        }

	    return false;
	}

	public function getTracking ($trackings)
	{
	    $this->_result = Mage::getModel ('shipping/tracking_result');

        foreach ((array) $trackings as $code)
        {
            $this->_getTracking ($code);
        }

	    return $this->_result;
	}

	protected function _getTracking ($code)
	{
		$tracking = Mage::getModel ('shipping/tracking_result_status');
		$tracking->setTracking ($code);
		$tracking->setCarrier ($this->getConfigData ('name'));
		$tracking->setCarrierTitle ($this->getConfigData ('title'));

        $hash = Mage::app ()->getRequest ()->getParam ('hash');
        $data = Mage::helper ('shipping')->decodeTrackingHash ($hash);

        $description = Mage::helper ('mhub')->__('No tracking information available.');

        if (!empty ($data) && is_array ($data))
        {
            $collection = Mage::getModel ('sales/order_shipment_track')->getCollection ()
                ->addAttributeToFilter ('entity_id',    array ('eq' => $data ['id']))
                ->addAttributeToFilter ('track_number', array ('eq' => $code))
            ;

            $description = $collection->getFirstItem ()->getDescription ();

            $url = parse_url ($description);
            if (!empty ($url ['host']))
            {
                try
                {
                    $client = new Zend_Http_Client ();
                    $client->setUri ($description);

                    $response = $client->request ('GET');

                    $description = $response->getBody ();
                }
                catch (Exception $e)
                {
                    // nothing
                }
            }
		}

		$track ['status'] = $description;

		$tracking->addData ($track);

		$this->_result->append ($tracking);

		return true;
	}

    public function _getError ($message)
    {
        $erromsg = $this->getConfigData ('specificerrmsg');

        $error = Mage::getModel ('shipping/rate_result_error')
            ->setCarrier ($this->_code)
            ->setCarrierTitle ($this->getConfigData ('title'))
            ->setErrorMessage ($errmsg ? $errmsg : $message)
        ;

        return $error;
    }
}

