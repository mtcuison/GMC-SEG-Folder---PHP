<?php
//integsys/dcp/request_address_update.php

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

$userid	= $myheader['g-api-user'];

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

if(is_null($parjson)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Invalid parameters detected";
    echo json_encode($json);
    return;
}

$params = array("sTransNox", "sClientID", "cReqstCDe", "cAddrssTp", "sHouseNox", "sAddressx", "sTownIDxx", "sBrgyIDxx", "cPrimaryx", "nLatitude", "nLongitud", "sRemarksx", "sSourceCD", "sSourceNo");

$rows_found = sizeof($params);
for($ctr=0; $ctr<$rows_found; $ctr++){
    //check if parameter was passsed
    if(!isset($parjson[$params[$ctr]])){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
        $json["error"]["message"] = "Unset PARAMETER detected. ";    
        echo json_encode($json);
        return;
    }
    //check if parameter was empty
    if($parjson[$params[$ctr]] == ""){
        if ($params[$ctr] != "sRemarksx"){
            $json["result"] = "error";
            $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
            $json["error"]["message"] = "Empty PARAMETER detected.";
            echo json_encode($json);
            return;
        }
    }
    
    switch ($params[$ctr]){
        case "":
            $sTransNox = $parjson[$params[$ctr]];
            break;
        case "sClientID":
            $sClientID = $parjson[$params[$ctr]];
            break;
        case "cReqstCDe":
            $cReqstCDe = $parjson[$params[$ctr]];            
            break;
        case "cAddrssTp":
            $cAddrssTp = $parjson[$params[$ctr]];
            break;
        case "sHouseNox":
            $sHouseNox = $parjson[$params[$ctr]];
            break;
        case "sAddressx":
            $sAddressx = mb_convert_encoding($parjson[$params[$ctr]], "ISO-8859-1", $encoding);
            break;
        case "sTownIDxx":
            $sTownIDxx = $parjson[$params[$ctr]];
            break;
        case "sBrgyIDxx":
            $sBrgyIDxx = $parjson[$params[$ctr]];
            break;
        case "cPrimaryx":
            $cPrimaryx = $parjson[$params[$ctr]];
            break;
        case "nLatitude":
            $nLatitude = $parjson[$params[$ctr]];
            
            if (!is_numeric($nLatitude)){
                $json["result"] = "error";
                $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
                $json["error"]["message"] = "Latitude must be NUMERIC detected.";
                echo json_encode($json);
                return;
            }
            break;
        case "nLongitud":
            $nLongitud = $parjson[$params[$ctr]];
            
            if (!is_numeric($nLongitud)){
                $json["result"] = "error";
                $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
                $json["error"]["message"] = "Latitude must be NUMERIC detected.";
                echo json_encode($json);
                return;
            }
            break;
        case "sRemarksx":
            $sRemarksx = mb_convert_encoding($parjson[$params[$ctr]], "ISO-8859-1", $encoding);
            break;
        case "sSourceCD":
            $sSourceCD = $parjson[$params[$ctr]];
            break;
        case "sSourceNo":
            $sSourceNo = $parjson[$params[$ctr]];
            break;
    }
}

$sql = "SELECT sTransNox FROM Address_Update_Request WHERE sTransNox = " . CommonUtil::toSQL($sTransNox);

if(null === $rows = $app->fetch($sql)){ //exception detected
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
} elseif(!empty($rows)){
    $json["result"] = "success";
    $json["sTransNox"] = $rows[0]["sTransNox"];
    echo json_encode($json);
    return;
}

date_default_timezone_set('Asia/Manila');
$date = new DateTime('now');
$year = $date->format("y");

$nextcd = CommonUtil::GetNextCode("Address_Update_Request", "sTransNox", $year, $app->getConnection(), "MX01");

$sql = "INSERT INTO Address_Update_Request SET" .
        "  sTransNox = " . CommonUtil::toSQL($nextcd) .
        ", dTransact = " . CommonUtil::toSQL($date->format(CommonUtil::format_date)) .
        ", sClientID = " . CommonUtil::toSQL($sClientID) .
        ", cReqstCDe = " . CommonUtil::toSQL($cReqstCDe) .
        ", cAddrssTp = " . CommonUtil::toSQL($cAddrssTp) .
        ", sHouseNox = " . CommonUtil::toSQL($sHouseNox) .
        ", sAddressx = " . CommonUtil::toSQL($sAddressx) .
        ", sTownIDxx = " . CommonUtil::toSQL($sTownIDxx) .
        ", sBrgyIDxx = " . CommonUtil::toSQL($sBrgyIDxx) .
        ", nLatitude = " . $nLatitude .
        ", nLongitud = " . $nLongitud .
        ", cPrimaryx = " . CommonUtil::toSQL($cPrimaryx) .
        ", sRemarksx = " . CommonUtil::toSQL($sRemarksx) .
        ", sSourceCD = " . CommonUtil::toSQL($sSourceCD) .
        ", sSourceNo = " . CommonUtil::toSQL($sSourceNo) .        
        ", cTranStat = '0'" .
        ", sModified = " . CommonUtil::toSQL($userid) . 
        ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp));

if($app->execute($sql) <= 0){ //do not execute to replication
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Unable to save Address_Update_Request:" . $app->getErrorMessage();
    echo json_encode($json);
    return;
}

$json["result"] = "success";
$json["sTransNox"] = $nextcd;
echo json_encode($json);
return;
?>