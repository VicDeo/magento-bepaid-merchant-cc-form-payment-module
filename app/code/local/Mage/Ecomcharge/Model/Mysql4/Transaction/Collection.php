<?php

class Mage_Ecomcharge_Model_Mysql4_Message_Transaction_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
    public function _construct()
    {
        parent::_construct();
        $this->_init('ecomcharge/transaction');
    }
}
