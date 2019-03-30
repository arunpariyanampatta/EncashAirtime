<?php
//error_reporting(0);
date_default_timezone_set('Africa/Dar_Es_Salaam');
class LukuProcess extends CI_Controller {
	var $balance,$agent_id,$agentMsisdn,$company_name,$unique,$requestmode,$operator;

	public function __construct() {
		parent::__construct();
		$this->load->model('EncashServices'); //This file contanis Main-Menu and its operations
		$this->load->model('CorporateServices','CorporateServices');
	}
function index(){  
  $xml = '<?xml version="1.0" encoding="iso-8859-1"?>
<methodResponse>
<params>
 <param>
  <value>
   <struct>
    <member>
     <name>transid</name>
     <value>
      <string>171114005808</string>
     </value>
    </member>
    <member>
     <name>reference</name>
     <value>
      <string>6243078951</string>
     </value>
    </member>
    <member>
     <name>resultcode</name>
     <value>
      <string>000</string>
     </value>
    </member>
    <member>
     <name>result</name>
     <value>
      <string>SUCCESS</string>
     </value>
    </member>
    <member>
     <name>receipt</name>
     <value>
      <string>SELCOM3EMDB94761423</string>
     </value>
    </member>
    <member>
     <name>units</name>
     <value>
      <string>5.7kWh</string>
     </value>
    </member>
    <member>
     <name>token</name>
     <value>
      <string>04332311409582352806</string>
     </value>
    </member>
    <member>
     <name>message</name>
     <value>
      <string>LUKU
ANANKIRA ZABLON NYITI
Meter 24218403285
Receipt SELCOM3EMDB94761423
Units 5.70kWh

Token 0433 2311 4095 8235 2806

Cost TZS 1,639.35
VAT 18% TZS 295.08
EWURA 1% TZS 16.39
REA 3% TZS 49.18
TOTAL TZS 2,000.00
Reference 6243078951</string>
     </value>
    </member>
   </struct>
  </value>
 </param>
</params>
</methodResponse>
';
 $xmlArray = xmlrpc_decode($xml);

if ($xmlArray ['result'] == "SUCCESS") {

      $trans ['ERROR_CODE'] = "000";
      $trans ['TXN_STATUS'] = 'SUCCESS';
      $trans ['TXN_DESCRIPTION'] = "Luku Transaction processed Successfully";
      $trans ['TXN_THIRD_REFERENCE'] = $xmlArray['reference'];
      $trans ['TXN_ID'] = $refID;
      $trans ['COMMENTS'] = $xmlArray ['message'];
      $trans ['LUKU_TOKEN'] = $xmlArray ['token'];
      $trans['EXTRA'] = "Units : ".$xmlArray['units'];
     
    }
 $device = "24218403285";
  $txnDat = $trans;
  $ackRef = "33E81BD1EEB68A1BAB78";
  $UUID = "4bd5f36a-6a38-480c-add9-eef72f2cfd9c";
  $this->agentMsisdn = "255655222655";
  $route = "SELCOME";
  $refID = "171114005808";
  $txnReference = $refID;
  $this->requestmode = "THIRDPARTY";
  $this->agent_id= 14;
  $amount = "2000";
  $result = $txnDat;
  $notifymsisdn = "255689682777";
 if($txnDat['TXN_STATUS']=='SUCCESS'){   
        


$data = array (
            "TXN_REFERENCE" => $refID,
            "THIRDPARTY_REF" => $thirdParty,
            "REQUEST_TYPE" => $this->requestmode,
            "AGENT_ID" => $this->agent_id,
            "AGENT_MSISDN" => $this->agentMsisdn,
            "SERVICE_NAME"=>"LUKU",
            "CARD_NUMBER"=>$device,
            "CUSTOMER_MOBILE" => $notifymsisdn,
            "AMOUNT" => $amount,
            "TXN_STATUS" => "SUCCESS",
            "TOKEN_NUMBER"=>$trans ['LUKU_TOKEN'],
            "ERROR_CODE" => $errorCode,
            "COMMENTS" => $result['COMMENTS']
        );
$this->EncashServices->_recordTransaction("luku_transaction",$data);

}

// if($this->requestmode=="THIRDPARTY"||$this->requestmode=="WEB"){
  
// $transactionDetails = $result['COMMENTS'];
// $date = date("Y-m-d h:i:s");
//  $response = '{"Response": {"StatusCode" : "0","StatusMessage": "SUCCESS","ThirdPartyTransactionRef": "'.$ackRef.'","UUID":"'.$UUID.'","TransactionDetails" : "'.$transactionDetails.'","ServiceID":"LUKU","TransactionDate" : "'.$date.'"}}';

// $callbackUrl =  $this->EncashServices->getCallbackUrl($this->agent_id);
//    $url = $callbackUrl['CALLBACK_URL'];
//  $ch = curl_init();
//     curl_setopt($ch,CURLOPT_URL,$url);
//     curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
//     curl_setopt($ch,CURLOPT_TIMEOUT,100);
//     curl_setopt($ch,CURLOPT_POST,true);
//     curl_setopt($ch,CURLOPT_POSTFIELDS,$response);
//     curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
//     $data = curl_exec($ch);
// }


}
}
?>