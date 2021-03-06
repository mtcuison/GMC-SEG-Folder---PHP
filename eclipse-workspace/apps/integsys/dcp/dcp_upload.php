<?php
//integsys/dcp/dcp_submit.php

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
$userid	= "M001210006"; //$myheader['g-api-user'];

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
$parjson = json_decode($param,  true);
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

if(!isset($parjson['master'])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Unset MASTER INFO detected.";
    echo json_encode($json);
    return;
}

if(!isset($parjson['detail'])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Unset DETAIL INFO detected.";
    echo json_encode($json);
    return;
}

$jsonmaster = $parjson['master'];
$jsondetail = $parjson['detail'];

//check if the transaction was already saved
$sql = "SELECT sTransNox FROM LR_DCP_Collection_Master" .
    " WHERE sTransNox = " . CommonUtil::toSQL($jsonmaster["sTransNox"]);

if(null === $rows = $app->fetch($sql)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}
elseif(!empty($rows)){
    //transaction was previously upload, return success
    $json["result"] = "success";
    echo json_encode($json);
    return false;
}

$sql = "INSERT INTO LR_DCP_Collection_Master SET" .
    "  sTransNox = " . CommonUtil::toSQL($jsonmaster["sTransNox"]) .
    ", dTransact = " . CommonUtil::toSQL($jsonmaster["dTransact"]) .
    ", sReferNox = " . CommonUtil::toSQL($jsonmaster["sReferNox"]) .
    ", sCollctID = " . CommonUtil::toSQL($jsonmaster["sCollctID"]) .
    ", dReferDte = " . CommonUtil::toSQL($jsonmaster["dReferDte"]) .
    ", cDCPTypex = " . CommonUtil::toSQL($jsonmaster["cDCPTypex"]) .
    ", cTranStat = " . CommonUtil::toSQL($jsonmaster["cTranStat"]) .
    ", nEntryNox = " . CommonUtil::toSQL($jsonmaster["nEntryNox"]) .
    ", sModified = " . CommonUtil::toSQL($jsonmaster["sModified"]) .
    ", dModified = " . CommonUtil::toSQL($jsonmaster["dModified"]);

$app->beginTrans(); //begin database transaction

if($app->execute($sql, "LR_DCP_Collection_Master") <= 0){ //do execute to replication
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Unable to save LR_DCP_Collection_Master:" . $app->getErrorMessage();
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

$rows_found = sizeof($jsondetail);

for($ctr=0; $ctr<$rows_found; $ctr++){
    $sql = "INSERT INTO LR_DCP_Collection_Detail SET" .
        "   sTransNox = " . CommonUtil::toSQL($jsonmaster["sTransNox"]) .
        ",  nEntryNox = " . $jsondetail[$ctr]["nEntryNox"] .
        ",  sAcctNmbr = " . CommonUtil::toSQL($jsondetail[$ctr]["sAcctNmbr"]) .
        ",  sReferNox = " . CommonUtil::toSQL($jsondetail[$ctr]["sReferNox"]) .
        ",  cPaymForm = " . CommonUtil::toSQL($jsondetail[$ctr]["cPaymForm"]) .
        ",  cIsDCPxxx = " . CommonUtil::toSQL($jsondetail[$ctr]["cIsDCPxxx"]) .
        ",  cIsNwNmbr = " . CommonUtil::toSQL($jsondetail[$ctr]["cIsNwNmbr"]) .
        ",  cIsNwAddx = " . CommonUtil::toSQL($jsondetail[$ctr]["cIsNwAddx"]) .
        ",  cIsNwCltx = " . CommonUtil::toSQL($jsondetail[$ctr]["cIsNwCltx"]) .
        ",  dModified = " . CommonUtil::toSQL($jsondetail[$ctr]["dModified"]);
    
    //",  dPromised = " . CommonUtil::toSQL($jsondetail[$ctr]["dPromised"]) .
    //",  sRemCodex = " . CommonUtil::toSQL($jsondetail[$ctr]["sRemCodex"]) .
    //",  sRemarksx = " . CommonUtil::toSQL($jsondetail[$ctr]["sRemarksx"]) .
    
    if($app->execute($sql, "LR_DCP_Collection_Detail") <= 0){ //do execute to replication
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = "Unable to save LR_DCP_Collection_Detail:" . $app->getErrorMessage();
        $app->rollbackTrans();
        echo json_encode($json);
        return;
    }
}
$app->commitTrans(); //end database transaction

$json["result"] = "success";
echo json_encode($json);
return;
?>