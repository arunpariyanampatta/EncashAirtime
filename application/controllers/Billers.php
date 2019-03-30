<?php

/*
 * Author : Arun.Pariyanampatta.
 * Organization : GREEN TELECOM
 * and open the template in the editor.
 */
error_reporting(0);
date_default_timezone_set('Africa/Dar_Es_Salaam');
class Billers extends CI_Controller {
	var $balance,$agent_id,$agentMsisdn,$company_name,$unique,$requestmode,$operator;

	public function __construct() {
		parent::__construct();
		$this->load->model('EncashServices'); //This file contanis Main-Menu and its operations
		$this->load->model('CorporateServices','CorporateServices');
	}



function index(){
$post = file_get_contents("php://input");
$json = json_decode($post,TRUE);
file_put_contents("post-data.txt",$post,FILE_APPEND|LOCK_EX);
$this->operator = $json['OPERATOR'];
$notifymsisdn   = $json['NOTIFY_NUMBER'];//
$device         = $json['DEVICE_NUMBER'];
$amount         = $json['AMOUNT'];
$service        = $json['SERVICE'];
$txnReference   = $json['TXN_REFERENCE'];
$UUID           = $json['UniqueID'];
$this->agentMsisdn = $json['AGENT_MSISDN'];
$this->requestmode = $json['REQUEST_MODE'];
$ackRef       = $json['ACK_REF'];


$id = $this->EncashServices->_getAgentID($this->agentMsisdn);
$this->agent_id = $id['ID'];
if($service == "AIRTIME"){
  $this->operator = $json['OPERATOR'];
  $this->_processAirtime($txnReference,$device,$amount,$this->operator,$service,$ackRef,$UUID);
}

if($service == "NHC"){

$txnDat = $this->_processNHC($device, $amount, $notifymsisdn, $txnReference, $ackRef, $UUID);
 $result = $txnDat;


 if ($txnDat['TXN_STATUS'] == 'SUCCESS') {//check if the transaction is success

                $opBAL = $this->EncashServices->getBalance($this->agentMsisdn); // get the available balance
                $this->EncashServices->updateBalance($this->agentMsisdn, $amount); // update the balance

                $desc = $txnDat['TXN_STATUS'];
                $thirdParty = $txnDat['TXN_THIRD_REFERENCE'];
                $data = array("TXN_STATUS" => "SUCCESS", "THIRDPARTY_REF" => $thirdParty, "ERROR_CODE" => $errorCode, "ERROR_DESC" => $desc);
                $this->EncashServices->_updateTransaction($data, $txnReference, $this->agentMsisdn);
                $clBalance = $opBAL['TOTAL_AVAIL_BALANCE'] - $amount;



                $txnSuccess = array(
                    "TXN_REFERENCE" => $txnReference,
                    "TXN_TYPE" => "NHC",
                    "OP_BALANCE" => $opBAL['TOTAL_AVAIL_BALANCE'],
                    "TXN_ROUTE" => "SELCOM",
                    "CL_BALANCE" => $clBalance,
                    "REQUEST_TYPE" => $this->requestmode,
                    "AGENT_ID" => $this->agent_id,
                    "AGENT_MSISDN" => $this->agentMsisdn,
                    "TXN_DEVICE_NUMBER" => $device,
                    "TXN_AMOUNT" => $amount,
                    "TXN_STATUS" => "SUCCESS",
                    "THIRDPARTY_REF" => $txnDat['TXN_THIRD_REFERENCE'],
                    "ERROR_CODE" => "0000",
                    "ERROR_DESC" => "SUCCESS",
                    "ACK_REF" => $ackRef,
                    "TXN_EXTRA" => $UUID
                );
                $merchant = "SELCOM-NHC";

                // $perc = $this->EncashServices->_getCommsionRate($merchant); // get commission for the tranasction
                // $percentage = $perc ['COMMISSION_PERCENTAGE'];
                // $commissionValue = $amount * $percentage / 100;
                // $commissionArray = array("AGENT_ID" => $this->agent_id, "TXN_TYPE" => "DAWASCO", "TXN_AMOUNT" => $amount, "TXN_COMMISSION_PERCENTAGE" => $percentage, "TXN_COMMISSION_VALUE" => $commissionValue);
                // $this->EncashServices->_recordTransaction("agent_commission_success", $commissionArray);// record the commission
                
                
                // $this->EncashServices->_setCommission($this->agentMsisdn, $percentage, $amount); //update the commission balance;
                $op_balance = $opBAL['TOTAL_AVAIL_BALANCE'];
                $cl_balance = $op_balance - $amount;
                // $commissionamount = $amount * $percentage / 100;
                // $cl_balance = $cl_balance + $commissionamount;
                $this->EncashServices->_recordTransaction("transaction_logs_success", $txnSuccess);
                $this->EncashServices->_updateMerchantBalance($amount, "SELCOM");

                $sales = $amount;
                $this->EncashServices->_updateAgentAccount($cl_balance, $sales, $this->agent_id, "CLOSING_BALANCE");

                $txnLog = array(
                    "TXN_REFERENCE" => $txnReference,
                    "TXN_TYPE" => "NHC",
                    "REQUEST_TYPE" => $this->requestmode,
                    "AGENT_MSISDN" => $this->agentMsisdn,
                    "TXN_DEVICE_NUMBER" => $device,
                    "TXN_AMOUNT" => $amount,
                    "TXN_STATUS" => "SUCCESS",
                    "THIRDPARTY_REF" => $txnDat['TXN_THIRD_REFERENCE'],
                    "ERROR_CODE" => "0000",
                    "ERROR_DESC" => "SUCCESS",
                    "TXN_ROUTE" => "SELCOM",
                    "ACK_REF" => $ackRef,
                    "TXN_EXTRA" => $UUID
                );

                $table = "transaction_logs_selcom";
                $this->EncashServices->_recordTransaction($table, $txnLog);

                $dawasco_data = array(
                    "TXN_REFERENCE" => $txnReference,
                    "THIRDPARTY_REF" => $txnDat['TXN_THIRD_REFERENCE'],
                    "REQUEST_TYPE" => $this->requestmode,
                    "AGENT_ID" => $this->agent_id,
                    "AGENT_MSISDN" => $this->agentMsisdn,
                    "SERVICE_NAME" => "NHC",
                    "CARD_NUMBER" => $device,
                    "CUSTOMER_MOBILE" => $notifymsisdn,
                    "AMOUNT" => $amount,
                    "TXN_STATUS" => "SUCCESS",
                    "ACK_REF"=>$ackRef,
                    "TXN_EXTRA"=>$UUID,
                    "ERROR_CODE" => $errorCode,
                    "COMMENTS" => $result['COMMENTS']
                );
                $this->EncashServices->_recordTransaction("nhc_transaction", $nhc);

                if ($this->requestmode == "THIRDPARTY" || $this->requestmode == "WEB") {// a callback
                    $serviceID = "NHCTANZANIA";

                    $date = date("Y-m-d H:i:s");
                    $response = '{"Response": {"StatusCode" : "0","StatusMessage": "SUCCESS","NotifyMsisdn" : "'.$notifymsisdn.'","ThirdPartyTransactionRef": "' . $ackRef . '","UUID":"' . $UUID . '","TransactionDetails" : "NHC Payment been done successfully","ServiceID":"' . $serviceID . '","TransactionDate" : "' . $date . '"}}';


                    $callbackUrl = $this->EncashServices->getCallbackUrl($this->agent_id);
                    $url = $callbackUrl['CALLBACK_URL'];
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 100);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
                    // curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
                    $data = curl_exec($ch);
                }
            }


else { //if it is failed

                $serviceID = "NHCTANZANIA";
                $date = date("Y-m-d H:i:s");
                $data = array("TXN_STATUS" => "FAILED", "THIRDPARTY_REF" => $thirdParty, "ERROR_CODE" => $errorCode, "ERROR_DESC" => "FAILED");
                $this->EncashServices->_updateTransaction($data, $txnReference, $this->agentMsisdn);
                if ($this->requestmode == "THIRDPARTY" || $this->requestmode == "WEB") {
                    $response = '{"Response": {"StatusCode" : "010","StatusMessage": "FAILED","NotifyMsisdn" : "'.$notifymsisdn.'","ThirdPartyTransactionRef": "' . $ackRef . '","UUID":"' . $UUID . '","TransactionDetails" : "Transaction has been failed","ServiceID":"' . $serviceID . '","TransactionDate":"' . $date . '"}}';

                    $callbackUrl = $this->EncashServices->getCallbackUrl($this->agent_id);
                    $url = $callbackUrl['CALLBACK_URL'];
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 100);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
                    $data = curl_exec($ch);
                }
            }


}

  if ($service == "DAWASCO") {



            $txnDat = $this->_processDAWASCO($device, $amount, $notifymsisdn, $txnReference, $ackRef, $UUID);


            $result = $txnDat;


            if ($txnDat['TXN_STATUS'] == 'SUCCESS') {//check if the transaction is success

                $opBAL = $this->EncashServices->getBalance($this->agentMsisdn); // get the available balance
                $this->EncashServices->updateBalance($this->agentMsisdn, $amount); // update the balance

                $desc = $txnDat['TXN_STATUS'];
                $thirdParty = $txnDat['TXN_THIRD_REFERENCE'];
                $data = array("TXN_STATUS" => "SUCCESS", "THIRDPARTY_REF" => $thirdParty, "ERROR_CODE" => $errorCode, "ERROR_DESC" => $desc);
                $this->EncashServices->_updateTransaction($data, $txnReference, $this->agentMsisdn);
                $clBalance = $opBAL['TOTAL_AVAIL_BALANCE'] - $amount;



                $txnSuccess = array(
                    "TXN_REFERENCE" => $txnReference,
                    "TXN_TYPE" => "DAWASCO",
                    "OP_BALANCE" => $opBAL['TOTAL_AVAIL_BALANCE'],
                    "TXN_ROUTE" => "SELCOM",
                    "CL_BALANCE" => $clBalance,
                    "REQUEST_TYPE" => $this->requestmode,
                    "AGENT_ID" => $this->agent_id,
                    "AGENT_MSISDN" => $this->agentMsisdn,
                    "TXN_DEVICE_NUMBER" => $device,
                    "TXN_AMOUNT" => $amount,
                    "TXN_STATUS" => "SUCCESS",
                    "THIRDPARTY_REF" => $txnDat['TXN_THIRD_REFERENCE'],
                    "ERROR_CODE" => "0000",
                    "ERROR_DESC" => "SUCCESS",
                    "ACK_REF" => $ackRef,
                    "TXN_EXTRA" => $UUID
                );
                $merchant = "SELCOM-DAWASCO";

                $perc = $this->EncashServices->_getCommsionRate($merchant); // get commission for the tranasction
                $percentage = $perc ['COMMISSION_PERCENTAGE'];
                $commissionValue = $amount * $percentage / 100;
                $commissionArray = array("AGENT_ID" => $this->agent_id, "TXN_TYPE" => "DAWASCO", "TXN_AMOUNT" => $amount, "TXN_COMMISSION_PERCENTAGE" => $percentage, "TXN_COMMISSION_VALUE" => $commissionValue);
                $this->EncashServices->_recordTransaction("agent_commission_success", $commissionArray);// record the commission
                
                
                $this->EncashServices->_setCommission($this->agentMsisdn, $percentage, $amount); //update the commission balance;
                $op_balance = $opBAL['TOTAL_AVAIL_BALANCE'];
                $cl_balance = $op_balance - $amount;
                $commissionamount = $amount * $percentage / 100;
                $cl_balance = $cl_balance + $commissionamount;
                $this->EncashServices->_recordTransaction("transaction_logs_success", $txnSuccess);
                $this->EncashServices->_updateMerchantBalance($amount, "SELCOM");

                $sales = $amount;
                $this->EncashServices->_updateAgentAccount($cl_balance, $sales, $this->agent_id, "CLOSING_BALANCE");

                $txnLog = array(
                    "TXN_REFERENCE" => $txnReference,
                    "TXN_TYPE" => "DTH-" . $this->operator,
                    "REQUEST_TYPE" => $this->requestmode,
                    "AGENT_MSISDN" => $this->agentMsisdn,
                    "TXN_DEVICE_NUMBER" => $device,
                    "TXN_AMOUNT" => $amount,
                    "TXN_STATUS" => "SUCCESS",
                    "THIRDPARTY_REF" => $txnDat['TXN_THIRD_REFERENCE'],
                    "ERROR_CODE" => "0000",
                    "ERROR_DESC" => "SUCCESS",
                    "TXN_ROUTE" => "SELCOM",
                    "ACK_REF" => $ackRef,
                    "TXN_EXTRA" => $UUID
                );

                $table = "transaction_logs_selcom";
                $this->EncashServices->_recordTransaction($table, $txnLog);

                $dawasco_data = array(
                    "TXN_REFERENCE" => $txnReference,
                    "THIRDPARTY_REF" => $txnDat['TXN_THIRD_REFERENCE'],
                    "REQUEST_TYPE" => $this->requestmode,
                    "AGENT_ID" => $this->agent_id,
                    "AGENT_MSISDN" => $this->agentMsisdn,
                    "SERVICE_NAME" => "DAWASCO",
                    "CARD_NUMBER" => $device,
                    "CUSTOMER_MOBILE" => $notifymsisdn,
                    "AMOUNT" => $amount,
                    "TXN_STATUS" => "SUCCESS",
                    "ACK_REF"=>$ackRef,
                    "TXN_EXTRA"=>$UUID,
                    "ERROR_CODE" => $errorCode,
                    "COMMENTS" => $result['COMMENTS']
                );
                $this->EncashServices->_recordTransaction("dawasco_transaction", $dawasco_data);

                if ($this->requestmode == "THIRDPARTY" || $this->requestmode == "WEB") {// a callback
                    $serviceID = "DAWASCOTANZANIA";

                    $date = date("Y-m-d H:i:s");
                    $response = '{"Response": {"StatusCode" : "0","StatusMessage": "SUCCESS","NotifyMsisdn" : "'.$notifymsisdn.'","ThirdPartyTransactionRef": "' . $ackRef . '","UUID":"' . $UUID . '","TransactionDetails" : "DAWASCO Recharge has been done successfully","ServiceID":"' . $serviceID . '","TransactionDate" : "' . $date . '"}}';


                    $callbackUrl = $this->EncashServices->getCallbackUrl($this->agent_id);
                    $url = $callbackUrl['CALLBACK_URL'];
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 100);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
                    // curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
                    $data = curl_exec($ch);
                }
            } else { //if it is failed

                $serviceID = "DAWASCOTANZANIA";
                $date = date("Y-m-d H:i:s");
                $data = array("TXN_STATUS" => "FAILED", "THIRDPARTY_REF" => $thirdParty, "ERROR_CODE" => $errorCode, "ERROR_DESC" => "FAILED");
                $this->EncashServices->_updateTransaction($data, $txnReference, $this->agentMsisdn);
                if ($this->requestmode == "THIRDPARTY" || $this->requestmode == "WEB") {
                    $response = '{"Response": {"StatusCode" : "010","StatusMessage": "FAILED","NotifyMsisdn" : "'.$notifymsisdn.'","ThirdPartyTransactionRef": "' . $ackRef . '","UUID":"' . $UUID . '","TransactionDetails" : "Transaction has been failed","ServiceID":"' . $serviceID . '","TransactionDate":"' . $date . '"}}';

                    $callbackUrl = $this->EncashServices->getCallbackUrl($this->agent_id);
                    $url = $callbackUrl['CALLBACK_URL'];
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 100);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
                    $data = curl_exec($ch);
                }
            }
        }
