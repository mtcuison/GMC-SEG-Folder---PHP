<?php
require_once 'config.php';
require_once APPPATH.'/core/old/Nautilus.php';

$app = new Nautilus(APPPATH);
$myheader = apache_request_headers();

if(!isset($myheader['g-api-id'])){
    echo "anggapoy nagawaan ti labat awa!";
    return;
}

$json = array();
if(!$app->isHeaderOk($myheader)){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getMessage();
    echo json_encode($json);
    return;
}

//GET HEADERS HERE
//Product ID
$prodctid = $myheader['g-api-id'];
//Computer Name / IEMI No
$pcname 	= $myheader['g-api-imei'];
//SysClient ID
$clientid = $myheader['g-api-client'];
//Log No
$logno 		= $myheader['g-api-log'];
//User ID
$userid		= $myheader['g-api-user'];

if($logno == ""){
    $json["result"] = "error";
    $json["error"]["message"] = "Invalid LOG NO detected";
    echo json_encode($json);
    return;
}

if(!$app->LoadEnv($prodctid)){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

if(!$app->validLog($logno, $prodctid, $clientid, $userid, $pcname)){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

$json["result"] = "success";
$json["knox_base"] = "https://eu-kg-integration.samsungknox.com";
$json["knox_url"] = "/api/v1/devices/approve";
$json["knox_api"] = "4acf4e04bc32889d41343bcdca160fc86c0afa95cc81c3f79a5fa391aa2a30d7";
echo json_encode($json);
?>	
