<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class SelcomApi{
    
    
function _Airtime($msisdn,$amount,$refID){
    
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
<string>1234</string>
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
    $headers = array(
                        "Content-type: text/xml;charset=\"utf-8\"",
                        "Accept: text/xml",
                        "Cache-Control: no-cache",
                        "Pragma: no-cache",
                        "Content-length: ".strlen($xml),
                    );
 
 $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,"https://paypoint.selcommobile.com/api/selcom.pos.server.php");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $data = curl_exec($ch);
            file_put_contents('SELCOMRESPONSE.txt',$data,FILE_APPEND|LOCK_EX);
            $xmlArray = xmlrpc_decode($data);
            return $xmlArray;
}    
}