if($service == "DTH"){


$txnDat = $this->_processDTH($device,$amount,$notifymsisdn,$txnReference,$this->operator,$ackRef,$UUID);
$result = $txnDat;

if($txnDat['TXN_STATUS']=='SUCCESS'){ 

$voucher = $txnDat['VOUCHER'];
$transactionDetails = "CARDNUMBER|".$device."|AMOUNT|".$amount."|VOUCHER|".$voucher;
       $opBAL =  $this->EncashServices->getBalance($this->agentMsisdn);
        $this->EncashServices->updateBalance($this->agentMsisdn,$amount);
        $desc = $txnDat['TXN_STATUS'];
        $thirdParty = $txnDat['TXN_THIRD_REFERENCE'];
        $data = array("TXN_STATUS"=>"SUCCESS","THIRDPARTY_REF"=>$thirdParty,"ERROR_CODE"=>$errorCode,"ERROR_DESC"=>$desc);
        $this->EncashServices->_updateTransaction($data,$txnReference, $this->agentMsisdn);
        $clBalance = $opBAL['TOTAL_AVAIL_BALANCE'] - $amount;
        $txnSuccess = array (
        "TXN_REFERENCE" => $txnReference,
        "TXN_TYPE" => "DTH-".$this->operator,
        "OP_BALANCE"=>$opBAL['TOTAL_AVAIL_BALANCE'],
        "TXN_ROUTE"=>"SELCOM",
        "CL_BALANCE"=>$clBalance,
        "REQUEST_TYPE" => $this->requestmode,
            "AGENT_ID" => $this->agent_id,
            "AGENT_MSISDN" => $this->agentMsisdn,
            "TXN_DEVICE_NUMBER" => $device,
            "TXN_AMOUNT" => $amount,
            "TXN_STATUS" => "SUCCESS",
            "THIRDPARTY_REF" => $txnDat['TXN_THIRD_REFERENCE'],
            "ERROR_CODE" => "0000",
            "ERROR_DESC" => "SUCCESS",
            "ACK_REF"=>$ackRef,
            "TXN_EXTRA"=>$UUID

        );
$merchant = "SELCOM-".$this->operator;  
$perc = $this->EncashServices->_getCommsionRate($merchant);
        $percentage = $perc ['COMMISSION_PERCENTAGE'];
        $this->EncashServices->_setCommission($this->agentMsisdn,$percentage,$amount);
        $op_balance = $opBAL['TOTAL_AVAIL_BALANCE'];
        $cl_balance = $op_balance - $amount;
        $commissionamount = $amount * $percentage/100;
        $cl_balance = $cl_balance + $commissionamount;
        $this->EncashServices->_recordTransaction("transaction_logs_success",$txnSuccess);
        $this->EncashServices->_updateMerchantBalance($amount,"SELCOM");

$sales= $amount;
        $this->EncashServices->_updateAgentAccount($cl_balance,$sales,$this->agent_id,"CLOSING_BALANCE");

    $txnLog = array (
            "TXN_REFERENCE" => $txnReference,
            "TXN_TYPE" => "DTH-".$this->operator,
            "REQUEST_TYPE" => $this->requestmode,
            "AGENT_MSISDN" => $this->agentMsisdn,
            "TXN_DEVICE_NUMBER" => $device,
            "TXN_AMOUNT" => $amount,
            "TXN_STATUS" => "SUCCESS",
            "THIRDPARTY_REF" => $txnDat['TXN_THIRD_REFERENCE'],
            "ERROR_CODE" => "0000",
            "ERROR_DESC" => "SUCCESS",
            "TXN_ROUTE" =>"SELCOM",
            "ACK_REF"=>$ackRef,
            "TXN_EXTRA"=>$UUID
        );

$table = "transaction_logs_selcom";
$this->EncashServices->_recordTransaction($table,$txnLog);

$dth_data = array (
            "TXN_REFERENCE" => $txnReference,
            "THIRDPARTY_REF" => $txnDat['TXN_THIRD_REFERENCE'],
            "REQUEST_TYPE" => $this->requestmode,
            "AGENT_ID" => $this->agent_id,
            "AGENT_MSISDN" => $this->agentMsisdn,
            "SERVICE_NAME"=>"DTH-".$this->operator,
            "CARD_NUMBER"=>$device,
            "CUSTOMER_MOBILE" => $notifymsisdn,
            "AMOUNT" => $amount,
            "TXN_STATUS" => "SUCCESS",
            "TOKEN_NUMBER"=>$voucher,
            "ERROR_CODE" => $errorCode,
            "COMMENTS" => $result['COMMENTS']
        );
$this->EncashServices->_recordTransaction("dth_transaction",$dth_data);

if($this->requestmode=="THIRDPARTY"||$this->requestmode=="WEB"){
 $serviceID = $this->operator."TANZANIA";

$date = date("Y-m-d H:i:s");
 $response = '{"Response": {"StatusCode" : "0","StatusMessage": "SUCCESS","NotifyMsisdn" : "'.$notifymsisdn.'","ThirdPartyTransactionRef": "'.$ackRef.'","UUID":"'.$UUID.'","TransactionDetails" : "'.$transactionDetails.'","ServiceID":"'.$serviceID.'","TransactionDate" : "'.$date.'"}}';


$callbackUrl =  $this->EncashServices->getCallbackUrl($this->agent_id);
   $url = $callbackUrl['CALLBACK_URL'];
 $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_TIMEOUT,100);
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$response);
   // curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
    $data = curl_exec($ch);
}

}

