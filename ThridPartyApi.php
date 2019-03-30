<?php

/*
 * Author : Arun.Pariyanampatta.
 * Organization : GREEN TELECOM
 * and open the template in the editor.
 */
error_reporting(0);
date_default_timezone_set('Africa/Dar_Es_Salaam');
class ThirdPartyAPI extends CI_Controller {
	var $balance,$company_msisdn,$unique;
	public function __construct() {
		parent::__construct();
		$this->load->model('EncashServices');
	}


public index(){
$jsonRequest = file_get_contents("php://input");
$json = json_decode($post);
$service 				= 	$json->SERVICE;
$agent_id 				= 	$json->AGENT_ID;
$this->company_msisdn	=	$json->AGENT_MSISDN;
$transactionReference	=	$json->REFERENCE_NUMBER;

if($service == "AIRTIME"){
$operator 				= 	$json->OPERATOR;
}	 

$amount 				=	$json->AMOUNT; 
$deviceNumber			=	$json->DEVICE_NUMBER;
$notificationmobile		=	$json->MOBILE;	
if($service == "LUKU"){

$errorCode = $result ['ERROR_CODE'];
			$status = $result ['TXN_STATUS'];
			$desc = $result ['TXN_DESCRIPTION'];
			$thirdParty = $result ['TXN_THIRD_REFERENCE'];
			$unit = $result['EXTRA'];
			$txnid = $result ['TXN_ID'];
			if ($status == "SUCCESS") {
				$token = $result['LUKU_TOKEN'];
				$data = array("TXN_STATUS"=>"SUCCESS","THIRDPARTY_REF"=>$thirdParty,"ERROR_CODE"=>$errorCode,"ERROR_DESC"=>$desc);
				$this->EncashServices->_updateTransaction($data,$txnid,$this->company_msisdn);
				$status = "SUCCESS";
				$this->end = TRUE;
				$career = "SELCOM-LUKU";
				$perc = $this->EncashServices->_getCommsionRate( $career);
				$percentage = $perc ['COMMISSION_PERCENTAGE'];
				$this->EncashServices->_setCommission($this->company_msisdn,$percentage,$amount);
				
				$this->EncashServices->_updateMerchantBalance($amount,"SELCOM");
				$data = array (
						"TXN_REFERENCE" => $txnid,
						"THIRDPARTY_REF" => $thirdParty,
						"REQUEST_TYPE" => "USSD",
						"AGENT_ID" => $agent_id,
						"AGENT_MSISDN" => $this->company_msisdn,
						"SERVICE_NAME"=>"LUKU",
						"CARD_NUMBER"=>$meterNumber,
						"CUSTOMER_MOBILE" => $mobile,
						"AMOUNT" => $amount,
						"TXN_STATUS" => "SUCCESS",
						"TOKEN_NUMBER"=>$token,
						"ERROR_CODE" => $errorCode,
						"COMMENTS" => $result['COMMENTS']
				);
				$op_balance = $this->_getElement("OP_BALANCE");
				$cl_balance = $op_balance - $amount;
				$this->EncashServices->_recordTransaction("luku_transaction",$data);
				$commissionamount = $amount * $percentage/100;
				$cl_balance = $cl_balance + $commissionamount;
				$data = array (
						"TXN_REFERENCE" => $txnid,
						"TXN_TYPE" => $luku_type,
						"REQUEST_TYPE" => "USSD",
						"AGENT_ID" => $agent_id,
						"AGENT_MSISDN" => $this->company_msisdn,
						"TXN_DEVICE_NUMBER" => $meterNumber,
						"OP_BALANCE"=>$op_balance,
						"CL_BALANCE"=>$cl_balance,
						"TXN_AMOUNT" => $amount,
						"TXN_STATUS" => "SUCCESS",
						"THIRDPARTY_REF" => $thirdParty,
						"ERROR_CODE" => $errorCode,
						"ERROR_DESC" => $result['COMMENTS'],
						"TXN_ROUTE" =>$route
				);

				$this->EncashServices->_recordTransaction("transaction_logs_success",$data);
				$commissionValue = $amount * $perc['COMMISSION_PERCENTAGE']/100;
				$commissionArray = array("AGENT_ID"=>$agent_id,"TXN_TYPE"=>"LUKU","TXN_AMOUNT"=>$amount,"TXN_COMMISSION_PERCENTAGE"=>$perc['COMMISSION_PERCENTAGE'],"TXN_COMMISSION_VALUE"=>$commissionValue);
				$this->EncashServices->_recordTransaction("agent_commission_success",$commissionArray);
				$sales = $amount;
				$this->EncashServices->_updateAgentAccount($cl_balance,$sales,$agent_id,"CLOSING_BALANCE");
				$sms = "TOKEN : ".$token. "\n";
				$sms .= $unit;
				$sms .= "Amount : ".$amount;
				$sms  = urlencode($sms);
				$msisdn  = $this->formatmobile($mobile);

				$selcom = array (
						"TXN_REFERENCE" => $txnid,
						"TXN_TYPE" => $luku_type,
						"REQUEST_TYPE" => "USSD",
						"AGENT_MSISDN" => $this->company_msisdn,
						"TXN_DEVICE_NUMBER" => $meterNumber,
						"TXN_AMOUNT" => $amount,
						"TXN_MOBILE_NUMBER"=>$mobile,
						"TXN_STATUS" => "SUCCESS",
						"THIRDPARTY_REF" => $thirdParty,
						"ERROR_CODE" => $errorCode,
						"ERROR_DESC" => $desc,
						"TXN_ROUTE" =>$route
				);
						$this->EncashServices->_recordTransaction("transaction_logs_selcom",$selcom);
				$sms = "TOKEN : ".$token. "\n";
				$sms .= $unit ."\n";
				$sms  = urlencode($sms);
                    $msisdn  = $this->company_msisdn;
				file_get_contents("http://192.168.168.2:13013/cgi-bin/sendsms?username=airtelTX&password=greentx&to=$msisdn&text=$sms&from=15670&dlr-mask=31");		
				return "Luku Transaction has been processed Successfully . TOKEN :".$token;
			}
			else{
				$status = "FAILED";
				$this->end = TRUE;
				$data = array("TXN_STATUS"=>"FAILED","THIRDPARTY_REF"=>$thirdParty,"ERROR_CODE"=>$errorCode,"ERROR_DESC"=>$desc);
				$this->EncashServices->_updateTransaction($data,$txnid,$this->company_msisdn);
				$this->EncashServices->_rollbackTransaction($this->company_msisdn,$this->_getElement("amount"));
				return "Failed Please Try later";
		
			}


}
if($service == "AIRTIME"){
$this->_processAirtime($operator,$deviceNumber,$transactionReference,$amount);
}

if($service == "DTH"){

}
}
function _processLUKU($meterNumber,$amount,$mobile,$refID) {
		
			$luku_type = "LUKU";
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
<string>'.$luku_type.'</string>
</value>
</member>
<member>
<name>utilityref</name>
<value>
<string>'.$meterNumber.'</string>
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
<string>'.$mobile.'</string>
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
		$date = date("YmdHi");
		$date = date("YmdHi");	
		$xmlArray = xmlrpc_decode($data);
		if ($xmlArray ['result'] == "SUCCESS") {
			$trans ['ERROR_CODE'] = "000";
			$trans ['TXN_STATUS'] = 'SUCCESS';
			$trans ['TXN_DESCRIPTION'] = "Luku Transaction processed Successfully";
			$trans ['TXN_THIRD_REFERENCE'] = $xmlArray ['reference'];
			$trans ['TXN_ID'] = $refID;
			$trans ['COMMENTS'] = $xmlArray ['message'];
			$trans ['LUKU_TOKEN'] = $xmlArray ['token'];
			$trans['EXTRA'] = "Units : ".$xmlArray['units'];
			return $trans;
		} else {
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,"https://paypoint.selcommobile.com/api/selcom.pos.server.php");
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_TIMEOUT,10);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
		curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
		$data = curl_exec($ch);
		$date = date("YmdHi");
		$date = date("YmdHi");
		$xmlArray = xmlrpc_decode($data);
		if ($xmlArray ['result'] == "SUCCESS") {
			$token = $xmlArray['message'];
			$unit  = $xmlArray['message'];
			$token = $this->get_string_between($token, "Token", "Cost");
			$token = str_replace(" ","",$token);
			$unit = $this->get_string_between($token, "Units", "Token");
			$unit = str_replace(" ","",$unit);
			$retry ['ERROR_CODE'] = "000";
			$retry ['TXN_STATUS'] = 'SUCCESS';
			$retry ['TXN_DESCRIPTION'] = "Luku Transaction processed Successfully";
			$retry ['TXN_THIRD_REFERENCE'] = $xmlArray ['reference'];
			$retry ['TXN_ID'] = $refID;
			$retry ['COMMENTS'] = $xmlArray ['message'];
			$retry ['LUKU_TOKEN'] = $token;
			$retry['EXTRA'] = "Units : ".$unit;
			return $trans;
		}
		else{
			$retry ['ERROR_CODE'] = '001';
			$retry ['TXN_STATUS'] = 'FAILED';
			$retry ['TXN_DESCRIPTION'] = 'FAILED';
			$retry ['TXN_THIRD_REFERENCE'] = $xmlArray ['reference'];
			$retry ['COMMENTS'] = $xmlArray ['message'];
			$retry ['TXN_ID'] = $refID;
		}
		return $trans;
		}
	}
	


