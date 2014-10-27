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
        if ($this->getInfo()->getLastTransId()){
           $transport->addData(array(
                Mage::helper('payment')->__('Transaction ID') => $this->getInfo()->getLastTransId(),
            ));
        }
        return $transport;
    }
}
