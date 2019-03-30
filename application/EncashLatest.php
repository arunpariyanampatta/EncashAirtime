<?php

/*
 * Author : Arun.Pariyanampatta.
 * Organization : GREEN TELECOM
 * and open the template in the editor.
 */
error_reporting(0);
date_default_timezone_set('Africa/Dar_Es_Salaam');
class EncashLatest extends CI_Controller {
	
	public function __construct() {
		parent::__construct();
		$this->load->model('EncashLatestModel','EncashLatest'); //This file contanis Main-Menu and its operations
		$this->load->library('encashlib');
		$this->lang->load('ussd');
	}
	var $isWhitelist,$nextMen,$isExist,$min,$max,$ussdSession,$ussdMsisdn,$ussdMsg,$ussdType,$message,$currentLevel,$previousLevel,$menuItems,$ctype,$const,$currentSelection,$ussdSequence,$passcode,$operator;
	var $index = 1;
	var $level = 1;
	var $morelevel = 3;
	var $moreData = FALSE;
	var $endsession = FALSE;
	var $pressedBack = FALSE;
	var $end = FALSE;
	var $choices = array();
	var $menu = array (
			"encash_main" => 1,
			"set_main" => 2,
			"encash_airtime" => 3,
			"encash_setAirtime" => 4,
			"encash_selectVoice" => 5,
			"set_selectVoice" => 6 
	);
	var $options = array (
			"more" => "*",
			"back" => "00" 
	);
	public function index() {
		$this->ussdMsisdn = $_GET ['MOBILE_NUMBER'];
		$this->ussdSession = $_GET ['SESSION_ID'];
		$this->ussdMsg = $_GET ['USER_INPUT'];
		$this->ussdSequence = $_GET ['SESSION_TYPE'];
		$this->operator = $_GET ['OPERATOR'];
		if ($this->ussdSequence > 1) {
			$this->ussdType = 2;
		} else {
			$mobile = $this->ussdMsisdn;
			$session = $this->ussdSession;
			$data = array (
					"MSISDN" => $mobile,
					"SESSION_ID" => $session 
			);
			$this->ussdType = 1;
			$this->EncashLatest->_logUssd($data); // Log the starting session
		}
		switch ($this->ussdType) {
			case USSD_SESSION_NEW :
				$this->ussdType = 2;
				$this->_setSessionForMsisdn();
				$this->message = $this->encash_main();
				break;
			case USSD_SESSION_EXIST :
				$sessionState = $this->_getSessionState();
				$this->ussdMsg = $_GET ['USER_INPUT'];
				$this->message = $this->_checkInput($this->ussdMsg);
				break;
			case USSD_SESSION_END :
				$this->ussdType = 3;
				$this->message = "Session ended";
				break;
			case USSD_SESSION_TIMEOUT :
				$this->ussdType = 4;
				$this->message = "Session Timeout";
				break;
		}
		$this->_packUssdMessage();
	}
	function encash_main() {
		$exist = $this->EncashLatest->_checkUser($this->ussdMsisdn);
		if (empty($exist )) {
			$this->end = TRUE;
			return "Karibu";
		}
		if ($exist['AGENT_FLAG'] == 1) { // not active
			return "Please Activate your account first";
		} else if ($exist ['AGENT_FLAG'] == 0) { // this means this user is active
			
			if ($exist ['AGENT_LANGUAGE'] == NULL) {
				return $this->encash_language();
			} else { // this will show the main menu;
				$balance = $this->EncashLatest->_getBalanceStatus($this->ussdMsisdn);
				$lang = $exist ['AGENT_LANGUAGE'];
				$this->_setElement("language",$exist ['AGENT_LANGUAGE']);
				$this->_setElement("password",$exist ['AGENT_PASSWORD']);
				$this->_setElement("agent_type_id",$exist ["AGENT_TYPE_ID"]);
				$this->_setElement("agent_id",$exist ['ID']);
				$this->_setElement("super_agent_ref",$exist["AGENT_REF_NUMBER"]);
				$this->_setElement("MAIN_BALANCE",$balance ['MAIN_BALANCE']);
				$this->_setElement("COMMISSION_BALANCE",$balance ['COMMISSION_BALANCE']);
				$menu = $this->EncashLatest->_getMainMenu($this->_getElement("language" ));
				$tempString = $this->lang->line('welcome_'.$lang )."\n";
				foreach($menu as $index => $key ) {
					$tempString .= $menu [$index] ['menu_order'].". ".$menu [$index] ['node']." \n";
				}
				$this->_setElement("nextMenu","set_Encashmain");
				return $tempString;
			}
		}
	}
	function encash_language() {
		$tempString = "1. English \n2. Kiswahili";
		$this->_setElement("nextMenu","encash_setLanguage");
		return $tempString;
	}
	function encash_setLanguage() {
		if ($this->ussdMsg == 1) {
			$node = "english";
			$this->EncashLatest->_SetAgentLanguage($node,$this->ussdMsisdn);
			return $this->encash_main();
		} else if ($this->ussdMsg == 2) {
			$node = "kiswahili";
			$this->EncashLatest->_SetAgentLanguage($node,$this->ussdMsisdn);
			return $this->encash_main();
		} else {
			return $this->encash_language();
		}
	}
	function set_EncashMain() {
		$min = 1;
		$max = 7;
		$isValid = $this->_validatestaticInput($min,$max,$this->ussdMsg);
		if ($isValid) {
			$function = $this->EncashLatest->_GetMainAction($this->ussdMsg);
			$function = $function ['menu_action'];
			return call_user_func(array (
					$this,
					$function 
			));
		} else {
			
			return $this->encash_main();
		}
	}
	
	// For Airtime
	function encash_selectAirtime() {
		$tempString = $this->lang->line('mobile_'.$this->_getElement("language" )); // enter mobile number
		$this->_setElement("nextMenu","set_selectAirtime");
		$this->_setElement("prevMenu","encash_main");
		return $tempString;
	}
	function set_selectAirtime() {
		$operator = $this->getOperator($this->ussdMsg);
		if ($operator ['OPERATOR_NAME'] == "ZANTEL") {
			$this->end = TRUE;
			return "Unable to Recharge Zantel number at the moment.";
			
		}

		if ($operator ['OPERATOR_NAME'] == "UNKNOWN") {
			return $this->encash_selectAirtime();
		} else {
			$this->_setElement("mobile_number",$this->ussdMsg);
			$this->_setElement("OPERATOR",$operator ['OPERATOR_NAME']);
			$this->_setElement("OPERATOR_ID",$operator ['OPERATOR_ID']);
			return $this->encash_airtimeAmount();
		}
	}
	function encash_airtimeAmount() {
		$tempString = $this->lang->line('enter_amount_'.$this->_getElement("language" ));
		$this->_setElement("prevMenu","encash_main");
		$this->_setElement("nextMenu","set_airtimeAmount");
		return $tempString;
	}
	function set_airtimeAmount() {
		$amount = $this->ussdMsg;
		$balance = FALSE;
		$this->_setElement("amount",$amount);
		$operator_id = $this->_getElement("OPERATOR");
		$bundle = $this->EncashLatest->_checkBundleList($amount,$operator_id); // check if it is a bundle amount
		$main = $this->_getElement("MAIN_BALANCE");
		$commission = $this->_getElement("COMMISSION_BALANCE");
		$totbalance = $main+$commission;
		$this->_setElement("OP_BALANCE",$totbalance);
		if ($amount > $main) { // will check if the amount is greater than main balance
			$this->end = TRUE;
            return $this->lang->line('topup_account_'.$this->_getElement("language" ));
        }
		else{
			$this->_setElement("BalanceType","MAIN");
			$balance = TRUE;
		
		if (!empty($bundle)) {
			return $this->encash_chooseAirtimeOption($amount,$operator_id);
		}
		else{

			return $this->encash_Airtime_confirmation();
		}
	}
}
	function encash_chooseAirtimeOption($amount,$operator_id) { // incase of bundle show options of Bundle,Airtime
	$tempString = "1. ".$this->lang->line('airtime_'.$this->_getElement("language" ) )." \n2. ".$this->lang->line('bundle_'.$this->_getElement("language" ) )." \n".$this->lang->line('goback_'.$this->_getElement("language" ));
		$this->_setElement("prevMenu","encash_airtimeAmount");
		$this->_setElement("nextMenu","encash_setchooseAirtimeOption");
		return $tempString;
	}
	function encash_setchooseAirtimeOption() {
		if ($this->ussdMsg = "1") {
			$this->_setElement("AIRTIME_TYPE","AIRTIME");
			return $this->set_Airtime_confirmation();
		} else {
			$this->_setElement("AIRTIME_TYPE","BUNDLE");

			if($this->_getElement("OPERATOR")=="TIGO"){

				return $this->tigoBundle();
			}
			else{
			return $this->set_Airtime_confirmation();
		}
		}
	}
	
function tigoBundle(){

$tempString = "1.VOICE \n";
$tempString .="2.DATA";
$this->_setElement("nextMenu","tigo_bundleSelection");
return $tempString;
}

function tigo_bundleSelection(){
$tempString ='';
if($this->ussdMsg == 1){

	$this->_setElement("BUNDLE_TYPE","VOICE");


}
else{
	$this->_setElement("BUNDLE_TYPE","DATA");
}
	$menu = $this->EncashLatest->getBundle($this->_getElement("BUNDLE_TYPE"),"TIGO",$this->_getElement("amount"));

	foreach ($menu as $index => $key) {
			$tempString .= $menu[$index]['menu_order'] . ". " . $menu[$index]['description'] . " \n";
		}
		$this->_setElement("nextMenu","encash_setBundle");
		return $tempString;
}
function encash_setBundle(){

$menu = $this->EncashLatest->getBundleDeatils($this->_getElement("BUNDLE_TYPE"),"TIGO",$this->_getElement("amount"),$this->ussdMsg);
$this->_setElement("Bundle_Detail",$menu['description']);

return $this->encash_Airtime_confirmation();

}

