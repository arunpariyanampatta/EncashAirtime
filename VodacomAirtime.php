<?php

function Bin_Hex($sBin)
{
	$sReturn = ""; 
	$sHex = "";
	for ($i=0; $i < strlen($sBin); ++$i)
	{
		$sHex=$sHex.substr($sBin,$i,1);
		if(strlen($sHex)==4){
			if($sHex=="0000") $sReturn .= "0";
			if($sHex=="0001") $sReturn .= "1";
			if($sHex=="0010") $sReturn .= "2";
			if($sHex=="0011") $sReturn .= "3";
			if($sHex=="0100") $sReturn .= "4";
			if($sHex=="0101") $sReturn .= "5";
			if($sHex=="0110") $sReturn .= "6";
			if($sHex=="0111") $sReturn .= "7";
			if($sHex=="1000") $sReturn .= "8";
			if($sHex=="1001") $sReturn .= "9";
			if($sHex=="1010") $sReturn .= "A";
			if($sHex=="1011") $sReturn .= "B";
			if($sHex=="1100") $sReturn .= "C";
			if($sHex=="1101") $sReturn .= "D";
			if($sHex=="1110") $sReturn .= "E";
			if($sHex=="1111") $sReturn .= "F";
			$sHex="";
		}
	}
	return $sReturn;
}
function Hex_Bin($sHex)
{
	$sReturn = "";
	$sHex=strtoupper($sHex);
	for ($i=0; $i < strlen($sHex); ++$i)
	{
		switch (substr($sHex, $i, 1))
		{
			case '0': $sReturn .= "0000"; break;
			case '1': $sReturn .= "0001"; break;
			case '2': $sReturn .= "0010"; break;
			case '3': $sReturn .= "0011"; break;
			case '4': $sReturn .= "0100"; break;
			case '5': $sReturn .= "0101"; break;
			case '6': $sReturn .= "0110"; break;
			case '7': $sReturn .= "0111"; break;
			case '8': $sReturn .= "1000"; break;
			case '9': $sReturn .= "1001"; break;
			case 'A': $sReturn .= "1010"; break;
			case 'B': $sReturn .= "1011"; break;
			case 'C': $sReturn .= "1100"; break;
			case 'D': $sReturn .= "1101"; break;
			case 'E': $sReturn .= "1110"; break;
			case 'F': $sReturn .= "1111"; break;
		}
	}
	return $sReturn;
}
function ISOFormater($profile, $sBit=array()){
	$sBin1="";
	$sBin2="";
	$sHex1="";
	$sHex2="";
	$sISO="";
	$sData="";
	$sVC="";
	$sVN="";
	$nVC=0; 
	$nVN=0;
	$sMTI = $sBit['mti'];
	
	echo "Format ISO8583 MTI=[$sMTI]<br/>";
	for($n=2;$n<=128;$n++){
		if($n<65){
			if(!$sBit[$n]){
				$sBin1=$sBin1."0";
			}else{
				$sBin1=$sBin1."1";
			}
		}else{
			if(!$sBit[$n]){
				$sBin2=$sBin2."0";
			}else{
				$sBin2=$sBin2."1";
			}
		}
		if(! $sBit[$n]) continue;
		switch($isocfg['bit_type'][$n]){
			/* Fixed Char */
			case 0:
				for($x=strlen($sBit[$n]);$x<$isocfg['bit_len'][$n];$x++)
					$sBit[$n]=$sBit[$n]." ";
				$sData=$sData.$sBit[$n];
				break;
			/* Variable Char */
			case 1:
				$nVC=strlen($sBit[$n]);
				$sVC = strval($nVC);
				for($x=strlen($sVC);$x<$isocfg['bit_len'][$n];$x++)
					$sVC="0".$sVC;
				$sData=$sData.$sVC.$sBit[$n];
				break;
			/* Fixed Numeric */
			case 2:
				for($x=strlen($sBit[$n]);$x<$isocfg['bit_len'][$n];$x++)
					$sBit[$n]="0".$sBit[$n];
				$sData=$sData.$sBit[$n];
				break;
			/* Variable Numeric */
			case 3:
				$nVN=strlen($sBit[$n]);
				$sVN = strval($nVN);
				for($x=strlen($sVN);$x<$isocfg['bit_len'][$n];$x++)
					$sVC="0".$sVN;
				$sData=$sData.$sVN.$sBit[$n];
				break;
			default:
				break;
		}
		echo "Format ISO8583 DataBit[$n]=[$sBit[$n]]<br/>";
	}
	$sHex2=Bin_Hex($sBin2);
	if($sHex2=="0000000000000000"){
		$sHex2="";
		$sBin1="0".$sBin1;
	}else{
		$sBin1="1".$sBin1;		
	}
	$sHex1=Bin_Hex($sBin1);
	echo "Format Bin1=[$sBin1] Hex1=[$sHex1] Bin2=[$sBin2] Hex2=[$sHex2] <br/>";
	$sISO=$sMTI.$sHex1.$sHex2.$sData;
	echo "Format ISO8583 Data = [$sISO]<br/>";
	return $sISO;
}
function ISOParser($profile, $sISO){
	$sBin1="";
	$sBin2="";
	$sBin="";
	$sVC="";
	$sVN="";
	$sLogISO="";
	$m=0;
	$nVC=0;
	$nVN=0;
	$nBit=0;
	$sDataBit = array();
	for($n=0;$n<130;$n++)
		$sDataBit[$n]="";
	if (strlen($sISO)<32){
		return "";
	}
	$sMTI = substr($sISO, $m,4);
	if (! $isocfg=getISOCfg($profile, $sMTI)){
	   return "";
	}
	$sDataBit['mti'] = $sMTI;
	echo "MTI=[$sMTI] <br/>";
	$m+=$isocfg['mti_len'];
	$nBit=64;
	for($n=0;$n<=$nBit;$n++){
		if($n==0){
			$sDataBit[$n] = substr($sISO, $m, $isocfg['bit_len'][$n]);
			$sBin1 = Hex_Bin($sDataBit[$n]);
			echo "Bin1=[$sBin1] <br/>";
			$sBin=$sBin1;
			$m += $isocfg['bit_len'][$n];
			if(substr($sBin1,0,1)=='1')
			{
				$sDataBit[1] = substr($sISO,$m,$isocfg['bit_len'][$n]);
				$sBin2 = Hex_Bin($sDataBit[1]);
				echo "Bin2=[$sBin2] <br/>";
				$nBit=128;
				$sBin=$sBin1.$sBin2;
				$m += $isocfg['bit_len'][$n];
			}else{
				$sDataBit[1]="";
			}
		}
		if($n>1){
			if(substr($sBin, $n-1,1)=='0'){
				$sDataBit[$n]="";
				continue;
			}
			switch($isocfg['bit_type'][$n]){
				/* Fixed Char */
				case 0:
					$sDataBit[$n] = substr($sISO,$m,$isocfg['bit_len'][$n]);
					$m += $isocfg['bit_len'][$n];
					echo "Parsing ISO8583 DataBit[$n]=[$sDataBit[$n]]<br/>";
					break;
				/* Variable Char */
				case 1:
					$sVC = substr($sISO, $m, $isocfg['bit_len'][$n]);
					$nVC = intval($sVC);
					$m += $isocfg['bit_len'][$n];
					$sDataBit[$n] = substr($sISO, $m, $nVC);
					$m += $nVC;
					echo "Parsing ISO8583 DataBit[$n]=[$sVC][$sDataBit[$n]]<br/>";
					break;
				/* Fixed Numeric */
				case 2:
					$sDataBit[$n] = substr($sISO, $m, $isocfg['bit_len'][$n]);
					$m += $isocfg['bit_len'][$n];
					echo "Parsing ISO8583 DataBit[$n]=[$sDataBit[$n]]<br/>";
					break;
				/* Variable Numeric */
				case 3:
					$sVN = substr($sISO, $m, $isocfg['bit_len'][$n]);
					$nVN = intval($sVN);
					$m += $isocfg['bit_len'][$n];
					$sDataBit[$n] = substr($sISO, $m, $nVN);
					$m += $nVN;
					echo "Parsing ISO8583 DataBit[$n]=[$sVN][$sDataBit[$n]]<br/>";
					break;
				default:
					$sDataBit[$n] = substr($sISO, $m, $isocfg['bit_len'][$n]);
					$m += $isocfg['bit_len'][$n];
					break;
			}
		}
	}
	return $sDataBit;
}
		


                $amount = "500";
                $padding = 12- sizeof($amount);
                for($i=0;$i<$padding;$i++){
                    
                    $denomination="0".$amount;
                    
                }
                
		$sBit=array();
		$sBit['mti'] = "0800";
		$sBit[2]= "541211111111111";	
		$sBit[3]= "220000";
		$sBit[4]= $denomination;
		$sBit[7]= date("mdHis");
		$sBit[11]=date("Ymd"); 
		$sBit[12]=date("His"); 
		$sBit[13]= date("md");
                $sBit[14]= date("Ym");
                $sBit[18]= "000";
                $sBit[25]= "00";
                $sBit[26]= "00";
                $sBit[32]= "100032";
                $sBit[37]= "255759520253";
                $sBit[41]="11111111";
                $sBit[42]="111111111111111";
                $sBit[43]="11111111111111111111111111111111111111ZA";
                $sBit[49]="834";
                $sBit[52]="00011111";
                $sBit[59] = "12123";
                $sBit[61]="1~MPESA~255759520253";
                $sBit[70]="301";
                $sBit[123]="212122997589092";
                $sBit["127.9"] = "2566585526655896";
                
		$sDataBit = ISOFormater($profile, $sBit);
		/* Create a TCP/IP socket. */
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if ($socket === false) {
			echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "<br/>";
			return;
		}
		$address = "41.217.201.166";
		$service_port = "39201";
		echo "Attempting to connect to '$address' on port '$service_port'...<br/>";
		$result = socket_connect($socket, $address, $service_port);
		if ($result === false) {
			echo "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "<br/>";
			return;
		}else{
			echo "Server connected <br/>";
		}
		socket_write($socket, $sDataBit, strlen($sDataBit));
		if ($out = socket_read($socket, 2048)) {
			echo "Incoming Data=".$out."<br/>";
			$sDataBit = ISOParser($profile, $out);
		}
		echo "Closing socket...<br/>";
		socket_close($socket);
		
	
