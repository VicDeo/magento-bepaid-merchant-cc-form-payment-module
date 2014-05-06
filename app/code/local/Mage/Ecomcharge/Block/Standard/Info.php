<?php
class Mage_Ecomcharge_Block_Standard_Info extends Mage_Payment_Block_Info
{
	protected function _construct()
    {
        parent::_construct();     
    }
 	
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $info = $this->getInfo();
        $transport = new Varien_Object();
        $transport = parent::_prepareSpecificInformation($transport);
		if ($this->gettr_no())
        $transport->addData(array(
            Mage::helper('payment')->__('Transaction ID') => $this->gettr_no(),
    //        Mage::helper('payment')->__('Card No') => $this->getInfo()->getCcNumberEnc(),			
        ));
        return $transport;
    }
	
	public function gettr_no()
	{    
	    $tr_no = '';
		$details = @unserialize($this->getInfo()->getAdditionalData());
		$tr_no = $details['Transaction_Id'];

		return $tr_no; 
		
      }

}
