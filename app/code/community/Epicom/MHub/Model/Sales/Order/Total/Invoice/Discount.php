<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2020 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Sales_Order_Total_Invoice_Discount
    extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    public function collect (Mage_Sales_Model_Order_Invoice $invoice)
    {
        $order = $invoice->getOrder ();

        $discountAmountLeft     = $order->getDiscountAmount () - $order->getDiscountAmountInvoiced ();
        $baseDiscountAmountLeft = $order->getBaseDiscountAmount () - $order->getBaseDiscountAmountInvoiced ();

        if (abs ($baseDiscountAmountLeft) < $invoice->getBaseGrandTotal ())
        {
            $invoice->setGrandTotal ($invoice->getGrandTotal () + $discountAmountLeft);
            $invoice->setBaseGrandTotal ($invoice->getBaseGrandTotal () + $baseDiscountAmountLeft);
        }
        else
        {
            $discountAmountLeft     = $invoice->getGrandTotal () * -1;
            $baseDiscountAmountLeft = $invoice->getBaseGrandTotal () * -1;

            $invoice->setGrandTotal (0);
            $invoice->setBaseGrandTotal (0);
        }

        $invoice->setDiscountAmount ($discountAmountLeft);
        $invoice->setBaseDiscountAmount ($baseDiscountAmountLeft);

        return $this;
    }
}

