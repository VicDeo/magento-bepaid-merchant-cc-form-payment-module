<?php
// class written by  Senthil.R(senthil1975@gmail.com)
// Date : 04th March 2014

class ecomChargeLib
{
  public $ecomCheckoutsUrl;
  public $ecomRefundUrl;  
  public $ecomCaptureUrl;    
  public $ecomUser;  
  public $ecomPass;    
  public $ecomCheckoutArray;      
  public $ecomRefundArray;        
  public $ecomIPNArray; 
  public $ecomMode;  //test or live        
  public $CcyMultiplyer;  //test or live          
  
  public function __construct($_ecomUser, $_ecomPass, $_mode)
  {
    $this->ecomCheckoutsUrl = 'https://checkout.ecomcharge.com/ctp/api/checkouts';
    $this->ecomRefundUrl = 'https://processing.ecomcharge.com/transactions/refunds';	
    $this->ecomCaptureUrl = 'https://processing.ecomcharge.com/transactions/captures';		
	$this->ecomUser = $_ecomUser;
	$this->ecomPass = $_ecomPass;	 
	$this->ecomMode = $_mode;
//	$this->ecomMode = '';
	$this->ecomMode = 'test';
	$this->CcyMultiplyer = 100;	
	$this->ecomIPNArray = array();
	$this->ecomCheckoutArrayarray = array("checkout" => array("transaction_type" => "payment"));	
	if ($this->ecomMode == 'test') $this->ecomCheckoutArrayarray["checkout"]["test"] = "true";
	
	$this->ecomRefundArray = array("request" => array());	
	if ($this->ecomMode == 'test') $this->ecomRefundArray["request"]["test"] = "true";	
  }
  
  public function SetCheckoutArray($_KeyName, $_KeyVal)  
  {
	$this->ecomCheckoutArrayarray["checkout"][$_KeyName]	 =  $_KeyVal;
  }

  public function SetAuthorization()  
  {
	$this->ecomCheckoutArrayarray["checkout"]['transaction_type'] =  'authorization';
  }

  public function GetCheckoutUrl()  
  {
	$ChkRes = $this->_curlRequest($this->ecomCheckoutsUrl, json_encode($this->ecomCheckoutArrayarray));
	$token = $ChkRes['checkout']['token'];
	$chUrl = "https://checkout.ecomcharge.com/checkout?token=".$token;
	return $chUrl;	
  }

  public function GetToken()  
  {
	$ChkRes = $this->_curlRequest($this->ecomCheckoutsUrl, json_encode($this->ecomCheckoutArrayarray));
	$token = $ChkRes['checkout']['token'];
	return $token;	
  }

  public function Refund($parent_uid, $amount, $reason)  
  {
    $amount =  ($amount * $this->CcyMultiplyer);
	$this->ecomRefundArray['request']['parent_uid'] = $parent_uid;
	$this->ecomRefundArray['request']['amount'] = $amount;	
	$this->ecomRefundArray['request']['reason'] = $reason;		
	$ChkRes = $this->_curlRequest($this->ecomRefundUrl, json_encode($this->ecomRefundArray));	

	return $ChkRes;	
  } 
  
  public function SetCurrencyMultiplyer($Currency)  
  {
	  $exceptions=array(
		'BIF'=>1,
		'BYR'=>1,
		'CLF'=>1,
		'CLP'=>1,
		'CVE'=>1,
		'DJF'=>1,
		'GNF'=>1,
		'IDR'=>1,
		'IQD'=>1,
		'IRR'=>1,
		'ISK'=>1,
		'JPY'=>1,
		'KMF'=>1,
		'KPW'=>1,
		'KRW'=>1,
		'LAK'=>1,
		'LBP'=>1,
		'MMK'=>1,
		'PYG'=>1,
		'RWF'=>1,
		'SLL'=>1,
		'STD'=>1,
		'UYI'=>1,
		'VND'=>1,
		'VUV'=>1,
		'XAF'=>1,
		'XOF'=>1,
		'XPF'=>1,
		'MOP'=>10,
		'BHD'=>1000,
		'JOD'=>1000,
		'KWD'=>1000,
		'LYD'=>1000,
		'OMR'=>1000,
		'TND'=>1000
       );

	foreach($exceptions as $key=>$value)
	{
    	if((strtoupper($Currency)==$key))
	    {			
    	    $this->CcyMultiplyer =$value;
			break;
	    }			   
	}
  }
  
  public function GetCurrencyMultiplyer()  
  {
	  return $this->CcyMultiplyer;
	  }
  
  public function Capture($parent_uid, $amount)  
  {
     $amount =  ($amount * $this->CcyMultiplyer);
	 $cp_json = '{"request":{"parent_uid":"'.$parent_uid.'", "amount":'.$amount.'}}';
 	 $ChkRes = $this->_curlRequest($this->ecomCaptureUrl, $cp_json);		 
     return $ChkRes;	
  }

  public function Query($Uid)
  {
	 $url = 'https://processing.ecomcharge.com/transactions/'.$Uid; 
	 $ChkRes = $this->_curlRequest2($url);
     return $ChkRes;		 
  }
  
  public function Query2($Token)
  {
	 $url = 'https://checkout.ecomcharge.com/ctp/api/checkouts/'.$Token; 
	 $ChkRes = $this->_curlRequest2($url);
     return $ChkRes;		 
  }  
  public function validateIPN()  
  {
	$ResJson = file_get_contents('php://input');
	$this->ecomIPNArray = json_decode($ResJson, true);
  }
  
  public function GetPaymentStatus()  
  {
	return $this->ecomIPNArray['transaction']['status'];
  }
  
  public function Gettestmode()  
  {
	return $this->ecomIPNArray['transaction']['test'];
  }
  
  public function GetPaymentAmount()  
  {
	return ($this->ecomIPNArray['transaction']['amount']/$this->CcyMultiplyer);
  }  
  
 public function GetPaymentCurrency()  
  {
	return $this->ecomIPNArray['transaction']['currency'];
  }  

  public function GetPaymentType()  
  {
	return $this->ecomIPNArray['transaction']['type'];
  }

  public function GetPaymentUid()  
  {
	return $this->ecomIPNArray['transaction']['uid'];
  }


  public function GetPaymentOrderno()  
  {
	return $this->ecomIPNArray['transaction']['tracking_id'];
  }



  public function GetPaymentMessage()  
  {
	return $this->ecomIPNArray['transaction']['message'];
  }  
  
  private function _curlRequest($reqUrl, $reqJsondata)
  {
	$curl_headers = array("Content-Type: application/json", "Accept: application/json");  
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $reqUrl);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $reqJsondata);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_USERPWD,  $this->ecomUser.':'.$this->ecomPass);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
	$Jsonresult = curl_exec($ch);

	curl_close($ch);
	$retArray = json_decode($Jsonresult, true);
	if (isset($retArray['errors']))	print_r($retArray);
	return $retArray;
	  
  }    
  
  private function _curlRequest2($reqUrl)
  {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $reqUrl);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
//	curl_setopt($ch, CURLOPT_GET, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_USERPWD,  $this->ecomUser.':'.$this->ecomPass);
	$Jsonresult = curl_exec($ch);
//	echo $Jsonresult;
	curl_close($ch);
	$retArray = json_decode($Jsonresult, true);
	return $retArray;
	  
  }      
  
}
?>