	function encash_Airtime_confirmation() {
		$tempString = "Select any option \n";
		$tempString .= "1. Confirm \n";
		$tempString .= "2. Cancel \n";
		$tempString .= "00. Go Back";
		$this->_setElement("nextMenu","password");
		$this->_setElement("AIRTIME_TYPE","AIRTIME");
		$this->_setElement("endMenu","set_Airtime_confirmation");
		$this->_setElement("prevMenu","encash_chooseAirtimeOption");
		return $tempString;
	}
	function set_Airtime_confirmation() {
		$txn_ref = $this->incrementalHash();
		$airtimeType = $this->_getElement("AIRTIME_TYPE");
		$this->end = TRUE;
		$operator_id = $this->_getElement("OPERATOR_ID");
		$amount = $this->_getElement("amount");
		$agent_id = $this->_getElement("agent_id");
		$mobile = $this->_getElement("mobile_number");
		$transaction_reference = $this->incrementalHash();
		$merchant_id = $this->_getElement("OPERATOR");
		if($merchant_id == "TIGO"||$merchant_id =="SMART"){
			$merchant_id = $merchant_id;
		}
		else{
			$merchant_id = "SELCOM";
		}
		$merchantBalance = $this->EncashLatest->_checkMerchantAccount($merchant_id);
		if ($merchantBalance ['FINAL_FLOAT_VALUE'] < $amount) {
			$this->end = TRUE;
			return "Error Occured please try later ";
		} else {
			if ($merchant_id == "SMART" || $merchant_id == "TIGO") {
				$route = "DIRECT";
			} else {
				$route = "SELCOM";
			}
			if ($this->_getElement("BalanceType" ) == "MAIN") {
				$this->EncashLatest->_updateMainBalance($amount,$this->ussdMsisdn);
				$transaction = array (
						"TXN_TYPE" => $airtimeType,
						"REQUEST_TYPE" => "USSD",
						"TXN_REFERENCE" => $txn_ref,
						"TXN_AMOUNT" => $amount,
						"AGENT_MSISDN" => $this->ussdMsisdn,
						"TXN_DEVICE_NUMBER" => $mobile,
						"TXN_MOBILE_NUMBER" => $mobile,
						"TXN_ROUTE" => $route,
						"TXN_STATUS" => "SUBMITED" 
				);
				$this->EncashLatest->_LogTransaction($transaction);
			} else {
				return "Low Balance. Please Try later";
				$this->end= TRUE;
				$this->EncashLatest->_updateCommissionBalance($amount,$this->_getElement("MAIN_AMT" ),$this->_getElement("COMMISSION_BALANCE" ),$this->ussdMsisdn);
				$transaction = array (
						"TXN_TYPE" => $airtimeType,
						"REQUEST_TYPE" => "USSD",
						"AGENT_ID" => $this->_getElement("agent_id" ),
						"TXN_REFERENCE" => $txn_ref,
						"TXN_AMOUNT" => $amount,
						"AGENT_MSISDN" => $this->ussdMsisdn,
						"TXN_DEVICE_NUMBER" => $mobile,
						"TXN_MOBILE_NUMBER" => $mobile,
						"TXN_ROUTE" => $route,
						"TXN_STATUS" => "SUBMITED" 
				);
				$this->EncashLatest->_LogTransaction($transaction);
			}
			$result = $this->_processAirtime($mobile,$txn_ref,$amount);
			$errorCode = $result ['ERROR_CODE'];
			$status = $result ['TXN_STATUS'];
			$desc = $result ['TXN_DESCRIPTION'];
			$thirdParty = $result ['TXN_THIRD_REFERENCE'];
			$txnid = $result ['TXN_ID'];
			if ($status == "SUCCESS") {
				$data = array("TXN_STATUS"=>"SUCCESS","THIRDPARTY_REF"=>$thirdParty,"ERROR_CODE"=>$errorCode,"ERROR_DESC"=>$desc);
				$this->EncashLatest->_updateTransaction($data,$txnid,$this->ussdMsisdn);
				$status = "SUCCESS";
				$this->end = TRUE;
				$merchant_id = $this->_getElement("OPERATOR");
				if ($merchant_id == "TIGO") {
					$merchant = "TIGO";
				} else if ($merchant_id == "SMART") {
					$merchant = "SMART";
				} else {
					$merchant = "SELCOM-".$merchant_id;
				}
				$perc = $this->EncashLatest->_getCommsionRate( $merchant);
				$percentage = $perc ['COMMISSION_PERCENTAGE'];
				$this->EncashLatest->_setCommission($this->ussdMsisdn,$percentage,$amount);
				if ($merchant_id == "TIGO") {
					$merchant = "TIGO";
				} else if ($merchant_id == "SMART") {
					$merchant = "SMART";
				} else {
					$merchant = "SELCOM";
				}
				$this->EncashLatest->_updateMerchantBalance($amount,$merchant);
				$data = array (
						"TXN_REFERENCE" => $txnid,
						"REQUEST_TYPE" => "USSD",
						"AGENT_ID" => $this->_getElement("agent_id" ),
						"AGENT_MSISDN" => $this->ussdMsisdn,
						"OPERATOR_ID" => $this->_getElement("OPERATOR_ID" ),
						"MSISDN" => $mobile,
						"AMOUNT" => $amount,
						"TXN_STATUS" => "SUCCESS",
						"THIRDPARTY_REF" => $thirdParty,
						"ERROR_CODE" => $errorCode,
						"COMMENTS" => $desc 
				);
				$this->EncashLatest->_recordTransaction("airtime_transaction",$data);
				$op_balance = $this->_getElement("OP_BALANCE");
				$cl_balance = $op_balance - $amount;
				$commissionamount = $amount * $percentage/100;
				$cl_balance = $cl_balance + $commissionamount;
				$data = array (
						"TXN_REFERENCE" => $txnid,
						"TXN_TYPE" => "AIRTIME",
						"REQUEST_TYPE" => "USSD",
						"AGENT_ID" => $this->_getElement("agent_id" ),
						"AGENT_MSISDN" => $this->ussdMsisdn,
						"TXN_DEVICE_NUMBER" => $mobile,
						"TXN_AMOUNT" => $amount,
						"TXN_STATUS" => "SUCCESS",
						"OP_BALANCE"=>$op_balance,
						"CL_BALANCE"=>$cl_balance,
						"THIRDPARTY_REF" => $thirdParty,
						"ERROR_CODE" => $errorCode,
						"ERROR_DESC" => $desc,
						"TXN_ROUTE" =>$route
				);
				$this->EncashLatest->_recordTransaction("transaction_logs_success",$data);
				$commissionValue = $amount * $perc['COMMISSION_PERCENTAGE']/100;
				$commissionArray = array("AGENT_ID"=>$this->_getElement("agent_id"),"TXN_TYPE"=>"AIRTIME","TXN_AMOUNT"=>$amount,"TXN_COMMISSION_PERCENTAGE"=>$perc['COMMISSION_PERCENTAGE'],"TXN_COMMISSION_VALUE"=>$commissionValue);
				$this->EncashLatest->_recordTransaction("agent_commission_success",$commissionArray);
				$sales= $amount;
				$this->EncashLatest->_updateAgentAccount($cl_balance,$sales,$this->_getElement("agent_id"),"CLOSING_BALANCE");

				if($merchant == "SELCOM"){
						$selcom = array (
						"TXN_REFERENCE" => $txnid,
						"TXN_TYPE" => "AIRTIME",
						"REQUEST_TYPE" => "USSD",
						"AGENT_MSISDN" => $this->ussdMsisdn,
						"TXN_DEVICE_NUMBER" => $mobile,
						"TXN_AMOUNT" => $amount,
						"TXN_MOBILE_NUMBER"=>$mobile,
						"TXN_STATUS" => "SUCCESS",
						"THIRDPARTY_REF" => $thirdParty,
						"ERROR_CODE" => $errorCode,
						"ERROR_DESC" => $desc,
						"TXN_ROUTE" =>$route
				);
						$this->EncashLatest->_recordTransaction("transaction_logs_selcom",$selcom);
				}
				else if($merchant == "TIGO"){
					$tigo = array (
						"TXN_REFERENCE" => $txnid,
						"TXN_TYPE" => "AIRTIME",
						"REQUEST_TYPE" => "USSD",
						"AGENT_MSISDN" => $this->ussdMsisdn,
						"TXN_DEVICE_NUMBER" => $mobile,
						"TXN_AMOUNT" => $amount,
						"TXN_MOBILE_NUMBER"=>$mobile,
						"TXN_STATUS" => "SUCCESS",
						"THIRDPARTY_REF" => $thirdParty,
						"ERROR_CODE" => $errorCode,
						"ERROR_DESC" => $desc,
						"TXN_ROUTE" =>$route
				);
						$this->EncashLatest->_recordTransaction("transaction_logs_tigo",$tigo);

				}
				else{

					$smart = array (
						"TXN_REFERENCE" => $txnid,
						"TXN_TYPE" => "AIRTIME",
						"REQUEST_TYPE" => "USSD",
						"AGENT_MSISDN" => $this->ussdMsisdn,
						"TXN_DEVICE_NUMBER" => $mobile,
						"TXN_AMOUNT" => $amount,
						"TXN_MOBILE_NUMBER"=>$mobile,
						"TXN_STATUS" => "SUCCESS",
						"THIRDPARTY_REF" => $thirdParty,
						"ERROR_CODE" => $errorCode,
						"ERROR_DESC" => $desc,
						"TXN_ROUTE" =>$route
				);
						$this->EncashLatest->_recordTransaction("transaction_logs_smart",$smart);

				}
				return "Airtime Transaction has been processed Successfully";
			}
			else{
				$status = "FAILED";
				$this->end = TRUE;
				$data = array("TXN_STATUS"=>"FAILED","THIRDPARTY_REF"=>$thirdParty,"ERROR_CODE"=>$errorCode,"ERROR_DESC"=>$desc);
				$this->EncashLatest->_updateTransaction($data,$txnid,$this->ussdMsisdn);
				$this->EncashLatest->_rollbackTransaction($this->ussdMsisdn,$this->_getElement("amount"));
				return "Failed Please Try later";
				
			}
		}
	}
	