else{
 $serviceID = $this->operator."TANZANIA";
$date = date("Y-m-d H:i:s");
         $data = array("TXN_STATUS"=>"FAILED","THIRDPARTY_REF"=>$thirdParty,"ERROR_CODE"=>$errorCode,"ERROR_DESC"=>"FAILED");
        $this->EncashServices->_updateTransaction($data,$txnReference, $this->agentMsisdn);
        if($this->requestmode=="THIRDPARTY"||$this->requestmode=="WEB"){
       $response = '{"Response": {"StatusCode" : "010","StatusMessage": "FAILED","NotifyMsisdn" : "'.$notifymsisdn.'" ,"ThirdPartyTransactionRef": "'.$ackRef.'","UUID":"'.$UUID.'","TransactionDetails" : "Transaction has been failed","ServiceID":"'.$serviceID.'","TransactionDate":"'.$date.'"}}'; 

$callbackUrl =  $this->EncashServices->getCallbackUrl($this->agent_id);
   $url = $callbackUrl['CALLBACK_URL'];
 $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_TIMEOUT,100);
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$response);

    $data = curl_exec($ch);

     }


}



}

if($service == "LUKU"){
  $txnDat = $this->_processLUKU($device,$amount,$notifymsisdn,$txnReference,"LUKU",$ackRef,$UUID);
  $result = $txnDat;
 if($txnDat['TXN_STATUS']=='SUCCESS'){   
        $token = $txnDat['LUKU_TOKEN'];    
        $opBAL =  $this->EncashServices->getBalance($this->agentMsisdn);
        $this->EncashServices->updateBalance($this->agentMsisdn,$amount);
        $desc = $txnDat['TXN_STATUS'];
        $thirdParty = $txnDat['TXN_THIRD_REFERENCE'];
        $data = array("TXN_STATUS"=>"SUCCESS","THIRDPARTY_REF"=>$thirdParty,"ERROR_CODE"=>$errorCode,"ERROR_DESC"=>$desc);
        $this->EncashServices->_updateTransaction($data,$txnReference, $this->agentMsisdn);
        $clBalance = $opBAL['TOTAL_AVAIL_BALANCE'] - $amount;
        $txnSuccess = array (
        "TXN_REFERENCE" => $txnReference,
        "TXN_TYPE" => "LUKU",
        "OP_BALANCE"=>$opBAL['TOTAL_AVAIL_BALANCE'],
        "CL_BALANCE"=>$clBalance,
        "TXN_ROUTE"=>"SELCOM",
        "REQUEST_TYPE" => $this->requestmode,
            "AGENT_ID" => $this->agent_id,
            "AGENT_MSISDN" => $this->agentMsisdn,
            "TXN_DEVICE_NUMBER" => $device,
            "TXN_AMOUNT" => $amount,
            "TXN_STATUS" => "SUCCESS",
            "THIRDPARTY_REF" => $txnDat['TXN_THIRD_REFERENCE'],
            "ERROR_CODE" => "0000",
            "ERROR_DESC" => "SUCCESS",
            "ACK_REF"=>$ackRef,
            "TXN_EXTRA"=>$UUID

        );
           $merchant = "SELCOM-LUKU";
        $perc = $this->EncashServices->_getCommsionRate($merchant);
        $percentage = $perc ['COMMISSION_PERCENTAGE'];
        $this->EncashServices->_setCommission($this->agentMsisdn,$percentage,$amount);
        $op_balance = $opBAL['TOTAL_AVAIL_BALANCE'];
        $cl_balance = $op_balance - $amount;
        $commissionamount = $amount * $percentage/100;
        $cl_balance = $cl_balance + $commissionamount;
        $this->EncashServices->_recordTransaction("transaction_logs_success",$txnSuccess);
        $this->EncashServices->_updateMerchantBalance($amount,"SELCOM"); 
        $txnLog = array (
            "TXN_REFERENCE" => $txnReference,
            "TXN_TYPE" => "LUKU",
            "REQUEST_TYPE" => $this->requestmode,
            "AGENT_MSISDN" => $this->agentMsisdn,
            "TXN_DEVICE_NUMBER" => $device,
            "TXN_AMOUNT" => $amount,
            "TXN_STATUS" => "SUCCESS",
            "THIRDPARTY_REF" => $txnDat['TXN_THIRD_REFERENCE'],
            "ERROR_CODE" => "0000",
            "ERROR_DESC" => "SUCCESS",
            "TXN_ROUTE" =>"SELCOM",
            "ACK_REF"=>$ackRef,
            "TXN_EXTRA"=>$UUID
        );
$commissionValue = $amount * $percentage/100;
$commissionArray = array("AGENT_ID"=> $this->agent_id,"TXN_TYPE"=>"LUKU","TXN_AMOUNT"=>$amount,"TXN_COMMISSION_PERCENTAGE"=>$percentage,"TXN_COMMISSION_VALUE"=>$commissionValue);
        $this->EncashServices->_recordTransaction("agent_commission_success",$commissionArray);
        $sales= $amount;
        $this->EncashServices->_updateAgentAccount($cl_balance,$sales,$this->agent_id,"CLOSING_BALANCE");
$table = "transaction_logs_selcom";
$this->EncashServices->_recordTransaction($table,$txnLog);


$luku_data = array (
            "TXN_REFERENCE" => $txnReference,
            "THIRDPARTY_REF" => $txnDat['TXN_THIRD_REFERENCE'],
            "REQUEST_TYPE" => $this->requestmode,
            "AGENT_ID" => $this->agent_id,
            "AGENT_MSISDN" => $this->agentMsisdn,
            "SERVICE_NAME"=>"LUKU",
            "CARD_NUMBER"=>$device,
            "CUSTOMER_MOBILE" => $notifymsisdn,
            "AMOUNT" => $amount,
            "TXN_STATUS" => "SUCCESS",
            "TOKEN_NUMBER"=>$token,
            "ERROR_CODE" => $errorCode,
            "COMMENTS" => $result['COMMENTS']
        );
$this->EncashServices->_recordTransaction("luku_transaction",$luku_data);

if($this->requestmode=="THIRDPARTY"||$this->requestmode=="WEB"){
 
 $comments = $result['COMMENTS'];
 $units = $this->get_string_between($comments, "Units", "Token");
 $units = str_replace(" ", "", $units);

$transactionDetails = "METER|".$device."|AMOUNT|".$amount."|TOKEN|".$token."|UNITS|".$units;
$date = date("Y-m-d H:i:s");
 $response = '{"Response": {"StatusCode" : "0","StatusMessage": "SUCCESS","NotifyMsisdn" : "'.$notifymsisdn.'","ThirdPartyTransactionRef": "'.$ackRef.'","UUID":"'.$UUID.'","TransactionDetails" : "'.$transactionDetails.'","ServiceID":"LUKUTANZANIA","TransactionDate" : "'.$date.'"}}';


$callbackUrl =  $this->EncashServices->getCallbackUrl($this->agent_id);
   $url = $callbackUrl['CALLBACK_URL'];
 $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_TIMEOUT,100);
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$response);
   // curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
    $data = curl_exec($ch);
}
}

