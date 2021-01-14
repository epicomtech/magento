<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2020 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Sales_Quote_Address_Total_Discount
    extends Mage_SalesRule_Model_Quote_Discount
{
    protected $_code = 'discount';

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

        /**
         * Cart
         */
        if (!Mage::app ()->getStore ()->isAdmin ())
        {
            $address->setCouponCode ($quote->getCouponCode ());

            return Mage_SalesRule_Model_Quote_Discount::collect ($address);
        }

        if (Epicom_MHub_Model_Discount::canApply ($address))
        {
            $amount  = $quote->getDiscountAmount ();
            $discount = Epicom_MHub_Model_Discount::getDiscount ();
            $balance = $discount - $amount;

            $balance = Epicom_MHub_Model_Discount::getBalance ();

            if (!$balance) return $this;

            $address->setDiscountAmount ($balance);
            $address->setBaseDiscountAmount ($balance);

            $quote->setDiscountAmount ($balance);
            $quote->setBaseDiscountAmount ($balance);

            $address->setGrandTotal ($address->getGrandTotal () + $address->getDiscountAmount ());
            $address->setBaseGrandTotal ($address->getBaseGrandTotal () + $address->getBaseDiscountAmount ());
        }

        return $this;
    }

    public function fetch (Mage_Sales_Model_Quote_Address $address)
    {
        $amount = $address->getDiscountAmount ();

        $address->addTotal (array(
            'code'  => $this->getCode (),
            'title' => Mage::helper ('mhub')->__('Discount'),
            'value' => $amount
        ));

        return $this;
    }
}

