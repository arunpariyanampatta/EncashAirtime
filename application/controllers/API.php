<?php

/*
 * Author : Arun.Pariyanampatta.
 * Organization : GREEN TELECOM
 * and open the template in the editor.
 */
error_reporting(0);
date_default_timezone_set('Africa/Dar_Es_Salaam');
class API extends CI_Controller {
	var $balance,$company_id,$company_msisdn,$company_name,$unique;

	public function __construct() {
		parent::__construct();
		$this->load->model('EncashServices'); //This file contanis Main-Menu and its operations
		$this->load->model('CorporateServices','CorporateServices');
	}



function index(){

$post = file_get_contents("php://input");
file_put_contents('post.txt',$post);
$json = json_decode($post,TRUE);
$operator = $json['OPERATOR'];
$refID = $json['REFID'];
$msisdn = $json['MSISDN'];
$mobile = $msisdn;
$amount = $json['AMOUNT'];
$msisdn = preg_replace('/[^A-Za-z0-9\-]/', '', $msisdn);
$this->company_msisdn = $json['COMPANYMSISDN'];
$this->company_id = $json['COMPANYID'];

if($operator =="TIGO"){
  
$txnDat = $this->TIGO($refID,$msisdn,$amount);
$route= "DIRECT";
$table = "TIGO";

}
else if($operator=="HALOTEL"){
$txnDat = $this->Halotel($msisdn,$amount);
$route= "DIRECT";
$table = "HALOTEL";
}
else if($operator == "AIRTEL"){

$txnDat = $this->Airtel($msisdn,$refID,$amount);
$route= "DIRECT";
$table = "AIRTEL";
}
elseif($operator == "SMART") {

$txnDat = $this->SMART($refID,$msisdn,$amount);
$route= "DIRECT";
$table = "SMART";
}
else{
  $txnDat = $this->SelcomAirtime($refID,$msisdn,$amount);
  $route= "SELCOM";
}



    if($txnDat['TXN_STATUS']=='SUCCESS'){
      $dt = $this->CorporateServices->getContactdetails($mobile);
      if(empty($dt)){

        $data['COMPANY_ID'] = $this->company_id;
        $data['MSISDN'] = $mobile;
        $data['TXN_ID'] = $refID;
        $data['AMOUNT']  = $amount;
        $data['status'] = 'SUCCESS';
      }
      else{
     foreach($dt as $index=>$key){
        $data['FNAME'] =$dt[$index]['F_NAME'];
        $data['LNAME'] =$dt[$index]['L_NAME'];
        $data['COMPANY_ID'] = $this->company_id;
        $data['MSISDN'] = $mobile;
        $data['TXN_ID'] = $refID;
        $data['AMOUNT']  = $amount;
        $data['status'] = 'SUCCESS';
      }
    }
        $opBAL =  $this->EncashServices->getBalance($this->company_msisdn);
        $this->EncashServices->updateBalance($this->company_msisdn,$amount);
        $this->CorporateServices->_recordTransaction("recharge_list",$data);
        
        $txn = array (
            "TXN_REFERENCE" => $refID,
            "TXN_TYPE" => "AIRTIME",
            "REQUEST_TYPE" => "WEB",
            "AGENT_ID" => $res['ID'],
            "AGENT_MSISDN" => $this->company_msisdn,
            "TXN_DEVICE_NUMBER" => $mobile,
            "TXN_AMOUNT" => $amount,
            "TXN_STATUS" => "SUCCESS",
            "THIRDPARTY_REF" => $txnData['TXN_THIRD_REFERENCE'],
            "ERROR_CODE" => "0000",
            "ERROR_DESC" => "SUCCESS",
            "TXN_ROUTE" =>$route
        );
        $this->EncashServices->_recordTransaction("transaction_logs_all",$txn);
          $clBalance = $opBAL['TOTAL_AVAIL_BALANCE'] - $amount;
           $txnSuccess = array (
            "TXN_REFERENCE" => $refID,
            "TXN_TYPE" => "AIRTIME",
            "OP_BALANCE"=>$opBAL['TOTAL_AVAIL_BALANCE'],
            "CL_BALANCE"=>$clBalance,
            "REQUEST_TYPE" => "WEB",
            "AGENT_ID" => $res['ID'],
            "AGENT_MSISDN" => $this->company_msisdn,
            "TXN_DEVICE_NUMBER" => $mobile,
            "TXN_AMOUNT" => $amount,
            "TXN_STATUS" => "SUCCESS",
            "THIRDPARTY_REF" => $txnDat['TXN_THIRD_REFERENCE'],
            "ERROR_CODE" => "0000",
            "ERROR_DESC" => "SUCCESS",
            "TXN_ROUTE" =>$route
        );


            $this->EncashServices->_recordTransaction("transaction_logs_success",$txnSuccess);
             $this->EncashServices->_updateMerchantBalance($amount,$route); 
 $txnLog = array (
            "TXN_REFERENCE" => $refID,
            "TXN_TYPE" => "AIRTIME",
            "REQUEST_TYPE" => "WEB",
            "AGENT_MSISDN" => $this->company_msisdn,
            "TXN_DEVICE_NUMBER" => $mobile,
            "TXN_AMOUNT" => $amount,
            "TXN_STATUS" => "SUCCESS",
            "THIRDPARTY_REF" => $txnDat['TXN_THIRD_REFERENCE'],
            "ERROR_CODE" => "0000",
            "ERROR_DESC" => "SUCCESS",
            "TXN_ROUTE" =>$route
        );
if($operator == "SMART"){
  $table = "transaction_logs_smart";
}
elseif($operator == "TIGO"){
  $table = "transaction_logs_tigo";
}
else if($operator == "HALOTEL"){
  $table = "transaction_logs_halotel";
}

else if($operator == "AIRTEL"){
  $table = "transaction_logs_airtel";
}

else{
  $table = "transaction_logs_selcom";
}
$this->EncashServices->_recordTransaction($table,$txnLog);

      //}
    }
else{
      $dt = $this->CorporateServices->getContactdetails($mobile);
      if(empty($dt)){

        $failed['COMPANY_ID'] = $this->company_id;
        $failed['MSISDN'] = $mobile;
        $failed['TXN_ID'] = $refID;
        $failed['AMOUNT']  = $amount;
        $failed['status'] = 'FAILED';
      }
      else{
     foreach($dt as $index=>$key){
        $failed['FNAME'] =$dt[$index]['F_NAME'];
        $failed['LNAME'] =$dt[$index]['L_NAME'];
        $failed['COMPANY_ID'] = $this->company_id;
        $failed['MSISDN'] = $mobile;
        $failed['TXN_ID'] = $refID;
        $failed['AMOUNT']  = $amount;
        $failed['status'] = 'FAILED';
}
}
 $this->CorporateServices->_recordTransaction("recharge_list",$failed);
         $txnFailed = array (
            "TXN_REFERENCE" => $refID,
            "TXN_TYPE" => "AIRTIME",
            "REQUEST_TYPE" => "WEB",
            "AGENT_ID" => $res['ID'],
            "AGENT_MSISDN" => $this->company_msisdn,
            "TXN_DEVICE_NUMBER" => $mobile,
            "TXN_AMOUNT" => $amount,
            "TXN_STATUS" => "FAILED",
            "THIRDPARTY_REF" => $txnData['TXN_THIRD_REFERENCE'],
            "ERROR_CODE" => "0000",
            "ERROR_DESC" => $txnData['TXN_DESCRIPTION'],
            "TXN_ROUTE" =>$route
            );
         
          $this->EncashServices->_recordTransaction("transaction_logs_all",$txnFailed);
    }

}
function Halotel($msisdn,$amount){
  $url = "http://10.225.198.52:8383/vpg.asmx?wsdl";
$process_code   = "000000";
$client     = "greentel";
$date       = date("Ymdhis");
$trace      = "0".$date;
$signelements   = $msisdn.$process_code.$amount.$date.$trace.$client;
$signature = $this->generatesignature($signelements);
$txn = $trace;
$data = '<DATA>
<MTI>0200</MTI>
<MSISDN>'.$mobile.'</MSISDN>
<PROCESS_CODE>000000</PROCESS_CODE>
<TRANS_AMOUNT>'.$amount.'</TRANS_AMOUNT>
<TRANS_TIME>'.$date.'</TRANS_TIME>
<SYSTEM_TRACE>'.$trace.'</SYSTEM_TRACE>
<CLIENT_ID>greentel</CLIENT_ID>
<SIGNATURE>'.$signature.'</SIGNATURE><ADD_DATA></ADD_DATA>
</DATA>';


$post = htmlentities($data);
$xml = '<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://ws.vpg.viettel.com/">
<SOAP-ENV:Body>
<ns1:send>
<data>'.$post.'</data>
</ns1:send>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>';
  $headers = array (
        "Content-type: text/xml;charset=\"utf-8\"",
        "Accept: text/xml",
        "Cache-Control: no-cache",
        "Pragma: no-cache",
        "Content-length: ".strlen($xml )
);
$ch = curl_init();
      curl_setopt($ch,CURLOPT_URL,$url);
      curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
      curl_setopt($ch,CURLOPT_TIMEOUT,100);
      curl_setopt($ch,CURLOPT_POST,true);
      curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
      curl_setopt($ch, CURLINFO_HEADER_OUT, true);
      curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
      $data = curl_exec($ch);
      $status  = $this->get_string_between($data,"&lt;RESPONSE_CODE&gt;","&lt;/RESPONSE_CODE&gt;");
        if($status =="00"){
        $trans ['ERROR_CODE'] = "000";
        $trans ['TXN_STATUS'] = 'SUCCESS';
        $trans ['TXN_DESCRIPTION'] = "Airtime Transaction processed Successfully";
        $trans ['TXN_THIRD_REFERENCE'] = $txn;
        $trans ['TXN_ID'] = $txn;
        return $trans;
        }
        else{
        $trans ['ERROR_CODE'] = "001";
        $trans ['TXN_STATUS'] = 'FAILED';
        $trans ['TXN_DESCRIPTION'] = $status;
        $trans ['TXN_THIRD_REFERENCE'] = $txn;
        $trans ['TXN_ID'] = $txn;
        return $trans;
        }
}

function generatesignature($toSign){
$key = file_get_contents('http://localhost/halotelkey.pem');

$pkeyid = openssl_get_privatekey($key);
openssl_sign($toSign, $signature, $pkeyid);
openssl_free_key($pkeyid);
$base64 = base64_encode($signature);

return $base64;
}
function Airtel($msisdn,$txnID,$amount){
$url = "https://172.23.12.29:4412/pretups/C2SReceiver?";  
$xml = '<?xml version="1.0"?> <!DOCTYPE COMMAND PUBLIC "-//Ocam//DTD XML Command 1.0//EN""xml/command.dtd">
<COMMAND><TYPE>EXRCTRFREQ</TYPE> <DATE></DATE> <EXTNWCODE>TZ</EXTNWCODE><MSISDN>789813064</MSISDN><PIN>0606</PIN><LOGINID></LOGINID><PASSWORD></PASSWORD><EXTCODE></EXTCODE> <EXTREFNUM>'.$txnID.'</EXTREFNUM> <MSISDN2>'.$msisdn.'</MSISDN2><AMOUNT>'.$amount.'</AMOUNT><LANGUAGE1>1</LANGUAGE1><LANGUAGE2>1</LANGUAGE2><SELECTOR>1</SELECTOR></COMMAND>';

$headers = array (
        "Content-type: application/xml;charset=\"utf-8\"",
        "Accept: application/xml",
        "Connection: Keep-Alive", 
        "Cache-Control: no-cache",
        "Pragma: no-cache",
        "Content-length:".strlen($xml ) ,
        "Authorization: LOGIN=pretups&PASSWORD=ed8c476255e5fdfff09bd75c7d10b180&REQUEST_GATEWAY_CODE=GRNT&REQUEST_GATEWAY_TYPE=EXTGW&SERVICE_PORT=190&SOURCE_TYPE=EXT"
    );
$ch = curl_init();
      curl_setopt($ch,CURLOPT_URL,$url);
     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
       curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
      curl_setopt($ch,CURLOPT_TIMEOUT,100);
      curl_setopt($ch,CURLOPT_POST,true);
      curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
      curl_setopt($ch, CURLINFO_HEADER_OUT, true);
      curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
      $data = curl_exec($ch);
      
      $status = $this->get_string_between($data,"<TXNSTATUS>","</TXNSTATUS>");
      $thirdParty = $this->get_string_between($data,'<TXNID>','</TXNID>');
      if($status == "200"){
        $trans ['ERROR_CODE'] = "000";
        $trans ['TXN_STATUS'] = 'SUCCESS';
        $trans ['TXN_DESCRIPTION'] = "Airtime Transaction processed Successfully";
        $trans ['TXN_THIRD_REFERENCE'] = $thirdParty;
        $trans ['TXN_ID'] = $txnID;
        return $trans;
      }
      else{
        $trans ['ERROR_CODE'] = '001';
        $trans ['TXN_STATUS'] = 'FAILED';
        $trans ['TXN_DESCRIPTION'] = $status;
        $trans ['TXN_THIRD_REFERENCE'] = $txnID;
        $trans ['TXN_ID'] = $txnID;
        return $trans;  
      }
}
function SelcomAirtime($refID,$msisdn,$amount){
          $xml = '<?xml version="1.0" encoding="iso-8859-1"?>
<methodCall>
<methodName>SELCOM.utilityPayment</methodName>
<params>
<param>
<value>
<struct>
<member>
<name>vendor</name>
<value>
<string>GREENTELECOM</string>
</value>
</member>
<member>
<name>pin</name>
<value>
<string>6952</string>
</value>
</member>
<member>
<name>utilitycode</name>
<value>
<string>TOP</string>
</value>
</member>
<member>
<name>utilityref</name>
<value>
<string>'.$msisdn.'</string>
</value>
</member>
<member>
<name>transid</name>
<value>
<string>'.$refID.'</string>
</value>
</member>
<member>
<name>amount</name>
<value>
<string>'.$amount.'</string>
</value>
</member>
<member>
<name>msisdn</name>
<value>
<string>'.$msisdn.'</string>
</value>
</member>
</struct>
</value>
</param>
</params>
</methodCall>';
    $headers = array (
        "Content-type: text/xml;charset=\"utf-8\"",
        "Accept: text/xml",
        "Cache-Control: no-cache",
        "Pragma: no-cache",
        "Content-length: ".strlen($xml ) 
    );
    
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,"https://paypoint.selcommobile.com/api/selcom.pos.server.php");
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_TIMEOUT,100);
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
    curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
    $data = curl_exec($ch);
    $result = xmlrpc_decode($data);
    file_put_contents("result.txt",$data);	  
    if ($result ['result'] == "SUCCESS") {
        $trans ['ERROR_CODE'] = "000";
        $trans ['TXN_STATUS'] = 'SUCCESS';
        $trans ['TXN_DESCRIPTION'] = "Airtime Transaction processed Successfully";
        $trans ['TXN_THIRD_REFERENCE'] = $result ['reference'];
        $trans ['TXN_ID'] = $refID;
        return $trans;
      } else {
        $trans ['ERROR_CODE'] = '000';
        $trans ['TXN_STATUS'] = 'FAILED';
        $trans ['TXN_DESCRIPTION'] = $result['message'];
        $trans ['TXN_THIRD_REFERENCE'] = $result ['reference'];
        $trans ['TXN_ID'] = $result['transid'];
        return $trans;
      }
    
}
function TIGO($txn,$mobile,$amount)
        {
      $xml = '<?xml version="1.0" encoding="utf-8"?>'.'<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'.'<soap:Body>'.'<RechargeEx xmlns="http://charginggw.org/">'.'<TransactionID>'.$txn.'</TransactionID>'.'<Username>GREENTELECOM</Username>'.'<Password>GREENTELECOM@2016</Password>'.'<DealerNumber>719618106</DealerNumber>'.'<PIN>01111987</PIN>'.'<PhoneNumber>'.$mobile.'</PhoneNumber>'.'<Amount>'.$amount.'</Amount>'.'<ClientID>21</ClientID>'.'</RechargeEx></soap:Body>'.'</soap:Envelope>';
      $headers = array (
          "Content-type: text/xml;charset=\"utf-8\"",
          "Accept: text/xml",
          "Cache-Control: no-cache",
          "Pragma: no-cache",
          "SOAPAction: http://charginggw.org/RechargeEx",
          "Content-length: ".strlen($xml ) 
      );
      try{
      $ch = curl_init();
      curl_setopt($ch,CURLOPT_URL,"http://10.222.15.34/Transaction/epinservice.asmx");
      curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
      curl_setopt($ch,CURLOPT_TIMEOUT,100);
      curl_setopt($ch,CURLOPT_POST,true);
      curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
      curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
      $data = curl_exec($ch);
    
      $status = $this->get_string_between($data,"<RechargeExResult>","</RechargeExResult>");
      if ($status == "success") {
        $trans ['ERROR_CODE'] = "000";
        $trans ['TXN_STATUS'] = 'SUCCESS';
        $trans ['TXN_DESCRIPTION'] = "Airtime Transaction processed Successfully";
        $trans ['TXN_THIRD_REFERENCE'] = $txn;
        $trans ['TXN_ID'] = $txn;
        return $trans;
      } else {
        $trans ['ERROR_CODE'] = '001';
        $trans ['TXN_STATUS'] = 'FAILED';
        $trans ['TXN_DESCRIPTION'] = $status;
        $trans ['TXN_THIRD_REFERENCE'] = $txn;
        $trans ['TXN_ID'] = $txn;
        return $trans;
      }
    }
    catch(Exception $ex){
        $this->end = TRUE;
        return $e->getMessage();  
    }

        }

        function SMART($txn,$mobile,$amount){
          $xml = '<?xml version="1.0" encoding= "UTF-8"?>
<COMMAND>
<TXNID>'.$txn.'</TXNID>
<MSISDN>'.$mobile.'</MSISDN>
<AMOUNT>'.$amount.'</AMOUNT>
<COMPANYNAME>GREEN_SMART</COMPANYNAME>
</COMMAND>';
      $url = "http://172.25.200.37:9090/Mobpay/GreenAirtime";
      $headers = array (
          "Content-type: text/xml",
          "Content-length: ".strlen($xml ),
          "Connection: close" 
      );
      $ch = curl_init();
      curl_setopt($ch,CURLOPT_URL,$url);
      curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
      curl_setopt($ch,CURLOPT_TIMEOUT,10);
      curl_setopt($ch,CURLOPT_POST,true);
      curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
      curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
      $data = curl_exec($ch);
      $date = date("Y-m-dH");
      
      $errorCode = $this->get_string_between($data,"<ERRORCODE>","</ERRORCODE>");
      $status = $this->get_string_between($data,"<RESULT>","</RESULT>");
      $flag = $this->get_string_between($data,"<FALG>","</FLAG>");
      $desc = $this->get_string_between($data,"<CONTENT>","</CONTENT>");
      $txnid = $this->get_string_between($data,"<TXNID>","</TXNID>");
      $thirdParty = $this->get_string_between($data,"<REFID>","</REFID>");
      if ($errorCode == "error000") {
        $trans ['ERROR_CODE'] = $errorCode;
        $trans ['TXN_STATUS'] = 'SUCCESS';
        $trans ['TXN_DESCRIPTION'] = $desc;
        $trans ['TXN_THIRD_REFERENCE'] = $thirdParty;
        $trans ['TXN_ID'] = $txnid;
        return $trans;
      } else {
        $trans ['ERROR_CODE'] = $errorCode;
        $trans ['TXN_STATUS'] = 'FAILED';
        $trans ['TXN_DESCRIPTION'] = $desc;
        $trans ['TXN_THIRD_REFERENCE'] = $thirdParty;
        $trans ['TXN_ID'] = $txnid;
        return $trans;
      }
      return $trans;
      if (curl_errno($ch ))
        print curl_error($ch);
      else
        curl_close($ch);


        }

        function get_string_between($string, $start, $end) {
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0)
      return '';
      $ini += strlen($start);
      $len = strpos($string, $end, $ini) - $ini;
      return substr($string, $ini, $len);
  }
}