else{
  $date = date("Y-m-d h:i:s");
         $data = array("TXN_STATUS"=>"FAILED","THIRDPARTY_REF"=>$thirdParty,"ERROR_CODE"=>$errorCode,"ERROR_DESC"=>"FAILED");
        $this->EncashServices->_updateTransaction($data,$txnReference, $this->agentMsisdn);
        if($this->requestmode=="THIRDPARTY"||$this->requestmode=="WEB"){
       $response = '{"Response": {"StatusCode" : "010","StatusMessage": "FAILED","NotifyMsisdn" : "'.$notifymsisdn.'","ThirdPartyTransactionRef": "'.$ackRef.'","UUID":"'.$UUID.'","TransactionDetails" : "Transaction has been failed","ServiceID":"LUKUTANZANIA","TransactionDate":"'.$date.'"}}'; 

$callbackUrl =  $this->EncashServices->getCallbackUrl($this->agent_id);
   $url = $callbackUrl['CALLBACK_URL'];
 $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_TIMEOUT,100);
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$response);
  //  curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
    $data = curl_exec($ch);

     }
}
}
}

function _processDTH($card,$amount,$mobile,$refID,$opr,$ackRef,$UUID){

$txn = array (
            "TXN_REFERENCE" => $refID,
            "TXN_TYPE" => "DTH-".$this->operator,
            "REQUEST_TYPE" => $this->requestmode,
            "AGENT_ID" => $this->agent_id,
            "AGENT_MSISDN" => $this->agentMsisdn,
            "TXN_DEVICE_NUMBER" => $card,
            "TXN_AMOUNT" => $amount,
            "TXN_STATUS" => "SUBMITED",
            "ERROR_DESC" => "SUBMITED",
            "TXN_ROUTE" =>"SELCOM",
            "TXN_MOBILE_NUMBER"=>$mobile,
            "ACK_REF"=>$ackRef,
            "TXN_EXTRA"=>$UUID

        );
        $this->EncashServices->_recordTransaction("transaction_logs_all",$txn);
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
<string>'.$this->operator.'</string>
</value>
</member>
<member>
<name>utilityref</name>
<value>
<string>'.$card.'</string>
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
    $filename = 'logs/requests/DTH/DTH-'.$this->operator.'-'.$date.'.txt';   
    file_put_contents($filename,$xml,FILE_APPEND | LOCK_EX);
    $date = date("YmdHi");
    $filename = 'logs/responses/DTH/DTH-'.$this->operator.'-'.$date.'.txt';
    file_put_contents($filename,$data,FILE_APPEND | LOCK_EX);
    $xmlArray = xmlrpc_decode($data);





    if ($xmlArray ['result'] == "SUCCESS") {

      $trans ['ERROR_CODE'] = "000";
      $trans ['TXN_STATUS'] = 'SUCCESS';
      $trans ['TXN_DESCRIPTION'] = "DTH Transaction processed Successfully";
      $trans ['TXN_THIRD_REFERENCE'] = $xmlArray['reference'];
      $trans ['TXN_ID'] = $refID;
      $trans ['COMMENTS'] = $xmlArray ['message'];
     if($this->operator == "AZAMTV"){
      $trans ['VOUCHER'] = $xmlArray['reference'];  
      }
      else{
        $trans ['VOUCHER'] = $xmlArray['reference']; 
      } 
      $trans['EXTRA'] = "DTH Recharge done Successfully";
      return $trans;
    }
    else{

      $trans ['ERROR_CODE'] = '001';
      $trans ['TXN_STATUS'] = 'FAILED';
      $trans ['TXN_DESCRIPTION'] = 'FAILED';
      $retry ['TXN_THIRD_REFERENCE'] = $xmlArray ['reference'];
      $trans ['COMMENTS'] = $xmlArray ['message'];
      $trans ['TXN_ID'] = $refID;
      return $trans;

    }
}
function _processLUKU($meterNumber,$amount,$mobile,$refID,$luku_type,$ackRef,$UUID) {


$txn = array (
            "TXN_REFERENCE" => $refID,
            "TXN_TYPE" => "LUKU",
            "REQUEST_TYPE" => $this->requestmode,
            "AGENT_ID" => $this->agent_id,
            "AGENT_MSISDN" => $this->agentMsisdn,
            "TXN_DEVICE_NUMBER" => $meterNumber,
            "TXN_AMOUNT" => $amount,
            "TXN_STATUS" => "SUBMITED",
            "ERROR_DESC" => "SUBMITED",
            "TXN_ROUTE" =>"SELCOM",
            "TXN_MOBILE_NUMBER"=>$mobile,
            "ACK_REF"=>$ackRef,
            "TXN_EXTRA"=>$UUID

        );
        $this->EncashServices->_recordTransaction("transaction_logs_all",$txn);
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
    $filename = 'logs/requests/LUKU/'.$date.'.txt';   
    file_put_contents($filename,$xml,FILE_APPEND | LOCK_EX);
    $date = date("YmdHi");
    $filename = 'logs/responses/LUKU/'.$date.'.txt';
    file_put_contents($filename,$data,FILE_APPEND | LOCK_EX);
    $xmlArray = xmlrpc_decode($data);
    if ($xmlArray ['result'] == "SUCCESS") {

      $trans ['ERROR_CODE'] = "000";
      $trans ['TXN_STATUS'] = 'SUCCESS';
      $trans ['TXN_DESCRIPTION'] = "Luku Transaction processed Successfully";
      $trans ['TXN_THIRD_REFERENCE'] = $xmlArray['reference'];
      $trans ['TXN_ID'] = $refID;
      $trans ['COMMENTS'] = $xmlArray ['message'];
      $trans ['LUKU_TOKEN'] = $xmlArray ['token'];
      $trans['EXTRA'] = "Units : ".$xmlArray['units'];
      return $trans;
    } else {
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,"https://paypoint.selcommobile.com/api/selcom.pos.server.php");
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_TIMEOUT,100);
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
    curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
    $data = curl_exec($ch);
    $date = date("YmdHi");
    $filename = 'logs/requests/LUKU/Retry-'.$date.'.txt';   
    file_put_contents($filename,$xml,FILE_APPEND | LOCK_EX);
    $date = date("YmdHi");
    $filename = 'logs/responses/LUKU/Retry-'.$date.'.txt';
    file_put_contents($filename,$data,FILE_APPEND | LOCK_EX);
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
      return $retry;
    }
    else{
      $retry ['ERROR_CODE'] = '001';
      $retry ['TXN_STATUS'] = 'FAILED';
      $retry ['TXN_DESCRIPTION'] = 'FAILED';
      $retry ['TXN_THIRD_REFERENCE'] = $xmlArray ['reference'];
      $retry ['COMMENTS'] = $xmlArray ['message'];
      $retry ['TXN_ID'] = $refID;
      return $retry;
    }
    return $trans;
    }
  }



