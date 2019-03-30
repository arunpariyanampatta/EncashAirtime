<?php

$response = '{"Response": {"StatusCode" : "0","StatusMessage": "SUCCESS","NotifyMsisdn" : "0655854040","ThirdPartyTransactionRef": "5D0465C8D24258D29272","UUID":"c4ba12f8-d6b0-4327-92b0-431065570e50","TransactionDetails" : "Airtime transaction has been processed","ServiceID":"AIRTIMETZTIGO","TransactionDate" : "2019-01-15 08:48:06"}}';

    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,"http://172.17.20.25:51105/TanzaniaUtilities/GreenTelecomCallBack.aspx");
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_TIMEOUT,100);
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$response);
    $data = curl_exec($ch);
