<?php

class Mage_Ecomcharge_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
  protected $_code  = 'ecomcharge_standard';
  protected $_formBlockType = 'ecomcharge/standard_form';
  protected $_infoBlockType = 'ecomcharge/standard_info';
  protected $_isInitializeNeeded      = true;	
  protected $_isGateway               = true;
  protected $_canAuthorize            = true;
  protected $_canCapture              = true;
  protected $_canCapturePartial       = false;
  protected $_canRefund               = true;
  protected $_canUseForMultishipping  = false;
  protected $_canSaveCc  = true;	
  protected $_order = null;


  public function getOrder()
  {
    $session = Mage::getSingleton('checkout/session');
    $session->setEcomchargeStandardQuoteId($session->getQuoteId());

    $order = Mage::getModel('sales/order');
    $order->loadByIncrementId($session->getLastRealOrderId());
    return $order;
  }

  /**
   * Get Config model
   *
   * @return object Mage_Ecomcharge_Model_Config
   */
  public function getConfig()
  {
    return Mage::getSingleton('ecomcharge/config');
  }

  public function isAvailable($quote = null)
  {
    if (parent::isAvailable($quote)) {
      return true;
    }
    return false;
  }


  /**
   * Return the payment info model instance for the order
   *
   * @return Mage_Payment_Model_Info
   */
  public function getInfoInstance()
  {
    $payment = $this->getData('info_instance');
    if (! $payment)
    {
      $payment = $this->getOrder()->getPayment();
      $this->setInfoInstance($payment);
    }
    return $payment;
  }

  /**
   * Capture payment
   *
   * @param   Varien_Object $orderPayment
   */
  public function capture(Varien_Object $payment, $amount)	
  {
    $trans_id = $payment->getParentTransactionId();
    if ($trans_id){
      $shop_id = $this->getConfig()->getshop_id();
      $shop_pass = $this->getConfig()->getshop_pass();
      $shop_ptype = $this->getConfig()->getpayment_action();		
      $shop_mode = $this->getConfig()->getMode();		
      $currency_code = $payment->getOrder()->getBaseCurrencyCode();		
      $ExternalLibPath =realpath(dirname(__FILE__)).DS.'..'.DS . 'lib' . DS .'ecomChargeLib.php';
      require_once ($ExternalLibPath);

      $order =  $payment->getOrder();


      $paymentfrm = new ecomChargeLib($shop_id, $shop_pass, $shop_mode);
      $paymentfrm->SetCurrencyMultiplyer($currency_code);
      $resAr = $paymentfrm->Capture($trans_id, $amount);	

      if (isset($resAr['transaction']['status']) && ($resAr['transaction']['status'] == 'successful')){		
        if (!$payment->getParentTransactionId() ||
          $resAr['transaction']['uid'] != $payment->getParentTransactionId()) {
            $payment->setTransactionId($resAr['transaction']['uid']);
          }
        $payment->setIsTransactionClosed(0);			
        return $this;
      }
      else Mage::throwException(Mage::helper('ecomcharge')->__('Payment capturing error.'));
    }
  }



  /**
   * Refund money
   *
   * @param Varien_Object $payment
   * @param float $amount
   *
   */
  public function refund(Varien_Object $payment, $amount)
  {
    $reason = $this->getReason() ? $this->getReason() : Mage::helper('ecomcharge')->__('No Reason');
    $comment = $this->getComment() ? $this->getComment() : Mage::helper('ecomcharge')->__('No Comment');
    $trans_id = $payment->getParentTransactionId();

    $shop_id = $this->getConfig()->getshop_id();
    $shop_pass = $this->getConfig()->getshop_pass();
    $shop_ptype = $this->getConfig()->getpayment_action();		
    $shop_mode = $this->getConfig()->getMode();		
    $currency_code = $payment->getOrder()->getBaseCurrencyCode();		


    $ExternalLibPath =realpath(dirname(__FILE__)).DS.'..'.DS . 'lib' . DS .'ecomChargeLib.php';
    require_once ($ExternalLibPath);


    $paymentfrm = new ecomChargeLib($shop_id, $shop_pass, $shop_mode);
    $paymentfrm->SetCurrencyMultiplyer($currency_code);
    $resRedund = $paymentfrm->Refund($trans_id,$amount,$reason);

    if ($resRedund['transaction']['status'] == 'successful') {	
      $shouldCloseCaptureTransaction = $payment->getOrder()->canCreditmemo() ? 0 : 1;
      $payment
        ->setIsTransactionClosed(1)
        ->setShouldCloseParentTransaction($shouldCloseCaptureTransaction);
      $payment->setTransactionId($resRedund['transaction']['uid']);					 						 
      return $this;
    }else return false;

  }

  public function canRefund()
  {
    return $this->_canRefund;
  }



  /**
   * Return the specified additional information from the payment info instance
   *
   * @param string $key
   * @param Varien_Object $payment
   * @return string
   */
  public function get_PaymentInfoData($key, $payment = null)
  {
    if (is_null($payment))
    {
      $timesammp=DATE("dmyHis");		
      $transactionId = $timesammp;
      $payment = $this->getInfoInstance();
      $payment->setAdditionalInformation('transaction_id', $transactionId);		
      $payment->save;
    }
    return $payment->getAdditionalInformation($key);
  }	

  /**
   * Return the transaction id for the current transaction
   *
   * @return string
   */
  public function get_TransactionId()
  {
    return $this->get_PaymentInfoData('transaction_id');
  }	


  /**
   * Assign data to info model instance
   *
   * @param   mixed $data
   * @return  Mage_Payment_Model_Info
   */
  public function assignData($data)
  {
    if (!($data instanceof Varien_Object)) {
      $data = new Varien_Object($data);
    }


    $info = $this->getInfoInstance();
    $info->setCcType($data->getCcType())
      ->setCcOwner($data->getCcOwner())
      ->setCcLast4(substr($data->getCcNumber(), -4))
      ->setCcNumber($data->getCcNumber())
      ->setCcCid($data->getCcCid())
      ->setCcExpMonth($data->getCcExpMonth())
      ->setCcExpYear($data->getCcExpYear())
      ->setCcSsIssue($data->getCcSsIssue())
      ->setCcSsStartMonth($data->getCcSsStartMonth())
      ->setCcSsStartYear($data->getCcCid())
      ;
    return $this;
  }	
  /**
   * Return debug flag
   *
   *  @return  boolean
   */
  public function getDebug ()
  {
    return $this->getConfig()->getDebug();
  }


  /**
   *  Return URL for Ecomcharge success response
   *
   *  @return	  string URL
   */
  protected function getSuccessURL ()
  {
    return Mage::getUrl('ecomcharge/standard/successresponse');
  }

  /**
   *  Return URL for Ecomcharge failure response
   *
   *  @return	  string URL
   */
  protected function getFailureURL ()
  {
    return Mage::getUrl('ecomcharge/standard/failureresponse');
  }

  /**
   *  Return URL for Ecomcharge CallBack response
   *
   *  @return	  string URL
   */
  protected function getCallbackURL ()
  {
    return Mage::getUrl('ecomcharge/standard/callbackresponse');
  }

  protected function getCancelURL ()
  {
    return Mage::getUrl('ecomcharge/standard/cancel');
  }


  /**
   * Transaction unique ID sent to Ecomcharge and sent back by Ecomcharge for order restore
   * Using created order ID
   *
   *  @return	  string Transaction unique number
   */
  protected function getVendorTxCode ()
  {
    return $this->getOrder()->getRealOrderId();
  }

  public function hasVerification()
  {
    return true;
  }
  /**
   *  Returns cart formatted
   *  String format:
   *  Number of lines:Name1:Quantity1:CostNoTax1:Tax1:CostTax1:Total1:Name2:Quantity2:CostNoTax2...
   *
   *  @return	  string Formatted cart items
   */
  protected function getFormattedCart ()
  {
    $items = $this->getOrder()->getAllItems();
    $resultParts = array();
    $totalLines = 0;
    if ($items) {
      foreach($items as $item) {
        if ($item->getParentItem()) {
          continue;
        }
        $quantity = $item->getQtyOrdered();

        $cost = sprintf('%.2f', $item->getBasePrice() - $item->getBaseDiscountAmount());
        $tax = sprintf('%.2f', $item->getBaseTaxAmount());
        $costPlusTax = sprintf('%.2f', $cost + $tax/$quantity);

        $totalCostPlusTax = sprintf('%.2f', $quantity * $cost + $tax);

        $resultParts[] = str_replace(':', ' ', $item->getName());
        $resultParts[] = $quantity;
        $resultParts[] = $cost;
        $resultParts[] = $tax;
        $resultParts[] = $costPlusTax;
        $resultParts[] = $totalCostPlusTax;
        $totalLines++; //counting actual formatted items
      }
    }

    // add delivery
    $shipping = $this->getOrder()->getBaseShippingAmount();
    if ((int)$shipping > 0) {
      $totalLines++;
      $resultParts = array_merge($resultParts, array('Shipping','','','','',sprintf('%.2f', $shipping)));
    }

    $result = $totalLines . ':' . implode(':', $resultParts);
    return $result;
  }


  /**
   *  Form block description
   *
   *  @return	 object
   */
  public function createFormBlock($name)
  {
    $block = $this->getLayout()->createBlock('ecomcharge/form_standard', $name);
    $block->setMethod($this->_code);
    $block->setPayment($this->getPayment());
    return $block;
  }

  /**
   *  Return Order Place Redirect URL
   *
   *  @return	  string Order Redirect URL
   */
  public function getOrderPlaceRedirectUrl()
  {
    return Mage::getUrl('ecomcharge/standard/redirect');
  }    

  /**
   * Get checkout session namespace
   *
   * @return Mage_Checkout_Model_Session
   */
  public function getCheckout()
  {
    return Mage::getSingleton('checkout/session');
  }


  /**
   * Get current quote
   *
   * @return Mage_Sales_Model_Quote
   */
  public function getQuote()
  {
    return $this->getCheckout()->getQuote();
  }

  /**
   * Prepare info instance for save
   *
   * @return Mage_Payment_Model_Abstract
   */
  public function prepareSave()
  {
    $info = $this->getInfoInstance();
    if ($this->_canSaveCc) {
      $info->setCcNumberEnc($info->encrypt($info->getCcNumber()));
    }
    //echo $info->getCcCid();
    // Uncommented this line
    //    $info->setCcCidEnc($info->encrypt($info->getCcCid()));

    //    $info->setCcNumber(null)->setCcCid(null); 

    return $this;

  }

  /**
   * Instantiate state and set it to state object
   * @param string $paymentAction
   * @param Varien_Object
   */
  public function initialize($paymentAction, $stateObject)
  {
    $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
    $stateObject->setState($state);
    $stateObject->setStatus('pending_payment');
    $stateObject->setIsNotified(false);
  }
  /**
   *  Return Standard Checkout Form Fields for request to Ecomcharge
   *
   *  @return	  array Array of hidden form fields
   */
  public function getStandardCheckoutFormFields ()	
  {

    //        $order = $this->getOrder();		

    $session = Mage::getSingleton('checkout/session');
    $session->setEcomchargeStandardQuoteId($session->getQuoteId());

    $order = Mage::getModel('sales/order');
    $order->loadByIncrementId($session->getLastRealOrderId());


    $amount = $order->getBaseGrandTotal();
    //        $description = Mage::app()->getStore()->getName() . ' ' . ' payment';
    $transactionId = $this->getVendorTxCode();						
    $description = 'Order # '.$transactionId;

    $billing = $order->getBillingAddress();		
    $customer_details = Mage::getModel('customer/customer')->load( $order->getCustomerId() );
    $payment = $this->getInfoInstance();		

    $shop_id = $this->getConfig()->getshop_id();
    $shop_pass = $this->getConfig()->getshop_pass();
    $shop_ptype = $this->getConfig()->getpayment_action();		
    $shop_mode = $this->getConfig()->getMode();		
    list($lang_iso_code) = explode("_", Mage::app()->getLocale()->getLocaleCode());

    $sp_lang = array('en', 'es', 'tr', 'de', 'it', 'ru', 'zh', 'fr');
    if (!in_array($lang_iso_code, $sp_lang)) $lang_iso_code = 'en';


    $currency_code = $order->getBaseCurrencyCode();		

    $settings = array(
      "success_url" => $this->getSuccessURL(), 
      "decline_url" => $this->getFailureURL(),
      "fail_url" => $this->getFailureURL(),
      "cancel_url" => $this->getCancelURL(),	  
      "notification_url" => $this->getCallbackURL(),
      "language"=> $lang_iso_code);

    $order = array("currency" => $currency_code,
      "amount"=> $amount,
      "tracking_id" => $transactionId,
      "description"=> $description)	 ;		

    $customer = array(
      "ip"=> $_SERVER['REMOTE_ADDR'],
      "first_name"=> $billing->getFirstname(),
      "last_name"=> $billing->getLastname(),	  
      "address"=> $billing->getStreetFull(),
      "country"=> $billing->getCountry(),
      "city"=> $billing->getCity(),
      "zip"=> $billing->getPostcode(),	  
      "phone"=> $billing->getTelephone(),	  			  
      "email"=> ( $billing->getEmail() ) ? $billing->getEmail() : $customer_details->getEmail()
    );	  		
    if ($billing->getCountry() == 'US' || $billing->getCountry() == 'CA') {
      $customer['state'] = $billing->getRegionCode();		
    }

    $ExternalLibPath =realpath(dirname(__FILE__)).DS.'..'.DS . 'lib' . DS .'ecomChargeLib.php';
    require_once ($ExternalLibPath);

    $paymentfrm = new ecomChargeLib($shop_id, $shop_pass, $shop_mode);
    $paymentfrm->SetCurrencyMultiplyer($order['currency']);
    if ($shop_ptype == 'authorize') $paymentfrm->SetAuthorization();
    $order["amount"] = $amount * $paymentfrm->GetCurrencyMultiplyer();
    $paymentfrm->SetCheckoutArray("settings", $settings);
    $paymentfrm->SetCheckoutArray("order", $order);
    $paymentfrm->SetCheckoutArray("customer", $customer);
    $token = $paymentfrm->GetToken();



    $action_url = 'https://checkout.ecomcharge.com/ctp/payments';
    $expyy = substr($payment->getCcExpYear(), 2);
    $expmm = str_pad($payment->getCcExpMonth(), 2, "0", STR_PAD_LEFT);
    $coFields = array();
    $coFields['request[credit_card][number]'] = $payment->getCcNumber();
    $coFields['request[credit_card][holder]'] =$payment->getCcOwner();
    $coFields['request[credit_card][exp_date]'] = $expmm .'/'. $expyy;
    $coFields['request[credit_card][verification_value]'] = $payment->getCcSsStartYear();
    $coFields['request[token]'] = $token;		


    return $coFields;
  }
}
