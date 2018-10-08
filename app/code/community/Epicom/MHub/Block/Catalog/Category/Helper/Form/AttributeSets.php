<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Catalog_Category_Helper_Form_AttributeSets extends Epicom_MHub_Block_Catalog_Category_Helper_Form_Boolean
{
    public function getAfterElementHtml ()
    {
        $html = parent::getAfterElementHtml ();

        /* if (Mage::helper ('mhub')->isMarketplace ()) */ return $html;

$afterHtml = <<< AFTER_HTML
<script>
    element = $('{$this->getHtmlId ()}');

    element.parentElement.parentElement.hide ();
</script>
AFTER_HTML;

        return $html . $afterHtml;
    }
}

