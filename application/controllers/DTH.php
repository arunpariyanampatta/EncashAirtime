<?php






if($service == "DTH"){



$this->_processDTH($meterNumber,$amount,$mobile,$refID,$dth_type,$ackRef,$UUID)

}

function _processDTH($meterNumber,$amount,$mobile,$refID,$dth_type,$ackRef,$UUID) {


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
            "TXN_MOBILE_NUMBER"=>$msisdn,
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
<string>'.$dth_type.'</string>
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
      $trans['EXTRA'] = "Units : ".$xmlArray['voucher'];
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

?>