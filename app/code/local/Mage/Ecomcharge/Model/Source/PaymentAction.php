<?php

class Mage_Ecomcharge_Model_Source_PaymentAction
{
    public function toOptionArray()
    {
        return array(
            array('value' => Mage_Ecomcharge_Model_Config::PAYMENT_TYPE_PAYMENT, 'label' => Mage::helper('ecomcharge')->__('Payment')),
            array('value' => Mage_Ecomcharge_Model_Config::PAYMENT_TYPE_AUTHORISE, 'label' => Mage::helper('ecomcharge')->__('Authorization')),
        );
    }
}
