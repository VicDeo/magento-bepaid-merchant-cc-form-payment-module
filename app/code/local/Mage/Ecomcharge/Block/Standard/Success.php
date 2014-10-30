<?php


class Mage_Ecomcharge_Block_Standard_Success extends Mage_Core_Block_Template
{
     public function getSuccessMessage ()
    {
        $error  = Mage::getSingleton('checkout/session')->getErrorMessage();
        Mage::getSingleton('checkout/session')->unsErrorMessage();
        return $error;
    }
    /**
     * Get continue shopping url
     */
    public function getContinueShoppingUrl()
    {
        return Mage::getUrl('checkout/cart');
    }
}