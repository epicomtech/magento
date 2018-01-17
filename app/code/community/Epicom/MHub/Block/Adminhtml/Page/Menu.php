<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

/**
 * Adminhtml menu block
 */
class Epicom_MHub_Block_Adminhtml_Page_Menu extends Mage_Adminhtml_Block_Page_Menu
{
    /**
     * Check Depends
     *
     * @param Varien_Simplexml_Element $depends
     * @return bool
     */
    protected function _checkDepends (Varien_Simplexml_Element $depends)
    {
        if ($depends->helper)
        {
            foreach ($depends->helper as $helper)
            {
                $method = $helper->getAttribute ('method');
                $active = $helper->getAttribute ('active');

                $instance = Mage::helper ($helper);
                if ($instance instanceof Mage_Core_Helper_Abstract)
                {
                    return $instance->{$method} () == boolval ($active);
                }
            }
        }

        return parent::_checkDepends ($depends);
    }
}

