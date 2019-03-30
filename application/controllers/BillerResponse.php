<?php

/*
 * Author : Arun.Pariyanampatta.
 * Organization : GREEN TELECOM
 * and open the template in the editor.
 */
error_reporting(0);
date_default_timezone_set('Africa/Dar_Es_Salaam');
class BillerResponse extends CI_Controller {
	var $balance,$agent_id,$agentMsisdn,$company_name,$unique,$requestmode,$operator;

	public function __construct() {
		parent::__construct();
		$this->load->model('EncashServices'); //This file contanis Main-Menu and its operations
		$this->load->model('CorporateServices','CorporateServices');
	}



function index(){

$response = '{"Credentials":{"AccountID":"125896"},"Transaction":{"AccountReference":"255655222655","ServiceType":"AIRTIME","Transactionref":"120003","TransactionDeviceNumber":"255684696995","Msisdn":"58985522","Amount":"1000","Operator":"AIRTEL","UniqueID":"048958f3-68d5-444b-9887-fb16a5f728fb","ServiceID":"AIRTIMETZAIRTEL"}}';


$agent

}
}
