<?php

class Mage_Ecomcharge_Model_Config extends Varien_Object
{
    const MODE_TEST         = 'test';
    const MODE_LIVE         = '';

    const PAYMENT_TYPE_PAYMENT      = 'payment';
    const PAYMENT_TYPE_AUTHORISE    = 'authorization';


    /**
     *  Return config var
     *
     *  @param    string Var key
     *  @param    string Default value for non-existing key
     *  @return	  mixed
     */
    public function getConfigData($key, $default=false)
    {
        if (!$this->hasData($key)) {
             $value = Mage::getStoreConfig('payment/ecomcharge_standard/'.$key);
             if (is_null($value) || false===$value) {
                 $value = $default;
             }
            $this->setData($key, $value);
        }
        return $this->getData($key);
    }


    /**
     *  Return Store description sent to Ecomcharge
     *
     *  @return	  string Description
     */
    public function getDescription ()
    {
        return $this->getConfigData('description');
    }

    /**
     *  Return Ecomcharge registered Product Id
     *
     *  @return	  string Product Id
     */
    public function getshop_id ()
    {
        return $this->getConfigData('shop_id');
    }



    /**
     *  Return Ecomcharge Payment Item ID
     *
     *  @return	  string Payment Item ID
     */
    public function getshop_pass ()
    {
        return $this->getConfigData('shop_pass');
    }

    /**
     *  Return Ecomcharge Mac Key
     *
     *  @return	  string Mac Key
     */
    public function getpayment_action ()
    {
        return $this->getConfigData('payment_action');
    }


    /**
     *  Return working mode (see SELF::MODE_* constants)
     *
     *  @return	  string Working mode
     */
    public function getMode ()
    {
        if($this->getConfigData('mode')){
            return self::MODE_TEST;
        } else {
            return self::MODE_LIVE;
        }
    }

    /**
     *  Return new order status
     *
     *  @return	  string New order status
     */
    public function getNewOrderStatus ()
    {
        return $this->getConfigData('order_status');
    }

    /**
     *  Return debug flag
     *
     *  @return	  boolean Debug flag (0/1)
     */
    public function getDebug ()
    {
        return $this->getConfigData('debug_flag');
    }




}