	//Paybills
	
	function encash_paybills(){
		$tempString = $this->lang->line('menu_heading_'.$this->_getElement("language"))."\n";
		$menu = $this->EncashLatest->getPaybillsMenu($this->_getElement("language"));
		$this->min = 1;
		$this->max = 3;
		$this->_setElement("nextMenu", "set_Paybills");
		foreach ($menu as $index => $key) {
			$tempString .= $menu[$index]['menu_order'] . ". " . $menu[$index]['node'] . " \n";
		}
		return $tempString;
	}
	
	function set_Paybills() {
		$min = 1;
		$max = 3;
		$isValid = $this->_validatestaticInput($min, $max, $this->ussdMsg);
		if ($isValid) {
			if ($this->ussdMsg == 1) {
				return $this->encash_Postpaid();
			}
			$function = $this->EncashLatest->getPaybillsAction($this->ussdMsg, "main", "1");
			$action = $function['menu_action'];
			return call_user_func(array($this, $action));
		} else {
			return $this->main();
		}
	}
	function encash_Postpaid() {
		$tempString = $this->lang->line('menu_heading_'.$this->_getElement("language"))."\n";
		$menu = $this->EncashLatest->getPaybillsChildMenu($this->_getElement("language"),1,"2");
		$this->_setElement("nextMenu", "encash_SetPostpaid");
		foreach ($menu as $index => $key) {
			$tempString .= $menu[$index]['menu_order'] . ". " . $menu[$index]['node'] . " \n";
		}
		return $tempString;
		
	}
	function encash_SetPostpaid() {
	if($this->ussdMsg==1){
		
		$this->_setElement("LUKU_TYPE","POSTPAID-LUKU");
		return $this->encash_paybills_meter();
	}
	else if($this->ussdMsg == 2){
		return $this->encash_Postpaid_Mobile();
	}
	else{
		
		return $this->encash_Postpaid();
	}
		
	}
	function encash_Postpaid_Mobile(){
		$tempString = "Enter the mobilenumber";
		$this->_setElement("nextMenu", "encash_set_Mobile");
		return $tempString;
	}
	function encash_set_Mobile(){
		
		$operator = $this->getOperator($this->ussdMsg);
		if ($operator['OPERATOR_NAME'] == "UNKNOWN") {
			return $this->encash_Postpaid_mobile();
		} else {
			$this->_setElement("mobile_number", $this->ussdMsg);
			$this->_setElement("OPERATOR", $operator['OPERATOR_NAME']);
			$this->_setElement("OPERATOR_ID", $operator['OPERATOR_ID']);
			return $this->encash_postpaid_Outstanding();
		}
	}
	function encash_postpaid_Outstanding() {
        $tempString = "Sorry this number doesnot have any outstanding";
        $this->end = TRUE;
        return $tempString;
    }
	
	function encash_Payments() {
		$tempString = $this->lang->line('menu_heading_'.$this->_getElement("language"))."\n";
		$menu = $this->EncashLatest->getPaybillsChildMenu($this->_getElement("language"),$this->ussdMsg,3);
		$this->min = 1;
		$this->max = 3;
		$this->_setElement("nextMenu", "set_Payments");
		foreach ($menu as $index => $key) {
			$tempString .= $menu[$index]['menu_order'] . ". " . $menu[$index]['node'] . " \n";
		}
	
		return $tempString;
	}
	
	function set_Payments() {
		$min = 1;
		$max = 3;
		$isValid = $this->_validatestaticInput($min, $max, $this->ussdMsg);
		if ($isValid) {
			if($this->ussdMsg == 1){
				$this->_setElement("LUKU_TYPE","PREPAID-LUKU");
			}
			if($this->ussdMsg == 2){
				$this->_setElement("LUKU_TYPE","IBE");
			}
			$function = $this->EncashLatest->getPaymentsAction($this->ussdMsg, "child", "2","3");
			$action = $function['menu_action'];
			return call_user_func(array($this, $action));
		} else {
			return $this->encash_Payments();
		}
	
	}
	
	function encash_paybills_meter(){
		$tempString = $this->lang->line('Meter_Number_'.$this->_getElement("language"))."\n";
		$this->_setElement("LUKU_TYPE","PREPAID-LUKU");
		$this->_setElement("nextMenu","encash_set_Luku_paybills_meter_mobile");
		return $tempString;
	}
	
	function encash_set_Luku_paybills_meter_mobile(){
		$this->_setElement("METER_NUMBER",$this->ussdMsg);
		$tempString = $this->lang->line('mobile_'.$this->_getElement("language"))."\n";
		$this->_setElement("nextMenu","encash_Luku_Amount"); 
		return $tempString;
	}
	function encash_Luku_Amount(){
		$this->_setElement("mobile_number",$this->ussdMsg);
		$tempString = $this->lang->line('enter_amount_'.$this->_getElement("language"))."\n";
		 $this->_setElement("nextMenu","encash_Luku_setAmount");
		 return $tempString;
	}
	
	
	function encash_Luku_setAmount(){
		$amount = $this->ussdMsg;
		$balance = FALSE;
		$this->_setElement("amount",$amount);
		
		$main = $this->_getElement("MAIN_BALANCE");
		$commission = $this->_getElement("COMMISSION_BALANCE");
		$totbalance = $main+$commission;
		$this->_setElement("OP_BALANCE",$totbalance);
		if($amount > $main){
					$this->end = TRUE;
					return $this->lang->line('topup_account_'.$this->_getElement("language" ));
		}
		else {
			$this->_setElement("BalanceType","MAIN");
			$balance = TRUE;
			return $this->encash_Luku_confirmation();
		}
		
		
	}
	
	
	function encash_Luku_confirmation() {
		$tempString = "Select any option \n";
		$tempString .= "1. Confirm \n";
		$tempString .= "2. Cancel \n";
		$tempString .= "00. Go Back";
		$this->_setElement("nextMenu","password");
		$this->_setElement("endMenu","set_Luku_confirmation");
		$this->_setElement("prevMenu","encash_Luku_Amount");
		return $tempString;
	}
	
