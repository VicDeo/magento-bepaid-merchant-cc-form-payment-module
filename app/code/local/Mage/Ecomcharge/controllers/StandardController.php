<?php

class Mage_Ecomcharge_StandardController extends Mage_Core_Controller_Front_Action
{
  public $isValidResponse = false;
  
  /**
  * Order we are working with
  *
  */
  protected $_order = false;

  /**
   * Get singleton with Ecomcharge strandard
   *
   */
  public function getStandard()
  {
    return Mage::getSingleton('ecomcharge/standard');
  }

  /**
   * Get Config model
   *
   */
  public function getConfig()
  {
    return $this->getStandard()->getConfig();
  }
  
  /**
   * Get API model
   *
   */
  public function getApi()
  {
    return $this->getStandard()->getApi();
  }

  /**
   *  Return debug flag
   *
   *  @return  boolean
   */
  public function getDebug ()
  {
    return $this->getStandard()->getDebug();
  }

  /**
   * When a customer chooses Ecomcharge on Checkout/Payment page
   *
   */
  public function redirectAction()
  {
    $this->loadLayout();
    $this->renderLayout();
  }

  protected function _expireAjax()
  {
    if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
      $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
      exit;
    }
  }

  protected function _loadOrder($incrementId){
    $order = Mage::getModel('sales/order');
    $this->_order = $order->loadByIncrementId($incrementId);
  }
  
  protected function _writeOrderHistory($message){
      $this->_order->addStatusToHistory(
        $this->_order->getStatus(),
        $message
      );
  }
  
  protected function _processPayment(){
      $payment = $this->_order->getPayment();
      $payment->setTransactionId($this->responseArr['transid'])
            ->setParentTransactionId(null)
            ->setShouldCloseParentTransaction(false)
            ->setIsTransactionClosed(0)
            ->registerCaptureNotification(
                $this->responseArr['amount'],
                false
              )
        ;
  }
  
  /**
   *  Success IPN (Call Back) response from eComCharge
   *
   *  @return	  void
   */
  public function  callbackResponseAction()
  {
    $this->preResponse();

    if (!$this->isValidResponse) {
      $this->_redirect('');
      return ;
    }

   /* Store debug info  */ 
   if ($this->getDebug()) {
        Mage::getModel('ecomcharge/api_debug')
          ->setResponseBody(print_r($this->responseArr,1))
          ->save();
   }

    /* Check if order exists */
    $this->_loadOrder($this->responseArr['orderno']);
    if (!$this->_order->getId()) {
        /*
         * need to have logic when there is no order with the order id from Ecomcharge
         */
        return false;
    }
    
    $test_msg = $this->responseArr['testmode'] == 'true' ? '  *** Test Mode ***' : '';
    
    if (($this->responseArr['status']) == 'successful') {
      $this->_writeOrderHistory(
          Mage::helper('ecomcharge')->__('Customer successfully returned from eComCharge')
      );

      //update table
      $shop_ptype = $this->getConfig()->getpayment_action();
      $shop_mode = $this->responseArr['testmode'];
      
      if ($shop_mode != 'true') $shop_mode = 'live';

      $dateNow = Mage::getModel('core/date')->date('yyyy-MM-dd H:m:s');
      $transaction = Mage::getModel('ecomcharge/transaction');
      $transaction->setType($shop_ptype)
          ->setIdEcomchargeCustomer($this->_order->getCustomerId())
          ->setIdCart($this->responseArr['orderno'])
          ->setIdOrder($this->responseArr['orderno'])
          ->setEcomUid($this->responseArr['transid'])
          ->setAmount($this->responseArr['amount'])
          ->setStatus($this->responseArr['status'])
          ->setCurrency($this->responseArr['currency'])
          ->setMode($shop_mode)
          ->setDateAdd($dateNow)
          ->save()
      ;

      $transactionType = $shop_ptype == 'authorize' ? Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH : Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
      $payment = $this->_order->getPayment();
      $payment->addTransaction($transactionType);
      if ($shop_ptype == 'authorize'){
        $message = Mage::helper('ecomcharge')->__('Callback received. eComCharge Payment Authorized. UID:'.$this->responseArr['transid'].$test_msg);
      } else {
        $message = Mage::helper('ecomcharge')->__('Callback received. eComCharge Payment Captured. UID:'.$this->responseArr['transid'].$test_msg);
        $this->_processPayment();
      }
      try {
        $this->_order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, $message, false)
            ->save()
        ;
        $invoice = $payment->getCreatedInvoice();
        if ($invoice){
            $this->_order->sendNewOrderEmail()
               ->setEmailSent(true)
               ->setIsCustomerNotified(true)
               ->save()
          ;
        }
      } catch (Exception $e) {
        Mage::logException($e);
      }
    } else {
      $this->_writeOrderHistory(
        Mage::helper('ecomcharge')->__('Callback received. eComCharge Payment Failed. UID:'.$this->responseArr['transid'].$test_msg)
      );
      $this->_order->save();
    }
    echo "OK";
  }
  /**
   *  Save invoice for order
   *
   *  @param    Mage_Sales_Model_Order $order
   *  @return	  boolean Can save invoice or not
   */
  protected function saveInvoice (Mage_Sales_Model_Order $order)
  {
    if ($order->canInvoice()) {
      $invoice = $order->prepareInvoice();

      $invoice->register()->capture();
      $order->addRelatedObject($invoice);
      return true;
    }

    return false;
  }


  /**
   *  Expected GET HTTP Method
   *
   *  @return	  void
   */
  protected function preResponse ()
  {
      $paymentfrm = $this->getApi();
    $paymentfrm->validateIPN();
    $this->responseArr['currency'] = $paymentfrm->GetPaymentCurrency();
    $paymentfrm->SetCurrencyMultiplyer($this->responseArr['currency']);
    $this->responseArr['status'] = $paymentfrm->GetPaymentStatus();
    $this->responseArr['amount'] = $paymentfrm->GetPaymentAmount();
    $this->responseArr['transid'] = $paymentfrm->GetPaymentUid();
    $this->responseArr['orderno'] = $paymentfrm->GetPaymentOrderno();
    $this->responseArr['testmode'] = $paymentfrm->Gettestmode();

    $this->isValidResponse = true;
  }

  public function  failureResponseAction()
  {
    $uid = $_REQUEST['token'];
    $message = 'Payment Failed. ';

    if ($uid) {
      $paymentfrm = $this->getApi();
      $res_ar = $paymentfrm->Query2($uid);
      if ($res_ar['checkout']['message']) {
        $message = $res_ar['checkout']['message'];
      }
    }
    $session = Mage::getSingleton('checkout/session');
    $session->setQuoteId($session->getEcomChargeStandardQuoteId(true));
    $session->setErrorMessage(Mage::helper('ecomcharge')->__($message));
    $this->_redirect('ecomcharge/standard/failure');
  }


  /**
   *  Failure Action
   *
   *  @return	  void
   */
  public function failureAction ()
  {
    $session = Mage::getSingleton('checkout/session');
    $session->setEcomchargeStandardQuoteId($session->getQuoteId());

    if (!$session->getErrorMessage()) {
      $this->_redirect('checkout/cart');
      return;
    }

    $this->loadLayout();
    $this->_initLayoutMessages('ecomcharge/session');
    $this->renderLayout();
  }

  public function  successResponseAction()
  {
    $uid = $_REQUEST['token'];

    if ($uid) {
      $shop_ptype = $this->getConfig()->getpayment_action();
      $paymentfrm = $this->getApi();
      $res_ar = $paymentfrm->Query2($uid);
      $uid = $res_ar['checkout']['gateway_response']['payment']['uid'];
      $res_ar1 = $paymentfrm->Query($uid);

      $msg3d = '';
      if (array_key_exists('three_d_secure_verification', $res_ar1['transaction'])){
        $msg3d = ', 3-D Enrollment Verification Status: '.$res_ar1['transaction']['three_d_secure_verification']['ve_status'];
        $msg3d .= ', 3-D Payment Authentication Status: '.$res_ar1['transaction']['three_d_secure_verification']['pa_status'];
      }

      $status = $res_ar1['transaction']['status'];
      if ($status == 'successful'){
        $orderno = $res_ar['checkout']['order']['tracking_id'] ;
        $testmode = $res_ar['checkout']['test'];
        $test_msg = '';
        if ($testmode == 'true') $test_msg = "  *** Test Mode ***";

        $this->_loadOrder($orderno);
        if (!$this->_order->getId()) {
          return false;
        }

        $transactionType = $shop_ptype == 'authorize' ? Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH : Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
        $payment = $this->_order->getPayment();
        $payment->addTransaction($transactionType);
        if ($shop_ptype == 'authorize'){
            $message = Mage::helper('ecomcharge')->__('Customer returned. eComCharge Payment Authorized. UID:'.$uid.', Payment Message: '.$msg3d.$test_msg);
        } else {
            $message = Mage::helper('ecomcharge')->__('Customer returned. eComCharge Payment Captured. UID:'.$uid.', Payment Message: '.$msg3d.$test_msg);
            $this->_processPayment();
        }
        
        try {
          $this->_order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, $message, false)
            ->save()
          ;
          $invoice = $payment->getCreatedInvoice();
          if ($invoice){
            $this->_order->sendNewOrderEmail()
               ->setEmailSent(true)
               ->setIsCustomerNotified(true)
               ->save()
            ;
          }
        } catch (Exception $e) {
          Mage::logException($e);
        }
      }
    }

    $session = Mage::getSingleton('checkout/session');
    $session->setQuoteId($session->getEcomChargeStandardQuoteId(true));
    $session->setErrorMessage(Mage::helper('ecomcharge')->__('Payment Complete'));
    $this->_redirect('ecomcharge/standard/success');
  }

  public function successAction ()
  {
    $session = Mage::getSingleton('checkout/session');
    $session->setEcomchargeStandardQuoteId($session->getQuoteId());

    if (!$session->getErrorMessage()) {
      $this->_redirect('checkout/cart');
      return;
    }
    $this->_redirect('checkout/onepage/success');
/*
    $this->loadLayout();
    $this->_initLayoutMessages('ecomcharge/session');
    $this->renderLayout();
*/
  }

  /**
   * When a customer cancel
   */
  public function cancelAction()
  {
    $session = Mage::getSingleton('checkout/session');
    $session->setQuoteId($session->getPaypalStandardQuoteId(true));
    $lastQuoteId = $session->getLastQuoteId();
    $lastOrderId = $session->getLastOrderId();

    if($lastQuoteId && $lastOrderId) {
      $orderModel = Mage::getModel('sales/order')->load($lastOrderId);
      if($orderModel->canCancel())
      {
        $quote = Mage::getModel('sales/quote')->load($lastQuoteId);
        $quote->setIsActive(true)->save();
        $orderModel->cancel();
        $orderModel->setStatus('canceled');
        $orderModel->save();
      }
    }
    $this->_redirect('checkout/cart');
  }

}
