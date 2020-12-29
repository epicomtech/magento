<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2020 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Sales_Order_Total_Creditmemo_Fee
    extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
    public function collect (Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder ();

        if ($order->getFeeAmountInvoiced () > 0)
        {
            $feeAmountLeft     = $order->getFeeAmountInvoiced () - $order->getFeeAmountRefunded ();
            $basefeeAmountLeft = $order->getBaseFeeAmountInvoiced () - $order->getBaseFeeAmountRefunded ();

            if ($basefeeAmountLeft > 0)
            {
                $creditmemo->setGrandTotal ($creditmemo->getGrandTotal () + $feeAmountLeft);
                $creditmemo->setBaseGrandTotal ($creditmemo->getBaseGrandTotal () + $basefeeAmountLeft);
                $creditmemo->setFeeAmount ($feeAmountLeft);
                $creditmemo->setBaseFeeAmount ($basefeeAmountLeft);
            }
        }
        else
        {
            $feeAmount     = $order->getFeeAmountInvoiced ();
            $basefeeAmount = $order->getBaseFeeAmountInvoiced ();

            $creditmemo->setGrandTotal ($creditmemo->getGrandTotal () + $feeAmount);
            $creditmemo->setBaseGrandTotal ($creditmemo->getBaseGrandTotal () + $basefeeAmount);
            $creditmemo->setFeeAmount ($feeAmount);
            $creditmemo->setBaseFeeAmount ($basefeeAmount);
        }

        return $this;
    }
}

