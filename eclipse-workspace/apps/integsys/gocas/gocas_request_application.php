<?php
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
$par4sql = json_decode($param, true);


//detect the encoding used in the parameter...
//we perform the detection here so that we can properly handle characters
//such as (ñ). These characters are received as two part ASCII characters
//but can be detected once decoded(?) and encoded(?) again...
$enc_param = json_encode($parjson, JSON_UNESCAPED_UNICODE);
$encoding = mb_detect_encoding($enc_param);

//set the encoding to UTF-8/ISO-8859-1 if not ASCII
if($encoding !== "ASCII"){
    //primarily used by JAVA/PHP
    if($encoding !== "UTF-8"){
        $parjson = mb_convert_encoding($parjson, "UTF-8", $encoding);
    }
    
    //Possibly VB6/We used as default encoding for MySQL
    if($encoding !== "ISO-8859-1"){
        $par4sql = mb_convert_encoding($par4sql, "ISO-8859-1", $encoding);
    }
}

$serialx = CommonUtil::SerializeNumber(substr($userid, 4));

//check if GOCAS was already saved previously...
$sql = "SELECT sTransNox" .
            ", sBranchCd" .
            ", dTransact" .
            ", sClientNm" . 
            ", sQMatchNo" .
            ", IFNULL(sGOCASNoF, sGOCASNox) sGOCASNox" . 
            ", cUnitAppl" . 
            ", sSourceCD" . 
            ", sDetlInfo" . 
            ", nDownPaym" . 
            ", sRemarksx" .
            ", sCreatedx" . 
            ", dCreatedx" .
            ", sVerified" . 
            ", dVerified" .
            ", cWithCIxx" .
            ", IFNULL(cTranStat, '') cTranStat" .
            ", sBranchCD" .
            ", cDivision" .
            ", cTranStat" .
            ", dReceived" .
    " FROM Credit_Online_Application" .
    " WHERE sTransNox LIKE " . CommonUtil::toSQL($serialx . "%");

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

$rows_found = sizeof($rows);

$detail = array();

for($ctr=0;$ctr<$rows_found;$ctr++){
    $detail[$ctr]["sTransNox"] = $rows[$ctr]["sTransNox"];
    $detail[$ctr]["sBranchCd"] = $rows[$ctr]["sBranchCd"];
    $detail[$ctr]["dTransact"] = $rows[$ctr]["dTransact"];
    $detail[$ctr]["sClientNm"] = mb_convert_encoding($rows[$ctr]["sClientNm"], $chr_rqst, "ISO-8859-1");
    $detail[$ctr]["sQMatchNo"] = $rows[$ctr]["sQMatchNo"];
    $detail[$ctr]["sGOCASNox"] = $rows[$ctr]["sGOCASNox"];
    $detail[$ctr]["cUnitAppl"] = $rows[$ctr]["cUnitAppl"];
    $detail[$ctr]["sSourceCD"] = $rows[$ctr]["sSourceCD"];
    $detail[$ctr]["sDetlInfo"] = mb_convert_encoding($rows[$ctr]["sDetlInfo"], $chr_rqst, "ISO-8859-1");
    $detail[$ctr]["nDownPaym"] = $rows[$ctr]["nDownPaym"];
    $detail[$ctr]["sRemarksx"] = mb_convert_encoding($rows[$ctr]["sRemarksx"], $chr_rqst, "ISO-8859-1");
    $detail[$ctr]["sCreatedx"] = $rows[$ctr]["sCreatedx"];
    $detail[$ctr]["dCreatedx"] = $rows[$ctr]["dCreatedx"];
    $detail[$ctr]["sVerified"] = $rows[$ctr]["sVerified"];
    $detail[$ctr]["dVerified"] = $rows[$ctr]["dVerified"];
    $detail[$ctr]["cWithCIxx"] = $rows[$ctr]["cWithCIxx"];
    $detail[$ctr]["cTranStat"] = $rows[$ctr]["cTranStat"];
    $detail[$ctr]["cDivision"] = $rows[$ctr]["cDivision"];
    $detail[$ctr]["dReceived"] = $rows[$ctr]["dReceived"];
    
    
}

$json["result"] = "success";
$json["detail"] = $detail;
echo json_encode($json);
return;
?>
