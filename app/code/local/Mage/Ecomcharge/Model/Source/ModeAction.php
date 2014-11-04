<?php

class Mage_Ecomcharge_Model_Source_ModeAction
{
    public function toOptionArray()
    {
        return array(
            array('value' => Mage_Ecomcharge_Model_Config::MODE_LIVE, 'label' => Mage::helper('ecomcharge')->__('Live')),
            array('value' => Mage_Ecomcharge_Model_Config::MODE_TEST, 'label' => Mage::helper('ecomcharge')->__('Test')),
        );
    }
}
