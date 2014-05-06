<?php

class Mage_Ecomcharge_Model_Source_PaymentAction
{
    public function toOptionArray()
    {
        return array(
            array('value' => Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE, 'label' => Mage::helper('ecomcharge')->__('Payment')),
            array('value' => Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE, 'label' => Mage::helper('ecomcharge')->__('Authorization')),				
        );
    }
}