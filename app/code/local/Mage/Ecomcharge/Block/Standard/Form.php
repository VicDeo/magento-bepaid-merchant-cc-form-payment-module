<?php


class Mage_Ecomcharge_Block_Standard_Form extends Mage_Payment_Block_Form_Cc
{
    protected function _construct()
    {
	    parent::_construct();
        $this->setTemplate('ecomcharge/standard/form.phtml');
     
    }
   


}