function _processAirtime($operator,$mobile,$txn,$amount){
	
		$type =$this->_getElement("AIRTIME_TYPE" );	
		if($operator =="SMART"){
			if($type == "AIRTIME_BALANCE"){

				$service = "SMART-AIRTIME";
			}
				else{
					$service = "SMART-BUNDLE";
				}
			}
		else if($operator=="TIGO"){
			$service = "SELCOM-AIRTIME";
		}
		else if($operator =="HALOTEL"){
			$service = "HALOTEL";
		}
		else if($operator == "AIRTEL"){
			$service = "AIRTEL";
		}
		else{
			$service = "SELCOM-AIRTIME";
		}

$result = $this->EncashServices->getAPIUrl($service);
if($service == "HALOTEL"){
$process_code   = "000000";
$client 		= "greentel";
$date 			= date("Ymdhis");
$trace  		= "0".$date;
$signelements   = $mobile.$process_code.$amount.$date.$trace.$client;
$signature = $this->generatesignature($signelements);
$data = $result['URL_PARAMETERS'];
$url = $result['API_URL'];
$data = $this->replace_string('{$mobile}',$mobile,$data);
		$data = $this->replace_string('{$amount}',$amount,$data); 
		$data = $this->replace_string('{$trace}',$trace,$data); 
		$data = $this->replace_string('{$date}',$date,$data); 
		$data = $this->replace_string('{$signature}',$signature,$data);
$post = htmlentities($data);
$xml = '<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://ws.vpg.viettel.com/">
<SOAP-ENV:Body>
<ns1:send>
<data>'.$post.'</data>
</ns1:send>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>';

}
else if($service == "AIRTEL"){
$url = $result['API_URL'];
$xml = $result['URL_PARAMETERS'];
		$url = $result['API_URL'];
		$xml = $this->replace_string('{$mobile}',$mobile,$xml);
		$xml = $this->replace_string('{$amount}',$amount,$xml); 
		$xml = $this->replace_string('{$txnid}',$txn,$xml);		
}

else{
		$xml = $result['URL_PARAMETERS'];
		$url = $result['API_URL'];
		$xml = $this->replace_string('{$mobile}',$mobile,$xml);
		$xml = $this->replace_string('{$amount}',$amount,$xml); 
		$xml = $this->replace_string('{$txnid}',$txn,$xml); 

}



// if($operator == "TIGO"){
// 	$headers = array (
// 					"Content-type: text/xml;charset=\"utf-8\"",
// 					"Accept: text/xml",
// 					"Cache-Control: no-cache",
// 					"Pragma: no-cache",
// 					"SOAPAction: http://charginggw.org/RechargeEx",
// 					"Content-length: ".strlen($xml ) 
// 			);
// }
// else 
if($operator == "HALOTEL"){
	$headers = array (
        "Content-type: text/xml;charset=\"utf-8\"",
        "Accept: text/xml",
        "Cache-Control: no-cache",
        "Pragma: no-cache",
        "Content-length: ".strlen($xml )
);
}
else if($operator == "AIRTEL"){
$headers = array (
				"Content-type: application/xml;charset=\"utf-8\"",
				"Accept: application/xml",
				"Connection: Keep-Alive",	
				"Cache-Control: no-cache",
				"Pragma: no-cache",
				"Content-length:".strlen($xml ) ,
				"Authorization: LOGIN=pretups&PASSWORD=ed8c476255e5fdfff09bd75c7d10b180&REQUEST_GATEWAY_CODE=GRNT&REQUEST_GATEWAY_TYPE=EXTGW&SERVICE_PORT=190&SOURCE_TYPE=EXT"
		);

}
else if($operator == "SMART"){
	$headers = array (
					"Content-type: text/xml",
					"Content-length: ".strlen($xml ),
					"Connection: close" 
			);
}
else{
$headers = array (
				"Content-type: text/xml;charset=\"utf-8\"",
				"Accept: text/xml",
				"Cache-Control: no-cache",
				"Pragma: no-cache",
				"Content-length: ".strlen($xml ) 
		);
}

		    $ch = curl_init();
			curl_setopt($ch,CURLOPT_URL,$url);
			if($service =="AIRTEL"){
				 curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                 curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			}
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch,CURLOPT_TIMEOUT,100);
			curl_setopt($ch,CURLOPT_POST,true);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
			curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
			$data = curl_exec($ch);
			$date = date("Y-m-dH");
			if($operator == "SMART"){
			$filename = "logs/responses/AIRTIME/SMART".date("YmdH").".txt";
			file_put_contents($filename, $data."\n",FILE_APPEND|LOCK_EX);//this will store the responses
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
}
			// else if($operator == "TIGO"){
			// $filename = "logs/responses/AIRTIME/TIGO".date("YmdH").".txt";
			// file_put_contents($filename, $data."\n",FILE_APPEND|LOCK_EX);//this will store the responses
			// $status = $this->get_string_between($data,"<RechargeExResult>","</RechargeExResult>");
			// if ($status == "success") {
			// 	$trans ['ERROR_CODE'] = "000";
			// 	$trans ['TXN_STATUS'] = 'SUCCESS';
			// 	$trans ['TXN_DESCRIPTION'] = "Airtime Transaction processed Successfully";
			// 	$trans ['TXN_THIRD_REFERENCE'] = $txn;
			// 	$trans ['TXN_ID'] = $txn;
			// 	return $trans;
			// } else {
			// 	$trans ['ERROR_CODE'] = '001';
			// 	$trans ['TXN_STATUS'] = 'FAILED';
			// 	$trans ['TXN_DESCRIPTION'] = $status;
			// 	$trans ['TXN_THIRD_REFERENCE'] = $txn;
			// 	$trans ['TXN_ID'] = $txn;
			// 	return $trans;
			// }

			// }
			else if($operator =="AIRTEL"){
			$filename = "logs/responses/AIRTIME/AIRTEL".date("YmdH").".txt";
			file_put_contents($filename, $data."\n",FILE_APPEND|LOCK_EX);
			$status = $this->get_string_between($data,"<TXNSTATUS>","</TXNSTATUS>");
			if($status == "200"){
				$trans ['ERROR_CODE'] = "000";
				$trans ['TXN_STATUS'] = 'SUCCESS';
				$trans ['TXN_DESCRIPTION'] = "Airtime Transaction processed Successfully";
				$trans ['TXN_THIRD_REFERENCE'] = $txn;
				$trans ['TXN_ID'] = $txn;
				return $trans;
			}
			else{
				$trans ['ERROR_CODE'] = '001';
				$trans ['TXN_STATUS'] = 'FAILED';
				$trans ['TXN_DESCRIPTION'] = $status;
				$trans ['TXN_THIRD_REFERENCE'] = $txn;
				$trans ['TXN_ID'] = $txn;
				return $trans;	
			}

			}

			else if($operator == "HALOTEL"){
			$filename = "logs/responses/AIRTIME/HALOTEL".date("YmdH").".txt";
			file_put_contents($filename, $data."\n",FILE_APPEND|LOCK_EX);
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
			else{
			$result = xmlrpc_decode($data);

			    /*$trans ['ERROR_CODE'] = "000";
				$trans ['TXN_STATUS'] = 'SUCCESS';
				$trans ['TXN_DESCRIPTION'] = "Airtime Transaction processed Successfully";
				$trans ['TXN_THIRD_REFERENCE'] = $txn;
				$trans ['TXN_ID'] = $txn;
				return $trans;*/

			if ($result ['result'] == "SUCCESS") {
				$trans ['ERROR_CODE'] = "000";
				$trans ['TXN_STATUS'] = 'SUCCESS';
				$trans ['TXN_DESCRIPTION'] = "Airtime Transaction processed Successfully";
				$trans ['TXN_THIRD_REFERENCE'] = $result ['reference'];
				$trans ['TXN_ID'] = $txn;
				return $trans;
			} else {// for Selcom repeat

				$filename = "logs/requests/AIRTIME/REPEAT".$operator.date("YmdH").".txt";
				file_put_contents($filename, $xml."\n",FILE_APPEND|LOCK_EX);
		    	$ch = curl_init();
				curl_setopt($ch,CURLOPT_URL,$url);
				curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
				curl_setopt($ch,CURLOPT_TIMEOUT,10);
				curl_setopt($ch,CURLOPT_POST,true);
				curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
				curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
				$data = curl_exec($ch);
				$date = date("Y-m-dH");
				$result = xmlrpc_decode($data);
				if ($result ['result'] == "SUCCESS") {
				$trans ['ERROR_CODE'] = "000";
				$trans ['TXN_STATUS'] = 'SUCCESS';
				$trans ['TXN_DESCRIPTION'] = $result['message'];
				$trans ['TXN_THIRD_REFERENCE'] = $txn;
				$trans ['TXN_ID'] = $txn;
				return $trans;
			}
			else{	
				$trans ['ERROR_CODE'] = '000';
				$trans ['TXN_STATUS'] = 'FAILED';
				$trans ['TXN_DESCRIPTION'] = $result['message'];
				$trans ['TXN_THIRD_REFERENCE'] = $result ['reference'];
				$trans ['TXN_ID'] = $result['transid'];
				return $trans;
			}
			}		
			}
			
	}
}