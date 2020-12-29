<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2020 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Sales_Quote_Address_Total_Fee
    extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    protected $_code = 'fee';

    public function collect (Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect ($address);

        $this->_setAmount (0);
        $this->_setBaseAmount (0);

        $items = $this->_getAddressItems ($address);

        if (!count ($items))
        {
            return $this;
        }

        $quote = $address->getQuote ();

        if (Epicom_MHub_Model_Fee::canApply ($address))
        {
            $amount  = $quote->getFeeAmount ();
            $fee     = Epicom_MHub_Model_Fee::getFee ();
            $balance = $fee - $amount;

            $balance = Epicom_MHub_Model_Fee::getBalance ();

            $address->setFeeAmount ($balance);
            $address->setBaseFeeAmount ($balance);

            $quote->setFeeAmount ($balance);
            $quote->setBaseFeeAmount ($balance);

            $address->setGrandTotal ($address->getGrandTotal () + $address->getFeeAmount ());
            $address->setBaseGrandTotal ($address->getBaseGrandTotal () + $address->getBaseFeeAmount ());
        }

        return $this;
    }

    public function fetch (Mage_Sales_Model_Quote_Address $address)
    {
        $amount = $address->getFeeAmount ();

        $address->addTotal (array(
            'code'  => $this->getCode (),
            'title' => Mage::helper ('mhub')->__('Fee'),
            'value' => $amount
        ));

        return $this;
    }
}

