<?php
/*
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Adminhtml_ProviderController extends Mage_Adminhtml_Controller_Action
{
	protected function _isAllowed ()
	{
	    return Mage::getSingleton ('admin/session')->isAllowed ('epicom/mhub/provider');
	}

	protected function _initAction ()
	{
		$this->loadLayout ()->_setActiveMenu ('epicom/mhub/provider')
            ->_addBreadcrumb (Mage::helper ('mhub')->__('Providers Manager'), Mage::helper ('mhub')->__('Providers Manager'))
        ;

		return $this;
	}

	public function indexAction ()
	{
	    $this->_title ($this->__('MHub'));
	    $this->_title ($this->__('Providers Manager'));

		$this->_initAction ();

		$this->renderLayout ();
	}

	public function editAction ()
	{			    
	    $this->_title ($this->__('MHub'));
		$this->_title ($this->__('Provider'));
	    $this->_title ($this->__('Edit Provider'));

		$id    = $this->getRequest()->getParam ('id');
		$model = Mage::getModel ('mhub/provider')->load ($id);
		if ($model->getId ())
        {
			Mage::register ('provider_data', $model);

			$this->loadLayout ();

			$this->_setActiveMenu ('epicom/mhub/provider');
			$this->_addBreadcrumb (Mage::helper('mhub')->__('Providers Manager'), Mage::helper ('mhub')->__('Providers Manager'));
			$this->_addBreadcrumb (Mage::helper('mhub')->__('Provider Description'), Mage::helper ('mhub')->__('Provider Description'));
			$this->getLayout ()->getBlock ('head')->setCanLoadExtJs (true);
			$this->_addContent ($this->getLayout ()->createBlock ('mhub/adminhtml_provider_edit'));
            $this->_addLeft ($this->getLayout()->createBlock ('mhub/adminhtml_provider_edit_tabs'));

			$this->renderLayout();
		} 
		else
        {
			Mage::getSingleton ('adminhtml/session')->addError (Mage::helper ('mhub')->__('Provider does not exist.'));

			$this->_redirect ('*/*/');
		}
	}

	public function newAction ()
	{
	    $this->_title ($this->__('MHub'));
	    $this->_title ($this->__('Provider'));
	    $this->_title ($this->__('New Provider'));

        $id    = $this->getRequest ()->getParam ('id');
	    $model = Mage::getModel ('mhub/provider')->load ($id);

	    $providerData = Mage::getSingleton ('adminhtml/session')->getProviderData (true);
	    if (!empty ($providerData))
        {
		    $model->setData ($providerData);
	    }

	    Mage::register ('provider_data', $model);

	    $this->loadLayout ();

	    $this->_setActiveMenu ('epicom/mhub/provider');
	    $this->_addBreadcrumb (Mage::helper ('mhub')->__('Providers Manager'), Mage::helper ('mhub')->__('Providers Manager'));
	    $this->_addBreadcrumb (Mage::helper ('mhub')->__('Provider Description'), Mage::helper ('mhub')->__('Provider Description'));
	    $this->getLayout ()->getBlock ('head')->setCanLoadExtJs (true);
	    $this->_addContent ($this->getLayout ()->createBlock ('mhub/adminhtml_provider_edit'));
        $this->_addLeft ($this->getLayout ()->createBlock ('mhub/adminhtml_provider_edit_tabs'));

	    $this->renderLayout ();
	}

	public function saveAction()
	{
		$postData = $this->getRequest ()->getPost ();
		if ($postData)
        {
			try
            {
                $id = $this->getRequest ()->getParam ('id');

                $postData = $this->_filterDates ($postData, array ('issued_at'));

				$model = Mage::getModel ('mhub/provider')
				    ->addData ($postData)
				    ->setId ($id)
                    ->setUpdatedAt (date ('c'))
				    ->save ()
                ;

                if ($id)
                {
                    $model->setOperation (Epicom_MHub_Helper_Data::OPERATION_OUT)
                        ->setStatus (Epicom_MHub_Helper_Data::STATUS_PENDING)
                        ->save ()
                    ;
                }

				Mage::getSingleton ('adminhtml/session')->addSuccess (Mage::helper ('mhub')->__('Provider was successfully saved'));
				Mage::getSingleton ('adminhtml/session')->setProviderData (false);

				if ($this->getRequest()->getParam ('back'))
                {
					$this->_redirect ('*/*/edit', array ('id' => $model->getId ()));

					return $this;
				}

				$this->_redirect ('*/*/');

				return $this;
			} 
			catch (Exception $e)
            {
				Mage::getSingleton ('adminhtml/session')->addError ($e->getMessage ());
				Mage::getSingleton ('adminhtml/session')->setProviderData ($this->getRequest ()->getPost ());

				$this->_redirect ('*/*/edit', array ('id' => $this->getRequest ()->getParam ('id')));

			    return $this;
			}
		}

		$this->_redirect ('*/*/');
	}

	public function deleteAction ()
	{
		if ($this->getRequest ()->getParam ('id') > 0 )
        {
			try
            {
				$model = Mage::getModel('mhub/provider');
				$model->setId ($this->getRequest ()->getParam ('id'))->delete ();

				Mage::getSingleton ('adminhtml/session')->addSuccess (Mage::helper ('mhub')->__('Provider was successfully deleted'));

				$this->_redirect ('*/*/');
			}
			catch (Exception $e)
            {
				Mage::getSingleton ('adminhtml/session')->addError ($e->getMessage ());

				$this->_redirect ('*/*/edit', array ('id' => $this->getRequest ()->getParam ('id')));
			}
		}

		$this->_redirect ('*/*/');
	}

    /**
     * Export customer grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName   = 'providers.csv';
        $content    = $this->getLayout()
            ->createBlock('mhub/adminhtml_provider_grid')
            ->getCsvFile()
        ;

        $this->_prepareDownloadResponse ($fileName, $content);
    }

	public function massRemoveAction ()
	{
		try
        {
			$ids = $this->getRequest ()->getPost ('entity_ids', array ());
			foreach ($ids as $id)
            {
                $model = Mage::getModel('mhub/provider');
                $model->setId ($id)->delete ();
			}

			Mage::getSingleton ('adminhtml/session')->addSuccess (Mage::helper ('mhub')->__('Provider(s) was successfully removed'));
		}
		catch (Exception $e)
        {
			Mage::getSingleton ('adminhtml/session')->addError ($e->getMessage ());
		}

		$this->_redirect('*/*/');
	}
}

