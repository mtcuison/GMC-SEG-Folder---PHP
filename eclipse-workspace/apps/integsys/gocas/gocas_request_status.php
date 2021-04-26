<?php
/*
 * gocas_request.php
 * kalyptus - 2019.12.13 02:43pm
 * use this API in requesting a copy of GOCAS..
 * Note:
 */

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

//default-request charset
$chr_rqst = "UTF-8";
if(isset($myheader['g-char-request'])){
    $chr_rqst = $myheader['g-char-request'];
}
header("Content-Type: text/html; charset=$chr_rqst");

$validator = (new WSHeaderValidatorFactory())->make($myheader['g-api-id']);
//var_dump($myheader);
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

if(isset($myheader['g-api-mobile'])){
    $mobile = $myheader['g-api-mobile'];
}
else{
    $mobile = "";
}

//Assumed that this API is always requested by Android devices
if(!CommonUtil::isValidMobile($mobile)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_AUTH_MOBILE;
    $json["error"]["message"] = "Invalid mobile number detected";
    echo json_encode($json);
    return;
}

$userid		= $myheader['g-api-user'];

$app = new Nautilus(APPPATH);
if(!$app->LoadEnv($prodctid)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

//kalyptus - 2019.06.29 01:39pm
//follow the validLog of new Nautilus;
if(!$app->validLog($prodctid, $userid, $pcname, $logno)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

//Google FCM token
$token = $myheader['g-api-token'];
if(!$app->loaduserClient($prodctid, $userid, $pcname, $token, $mobile)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

$param = file_get_contents('php://input');

//parse into json the PARAMETERS
$parjson = json_decode($param, true);

if(!isset($parjson['refernox'])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Please specify the the correct parameter!";
    echo json_encode($json);
    return;
}

$value = $parjson['refernox'];

$sql = "SELECT sTransNox" .
    ", sQMatchNo" .
    ", IFNULL(sGOCASNoF, sGOCASNox) sGOCASNox" .
    ", sCreatedx" .
    ", dCreatedx" .
    ", sVerified" .
    ", dVerified" .
    ", cWithCIxx" .
    ", IFNULL(cTranStat, '') " .
    " FROM Credit_Online_Application" .
    " WHERE sTransNox LIKE '$value'" .
    " ORDER BY sTransNox DESC" .
    " LIMIT 1";

if(null === $rows = $app->fetch($sql)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getMessage();
    echo json_encode($json);
    return;
}
elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "Record not found";
    echo json_encode($json);
    return false;
}

$json["result"] = "success";
$json["sQMatchNo"] = $rows[0]["sQMatchNo"];
$json["sGOCASNox"] = $rows[0]["sGOCASNox"];
$json["cUnitAppl"] = $rows[0]["cUnitAppl"];
$json["sCreatedx"] = $rows[0]["sCreatedx"];
$json["dCreatedx"] = $rows[0]["dCreatedx"];
$json["sVerified"] = $rows[0]["sVerified"];
$json["dVerified"] = $rows[0]["dVerified"];
$json["cWithCIxx"] = $rows[0]["cWithCIxx"];
$json["cTranStat"] = $rows[0]["cTranStat"];
echo json_encode($json, JSON_UNESCAPED_UNICODE );

?>