function _processNHC($houseNumber, $amount, $mobile, $refID, $ackRef, $UUID){

 $txn = array(
            "TXN_REFERENCE" => $refID,
            "TXN_TYPE" => "DTH-" . $this->operator,
            "REQUEST_TYPE" => $this->requestmode,
            "AGENT_ID" => $this->agent_id,
            "AGENT_MSISDN" => $this->agentMsisdn,
            "TXN_DEVICE_NUMBER" => $houseNumber,
            "TXN_AMOUNT" => $amount,
            "TXN_STATUS" => "SUBMITED",
            "ERROR_DESC" => "SUBMITED",
            "TXN_ROUTE" => "SELCOM",
            "TXN_MOBILE_NUMBER" => $mobile,
            "ACK_REF" => $ackRef,
            "TXN_EXTRA" => $UUID
        );
        $this->EncashServices->_recordTransaction("transaction_logs_all", $txn);
        

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
<string>NHC</string>
</value>
</member>
<member>
<name>utilityref</name>
<value>
<string>'.$houseNumber.'</string>
</value>
</member>
<member>
<name>transid</name>
<value>
<string>' . $refID . '</string>
</value>
</member>
<member>
<name>amount</name>
<value>
<string>' . $amount . '</string>
</value>
</member>
<member>
<name>msisdn</name>
<value>
<string>' . $mobile . '</string>
</value>
</member>
</struct>
</value>
</param>
</params>
</methodCall>';
        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "Content-length: " . strlen($xml)
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://paypoint.selcommobile.com/api/selcom.pos.server.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $data = curl_exec($ch);
        $date = date("YmdHi");
        $filename = 'logs/requests/NHC/NHC-' . $date . '.txt';
        file_put_contents($filename, $xml, FILE_APPEND | LOCK_EX);
        $date = date("YmdHi");
        $filename = 'logs/responses/NHC/NHC-' . $date . '.txt';
        file_put_contents($filename, $data, FILE_APPEND | LOCK_EX);
        $xmlArray = xmlrpc_decode($data);
           if ($xmlArray ['result'] == "SUCCESS") {

            $trans ['ERROR_CODE'] = "000";
            $trans ['TXN_STATUS'] = 'SUCCESS';
            $trans ['TXN_DESCRIPTION'] = "DTH Transaction processed Successfully";
            $trans ['TXN_THIRD_REFERENCE'] = $xmlArray['reference'];
            $trans ['TXN_ID'] = $refID;
            $trans ['COMMENTS'] = $xmlArray ['message'];
            $trans['EXTRA'] = "NHC  Payment done Successfully";
            return $trans;
        } else {
            $trans ['ERROR_CODE'] = '001';
            $trans ['TXN_STATUS'] = 'FAILED';
            $trans ['TXN_DESCRIPTION'] = 'FAILED';
            $retry ['TXN_THIRD_REFERENCE'] = $xmlArray ['reference'];
            $trans ['COMMENTS'] = $xmlArray ['message'];
            $trans ['TXN_ID'] = $refID;
            return $trans;
        }

}

