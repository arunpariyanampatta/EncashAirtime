<?php


class EncashServices extends CI_MODEL{
    var $db;
public function __construct(){

    
    parent ::__construct();

    $this->db = $this->load->database('encash_live',TRUE);
}

function getTrasanctionDetails($msisdn){

    $res = $this->db->select('*')->from('transaction_logs_all')->where('AGENT_MSISDN',$msisdn)->where('REQUEST_TYPE','WEB')->get();
    return $res->result_array();
}
function _checkTXN($txn){
        $this->db->select('ID')->from('transaction_logs_all')->where('TXN_REFERENCE',$txn);
        $result = $this->db->get();
        return $result->row_array();

    }
    function getAgentID($msisdn){
        $res = $this->db->select('ID')->from('agent_details')->where('AGENT_MSISDN',$msisdn)->get();
        return $res->row_array();
}

    function _getCommsionRate($service) {
        $this->db->select ( 'COMMISSION_PERCENTAGE' )->from ( 'commission_base' )->where ( 'SERVICE_NAME', $service );
        $result = $this->db->get ();
        
        return $result->row_array ();
    }
    function _setCommission($mobile, $perc, $amount) {
        $commission = $amount * ($perc / 100);
        $sql = "UPDATE agent_balance set COMMISSION_BALANCE = COMMISSION_BALANCE +" . $commission . ", LAST_TRANSACTION_VALUE = " . $amount . ", TOTAL_AVAIL_BALANCE = MAIN_BALANCE +  COMMISSION_BALANCE  WHERE AGENT_MSISDN = '{$mobile}'";
        $this->db->query ( $sql );
    }
function _updateAgentAccount($amount,$sales,$id,$column){
        if($column == "CLOSING_BALANCE"){
        $sql = "UPDATE agent_accounts set CLOSING_BALANCE = $amount, TOTAL_SALES = TOTAL_SALES + $sales WHERE AGENT_ID = $id ";
        }
        else{
            $sql = "UPDATE agent_accounts set FLOAT_ALLOCATION = FLOAT_ALLOCATION   + $amount  WHERE AGENT_ID = $id ";
        }
        $this->db->query($sql);

    }
function _getAgentID($mobile){
        $this->db->select('ID')->from('agent_details')->where('AGENT_MSISDN',$mobile);
        $result = $this->db->get();
        return $result->row_array();
        
    }

    function _updateTransaction($data,$txnRef,$mobile){
        $date = date("Y-m-d");
        $this->db->where('TXN_REFERENCE',$txnRef);
        $this->db->where('AGENT_MSISDN',$mobile);
        $this->db->where('date(TXN_DATE)',$date);
        $this->db->update('transaction_logs_all',$data);
        $rows = $this->db->affected_rows();
        return $rows;
        
    }

    function _updateMerchantBalance($amount, $vendor) {
        $sql = "UPDATE float_current_status set FINAL_FLOAT_VALUE = FINAL_FLOAT_VALUE - " . $amount . ",TOTAL_CONSUMED  = TOTAL_CONSUMED +" . $amount . " WHERE VENDOR = '{$vendor}'";

        $this->db->query ( $sql );
    }
function _recordTransaction($table,$data){
    $this->db->insert($table,$data);
    $id = $this->db->insert_id();
}
function getRechargeList($companyID){

$result = $this->db->select('*')->from('recharge_list')->where('COMPANY_ID',$companyID)->get();
return $result->result_array();

}
function getBalance($msisdn){

    $this->db->select('MAIN_BALANCE,TOTAL_AVAIL_BALANCE')->from('agent_balance')->where('AGENT_MSISDN',$msisdn);
    $result = $this->db->get();
    return $result->row_array();

}
function updateBalance ($msisdn,$amount){

        $sql = "UPDATE agent_balance  set MAIN_BALANCE = MAIN_BALANCE - $amount,TOTAL_AVAIL_BALANCE = TOTAL_AVAIL_BALANCE - $amount,LAST_TRANSACTION_VALUE = $amount WHERE AGENT_MSISDN = $msisdn ";
    $this->db->query($sql);
}

function getLoginDetails($msisdn){

$res = $this->db->select('*')->from('encash_web_login')->where('MSISDN',$msisdn)->get();
return $res->row_array();

}


function updateagentTable($data,$msisdn){

$this->db->where('AGENT_MSISDN',$msisdn);
$this->db->update('agent_details',$data);
$this->updateagent_accounts($data,$msisdn);


}
function updateagent_accounts($data,$msisdn){
$this->db->where('AGENT_MSISDN',$msisdn);
$this->db->update('agent_accounts',$data);
$this->updateagent_balance($data,$msisdn);
}
function updateagent_balance($data,$msisdn){
$this->db->where('AGENT_MSISDN',$msisdn);
$this->db->update('agent_balance',$data);
$this->db->updateagent_reg($data,$msisdn);


}

function getPaymentHistory($id){

    $res = $this->db->select('*')->from('agent_float_allocation')->where('AGENT_ID',$id)->get();
    return $res->result_array();
}


function getAPIUrl($service){

    $result = $this->db->select('API_URL,URL_PARAMETERS')->from('thirdparty_api')->where('SERVICE_NAME',$service)->get();

    return $result->row_array();
}


function getCallbackUrl($id){


    $result = $this->db->select('CALLBACK_URL')->from('client_details')->where('AGENT_ID',$id)->get();

    return $result->row_array();
}

}