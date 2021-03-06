<?php 
//integsys/dcp/dcp_android_download.php

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
$pcname	= $myheader['g-api-imei'];
//SysClient ID
$clientid = $myheader['g-api-client'];
//Log No
$logno = $myheader['g-api-log'];
//User ID
$userid	= $myheader['g-api-user'];

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

//parse into json the PARAMETERS
$parjson = json_decode($param, true);
$par4sql = json_decode($param, true);

//detect the encoding used in the parameter...
//we perform the detection here so that we can properly handle characters
//such as (?). These characters are received as two part ASCII characters
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

if(is_null($parjson)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Invalid parameters detected";
    echo json_encode($json);
    return;
}

if(!isset($parjson['sTransNox'])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Unset TRANSACTION NUMBER detected.";
    echo json_encode($json);
    return;
}

$transno = $parjson['sTransNox'];
$detail = array();

$sql = "SELECT" .
            "  sTransNox" .
            ", nEntryNox" .
            ", sAcctNmbr" .
            ", sRemCodex" .
            ", sJsonData" .
            ", dReceived" .
            ", sUserIDxx" .
            ", sDeviceID" .
            ", dModified" .
            ", dTimeStmp" .
        " FROM LR_DCP_Collection_Detail_Android" .
        " WHERE sTransNox = " . CommonUtil::toSQL($transno);

if(null === $rows = $app->fetch($sql)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}
elseif(empty($rows)){
    $json["result"] = "success";
    $json["detail"] = $detail;
    echo json_encode($json);
    return;
}
    
$rows_found = sizeof($rows);

$detail = array();
for($ctr=0; $ctr<$rows_found; $ctr++){
    $detail[$ctr]["sTransNox"] = $rows[$ctr]["sTransNox"];
    $detail[$ctr]["nEntryNox"] = $rows[$ctr]["nEntryNox"];
    $detail[$ctr]["sAcctNmbr"] = $rows[$ctr]["sAcctNmbr"];
    $detail[$ctr]["sRemCodex"] = $rows[$ctr]["sRemCodex"];
    $detail[$ctr]["sJsonData"] = mb_convert_encoding($rows[$ctr]["sJsonData"], $chr_rqst, "ISO-8859-1");
    $detail[$ctr]["dReceived"] = $rows[$ctr]["dReceived"];
    $detail[$ctr]["sUserIDxx"] = $rows[$ctr]["sUserIDxx"];
    $detail[$ctr]["sDeviceID"] = $rows[$ctr]["sDeviceID"];
    $detail[$ctr]["dModified"] = $rows[$ctr]["dModified"];
    $detail[$ctr]["dTimeStmp"] = $rows[$ctr]["dTimeStmp"];
}

$json["result"] = "success";
$json["detail"] = $detail;
echo json_encode($json);
return;
?>