function _processDAWASCO($meterNumber, $amount, $mobile, $refID, $ackRef, $UUID) {

        $txn = array(
            "TXN_REFERENCE" => $refID,
            "TXN_TYPE" => "DTH-" . $this->operator,
            "REQUEST_TYPE" => $this->requestmode,
            "AGENT_ID" => $this->agent_id,
            "AGENT_MSISDN" => $this->agentMsisdn,
            "TXN_DEVICE_NUMBER" => $card,
            "TXN_AMOUNT" => $amount,
            "TXN_STATUS" => "SUBMITED",
            "ERROR_DESC" => "SUBMITED",
            "TXN_ROUTE" => "SELCOM",
            "TXN_MOBILE_NUMBER" => $mobile,
            "ACK_REF" => $ackRef,
            "TXN_EXTRA" => $UUID
        );
        $this->EncashServices->_recordTransaction("transaction_logs_all", $txn);

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
<string>DAWASCO</string>
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
<string>' . $refID . '</string>
</value>
</member>
<member>
<name>amount</name>
<value>
<string>' . $amount . '</string>
</value>
</member>
<member>
<name>msisdn</name>
<value>
<string>' . $mobile . '</string>
</value>
</member>
</struct>
</value>
</param>
</params>
</methodCall>';
        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "Content-length: " . strlen($xml)
        );





        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://paypoint.selcommobile.com/api/selcom.pos.server.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $data = curl_exec($ch);
        $date = date("YmdHi");
        $filename = 'logs/requests/DAWASCO/DAWASCO' . $date . '.txt';
        file_put_contents($filename, $xml, FILE_APPEND | LOCK_EX);
        $date = date("YmdHi");
        $filename = 'logs/responses/DAWASCO/DAWASCO-' . $date . '.txt';
        file_put_contents($filename, $data, FILE_APPEND | LOCK_EX);
        $xmlArray = xmlrpc_decode($data);

        if ($xmlArray ['result'] == "SUCCESS") {

            $trans ['ERROR_CODE'] = "000";
            $trans ['TXN_STATUS'] = 'SUCCESS';
            $trans ['TXN_DESCRIPTION'] = "DTH Transaction processed Successfully";
            $trans ['TXN_THIRD_REFERENCE'] = $xmlArray['reference'];
            $trans ['TXN_ID'] = $refID;
            $trans ['COMMENTS'] = $xmlArray ['message'];
            $trans['EXTRA'] = "Dawasco  Recharge done Successfully";
            return $trans;
        } else {
            $trans ['ERROR_CODE'] = '001';
            $trans ['TXN_STATUS'] = 'FAILED';
            $trans ['TXN_DESCRIPTION'] = 'FAILED';
            $retry ['TXN_THIRD_REFERENCE'] = $xmlArray ['reference'];
            $trans ['COMMENTS'] = $xmlArray ['message'];
            $trans ['TXN_ID'] = $refID;
            return $trans;
        }
    }