	function set_Luku_confirmation(){
		$txn_ref = $this->incrementalHash();
		$this->end = TRUE;
		$meterNumber = $this->_getElement("METER_NUMBER");
		$amount = $this->_getElement("amount");
		$agent_id = $this->_getElement("agent_id");
		$mobile = $this->_getElement("mobile_number");
		$merchantBalance = $this->EncashLatest->_checkMerchantAccount("SELCOM");
		$luku_type = $this->_getElement("LUKU_TYPE");
		if ($merchantBalance ['FINAL_FLOAT_VALUE'] < $amount) {
			$this->end = TRUE;
			return "Error Occured please try later ";
		} else {
			$route = "SELCOM";
			if ($this->_getElement("BalanceType" ) == "MAIN") {
				$this->EncashLatest->_updateMainBalance($amount,$this->ussdMsisdn);
				$transaction = array (
						"TXN_TYPE" => "LUKU",
						"REQUEST_TYPE" => "USSD",
						"TXN_REFERENCE" => $txn_ref,
						"TXN_AMOUNT" => $amount,
						"AGENT_MSISDN" => $this->ussdMsisdn,
						"TXN_DEVICE_NUMBER" => $this->_getElement("METER_NUMBER"),
						"TXN_MOBILE_NUMBER" => $mobile,
						"TXN_ROUTE" => $route,
						"TXN_STATUS" => "SUBMITED"
				);
				$this->EncashLatest->_LogTransaction($transaction);
			} 
			$result = $this->_processLUKU($meterNumber,$amount,$mobile,$txn_ref,$luku_type);
			$errorCode = $result ['ERROR_CODE'];
			$status = $result ['TXN_STATUS'];
			$desc = $result ['TXN_DESCRIPTION'];
			$thirdParty = $result ['TXN_THIRD_REFERENCE'];
			$unit = $result['EXTRA'];
			$txnid = $result ['TXN_ID'];
			if ($status == "SUCCESS") {
				$token = $result['LUKU_TOKEN'];
				$data = array("TXN_STATUS"=>"SUCCESS","THIRDPARTY_REF"=>$thirdParty,"ERROR_CODE"=>$errorCode,"ERROR_DESC"=>$desc);
				$this->EncashLatest->_updateTransaction($data,$txnid,$this->ussdMsisdn);
				$status = "SUCCESS";
				$this->end = TRUE;
				$career = "SELCOM-LUKU";
				$perc = $this->EncashLatest->_getCommsionRate( $career);
				$percentage = $perc ['COMMISSION_PERCENTAGE'];
				$this->EncashLatest->_setCommission($this->ussdMsisdn,$percentage,$amount);
				
				$this->EncashLatest->_updateMerchantBalance($amount,"SELCOM");
				$data = array (
						"TXN_REFERENCE" => $txnid,
						"THIRDPARTY_REF" => $thirdParty,
						"REQUEST_TYPE" => "USSD",
						"AGENT_ID" => $this->_getElement("agent_id" ),
						"AGENT_MSISDN" => $this->ussdMsisdn,
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
				$this->EncashLatest->_recordTransaction("luku_transaction",$data);
				$commissionamount = $amount * $percentage/100;
				$cl_balance = $cl_balance + $commissionamount;
				$data = array (
						"TXN_REFERENCE" => $txnid,
						"TXN_TYPE" => $luku_type,
						"REQUEST_TYPE" => "USSD",
						"AGENT_ID" => $this->_getElement("agent_id" ),
						"AGENT_MSISDN" => $this->ussdMsisdn,
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

				$this->EncashLatest->_recordTransaction("transaction_logs_success",$data);
				$commissionValue = $amount * $perc['COMMISSION_PERCENTAGE']/100;
				$commissionArray = array("AGENT_ID"=>$this->_getElement("agent_id"),"TXN_TYPE"=>"LUKU","TXN_AMOUNT"=>$amount,"TXN_COMMISSION_PERCENTAGE"=>$perc['COMMISSION_PERCENTAGE'],"TXN_COMMISSION_VALUE"=>$commissionValue);
				$this->EncashLatest->_recordTransaction("agent_commission_success",$commissionArray);
				$sales = $amount;
				$this->EncashLatest->_updateAgentAccount($cl_balance,$sales,$this->_getElement("agent_id" ),"CLOSING_BALANCE");
				$sms = "TOKEN : ".$token. "\n";
				$sms .= $unit;
				$sms .= "Amount : ".$amount;
				$sms  = urlencode($sms);
				$msisdn  = $this->formatmobile($mobile);

				$selcom = array (
						"TXN_REFERENCE" => $txnid,
						"TXN_TYPE" => $luku_type,
						"REQUEST_TYPE" => "USSD",
						"AGENT_MSISDN" => $this->ussdMsisdn,
						"TXN_DEVICE_NUMBER" => $meterNumber,
						"TXN_AMOUNT" => $amount,
						"TXN_MOBILE_NUMBER"=>$mobile,
						"TXN_STATUS" => "SUCCESS",
						"THIRDPARTY_REF" => $thirdParty,
						"ERROR_CODE" => $errorCode,
						"ERROR_DESC" => $desc,
						"TXN_ROUTE" =>$route
				);
						$this->EncashLatest->_recordTransaction("transaction_logs_selcom",$selcom);
				$sms = "TOKEN : ".$token. "\n";
				$sms .= $unit ."\n";
				$sms  = urlencode($sms);
                    $msisdn  = $this->ussdMsisdn;
				file_get_contents("http://192.168.168.2:13013/cgi-bin/sendsms?username=airtelTX&password=greentx&to=$msisdn&text=$sms&from=15670&dlr-mask=31");		
				return "Luku Transaction has been processed Successfully . TOKEN :".$token;
			}
			else{
				$status = "FAILED";
				$this->end = TRUE;
				$data = array("TXN_STATUS"=>"FAILED","THIRDPARTY_REF"=>$thirdParty,"ERROR_CODE"=>$errorCode,"ERROR_DESC"=>$desc);
				$this->EncashLatest->_updateTransaction($data,$txnid,$this->ussdMsisdn);
				$this->EncashLatest->_rollbackTransaction($this->ussdMsisdn,$this->_getElement("amount"));
				return "Failed Please Try later";
		
			}
		}
		
		
	}
	function encash_DTH(){
		$tempString = $this->lang->line('menu_heading_'.$this->_getElement("language"))."\n";
		$menu = $this->EncashLatest->getPaybillsChildMenu($this->_getElement("language"),3,2);
		$this->_setElement("nextMenu", "set_DTH");
		foreach ($menu as $index => $key) {
			$tempString .= $menu[$index]['menu_order'] . ". " . $menu[$index]['node'] . " \n";
		}
		return $tempString;
	}
	
	function set_DTH(){
		$min = 1;
		$max = 4;
		$isValid = $this->_validatestaticInput($min, $max, $this->ussdMsg);
		if ($isValid) {
			$dth_service = $this->EncashLatest->getDTHServiceName($this->ussdMsg);
			$this->_setElement("DTH_SERVICE",$dth_service['english_node']);
			$function = $this->EncashLatest->getPaymentsAction($this->ussdMsg, "child", "3","2");
			$action = $function['menu_action'];
			return call_user_func(array($this, $action));
		} else {
			return $this->encash_DTH();
		}
		
	}
	function encash_paybills_dth_card(){
		$tempString = $this->lang->line('Meter_Number_'.$this->_getElement("language"))."\n";
	
		$this->_setElement("nextMenu","encash_set_DTH_paybills_meter_mobile");
		return $tempString;
	}

function encash_set_DTH_paybills_meter_mobile(){
		$this->_setElement("CARD_NUMBER",$this->ussdMsg);
		$tempString = $this->lang->line('mobile_'.$this->_getElement("language"))."\n";
		$this->_setElement("nextMenu","encash_DTH_Amount");
		return $tempString;
	}
	function encash_DTH_Amount(){
		$this->_setElement("mobile_number",$this->ussdMsg);
		$tempString = $this->lang->line('enter_amount_'.$this->_getElement("language"))."\n";
		$this->_setElement("nextMenu","encash_DTH_setAmount");
		return $tempString;
	}
	
	
	function encash_DTH_setAmount(){
		$amount = $this->ussdMsg;
		$balance = FALSE;
		$this->_setElement("amount",$amount);
	
		$main = $this->_getElement("MAIN_BALANCE");
		$commission = $this->_getElement("COMMISSION_BALANCE");
		$totbalance = $main+$commission;
		$this->_setElement("OP_BALANCE",$totbalance);
		if ($amount > $main) { // will check if the amount is greater than main balance
			$balance = FALSE;
			if (!$balance) {
				$totbalance = $main + $commission;
				if ($amount > $totbalance) {
					$balance = FALSE;
					$this->end = TRUE;
					return "Sorry your account is empty. Please  top up your account";
				} else {
	
					if ($main > 0 && $main < $amount) {
						$mainamt = $amount - $main;
						$commissionBalance = $commission - $mainamt;
					} else if ($main == 0) {
						$mainamt = 0;
						$commissionBalance = $commission - $amount;
					}
					$balance = TRUE;
					$this->_setElement("MAIN_AMT",$mainamt);
					$this->_setElement("COMMISSION_BALANCE",$commissionBalance);
					$this->_setElement("BalanceType","Main and commission");
					$balance = TRUE;
					return $this->encash_DTH_confirmation();
				}
			}
		} else {
			$this->_setElement("BalanceType","MAIN");
			$balance = TRUE;
			return $this->encash_DTH_confirmation();
		}
	
	}
	
	
	function encash_DTH_confirmation() {
		$tempString = "Select any option \n";
		$tempString .= "1. Confirm \n";
		$tempString .= "2. Cancel \n";
		$tempString .= "00. Go Back";
		$this->_setElement("nextMenu","password");
		$this->_setElement("endMenu","set_DTH_confirmation");
		$this->_setElement("prevMenu","encash_DTH_Amount");
		return $tempString;
	}
	
	function set_DTH_confirmation(){
		$txn_ref = $this->incrementalHash();
		$this->end = TRUE;
		$cardNumber = $this->_getElement("CARD_NUMBER");
		$amount = $this->_getElement("amount");
		$agent_id = $this->_getElement("agent_id");
		$mobile = $this->_getElement("mobile_number");
		$merchantBalance = $this->EncashLatest->_checkMerchantAccount("SELCOM");
		if ($merchantBalance ['FINAL_FLOAT_VALUE'] < $amount) {
			$this->end = TRUE;
			return "Error Occured please try later ";
		} else {
			$route = "SELCOM";
			if ($this->_getElement("BalanceType" ) == "MAIN") {
				$this->EncashLatest->_updateMainBalance($amount,$this->ussdMsisdn);
				$transaction = array (
						"TXN_TYPE" => "DTH",
						"REQUEST_TYPE" => "USSD",
						"TXN_REFERENCE" => $txn_ref,
						"TXN_AMOUNT" => $amount,
						"AGENT_MSISDN" => $this->ussdMsisdn,
						"TXN_DEVICE_NUMBER" => $cardNumber,
						"TXN_MOBILE_NUMBER" => $mobile,
						"TXN_ROUTE" => $route,
						"TXN_STATUS" => "SUBMITED"
				);
				$this->EncashLatest->_LogTransaction($transaction);
			} else {
				$this->EncashLatest->_updateCommissionBalance($amount,$this->_getElement("MAIN_AMT" ),$this->_getElement("COMMISSION_BALANCE" ),$this->ussdMsisdn);
				$transaction = array (
						"TXN_TYPE" => "DTH",
						"REQUEST_TYPE" => "USSD",
						"AGENT_ID" => $this->_getElement("agent_id" ),
						"TXN_REFERENCE" => $txn_ref,
						"TXN_AMOUNT" => $amount,
						"AGENT_MSISDN" => $this->ussdMsisdn,
						"TXN_DEVICE_NUMBER" => $cardNumber,
						"TXN_MOBILE_NUMBER" => $mobile,
						"TXN_ROUTE" => $route,
						"TXN_STATUS" => "SUBMITED"
				);
				$this->EncashLatest->_LogTransaction($transaction);
			}
			$result = $this->_processDTH($cardNumber,$amount,$mobile,$txn_ref,$dth_type);
			$errorCode = $result ['ERROR_CODE'];
			$status = $result ['TXN_STATUS'];
			$desc = $result ['TXN_DESCRIPTION'];
			$thirdParty = $result ['TXN_THIRD_REFERENCE'];
				
			$txnid = $result ['TXN_ID'];
			if ($status == "SUCCESS") {
				$token = $result['DTH_TOKEN'];
				$data = array("TXN_STATUS"=>"SUCCESS","THIRDPARTY_REF"=>$thirdParty,"ERROR_CODE"=>$errorCode,"ERROR_DESC"=>$desc);
				$this->EncashLatest->_updateTransaction($data,$txnid,$this->ussdMsisdn);
				$status = "SUCCESS";
				$this->end = TRUE;
				$career = "SELCOM-".$this->_getElement("DTH_TYPE");
				$perc = $this->EncashLatest->_getCommsionRate( $career);
				$percentage = $perc ['COMMISSION_PERCENTAGE'];
				$this->EncashLatest->_setCommission($this->ussdMsisdn,$percentage,$amount);
	
				$this->EncashLatest->_updateMerchantBalance($amount,$career);

				$data = array (
						"TXN_REFERENCE" => $txnid,
						"THIRDPARTY_REF" => $thirdParty,
						"REQUEST_TYPE" => "USSD",
						"AGENT_ID" => $this->_getElement("agent_id" ),
						"AGENT_MSISDN" => $this->ussdMsisdn,
						"SERVICE_NAME"=>"DTH",
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
				$this->EncashLatest->_recordTransaction("dth_transaction",$data);
				$commissionamount = $amount * $percentage/100;
				$cl_balance = $cl_balance + $commissionamount;
				$data = array (
						"TXN_REFERENCE" => $txnid,
						"TXN_TYPE" => "DTH",
						"REQUEST_TYPE" => "USSD",
						"AGENT_ID" => $this->_getElement("agent_id" ),
						"AGENT_MSISDN" => $this->ussdMsisdn,
						"TXN_DEVICE_NUMBER" => $cardNumber,
						"TXN_AMOUNT" => $amount,
						"TXN_STATUS" => "SUCCESS",
						"THIRDPARTY_REF" => $thirdParty,
						"OP_BALANCE" =>$op_balance,
						"CL_BALANCE"=>$cl_balance,
						"ERROR_CODE" => $errorCode,
						"ERROR_DESC" => $result['COMMENTS'],
						"TXN_ROUTE" =>$route
				);
				$this->EncashLatest->_recordTransaction("transaction_logs_success",$data);
				$commissionValue = $amount * $perc['COMMISSION_PERCENTAGE']/100;
				$data = array("AGENT_ID"=>$this->_getElement("agent_id"),"TXN_TYPE"=>"AIRTIME","TXN_AMOUNT"=>$amount,"TXN_COMMISSION_PERCENTAGE"=>$perc['COMMISSION_PERCENTAGE'],"TXN_COMMISSION_VALUE"=>$commissionValue);
				$this->EncashLatest->_recordTransaction("agent_commission_success",$data);
				$sales = $amount;
				$this->EncashLatest->_updateAgentAccount($cl_balance,$sales,$this->_getElement("agent_id"),"CLOSING_BALANCE");

				$selcom = array (
						"TXN_REFERENCE" => $txnid,
						"TXN_TYPE" => "DTH",
						"REQUEST_TYPE" => "USSD",
						"AGENT_MSISDN" => $this->ussdMsisdn,
						"TXN_DEVICE_NUMBER" => $cardNumber,
						"TXN_AMOUNT" => $amount,
						"TXN_MOBILE_NUMBER"=>$mobile,
						"TXN_STATUS" => "SUCCESS",
						"THIRDPARTY_REF" => $thirdParty,
						"ERROR_CODE" => $errorCode,
						"ERROR_DESC" => $desc,
						"TXN_ROUTE" =>$route
				);
						$this->EncashLatest->_recordTransaction("transaction_logs_selcom",$selcom);

				return "DTH Transaction has been processed Successfully";
			}
			else{
				$status = "FAILED";
				$this->end = TRUE;
				$data = array("TXN_STATUS"=>"FAILED","THIRDPARTY_REF"=>$thirdParty,"ERROR_CODE"=>$errorCode,"ERROR_DESC"=>$desc);
				$this->EncashLatest->_updateTransaction($data,$txnid,$this->ussdMsisdn);
				$this->EncashLatest->_rollbackTransaction($this->ussdMsisdn,$this->_getElement("amount"));
				return "Failed Please Try later";
	
			}
		}
	
	
	}
	function encash_mkopo(){
		
		$tempString = "Enter Mobile number";
		$this->_setElement("nextMenu","encash_setmkopo");
		return $tempString;
	}
	
	function encash_setmkopo(){
			$tempString = "Enter Amount";
			$this->_setElement("nextMenu","encash_MkopoEnd");
			return $tempString;
	}
	
	function encash_MkopoEnd(){
		$tempString = "Your transaction is being processed";
		$this->end = TRUE;
		return $tempString;
		
}
function encash_service() {
	$tempString = $this->lang->line('menu_heading_'.$this->_getElement("language"))."\n";
	$menus = $this->EncashLatest->_getServiceMenu($this->_getElement("language"));
	foreach($menus as $index=>$key){
		$tempString .= $menus[$index]['menu_order'].". ".$menus[$index]['node']."\n";
	}
	$tempString .="00.back";
	$this->_setElement("nextMenu","encash_setService");
	$this->_setElement("prevMenu","encash_main");
	return $tempString;
}
function encash_setService(){
$action = $this->ussdMsg;
	$min = 1;
	$max = 8;
	$isValid = $this->_validatestaticInput($min, $max, $this->ussdMsg);
	if ($isValid) {
		$function = $this->EncashLatest->_getServiceAction($this->ussdMsg);
		$function = $function['menu_action'];
		return call_user_func(array($this, $function));
	} else {
		return $this->main();
	}
}

function encash_checkBalance(){
	
	$result = $this->EncashLatest->_getBalanceStatus($this->ussdMsisdn);
	
	$tempString = "Dear Encash Agent your Main Balance :".$result['MAIN_BALANCE']."\n Commission Balance :".$result['COMMISSION_BALANCE'];
	$this->end= TRUE;
	return $tempString;
}

function encash_registerAgent(){
	$balance = $this->EncashLatest->_getBalanceStatus($this->ussdMsisdn);
	
	if($balance['MAIN_BALANCE'] >= "20000"){
		$tempString = "Enter Number";
		$this->_setElement("nextMenu", "encash_registerEnd");
		return $tempString;
	}
	else{
		$tempString = "Please topup your account to register an agent";
		$this->end = TRUE;
		return $tempString;
	}
	
}

function encash_registerEnd(){
	$mobile = $this->ussdMsg;
	
	$mobile = $this->formatmobile($mobile);
	$exist = $this->EncashLatest->_checkUser($mobile);
	if(!empty($exist)){
		$this->end = TRUE;
		return "Number is already registerd";
	}
	$operator = $this->getOperator($mobile);
	if($operator['OPERATOR_NAME'] == "UNKNOWN"){
		$this->end = TRUE;
		return "Invalid Mobile Number";
		
	}
	else{
		$reg['AGENT_MSISDN'] = $mobile;
		$id = $this->EncashLatest->_recordTransaction("agent_reference_number",$reg);
        $ref = $id;
		$data['AGENT_MSISDN'] = $mobile;
		$data['AGENT_NAME'] = NULL;
		$data['AGENT_LANGUAGE'] = NULL;
		$data['SUPER_AGENT_ID'] = $this->_getElement("agent_id");
		$data['AGENT_TYPE_ID'] = 1;
		$data['AGENT_REF_NUMBER'] = $ref;
		$data['AGENT_FLAG'] = 1;
		$data['AGENT_PASSWORD'] = md5('9999');
		$data['REGION_ID'] = 1;
		$data['SUPER_AGENT_REFERENCE'] = $this->_getElement("super_agent_ref");

		$id = $this->EncashLatest->_recordAgentDetails($data);

		$reg['AGENT_REF_NUMBER'] = $ref;

		$reg['AGENT_MSISDN'] = $mobile;
		$reg['SUPER_AGENT_MSISDN'] = $this->ussdMsisdn;
//		$id = $this->EncashLatest->_registerAgent($reg);


		if($id >=1){
			$this->end = TRUE;
			$password = md5('9999');
			$logPassword = array('AGENT_MSISDN'=>$mobile,"AGENT_PASSWORD"=>$password); 
			$this->EncashLatest->_recordTransaction("agent_password_transactions",$logPassword);

		$balance = array("AGENT_MSISDN"=>$mobile,"SUPER_AGENT_MSISDN"=>$this->ussdMsisdn,"MAIN_BALANCE"=>0,"COMMISSION_BALANCE"=>0,"LAST_TRANSACTION_VALUE"=>0,"TOTAL_AVAIL_BALANCE"=>0);
		$this->EncashLatest->_recordTransaction("agent_balance",$balance);
			

			return "Agent details recorded successfully please activate the account by balance transfer";
			}
		else{
			return "Error Occured";
		}
	}
}


function encash_send_balance(){//me2utransfer

	if($this->_getElement("agent_type_id")!=2){
		$this->end = TRUE;
		return "Please upgrade your account to Super Agent";

	}
	$tempString = $this->lang->line('mobile_'.$this->_getElement("language"));
	$this->_setElement("nextMenu","encash_checkLimit");
	return $tempString;
}




function encash_checkLimit(){

	$limit = $this->EncashLatest->_checkLimit($this->ussdMsisdn);
	
	$isLimit = FALSE;
	if($limit['CNT'] >= 5||$limit['AMT'] >=100000){

		$isLimit = TRUE;
	}

	if($isLimit){
		$this->end = TRUE;
		return "Balance Transfer limit has reached its limit";

	}
	else{
		$this->_setElement("AGENT_MOBILE",$this->ussdMsg);
		return $this->encash_send_balance_amount();

	}
}
function encash_send_balance_amount(){
$operator = $this->getOperator($this->ussdMsg);
if($operator == "UNKNOWN"){
	$this->end = TRUE;
	return "Invalid Mobile Number"; 
}
else{
$mobile = $this->formatmobile($this->ussdMsg);
$exist = $this->EncashLatest->_checkUser($mobile);
if(empty($exist)){

	$this->end = TRUE;
	return "Invalid Mobile Number";
}
else{
$this->_setElement("super_agent_id",$this->_getElement("agent_id"));	
$this->_setElement("agent_mobile",$mobile);	
$this->_setElement("agent_id",$exist['ID']);	
$tempString = $this->lang->line('enter_amount_'.$this->_getElement("language"));
$this->_setElement("nextMenu","encash_send_balance_checkBalance");
return $tempString;
}
}
}

function encash_send_balance_checkBalance(){
$amount = $this->ussdMsg;

if($amount >=$this->_getElement("MAIN_BALANCE")){

	$this->end = TRUE;
	return " Sorry you do not have sufficent balance ";
}
else{
$this->_setElement("amount",$amount);

return $this->encash_send_balance_Confirm();
}

}
function encash_send_balance_Confirm(){
$tempString = "1. Confirm \n2. Cancel";
$this->_setElement("nextMenu","password");
		
		$this->_setElement("endMenu","encash_send_balance_confirmation");
		$this->_setElement("prevMenu","encash_main");
return $tempString;
}

function encash_send_balance_confirmation(){
$table = "me2u_transfers";
$data['SUPER_AGENT_ID'] = $this->_getElement("super_agent_id");
$data['AGENT_ID'] = $this->_getElement("agent_id");
$data['SUPER_AGENT_MSISDN'] = $this->ussdMsisdn;
$data['AGENT_MSISDN'] = $this->_getElement("agent_mobile");
$data['TXN_AMOUNT'] = $this->_getElement("amount");
$this->EncashLatest->_recordTransaction($table,$data);

$main = $this->_getElement("MAIN_BALANCE");
		$commission = $this->_getElement("COMMISSION_BALANCE");
		$totbalance = $main+$commission;
		$this->_setElement("OP_BALANCE",$totbalance);

$isnotActivated = $this->EncashLatest->_checkActivation($data['AGENT_MSISDN']);
if(!empty($isnotActivated)){
$this->EncashLatest->_updateAgentBalance($this->ussdMsisdn,$data['AGENT_MSISDN'],$data['TXN_AMOUNT'],$activation = TRUE);
$Activationdata['SUPER_AGENT_ID']  = $this->_getElement("super_agent_id");
$Activationdata['AGENT_ID'] = $this->_getElement("agent_id");
$Activationdata['AMOUNT_TRANSFERRED'] = $this->_getElement("amount");
$Activationdata['ACTIVATION_COMMISSION'] = 500;
$this->EncashLatest->_recordTransaction("agent_activation",$Activationdata);
$mobile = $this->formatmobile($this->_getElement("agent_mobile"));
$accounts = array("AGENT_ID"=>$this->_getElement("agent_id"),"AGENT_MSISDN"=>$mobile,"OPENING_BALANCE"=>$this->_getElement("amount"),"CLOSING_BALANCE"=>$this->_getElement("amount"),"TOTAL_SALES"=>0);

$allocationData = array("ALLOCATION_MSISDN"=>$this->_getElement("agent_mobile"),"ALLOCATION_TYPE"=>"Balance Transfer","ALLOCATION_DETAILS"=>"Balance Transferred from ".$this->ussdMsisdn,"AMOUNT"=>$this->_getElement("amount"),"AGENT_ID"=>$this->_getElement("agent_id"));
$date = date("Ymdhis");
$txnLogs = array("TXN_TYPE"=>"Balance Transfer","TXN_REFERENCE"=>$date,"THIRDPARTY_REF"=>$date,"TXN_AMOUNT"=>$this->_getElement("amount"),"AGENT_ID"=>$this->_getElement("super_agent_id"),"AGENT_MSISDN"=>$this->ussdMsisdn,"TXN_DEVICE_NUMBER"=>$this->_getElement("agent_mobile"),"TXN_MOBILE_NUMBER"=>$this->_getElement("agent_mobile"),"TXN_ROUTE"=>"INTERNAL","TXN_STATUS"=>"SUCCESS","ERROR_CODE"=>"0000","ERROR_DESC"=>"Balance Transferred");
$this->EncashLatest->_recordTransaction("transaction_logs_all",$txnLogs);
$op_balance = $this->_getElement("OP_BALANCE");
$cl_balance = $op_balance - $this->_getElement("amount");
$txnLogs['OP_BALANCE'] = $op_balance ;
$txnLogs['CL_BALANCE'] = $cl_balance;
$this->EncashLatest->_recordTransaction("transaction_logs_success",$txnLogs);
$this->EncashLatest->_recordTransaction("agent_accounts",$accounts);
$this->EncashLatest->_recordTransaction("agent_float_allocation",$allocationData);
$this->end = TRUE;
return "Balance Transferred Successfully";

}
else{
$this->EncashLatest->_updateAgentBalance($this->ussdMsisdn,$data['AGENT_MSISDN'],$data['TXN_AMOUNT'],$activation = FALSE);
$allocationData = array("ALLOCATION_MSISDN"=>$data['AGENT_MSISDN'],"ALLOCATION_TYPE"=>"Balance Transferred","ALLOCATION_DETAILS"=>"Balance Transferred from ".$this->ussdMsisdn,"AMOUNT"=>$data['TXN_AMOUNT'],"AGENT_ID"=>$this->_getElement("agent_id"));
$date = date("Ymdhis");
$txnLogs = array("TXN_TYPE"=>"Balance Transfer","TXN_REFERENCE"=>$date,"THIRDPARTY_REF"=>$date,"TXN_AMOUNT"=>$this->_getElement("amount"),"AGENT_ID"=>$this->_getElement("super_agent_id"),"AGENT_MSISDN"=>$this->ussdMsisdn,"TXN_DEVICE_NUMBER"=>$this->_getElement("agent_mobile"),"TXN_MOBILE_NUMBER"=>$this->_getElement("agent_mobile"),"TXN_ROUTE"=>"INTERNAL","TXN_STATUS"=>"SUCCESS","ERROR_CODE"=>"0000","ERROR_DESC"=>"Balance Transferred");
$this->EncashLatest->_recordTransaction("transaction_logs_all",$txnLogs);

$op_balance = $this->_getElement("OP_BALANCE");
$cl_balance = $op_balance - $this->_getElement("amount");

$txnLogs = array("TXN_TYPE"=>"Balance Transfer","TXN_REFERENCE"=>$date,"THIRDPARTY_REF"=>$date,"TXN_AMOUNT"=>$this->_getElement("amount"),"AGENT_ID"=>$this->_getElement("super_agent_id"),"AGENT_MSISDN"=>$this->ussdMsisdn,"TXN_DEVICE_NUMBER"=>$this->_getElement("agent_mobile"),"TXN_MOBILE_NUMBER"=>$this->_getElement("agent_mobile"),"TXN_ROUTE"=>"INTERNAL","TXN_STATUS"=>"SUCCESS","ERROR_CODE"=>"0000","ERROR_DESC"=>"Balance Transferred","OP_BALANCE"=>$op_balance,"CL_BALANCE"=>$cl_balance);

$this->EncashLatest->_recordTransaction("transaction_logs_success",$txnLogs);

$this->EncashLatest->_updateAgentAccount($data['TXN_AMOUNT'],"",$this->_getElement("agent_id"),"");
$this->EncashLatest->_recordTransaction("agent_float_allocation",$allocationData);
$this->end = TRUE;
return "Balance Transferred Successfully";
}
}

function encash_change(){
    $tempString = "Enter your current password";
    $this->_setElement("nextMenu","encash_currentPassword");
    return $tempString;
}
function encash_currentPassword(){
    
    $result = $this->EncashLatest->_validatePassword($this->ussdMsisdn, md5($this->ussdMsg));
    if(empty($result)){
       $this->end = TRUE;
       return "Wrong Password";
    }
    else{
        
        return $this->encash_SetPassword();
    }
}

function encash_SetPassword(){
    
    $tempString = "Enter new password";
    $this->_setElement("nextMenu","encash_newPassword");
    return $tempString;
}

function encash_newPassword(){
    $password = $this->ussdMsg;
    $password = md5($password);
    $this->EncashLatest->_updatePassword($this->ussdMsisdn,$password);
    $this->end = TRUE;
    $this->EncashLatest->_agentPasswordLog($password,$this->ussdMsisdn);
    return "Your password has been updated";
}


function encash_miniStatement(){
	$tempString = "****MINI STATEMENT*** \n";
	$result = $this->EncashLatest->_getMinistatement($this->ussdMsisdn); 
    foreach($result as $index=>$key){
        
        $desc = "Processed " . $result[$index]['TXN_TYPE']. " to " . $result[$index]['TXN_DEVICE_NUMBER'].  " for ". $result[$index]['TXN_AMOUNT'];
        $tempString .= $result[$index]['TXN_DATE'].": ".$desc." \n";
        
    }
    $this->end = TRUE;
    return $tempString;
}

function encash_changeLanguage(){

$tempString = "1. English \n";
    $tempString .="2. Kiswahili";
    
$this->_setElement("nextMenu","ecnash_setChangeLanguage");
    return $tempString;


}




function ecnash_setChangeLanguage(){
    
    $min = 1;
    $max = 2;
    $isValid = $this->_validatestaticInput($min, $max, $this->ussdMsg);
    if($isValid){
        
        if($this->ussdMsg == 1){
            $node = "english";
        }
        else{
            $node = "kiswahili";
        }
        $this->EncashLatest->_changeLanguage($node,$this->ussdMsisdn);
        $this->end = TRUE;
        return "Language has been changed successfully";
    }
    else{
        
        return $this->encash_changeLanguage();
    }
}


function encash_help(){
    //$operator = $this->input->get('OPER')
    $tempString = $this->lang->line('menu_heading_'.$this->_getElement("language"))."\n";
    $menu = $this->EncashLatest->_getServiceSubnode("8",$this->_getElement("language"));
    foreach($menu as $index=>$key){
        
        $tempString .= $menu[$index]['menu_order'].".".$menu[$index]['node'] . "\n";
    }
    $this->_setElement("nextMenu","encash_SetHelp");
    $this->_setElement("prevMenu","encash_service");
    return $tempString;
}


function encash_SetHelp(){
    
     $function = $this->EncashLatest->_getSubnodeServiceAction($this->ussdMsg,8);
            $function = $function['menu_action'];
            return call_user_func(array($this, $function));
            
} 
  function encash_howToPay(){
      $tempString  = $this->lang->line('menu_heading_'.$this->_getElement("language"))."\n";
      $tempString .= "1.TIGO PESA \n2.Airtel Money";
      $this->_setElement("nextMenu","how_Topay_end");
      return $tempString;
  }          
    function how_Topay_end(){  
      $tempString = $this->lang->line('How_To_Pay_'.$this->ussdMsg.'_english');
      $refID = $this->EncashLatest->_getRefID($this->ussdMsisdn);
      $tempString = str_replace("#Ref#", $refID['refID'], $tempString);
      $this->end = TRUE;
      return $tempString;
      
    }

    function encash_callcenter(){
    $tempString = "customer  Care number : 06846969995";
    $this->end = TRUE;
    return $tempString;
    
}

function encash_commissionTransfer(){
	return $this->encash_setAmountcommissionTransfer();
	$tempString = $this->lang->line('enter_amount_'.$this->_getElement('language'));
	$this->_setElement("nextMenu","encash_setAmountcommissionTransfer");
	$this->_setElement("prevMenu","encash_main");
	return $tempString;
}


function encash_setAmountcommissionTransfer(){
//$this->_setElement("amount",$this->ussdMsg);

if($this->_getElement('COMMISSION_BALANCE') <= 0){
	$this->end = TRUE;
	return "You dont have sufficent balance to transfer";
}
else{
	$amount = $this->_getElement('COMMISSION_BALANCE');
	$this->_setElement("amount",$amount);
return  $this->encash_commissionTransferConfirm();

}
}

function encash_commissionTransferConfirm(){
$tempString = "1. confirm \n2. Cancel";
$this->_setElement("nextMenu","password");
$this->_setElement("endMenu","encash_commissionTransferEnd");
return $tempString;
}


function encash_commissionTransferEnd(){
	$main = $this->_getElement("MAIN_BALANCE");
		$commission = $this->_getElement("COMMISSION_BALANCE");
		$totbalance = $main+$commission;
		
	$amount = $this->_getElement("amount");

	$this->EncashLatest->_commissionTransfer($this->ussdMsisdn,$amount);
	$data = array("AGENT_ID"=>$this->_getElement("agent_id"),"ALLOCATION_TYPE"=>"COMMISSION TRANSFER","ALLOCATION_DETAILS"=>"Commission amount transferred","ALLOCATION_MSISDN"=>$this->ussdMsisdn,"AMOUNT"=>$amount);
	$transactionID =  $this->incrementalHash();
	$transactionID = "IN-".$transactionID;

	$transfer = array("TXN_TYPE"=>"COMMISSION TRANSFER","REQUEST_TYPE"=>"USSD","TXN_REFERENCE"=>$transactionID,"THIRDPARTY_REF"=>$transactionID,"TXN_AMOUNT"=>$amount,"AGENT_ID"=>$this->_getElement("agent_id"),"AGENT_MSISDN"=>$this->ussdMsisdn,
		"TXN_DEVICE_NUMBER"=>$this->ussdMsisdn,"TXN_MOBILE_NUMBER"=>$this->ussdMsisdn,"TXN_ROUTE"=>"INTERNAL","TXN_STATUS"=>"SUCCESS","ERROR_CODE"=>"000","ERROR_DESC"=>"Commission Transferred Successfully");
	$this->EncashLatest->_recordTransaction("transaction_logs_all",$transfer);
	$transfer["OP_BALANCE"]= $totbalance;
	$transfer["CL_BALANCE"]= $totbalance;
	$this->EncashLatest->_recordTransaction("transaction_logs_success",$transfer);
	$this->EncashLatest->_recordTransaction("agent_float_allocation",$data);
	$commissionTransfer = array("AGENT_ID"=>$this->_getElement("agent_id"),"AGENT_MSISDN"=>$this->ussdMsisdn,"TXN_AMOUNT"=>$amount);
	$this->EncashLatest->_recordTransaction("commission_transfers",$commissionTransfer);
	$this->end = TRUE;
	return "Commission Amount Transferred Successfully";

}
// System & General functions

function _generateAgentID(){

	$agentID = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
	$isValid =  $this->EncashLatest->_validateRef($agentID);
	if($isValid){
		return $agentID;
	}
	else{
		return $this->_generateAgentID();
	}
}
	function password() {
		if ($this->ussdMsg != 1) {
			$this->end = TRUE;
			return "You have cancelled this transaction";
		} else {
			$tempString = "Enter your Pin";
			$this->_setElement("nextMenu","_passwordValidate");
			return $tempString;
		}
	}
	function _passwordValidate() {
		if (md5($this->ussdMsg ) == $this->_getElement("password" )) {
			$function = $this->_getElement("endMenu");
			return call_user_func(array (
					$this,
					$function 
			));
		} else {
			$tempString = "Wrong Password";
			$this->end = TRUE;
			return $tempString;
		}
	}
	function getOperator($mobile) {
		if (substr($mobile,0,1 ) == 0) {
			$mobile = $str = ltrim($mobile,0);
			$mobile = (255).$mobile;
		} else if (substr($mobile,0,1 ) >= 6) {
			$mobile = (255).$mobile;
		}
		$series = substr($mobile,0,5);
		$operator = $this->EncashLatest->_getOperator($series);
		if ($operator ['OPERATOR_ID'] >= 1) {
			$data = array (
					"OPERATOR_ID" => $operator ['OPERATOR_ID'],
					"OPERATOR_NAME" => $operator ['OPERATOR_NAME'] 
			);
			return $data;
		} else {
			$data = array (
					"OPERATOR_ID" => 0,
					"OPERATOR_NAME" => "UNKNOWN" 
			);
			return $data;
		}
	}
	function _validatestaticInput($min,$max,$input) {
		if (! is_numeric($input )) {
			return false;
		} else {
			
			if ($input >= $min && $input <= $max) {
				return true;
			} else {
				return false;
			}
		}
	}
	private function _deleteSession() {
		$this->redis->del($this->ussdSession);
		$this->redis->del("{$this->ussdSession}:{$this->ussdMsisdn}");
	}
	private function _setSessionState($currentMenu,$nextMenu) {
		$userMobile = $this->ussdMsisdn;
		$this->nextMen = $nextMenu;
		if ($userMobile [0] == "0") {
			$mobile = "+255".( int ) $this->ussdMsisdn;
		} else {
			$mobile = $this->ussdMsisdn;
		}
		$this->redis->hmset("{$this->ussdSession}:{$mobile}",array (
				'previous' => $currentMenu,
				'current' => $nextMenu,
				'choice' => json_encode($this->choices ) 
		));
	}
	private function _getSessionState() {
		$userMobile = $this->ussdMsisdn;
		if ($userMobile [0] == "0") {
			$mobile = "+255".( int ) $this->ussdMsisdn;
		} else {
			
			$mobile = $this->ussdMsisdn;
		}
		$data = $this->redis->hgetall("{$this->ussdSession}:{$mobile}");
		$count = count($data);
		$index = 0;
		while($count ) {
			$array [$data [$index]] = $data [$index + 1];
			$index += 2;
			$count -= 2;
		}
		$log = $array;
		return $array;
	}
	private function _getElement($element) {
		$userMobile = $this->ussdMsisdn;
		if ($userMobile [0] == "0") {
			$mobile = "+255".( int ) $this->ussdMsisdn;
		} else {
			$mobile = $this->ussdMsisdn;
		}
		return $this->redis->hget("{$this->ussdSession}:{$mobile}",$element);
	}
	private function _setElement($element,$type) {
		$userMobile = $this->ussdMsisdn;
		if ($userMobile [0] == "0") {
			$mobile = "255".( int ) $this->ussdMsisdn;
		} else {
			
			$mobile = $this->ussdMsisdn;
		}
		$this->redis->hset("{$this->ussdSession}:{$mobile}",array (
				$element => $type 
		));
	}
	private function _setSessionForMsisdn() {
		$userMobile = $this->ussdMsisdn;
		if ($userMobile [0] == "0") {
			$mobile = "+255".( int ) $this->ussdMsisdn;
		} else {
			$mobile = $this->ussdMsisdn;
		}
		$this->redis->set($this->ussdSession,$mobile);
		$this->redis->expire($this->ussdSession,USSD_SESSION_TIMEOUT);
	}
	private function _packUssdMessage() {
		$this->message = htmlspecialchars($this->message);
		$session = $this->_getElement("isSession");
		$message = "{$this->message}";
		$data ['message'] = $message;
		$data ['end'] = $this->end;
		echo json_encode($data);
		if ($this->end) {
			$this->_deleteSession();
		}
		return;
	}
	function utf8_for_xml($string) {
		return preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u','',$string);
	}
	function value_in($element_name,$xml,$content_only = true) {
		if ($xml == false) {
			return false;
		}
		$found = preg_match('#<'.$element_name.'(?:\s+[^>]+)?>(.*?)'.'</'.$element_name.'>#s',$xml,$matches);
		if ($found != false) {
			if ($content_only) {
				
				return $matches [1]; // ignore the enclosing tags
			} else {
				var_dump($matches);
				return $matches [0]; // return the full pattern match
			}
		}
		// No match found: return false.
		return false;
	}
	private function _checkInput() {
		$sessionState = $this->_getSessionState();
		$menunav = $this->_getElement("nextMenu");
		$userMobile = $this->ussdMsisdn;
		if ($this->ussdMsg == "00") {
			$prevMenu = $this->_getElement("prevMenu");
			return call_user_func(array (
					$this,
					$prevMenu 
			));
		}
		if ($userMobile [0] == "0") {
			
			$mobile = "+255".( int ) $this->ussdMsisdn;
		} else {
			$mobile = $this->ussdMsisdn;
		}
		return call_user_func(array (
				$this,
				$menunav 
		));
	}


	function _processAirtime($mobile,$txn,$amount){
		$operator = $this->_getElement("OPERATOR");
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
			$service = "TIGO-AIRTIME";
			/*if($type=="AIRTIME_BALANCE"){
			$service = "TIGO-AIRTIME";
		}

		 if($type=="BUNDLE"){

		 	if($this->_getElement("BUNDLE_TYPE") == "DATA"){

		 		$service = "TIGO-DATA";
		 	}
		 	else{
		 		$service = "TIGO-VOICE";
		 	}
		 }*/
		}
		else{
			$service = "SELCOM-AIRTIME";
		}
$result = $this->EncashLatest->getAPIUrl($service);
		$xml = $result['URL_PARAMETERS'];
		$url = $result['API_URL'];
		$xml = $this->replace_string('{$mobile}',$mobile,$xml);
		$xml = $this->replace_string('{$amount}',$amount,$xml); 
		/*if($operator != "TIGO"){
		
	}*/
		$xml = $this->replace_string('{$txnid}',$txn,$xml); 
if($operator == "TIGO"){

	//$xml = $this->replace_string('{$package}',$this->_getElement("Bundle_Detail"),$xml); 
	$headers = array (
					"Content-type: text/xml;charset=\"utf-8\"",
					"Accept: text/xml",
					"Cache-Control: no-cache",
					"Pragma: no-cache",
					"SOAPAction: http://charginggw.org/RechargeEx",
					"Content-length: ".strlen($xml ) 
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

$filename = "logs/requests/AIRTIME/".$operator.date("YmdH").".txt";
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
			else if($operator == "TIGO"){
			$filename = "logs/responses/AIRTIME/TIGO".date("YmdH").".txt";
			file_put_contents($filename, $data."\n",FILE_APPEND|LOCK_EX);//this will store the responses
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

			else{
				$filename = "logs/responses/AIRTIME/".$operator.date("YmdH").".txt";
			file_put_contents($filename, $data."\n",FILE_APPEND|LOCK_EX);
			$result = xmlrpc_decode($data);
			if ($result ['result'] == "SUCCESS") {
				$trans ['ERROR_CODE'] = "000";
				$trans ['TXN_STATUS'] = 'SUCCESS';
				$trans ['TXN_DESCRIPTION'] = "Airtime Transaction processed Successfully";
				$trans ['TXN_THIRD_REFERENCE'] = $result ['reference'];
				$trans ['TXN_ID'] = $txn;
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
			
	}

	

	function _processLUKU($meterNumber,$amount,$mobile,$refID,$luku_type) {
		if($luku_type == "PREPAID-LUKU"){
			$luku_type = "LUKU";
		}
		else if($luku_type == "POSTPAID-LUKU"){
			
			$luku_type = "TANESCO";
		}
		else{
			$luku_type = "HIGHLAND";
}
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
		curl_setopt($ch,CURLOPT_TIMEOUT,10);
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
			$trans ['TXN_THIRD_REFERENCE'] = $xmlArray ['reference'];
			$trans ['TXN_ID'] = $refID;
			$trans ['COMMENTS'] = $xmlArray ['message'];
			$trans ['LUKU_TOKEN'] = $xmlArray ['token'];
			$trans['EXTRA'] = "Units : ".$xmlArray['units'];
			return $trans;
		} else {
			$trans ['ERROR_CODE'] = '001';
			$trans ['TXN_STATUS'] = 'FAILED';
			$trans ['TXN_DESCRIPTION'] = 'FAILED';
			$trans ['TXN_THIRD_REFERENCE'] = $xmlArray ['reference'];
			$trans ['COMMENTS'] = $xmlArray ['message'];
			$trans ['TXN_ID'] = $refID;
			return $trans;
		}
	}
	
	 function _processDTH($cardNumber,$amount,$mobile,$refID,$dth_type) {
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
<string>'.$dth_type.'</string>
</value>
</member>
<member>
<name>utilityref</name>
<value>
<string>'.$cardNumber.'</string>
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
		curl_setopt($ch,CURLOPT_TIMEOUT,10);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
		curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
		$data = curl_exec($ch);
		$date = date("YmdHi");
		$filename = 'logs/requests/DTH/'.$date.'.txt';		
		file_put_contents($filename,$xml,FILE_APPEND | LOCK_EX);
		$date = date("YmdHi");
		$filename = 'logs/responses/DTH/'.$date.'.txt';
		file_put_contents($filename,$xml,FILE_APPEND | LOCK_EX);
		$xmlArray = xmlrpc_decode($data);
		if ($xmlArray ['result'] == "SUCCESS") {
			$trans ['ERROR_CODE'] = "000";
			$trans ['TXN_STATUS'] = 'SUCCESS';
			$trans ['TXN_DESCRIPTION'] = "Luku Transaction processed Successfully";
			$trans ['TXN_THIRD_REFERENCE'] = $xmlArray ['reference'];
			$trans ['TXN_ID'] = $refID;
			$trans ['COMMENTS'] = $xmlArray ['message'];
			$trans ['DTH_TOKEN'] = $xmlArray ['token'];
			return $trans;
		} else {
			$trans ['ERROR_CODE'] = '001';
			$trans ['TXN_STATUS'] = 'FAILED';
			$trans ['TXN_DESCRIPTION'] = 'FAILED';
			$trans ['TXN_THIRD_REFERENCE'] = $xmlArray ['reference'];
			$trans ['COMMENTS'] = $xmlArray ['message'];
			$trans ['TXN_ID'] = $refID;
			return $trans;
		}
}
	function incrementalHash() {
		$txn = substr(md5(microtime()*rand(0,9999)),0,20);
		$exist = $this->EncashLatest->_checkTXN($txn);
		if($exist['ID'] >= 1 ){
			return $this->incrementalHash();
		}
		else{
		return strtoupper($txn);
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
	function _selComBalance(){
		$xml = '<methodCall>
		<methodName>SELCOM.balanceEnquiry</methodName>
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
		<string>6592</string>
		</value>
		</member>
		<member>
		<name>transid</name>
		<value>
		<string>1234567</string>
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
		curl_setopt($ch,CURLOPT_TIMEOUT,10);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
		curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
		$data = curl_exec($ch);
		$xmlArray = xmlrpc_decode($data);
	}
	function formatmobile($mobile) {
		if($mobile[0] >=6){
			$mobile = "255" . (int) $mobile;
		}
		if ($mobile[0] == "0") {
			$mobile = "255" . (int) $mobile;
		} else {
			$mobile = $mobile;
		}
		return $mobile;
	}

function replace_string($variable,$value,$string){

$result =  str_replace($variable,$value,$string);
return $result;

}
}
