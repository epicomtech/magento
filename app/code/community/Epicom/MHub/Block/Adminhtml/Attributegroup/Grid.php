<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Attributegroup_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('attributeGroupsGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('mhub/attributegroup')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn ('entity_id', array(
            'header' => Mage::helper ('mhub')->__('ID'),
            'align'  => 'right',
            'width'  => '50px',
            'type'   => 'number',
            'index'  => 'entity_id',
        ));

        $entityType = Mage::getModel ('catalog/product')->getResource ()->getEntityType ();

        $this->addColumn ('attribute_set_id', array(
            'header'  => Mage::helper('mhub')->__('Attribute Set'),
            'index'   => 'attribute_set_id',
            'type'    => 'options',
		    'options' => Mage::getResourceModel ('eav/entity_attribute_set_collection')
                ->setEntityTypeFilter ($entityType->getId ())
                ->load ()
                ->toOptionHash (),
        ));

        $this->addColumn ('group_name', array(
            'header' => Mage::helper ('mhub')->__('Group Name'),
            'index'  => 'group_name',
        ));

        $this->addColumn ('attribute_codes', array(
            'header' => Mage::helper('mhub')->__('Attribute Codes'),
            'index'  => 'attribute_codes',
        ));

        $this->addColumn ('action', array(
            'header'  => Mage::helper ('mhub')->__('Action'),
            'width'   => '50px',
            'type'    => 'action',
            'getter'  => 'getId',
            'filter'   => false,
            'sortable' => false,
            'index'    => 'stores',
            'actions' => array(
                array(
                    'caption' => Mage::helper ('mhub')->__('Edit'),
                    'url'     => array(
                        'base'   => 'admin_mhub/adminhtml_attributegroup/edit',
                        'params' => array ('store' => $this->getRequest ()->getParam ('store'), 'clear' => true)
                    ),
                    'field'   => 'id'
                )
            ),
        ));

        return parent::_prepareColumns ();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField ('entity_id');
        $this->getMassactionBlock ()->setFormFieldName ('entity_ids');
        $this->getMassactionBlock ()->setUseSelectAll (true);
        $this->getMassactionBlock ()->addItem ('remove_attributegroups', array(
            'label'   => Mage::helper ('mhub')->__('Remove Attribute Group(s)'),
            'url'     => $this->getUrl ('admin_mhub/adminhtml_attributegroup/massRemove'),
            'confirm' => Mage::helper ('mhub')->__('Are you sure?')
        ));

        return $this;
    }

    public function getRowUrl ($row)
    {
        // nothing
    }
}

