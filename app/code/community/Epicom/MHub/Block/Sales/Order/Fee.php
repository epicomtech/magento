<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2020 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Sales_Order_Fee extends Mage_Core_Block_Template
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
        if ((float) $this->getOrder ()->getBaseFeeAmount ())
        {
            $value = $this->getSource ()->getFeeAmount ();

            $this->getParentBlock ()->addTotal(
                new Varien_Object (array(
                    'code'   => 'fee',
                    'strong' => false,
                    'label'  => Mage::helper ('mhub')->__('Fee'),
                    'value'  => $value
                ))
            );
        }

        return $this;
    }
}

