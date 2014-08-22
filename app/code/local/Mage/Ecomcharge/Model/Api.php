<?php

$externalLibPath =realpath(dirname(__FILE__)).DS.'..'.DS . 'lib' . DS .'ecomChargeLib.php';
require_once ($externalLibPath);

class Mage_Ecomcharge_Model_Api extends Mage_Core_Model_Abstract
{

    public function getApi(){
        $config = Mage::getSingleton('ecomcharge/standard')->getConfig();
        return new ecomChargeLib(
            $config->getshop_id(),
            $config->getshop_pass(),
            $config->getMode()
        );
    }


}