function _processAirtime($refID,$msisdn,$amount,$operator,$service,$ackRef,$UUID){

$msisdn = preg_replace('/[^A-Za-z0-9\-]/', '', $msisdn);

// if($operator =="TIGO"){ 
// $txnDat = $this->TIGO($refID,$msisdn,$amount);
// $route= "DIRECT";
// $table = "TIGO";
// }

 if($operator=="HALOTEL"){
$txnDat = $this->Halotel($msisdn,$amount);
$route= "DIRECT";
$table = "HALOTEL";
$merchant = "HALOTEL";
}


 else if($operator == "AIRTEL"){
 $txnDat = $this->Airtel($msisdn,$refID,$amount);
 $route= "DIRECT";
 $table = "AIRTEL";
 $merchant = "AIRTEL";
 }


/*elseif($operator == "SMART") {

if($service == "BUNDLE"){

$txnDat = $this->SMARTBundle($refID,$msisdn,$amount);

}
else{
$txnDat = $this->SMART($refID,$msisdn,$amount);
}
$route= "DIRECT";
$table = "SMART";
$merchant = "SMART";
}*/


else{
  $txnDat = $this->SelcomAirtime($refID,$msisdn,$amount,$operator);
  $route= "SELCOM";
  $table = "SELCOM";
  $merchant = "SELCOM-".$operator;
}



$txn = array (
            "TXN_REFERENCE" => $refID,
            "TXN_TYPE" => "AIRTIME",
            "REQUEST_TYPE" => $this->requestmode,
            "AGENT_ID" => $this->agent_id,
            "AGENT_MSISDN" => $this->agentMsisdn,
            "TXN_DEVICE_NUMBER" => $msisdn,
            "TXN_AMOUNT" => $amount,
            "TXN_STATUS" => "SUBMITED",
            "ERROR_DESC" => "SUBMITED",
            "TXN_ROUTE" =>$route,
            "TXN_MOBILE_NUMBER"=>$msisdn,
            "ACK_REF"=>$ackRef,
            "TXN_EXTRA"=>$UUID

        );
        $this->EncashServices->_recordTransaction("transaction_logs_all",$txn);




    if($txnDat['TXN_STATUS']=='SUCCESS'){       
        $opBAL =  $this->EncashServices->getBalance($this->agentMsisdn);
        $this->EncashServices->updateBalance($this->agentMsisdn,$amount);
        $desc = $txnDat['TXN_STATUS'];
          $thirdParty = $txnDat['TXN_THIRD_REFERENCE'];
$data = array("TXN_STATUS"=>"SUCCESS","THIRDPARTY_REF"=>$thirdParty,"ERROR_CODE"=>$errorCode,"ERROR_DESC"=>$desc);
        $this->EncashServices->_updateTransaction($data,$refID, $this->agentMsisdn);


          $clBalance = $opBAL['TOTAL_AVAIL_BALANCE'] - $amount;
           $txnSuccess = array (
            "TXN_REFERENCE" => $refID,
            "TXN_TYPE" => "AIRTIME",
            "OP_BALANCE"=>$opBAL['TOTAL_AVAIL_BALANCE'],
            "CL_BALANCE"=>$clBalance,
            "REQUEST_TYPE" => $this->requestmode,
            "AGENT_ID" => $this->agent_id,
            "AGENT_MSISDN" => $this->agentMsisdn,
            "TXN_DEVICE_NUMBER" => $msisdn,
            "TXN_AMOUNT" => $amount,
            "TXN_STATUS" => "SUCCESS",
            "THIRDPARTY_REF" => $txnDat['TXN_THIRD_REFERENCE'],
            "ERROR_CODE" => "0000",
            "ERROR_DESC" => "SUCCESS",
            "TXN_ROUTE" =>$route,
            "ACK_REF"=>$ackRef,
            "TXN_EXTRA"=>$UUID

        );


        $perc = $this->EncashServices->_getCommsionRate($merchant);

        $percentage = $perc ['COMMISSION_PERCENTAGE'];
        $this->EncashServices->_setCommission($this->agentMsisdn,$percentage,$amount);


  $op_balance = $opBAL['TOTAL_AVAIL_BALANCE'];
        $cl_balance = $op_balance - $amount;
        $commissionamount = $amount * $percentage/100;
        $cl_balance = $cl_balance + $commissionamount;


if($table =="SELCOM"){

                $merchant = "SELCOM";
            }


            $this->EncashServices->_recordTransaction("transaction_logs_success",$txnSuccess);
             $this->EncashServices->_updateMerchantBalance($amount,$merchant); 
 $txnLog = array (
            "TXN_REFERENCE" => $refID,
            "TXN_TYPE" => "AIRTIME",
            "REQUEST_TYPE" => $this->requestmode,
            "AGENT_MSISDN" => $this->agentMsisdn,
            "TXN_DEVICE_NUMBER" => $msisdn,
            "TXN_AMOUNT" => $amount,
            "TXN_STATUS" => "SUCCESS",
            "THIRDPARTY_REF" => $txnDat['TXN_THIRD_REFERENCE'],
            "ERROR_CODE" => "0000",
            "ERROR_DESC" => "SUCCESS",
            "TXN_ROUTE" =>$route,
            "ACK_REF"=>$ackRef,
            "TXN_EXTRA"=>$UUID
        );
$commissionValue = $amount * $percentage/100;
$commissionArray = array("AGENT_ID"=> $this->agent_id,"TXN_TYPE"=>"AIRTIME","TXN_AMOUNT"=>$amount,"TXN_COMMISSION_PERCENTAGE"=>$percentage,"TXN_COMMISSION_VALUE"=>$commissionValue);
        $this->EncashServices->_recordTransaction("agent_commission_success",$commissionArray);
        $sales= $amount;
        $this->EncashServices->_updateAgentAccount($cl_balance,$sales,$this->agent_id,"CLOSING_BALANCE");



if($operator == "SMART"){
  $table = "transaction_logs_smart";
}
// elseif($operator == "TIGO"){
//   $table = "transaction_logs_tigo";
// }
else if($operator == "HALOTEL"){
  $table = "transaction_logs_halotel";
}

// else if($operator == "AIRTEL"){
//   $table = "transaction_logs_airtel";
// }

else{
  $table = "transaction_logs_selcom";
}
$this->EncashServices->_recordTransaction($table,$txnLog);


if($this->requestmode=="THIRDPARTY"||$this->requestmode=="WEB"){  
  $date = date("Y-m-d h:i:s");
  $serviceID = "AIRTIMETZ".$this->operator;
$response = '{"Response": {"StatusCode" : "0","StatusMessage": "SUCCESS","ThirdPartyTransactionRef": "'.$ackRef.'","NotifyMsisdn" : "'.$msisdn.'","UUID":"'.$UUID.'","TransactionDetails" : "Airtime transaction has been processed","ServiceID":"'.$serviceID.'","TransactionDate":"'.$date.'"}}';

   $callbackUrl =  $this->EncashServices->getCallbackUrl($this->agent_id);
   $url = $callbackUrl['CALLBACK_URL'];
   $transactionDetails = $result['COMMENTS'];
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_TIMEOUT,100);
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$response);
    $data = curl_exec($ch);
}
}
else{
$serviceID = "AIRTIMETZ".$this->operator;
$date = date("Y-m-d h:i:s");
         $data = array("TXN_STATUS"=>"FAILED","THIRDPARTY_REF"=>$thirdParty,"ERROR_CODE"=>$errorCode,"ERROR_DESC"=>"FAILED");
         $this->EncashServices->_updateTransaction($data,$refID, $this->agentMsisdn);
          if($this->requestmode=="THIRDPARTY"||$this->requestmode=="WEB"){
          $callbackUrl =  $this->EncashServices->getCallbackUrl($this->agent_id);
         $url = $callbackUrl['CALLBACK_URL'];  
         $response = '{"Response": {"StatusCode" : "010","StatusMessage": "FAILED","NotifyMsisdn" : "'.$msisdn.'","ThirdPartyTransactionRef": "'.$ackRef.'","UUID":"'.$UUID.'","TransactionDetails" : "Transaction has been failed","ServiceID":"'.$serviceID.'","TransactionDate":"'.$date.'"}}'; 
       $ch = curl_init();
      curl_setopt($ch,CURLOPT_URL,$url);
      curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
      curl_setopt($ch,CURLOPT_TIMEOUT,100);
      curl_setopt($ch,CURLOPT_POST,true);
      curl_setopt($ch,CURLOPT_POSTFIELDS,$response);
      $data = curl_exec($ch);
     }
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
<MSISDN>'.$msisdn.'</MSISDN>
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
$date = date("Y-m-dH");
file_put_contents("logs/requests/AIRTIME/HALOTEL-".$date.".txt", $xml." \n",FILE_APPEND|LOCK_EX);
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
      file_put_contents("logs/responses/AIRTIME/HALOTEL-".$date.".txt", $data." \n",FILE_APPEND|LOCK_EX);
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
//$url = "https://172.23.12.29:4412/pretups/C2SReceiver?";  
    $url = "https://196.46.122.186:4412/pretups/C2SReceiver?";
$xml = '<?xml version="1.0"?> <!DOCTYPE COMMAND PUBLIC "-//Ocam//DTD XML Command 1.0//EN""xml/command.dtd">
<COMMAND><TYPE>EXRCTRFREQ</TYPE> <DATE></DATE> <EXTNWCODE>TZ</EXTNWCODE><MSISDN>789813064</MSISDN><PIN>0606</PIN><LOGINID></LOGINID><PASSWORD></PASSWORD><EXTCODE></EXTCODE> <EXTREFNUM>'.$txnID.'</EXTREFNUM> <MSISDN2>'.$msisdn.'</MSISDN2><AMOUNT>'.$amount.'</AMOUNT><LANGUAGE1>1</LANGUAGE1><LANGUAGE2>1</LANGUAGE2><SELECTOR>1</SELECTOR></COMMAND>';

$date = date("Y-m-dH");
file_put_contents("logs/requests/AIRTIME/AIRTEL-".$date.".txt", $xml." \n",FILE_APPEND|LOCK_EX);

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
      file_put_contents("logs/responses/AIRTIME/AIRTEL-".$date.".txt", $data." \n",FILE_APPEND|LOCK_EX);
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
function SelcomAirtime($refID,$msisdn,$amount,$operator){
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
    $date = date("Y-m-dH");
    file_put_contents("logs/requests/AIRTIME/SELECOM-".$operator."-".$date.".txt", $xml." \n",FILE_APPEND|LOCK_EX);
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,"https://paypoint.selcommobile.com/api/selcom.pos.server.php");
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_TIMEOUT,100);
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
    curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
    $data = curl_exec($ch);
    file_put_contents("logs/responses/AIRTIME/SELECOM-".$operator."-".$date.".txt", $data." \n",FILE_APPEND|LOCK_EX);
     $result = xmlrpc_decode($data);
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
      $date = date("Y-m-dH");
    file_put_contents("logs/requests/AIRTIME/TIGO-".$date.".txt", $xml." \n",FILE_APPEND|LOCK_EX);
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
      file_put_contents("logs/responses/AIRTIME/TIGO-".$date.".txt",$data." \n",FILE_APPEND|LOCK_EX);
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
      $date = date("Y-m-dH");
      file_put_contents("logs/requests/AIRTIME/SMART-AIRTIME".$date.".txt",$xml,FILE_APPEND|LOCK_EX);
      $ch = curl_init();
      curl_setopt($ch,CURLOPT_URL,$url);
      curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
      curl_setopt($ch,CURLOPT_TIMEOUT,10);
      curl_setopt($ch,CURLOPT_POST,true);
      curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
      curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
      $data = curl_exec($ch);
      
      file_put_contents("logs/responses/AIRTIME/SMART-AIRTIME".$date.".txt",$data,FILE_APPEND|LOCK_EX);
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


        function SMARTBundle($txn,$mobile,$amount){
          $xml = '<?xml version="1.0" encoding= "UTF-8"?>
<COMMAND>
<TXNID>'.$txn.'</TXNID>
<MSISDN>'.$mobile.'</MSISDN>
<AMOUNT>'.$amount.'</AMOUNT>
<COMPANYNAME>GREEN_SMART</COMPANYNAME>
</COMMAND>';
      $url = "http://172.25.200.37:9090/Mobpay/GreenBundle";
      $headers = array (
          "Content-type: text/xml",
          "Content-length: ".strlen($xml ),
          "Connection: close" 
      );
      $date = date("Y-m-dH");
      file_put_contents("logs/requestes/AIRTIME/SMART-BUNDLE".$date.".txt",$xml,FILE_APPEND|LOCK_EX);
      $ch = curl_init();
      curl_setopt($ch,CURLOPT_URL,$url);
      curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
      curl_setopt($ch,CURLOPT_TIMEOUT,10);
      curl_setopt($ch,CURLOPT_POST,true);
      curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
      curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
      $data = curl_exec($ch);
      file_put_contents("logs/responses/AIRTIME/SMART-BUNDLE".$date.".txt",$data,FILE_APPEND|LOCK_EX);
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
function processVodacomTransaction(){

$post = file_get_contents("php://input");
$json = json_decode($post,TRUE);
file_put_contents("vodacom.txt",$post);
$this->EncashServices->_recordTransaction("transaction_logs_all",$json);
if($json['TXN_STATUS']=="SUCCESS"){



if($json['REQUEST_TYPE']=="THIRDPARTY"||$json['REQUEST_TYPE']=="WEB"){
// $serviceID = "VODACOMTANZANIA";
 $serviceID = "AIRTIMETZVODACOM";
$notifymsisdn = $json['TXN_DEVICE_NUMBER'];

$date = date("Y-m-d H:i:s");
 $response = '{"Response": {"StatusCode" : "0","StatusMessage": "SUCCESS","NotifyMsisdn" : "'.$notifymsisdn.'","ThirdPartyTransactionRef": "'.$json["ACK_REF"].'","UUID":"'.$json["TXN_EXTRA"].'","TransactionDetails" : "Airtime transaction has been successfully processed ","ServiceID":"'.$serviceID.'","TransactionDate" : "'.$date.'"}}';


$callbackUrl =  $this->EncashServices->getCallbackUrl($json['AGENT_ID']);
   $url = $callbackUrl['CALLBACK_URL'];
 $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_TIMEOUT,100);
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$response);
   // curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
    $data = curl_exec($ch);
}




$opBAL =  $this->EncashServices->getBalance($json['AGENT_MSISDN']);
$this->EncashServices->updateBalance($json['AGENT_MSISDN'],$json['TXN_AMOUNT']);
$clBalance = $opBAL['TOTAL_AVAIL_BALANCE'] - $json['TXN_AMOUNT'];
$data['TXN_TYPE'] = $json['TXN_TYPE'];
$data['REQUEST_TYPE'] = $json['REQUEST_TYPE'];
$data['TXN_REFERENCE'] = $json['TXN_REFERENCE'];
$data['THIRDPARTY_REF'] = $json['THIRDPARTY_REF'];
$data['ACK_REF'] = $json['ACK_REF'];
$data['TXN_EXTRA'] = $json['TXN_EXTRA'];
$data['TXN_AMOUNT'] = $json['TXN_AMOUNT'];
$data['AGENT_ID'] =$json['AGENT_ID'];
$data['AGENT_MSISDN'] = $json['AGENT_MSISDN'];
$data['TXN_DEVICE_NUMBER'] = $json['TXN_DEVICE_NUMBER'];
$data['TXN_MOBILE_NUMBER'] = $json['TXN_MOBILE_NUMBER'];
$data['OP_BALANCE'] = $opBAL['TOTAL_AVAIL_BALANCE'];
$data['CL_BALANCE'] =$clBalance;
$data['TXN_ROUTE'] = $json['TXN_ROUTE'];
$data['TXN_STATUS'] = $json['TXN_STATUS'];
$data['ERROR_CODE'] = '0';
$data['ERROR_DESC'] = 'Airtime has been processed Successfully';
$this->EncashServices->_recordTransaction("transaction_logs_success",$data);


$vodacom['TXN_TYPE'] = $json['TXN_TYPE'];
$vodacom['REQUEST_TYPE'] = $json['REQUEST_TYPE'];
$vodacom['TXN_REFERENCE'] = $json['TXN_REFERENCE'];
$vodacom['THIRDPARTY_REF'] = $json['THIRDPARTY_REF'];
$vodacom['ACK_REF'] = $json['ACK_REF'];
$vodacom['TXN_EXTRA'] = $json['TXN_EXTRA'];
$vodacom['TXN_AMOUNT'] = $json['TXN_AMOUNT'];
$vodacom['AGENT_MSISDN'] = $json['AGENT_MSISDN'];
$vodacom['TXN_DEVICE_NUMBER'] = $json['TXN_DEVICE_NUMBER'];
$vodacom['TXN_MOBILE_NUMBER'] = $json['TXN_MOBILE_NUMBER'];
$vodacom['TXN_ROUTE'] = $json['TXN_ROUTE'];
$vodacom['TXN_STATUS'] = $json['TXN_STATUS'];
$vodacom['ERROR_CODE'] = '0';
$vodacom['ERROR_DESC'] = 'Airtime has been processed Successfully';
$this->EncashServices->_recordTransaction("transaction_logs_vodacom",$vodacom);

 $perc = $this->EncashServices->_getCommsionRate("VODACOM");

        $percentage = $perc ['COMMISSION_PERCENTAGE'];

$commissionValue = $json['TXN_AMOUNT'] * $percentage/100;
$commissionArray = array("AGENT_ID"=> $json['AGENT_ID'],"TXN_TYPE"=>"AIRTIME","TXN_AMOUNT"=>$json['TXN_AMOUNT'],"TXN_COMMISSION_PERCENTAGE"=>$percentage,"TXN_COMMISSION_VALUE"=>$commissionValue);
        $this->EncashServices->_recordTransaction("agent_commission_success",$commissionArray);
        $sales= $json['TXN_AMOUNT'];
        $this->EncashServices->_updateAgentAccount($clBalance,$sales,$json['AGENT_ID'],"CLOSING_BALANCE");
        $this->EncashServices->_updateMerchantBalance($sales, "VODACOM");
        $this->EncashServices->_setCommission($json["AGENT_MSISDN"],$percentage,$sales);
}
    
