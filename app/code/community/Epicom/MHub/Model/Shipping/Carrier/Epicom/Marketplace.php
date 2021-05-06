<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Shipping_Carrier_Epicom_Marketplace
    extends Epicom_MHub_Model_Shipping_Carrier_Epicom
    implements Mage_Shipping_Model_Carrier_Interface
{
    const CODE   = 'epicom_marketplace';
    const METHOD = 'shipping_method';

    protected $_code   = self::CODE;
    protected $_method = self::METHOD;

    protected $_bestPrices = null;

    public function _construct ()
    {
        $this->_bestPrices = Mage::getStoreConfigFlag ('mhub/cart/best_price');
    }

	public function collectRates (Mage_Shipping_Model_Rate_Request $request)
	{
		if (!$this->getConfigFlag ('active'))
        {
            return false;
        }

		$rawPostcode = $request->getDestPostcode ();

		$postcode = Mage::helper ('mhub')->validatePostcode ($rawPostcode);

		if (empty ($postcode)) return false;

        $collection = Mage::getModel ('mhub/quote')->getCollection ()
            ->addFieldToFilter ('store_id', array ('eq' => $request->getStoreId ()))
            ->addFieldToFilter ('quote_id', array ('eq' => $request->getQuoteId ()))
            ->addFieldToFilter ('postcode', array ('eq' => $postcode))
        ;

        $direction = $this->_bestPrices ? 'ASC' : 'DESC';

        $collection->getSelect ()
            ->group ('sku')
            ->order (sprintf ("price %s", $direction))
            ->columns (array(
                'entity_id',
                'price',
                'days',
            ))
        ;

        if (!$collection->getSize ())
        {
            return false;
        }

        $deliveryPrice = 0;
        $deliveryDays  = 0;

        foreach ($collection as $quote)
        {
            $deliveryPrice += $quote->getPrice ();

            if ($quote->getDays () > $deliveryDays)
            {
                $deliveryDays = $quote->getDays ();
            }
        }

        $deliveryTitle = $this->getConfigData ('title');
        $deliveryDays  = Mage::helper ('mhub')->__('%s days', $deliveryDays);

		$result = Mage::getModel ('shipping/rate_result');

        $method = Mage::getModel ('shipping/rate_result_method')
            ->setCarrier ($this->_code)
            ->setCarrierTitle ($deliveryTitle)
            ->setMethod ($this->_method)
            ->setMethodTitle ($deliveryDays)
            ->setPrice ($deliveryPrice)
            ->setCost (0)
        ;

        $result->append ($method);

        return $result;
	}
}

