<?php	  
    require_once 'config.php';
	include APPPATH.'/core/old/Nautilus.php';
	//echo "hello";
	$app = new Nautilus(APPPATH);
	$myheader = apache_request_headers();
	//echo "hi";		
	
	if(!isset($myheader['g-api-id'])){
	    echo "anggapoy nagawaan ti labat awa!";
	    return;
	}

	$json = array();
	if(!$app->isHeaderOk($myheader)){
		$json["result"] = "error";
		$json["error"]["message"] = $app->getErrorMessage();
		echo json_encode($json);
		return;
	}
	//echo "how";
	$data = file_get_contents('php://input');
	//echo $data;
    $parjson = json_decode($data, true);	
	if(is_null($parjson)){
		$json["result"] = "error";
		$json["error"]["message"] = "Invalid parameters detected";
		echo json_encode($json);
		return false;
	}
	
	if(!isset($parjson['user'])){
		$json["result"] = "error";
		$json["error"]["message"] = "Unset User AUTH ACCOUNT detected.";
		echo json_encode($json);
		return false;
	}			

	if(!isset($parjson['pswd'])){
		$json["result"] = "error";
		$json["error"]["message"] = "Unset User AUTH PASSWORD detected.";
		echo json_encode($json);
		return false;
	}			
	
	//GET HEADERS HERE
	//Product ID
	$prodctid = $myheader['g-api-id'];
	//Computer Name / IEMI No
	$pcname 	= $myheader['g-api-imei'];
	//SysClient ID
	$clientid = $myheader['g-api-client'];
	
	//GET PARAMETERS HERE
	$username = $parjson['user'];
	$password = $parjson['pswd'];
	
	if(!$app->LoadEnv($prodctid)){
		$json["result"] = "error";
		$json["error"]["message"] = $app->getErrorMessage();
		echo json_encode($json);
		return;		
	}
	
	//echo "Client ID: " . $clientid . "\n";
	//echo "IEMI No  : " . $pcname . "\n";

	//If value of $clientid is empty then fetch clientid based on $productid and $pcname
	if($clientid == ""){
		$clientid = $app->getclient($prodctid, $pcname);
  	        //echo "Client ID: " . $clientid;
		 
		if($clientid == ""){
			$json["result"] = "error";
			$json["error"]["message"] = $app->getErrorMessage();
			echo json_encode($json);			
			return;
		}
	}		
	
	if(!$app->validproduct($clientid, $pcname, $prodctid)){
			$json["result"] = "error";
			$json["error"]["message"] = $app->getErrorMessage();
			echo json_encode($json);			
			return;
	}	
	
	if(!$app->Login($username,$password,$prodctid, $pcname)){
	    $json["result"] = "error";
	    $json["error"]["message"] = $app->getErrorMessage();
		echo json_encode($json);			
		return;		
	}
	//echo "hi there";
	$json["result"] = "success";
	$json["sClientID"] = $app->Env("sClientID");
	$json["sBranchCD"] = $app->Env("sBranchCD");
	$json["sBranchNm"] = $app->Env("sBranchNm");
	$json["sLogNoxxx"] = $app->Env("sLogNoxxx");
	$json["sUserIDxx"] = $app->Env("sUserIDxx");
	$json["sUserName"] = $app->Env("sUserName");
	$json["nUserLevl"] = $app->Env("nUserLevl");
	$json["sDeptIDxx"] = $app->Env("sDeptIDxx");
	$json["cAllowUpd"] = "1";
	echo json_encode($json, JSON_PARTIAL_OUTPUT_ON_ERROR);
?>	
