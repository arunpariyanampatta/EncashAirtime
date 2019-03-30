<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of chatlib
 *
 * @author arun
 */
class Encashlib {
    
     function sendAirtelsms($phone,$msg)
      {

file_put_contents("sms.txt","188.64.187.118:13013/cgi-bin/sendsms??username=airtelTX&password=greentx&to=$phone&text=$msg&from=15670&dlr-mask=31");
     $ch= curl_init();
     curl_setopt($ch,CURLOPT_URL, "188.64.187.118:13013/cgi-bin/sendsms?username=airtelTX&password=greentx&to=$phone&text=$msg&from=LSF&dlr-mask=31");
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
     $exec = curl_exec($ch);
     curl_close($ch);
	file_get_contents("http://188.64.187.214/lsfdirect/smsapi.php?mobile=$phone&msg=$msg");
     return $exec;
}

function sendzantelMT($phone,$msg)
{
     $ch= curl_init();
     curl_setopt($ch,CURLOPT_URL, "195.216.196.208:13013/cgi-bin/sendsms?username=Green_Telecom&password=123456&to=$phone&text=$msg&from=15670&dlr-mask=31");
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
     $exec = curl_exec($ch);
     curl_close($ch);
     return $exec;
}

/*function sendAirtelsms($message,$receiverNumber)
      {
 $response = file_get_contents("http://195.216.196.208:13013/cgi-bin/sendsms?username=airtelTX&password=greentx&to=$receiverNumber&text=".urlencode($message)."&from=15670&dlr-mask=31");	
 return $response;  
}*/
function recordLog($sms_id,$name,$sender,$msg,$receiverNumber,$status){
        $sql = "INSERT INTO sent_messages(application,msg_id,sender,content,receiver,status)VALUES('".mysql_real_escape_string($name)."','".$sms_id."','".mysql_real_escape_string($sender)."','".mysql_real_escape_string($msg)."','".mysql_real_escape_string($receiverNumber)."','".mysql_real_escape_string($status)."')";
	
        mysql_query($sql);
        $msg_id = mysql_insert_id();
	return $msg_id;
    }

}
