<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2020 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Sales_Order_Discount extends Mage_Core_Block_Template
{
    public function getOrder ()
    {
        return $this->getParentBlock ()->getOrder ();
    }

    public function getSource ()
    {
        return $this->getParentBlock ()->getSource ();
    }

    public function initTotals ()
    {
        if ((float) $this->getOrder ()->getBaseDiscountAmount ())
        {
            $value = $this->getSource ()->getDiscountAmount ();

            $this->getParentBlock ()->addTotal(
                new Varien_Object (array(
                    'code'   => 'discount',
                    'strong' => false,
                    'label'  => Mage::helper ('mhub')->__('Discount'),
                    'value'  => $value
                ))
            );
        }

        return $this;
    }
}

