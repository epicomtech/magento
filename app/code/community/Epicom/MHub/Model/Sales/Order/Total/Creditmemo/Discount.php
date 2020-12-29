<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2020 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Sales_Order_Total_Creditmemo_Discount
    extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
    public function collect (Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder ();

        if ($order->getDiscountAmountInvoiced () > 0)
        {
            $discountAmountLeft     = $order->getDiscountAmountInvoiced () - $order->getDiscountAmountRefunded ();
            $basediscountAmountLeft = $order->getBaseDiscountAmountInvoiced () - $order->getBaseDiscountAmountRefunded ();

            if ($basediscountAmountLeft > 0)
            {
                $creditmemo->setGrandTotal ($creditmemo->getGrandTotal () + $discountAmountLeft);
                $creditmemo->setBaseGrandTotal ($creditmemo->getBaseGrandTotal () + $basediscountAmountLeft);
                $creditmemo->setDiscountAmount ($discountAmountLeft);
                $creditmemo->setBaseDiscountAmount ($basediscountAmountLeft);
            }
        }
        else
        {
            $discountAmount     = $order->getDiscountAmountInvoiced ();
            $basediscountAmount = $order->getBaseDiscountAmountInvoiced ();

            $creditmemo->setGrandTotal ($creditmemo->getGrandTotal () + $discountAmount);
            $creditmemo->setBaseGrandTotal ($creditmemo->getBaseGrandTotal () + $basediscountAmount);
            $creditmemo->setDiscountAmount ($discountAmount);
            $creditmemo->setBaseDiscountAmount ($basediscountAmount);
        }

        return $this;
    }
}

