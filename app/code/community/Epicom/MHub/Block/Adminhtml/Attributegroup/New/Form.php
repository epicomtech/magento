<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Attributegroup_New_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm ()
    {
        $form = new Varien_Data_Form (array(
            'id'      => 'new_form',
            'action'  => $this->getUrl ('*/*/edit', array ('id' => $this->getRequest ()->getParam ('id'))),
            'method'  => 'post',
            'enctype' => 'multipart/form-data'
        ));

        $form->setUseContainer (true);

        $this->setForm ($form);

        return parent::_prepareForm ();
    }
}

