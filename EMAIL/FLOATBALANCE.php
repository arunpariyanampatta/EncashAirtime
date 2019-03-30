<?php
require_once 'db.php';

$db = new db();


$conn = $db :: connect_db();

$sql = "SELECT `VENDOR`,`LAST_AMT_CREDIT`,`LAST_AMOUNT_CHARGE`,`TOTAL_CONSUMED`,`FINAL_FLOAT_VALUE`,`DATE` FROM float_current_status ";

$data = $db :: select($conn,$sql);

var_dump($data);

?>