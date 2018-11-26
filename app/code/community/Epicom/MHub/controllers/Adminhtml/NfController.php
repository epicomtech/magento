<?php
/*
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Adminhtml_NfController extends Mage_Adminhtml_Controller_Action
{
	protected function _isAllowed ()
	{
	    return Mage::getSingleton ('admin/session')->isAllowed ('epicom/mhub/nf');
	}

	protected function _initAction ()
	{
		$this->loadLayout ()->_setActiveMenu ('epicom/mhub/nf')
            ->_addBreadcrumb (Mage::helper ('mhub')->__('NF Manager'), Mage::helper ('mhub')->__('NF Manager'))
        ;

		return $this;
	}

	public function indexAction ()
	{
	    $this->_title ($this->__('MHub'));
	    $this->_title ($this->__('NF Manager'));

		$this->_initAction ();

		$this->renderLayout ();
	}

	public function editAction ()
	{			    
	    $this->_title ($this->__('MHub'));
		$this->_title ($this->__('NF'));
	    $this->_title ($this->__('Edit NF'));

		$id    = $this->getRequest()->getParam ('id');
		$model = Mage::getModel ('mhub/nf')->load ($id);
		if ($model->getId ())
        {
			Mage::register ('nf_data', $model);

			$this->loadLayout ();

			$this->_setActiveMenu ('epicom/mhub/nf');
			$this->_addBreadcrumb (Mage::helper('mhub')->__('NF Manager'), Mage::helper ('mhub')->__('NF Manager'));
			$this->_addBreadcrumb (Mage::helper('mhub')->__('NF Description'), Mage::helper ('mhub')->__('NF Description'));
			$this->getLayout ()->getBlock ('head')->setCanLoadExtJs (true);
			$this->_addContent ($this->getLayout ()->createBlock ('mhub/adminhtml_nf_edit'));
            $this->_addLeft ($this->getLayout()->createBlock ('mhub/adminhtml_nf_edit_tabs'));

			$this->renderLayout();
		} 
		else
        {
			Mage::getSingleton ('adminhtml/session')->addError (Mage::helper ('mhub')->__('NF does not exist.'));

			$this->_redirect ('*/*/');
		}
	}

	public function newAction ()
	{
	    $this->_title ($this->__('MHub'));
	    $this->_title ($this->__('NF'));
	    $this->_title ($this->__('New NF'));

        $id    = $this->getRequest ()->getParam ('id');
	    $model = Mage::getModel ('mhub/nf')->load ($id);

	    $nfData = Mage::getSingleton ('adminhtml/session')->getNFData (true);
	    if (!empty ($nfData))
        {
		    $model->setData ($nfData);
	    }

	    Mage::register ('nf_data', $model);

	    $this->loadLayout ();

	    $this->_setActiveMenu ('epicom/mhub/nf');
	    $this->_addBreadcrumb (Mage::helper ('mhub')->__('NF Manager'), Mage::helper ('mhub')->__('NF Manager'));
	    $this->_addBreadcrumb (Mage::helper ('mhub')->__('NF Description'), Mage::helper ('mhub')->__('NF Description'));
	    $this->getLayout ()->getBlock ('head')->setCanLoadExtJs (true);
	    $this->_addContent ($this->getLayout ()->createBlock ('mhub/adminhtml_nf_edit'));
        $this->_addLeft ($this->getLayout ()->createBlock ('mhub/adminhtml_nf_edit_tabs'));

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

				$model = Mage::getModel ('mhub/nf')
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

				Mage::getSingleton ('adminhtml/session')->addSuccess (Mage::helper ('mhub')->__('NF was successfully saved'));
				Mage::getSingleton ('adminhtml/session')->setNFData (false);

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
				Mage::getSingleton ('adminhtml/session')->setNFData ($this->getRequest ()->getPost ());

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
				$model = Mage::getModel('mhub/nf');
				$model->setId ($this->getRequest ()->getParam ('id'))->delete ();

				Mage::getSingleton ('adminhtml/session')->addSuccess (Mage::helper ('mhub')->__('NF was successfully deleted'));

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
        $fileName   = 'nfs.csv';
        $content    = $this->getLayout()
            ->createBlock('mhub/adminhtml_nf_grid')
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
                $model = Mage::getModel('mhub/nf');
                $model->setId ($id)->delete ();
			}

			Mage::getSingleton ('adminhtml/session')->addSuccess (Mage::helper ('mhub')->__('NF(s) was successfully removed'));
		}
		catch (Exception $e)
        {
			Mage::getSingleton ('adminhtml/session')->addError ($e->getMessage ());
		}

		$this->_redirect('*/*/');
	}
}

