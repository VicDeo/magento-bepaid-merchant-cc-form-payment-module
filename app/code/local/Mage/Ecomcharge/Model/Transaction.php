<?php

class Mage_Ecomcharge_Model_Transaction extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('ecomcharge/transaction', 'id_ecomcharge_transaction');
    }
    
}