else{

if($json['ERROR_DESC'] == "Vodacom related problem, please repeat with same Transaction_id"||$json['ERROR_DESC'] == "Delayed recharge, subscribe account will be credited later."){

$repeat['AGENT_ID'] = $json['AGENT_ID'];
$repeat['AGENT_MSISDN'] = $json['AGENT_MSISDN'];
$repeat['TXN_AMOUNT'] = $json['TXN_AMOUNT'];
$repeat['IS_REPEAT'] = 0;
$repeat['ACK_REF'] = $json['ACK_REF'];
$repeat['TXN_EXTRA'] = $json['TXN_EXTRA'];
$msisdn = substr($json['TXN_DEVICE_NUMBER'],3);
$repeat['TXN_DEVICE_NUMBER'] = "0".$msisdn;
$repeat['TXN_SERVICE'] = 'AIRTIME';
$repeat['OPERATOR'] = $json['OPERATOR'];
$repeat['REQUEST_MODE'] = $json['REQUEST_TYPE'];
$repeat['TXN_MOBILE'] = $json['TXN_DEVICE_NUMBER'];
$repeat['OPERATOR'] = 'VODACOM';
$repeat['TXN_REFERENCE'] = $json['THIRDPARTY_REF'];

$this->EncashServices->_recordTransaction("recharge_process",$repeat);
exit;
}
$response = '{"Response": {"StatusCode" : "010","StatusMessage": "FAILED","NotifyMsisdn" : "'.$json["TXN_DEVICE_NUMBER"].'","ThirdPartyTransactionRef": "'.$json["ACK_REF"].'","UUID":"'.$json["TXN_EXTRA"].'","TransactionDetails" : "Airtime transaction Failed ","ServiceID":"'.$serviceID.'","TransactionDate" : "'.$date.'"}}';


$callbackUrl =  $this->EncashServices->getCallbackUrl($json['AGENT_ID']);
   $url = $callbackUrl['CALLBACK_URL'];
 $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_TIMEOUT,100);
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$response);
   // curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
    $data = curl_exec($ch);
}

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
