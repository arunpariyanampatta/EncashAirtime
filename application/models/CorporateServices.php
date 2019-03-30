<?php


class 	CorporateServices extends CI_MODEL{


public function __construct(){
   
    parent ::__construct();
   
}	
function _recordTransaction($table,$data=array()){

	$this->db->insert($table,$data);
	$id = $this->db->insert_id();
	return $id;
}

function getContacts($id){
	$this->db->select('ID,F_NAME,L_NAME,GROUP_ID,DESIGNATION,DEPARTMENT,MONTHLY_AMOUNT,COMPANY_ID,MSISDN,OPERATOR,ISACTIVE')->from('company_contacts')->where('COMPANY_ID',$id);
	$result = $this->db->get();
	return $result->result_array();

}

function checkInformation(){

	$res = $this->db->select('*')->from('company_contacts')->where('MSISDN',$msisdn)->get();
	return $res->row_array();
}
function removeContact($mobile){
$this->db->where('MSISDN',$mobile);
$this->db->delete('company_contacts');

}
function getBalance($msisdn){

	$res = $this->db->select('TOTAL_AVAIL_BALANCE')->from('agent_balance')->where('AGENT_MSISDN',$msisdn)->get();
	return $res->row_array();
}
function getContactdetails($msisdn){
$sql = "SELECT `F_NAME`,`ID`,`L_NAME`,`DESIGNATION`, `MSISDN`,`OPERATOR` FROM `company_contacts` WHERE  MSISDN IN (".$msisdn.")";
$res = $this->db->query($sql);
return $res->result_array();
}
	





	
}