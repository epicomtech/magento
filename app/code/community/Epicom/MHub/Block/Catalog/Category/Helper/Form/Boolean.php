<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Catalog_Category_Helper_Form_Boolean extends Varien_Data_Form_Element_Select
{
    public function getAfterElementHtml ()
    {
        $html = parent::getAfterElementHtml ();

$afterHtml = <<< AFTER_HTML
<script>
    element = $('{$this->getHtmlId ()}');

    $$('select').find (function (attributeSetElement){
        if ($(attributeSetElement).name == 'general[mhub_category_attributeset]')
        {
            if (!$(attributeSetElement).value && element.value == '0')
            {
                element.value = 1;
            }
        }
    });
</script>
AFTER_HTML;

        return $html . $afterHtml;
    }
}

