<?php
//system/masking/sendOTH.php

require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'WebClient.php';
require_once 'WSHeaderValidatorFactory.php';
require_once 'MySQLAES.php';

$myheader = apache_request_headers();

if(!isset($myheader['g-api-id'])){
    echo "anggapoy nagawaan ti labat awa!";
    return;
}

if(stripos(APPSYSX, $myheader["g-api-id"]) === false){
    echo "anto la ya... sika lamet!";
    return;
}

$validator = (new WSHeaderValidatorFactory())->make($myheader['g-api-id']);

$json = array();
if(!$validator->isHeaderOk($myheader)){
    $json["result"] = "error";
    $json["error"]["code"] = $validator->getErrorCode();
    $json["error"]["message"] = $validator->getMessage();
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

$app = new Nautilus(APPPATH);
if(!$app->LoadEnv($prodctid)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

if(!$app->loaduser($prodctid, $userid)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

$param = file_get_contents('php://input');
$parjson = mb_convert_encoding(json_decode($param, true), 'ISO-8859-1', 'UTF-8');

if(is_null($parjson)){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Invalid parameters detected";
    echo json_encode($json);
    return;
}

if(!isset($parjson['maskname'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Unset MASKNAME detected.";
    echo json_encode($json);
    return;
}

$maskname = $parjson['maskname'];

if(!isset($parjson['message'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Unset MESSAGE detected.";
    echo json_encode($json);
    return;
}

$message = $parjson['message'];

if(!isset($parjson['mobileno'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Unset MOBILE NO. detected.";
    echo json_encode($json);
    return;
}

$mobileno = $parjson['mobileno'];

//validate mobile number first
if (!CommonUtil::isValidMobile($mobileno)){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "MOBILE NO. is not valid.";
    echo json_encode($json);
    return;
}

$mobileno = str_replace("+", "", CommonUtil::fixMobile($mobileno));

$header = array();
$header[] = "Accept: application/json";
$header[] = "Content-Type: application/json";

$xparam = array();
$xparam["username"] = MASK_USER2;
$xparam["password"] = MASK_PASS2;
$xparam["text"] = $message;
$xparam["destination"] = $mobileno;
$xparam["source"] = $maskname;

$url = "https://messagingsuite.smart.com.ph/cgphttp/servlet/sendmsg";

$result = WebClient::httpRequest("GET", $url, $xparam, $header);
$result = str_replace("\r\n", "", $result);

if (strpos($result, 'Message-ID:') !== false) {
    $json["result"] = "success";
    $json["maskname"] = $maskname;
    $json["id"] = substr($result, 20);
    echo json_encode($json);
    return;
} else {
    $json["result"] = "error";
    $json["error"]["code"] = substr($result, 0, 5);
    $json["error"]["message"] = substr($result, 14);
    echo json_encode($json);
    return;
}
?>
