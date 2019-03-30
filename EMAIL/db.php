<?php
class db {
function connect_db(){
//$con = mysqli_connect("192.168.168.4",'encashPortal','encashussdDBUSER@#EnC@S#');
$con = mysqli_connect("localhost",'root','');
if(!$con){
	echo  'error';	
	exit;
}
else{

	mysqli_select_db($con,"encash_test");
	return $con;

}
}

function select($con,$sql){

echo $sql;
$result = mysqli_query($con,$sql);
var_dump($result);
$res = array();
while($row = mysqli_fetch_array($result)){
$res[] = $row;
}
return $res;
}

}

$db = new db();
?>