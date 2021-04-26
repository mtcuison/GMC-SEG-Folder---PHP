<?php

require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'WebClient.php';
require_once 'WSHeaderValidatorFactory.php';

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

//var_dump($myheader);
$json = array();
if(!$validator->isHeaderOk($myheader)){
    $json["result"] = "error";
    $json["error"]["message"] = $validator->getMessage();
    echo json_encode($json);
    return;
}

//GET HEADERS HERE
//Product ID
$prodctid = $myheader['g-api-id'];
//Computer Name / IEMI No
$pcname 	= $myheader['g-api-imei'];
//User ID
$userid		= $myheader['g-api-user'];

$logno = "";
$clientid = "";

if($prodctid != "GuanzonApp"){
    //SysClient ID
    $clientid = $myheader['g-api-client'];
    //Log No
    $logno 		= $myheader['g-api-log'];
    
    if($logno == ""){
        $json["result"] = "error";
        $json["error"]["message"] = "Invalid LOG NO detected";
        echo json_encode($json);
        return;
    }
}

//initialize driver to use
$app = new Nautilus(APPPATH);
//load the driver
if(!$app->LoadEnv($prodctid)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

//Make sure that account was currently log-in...
if(!$app->validLog($prodctid, $userid, $pcname, $logno, $clientid)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

//load user's information...
$sql = "SELECT" .
            "  a.sUserIDxx" .
            ", a.sModelCde xModelCde" .
            ", IF(IFNULL(a.sModelCde, '') = '', 'Unknown Device', IF(IFNULL(a.sModelCde, '') = 'UNKNOWN', 'Unknown Device', CONCAT(b.sBrandNme, ' ', b.sMarketNm))) sModelCde" .
            ", a.sIMEINoxx" .
            ", a.sLogNoxxx" .
            ", a.dLastLogx" .
            ", a.cRecdStat" .
        " FROM App_User_Device a" .
            " LEFT JOIN App_User_Device_Model b" . 
                " ON a.sModelCde = b.sModelNme" .
        " WHERE a.sUserIDxx = '$userid'";
$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Failed loading devices! " . $app->getErrorMessage();
    echo json_encode($json);
    return;
}
if(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "There are no device usage for this account! ";
    echo json_encode($json);
    return;
}

$rows_found = sizeof($rows);
$detail = array();
for($ctr=0;$ctr<$rows_found;$ctr++){
    $detail[$ctr]["sUserIDxx"] = $rows[$ctr]["sUserIDxx"];
    $detail[$ctr]["xModelCde"] = $rows[$ctr]["xModelCde"];
    $detail[$ctr]["sModelCde"] = $rows[$ctr]["sModelCde"];
    $detail[$ctr]["sIMEINoxx"] = $rows[$ctr]["sIMEINoxx"];
    $detail[$ctr]["sLogNoxxx"] = $rows[$ctr]["sLogNoxxx"];
    $detail[$ctr]["dLastLogx"] = $rows[$ctr]["dLastLogx"];
    $detail[$ctr]["cRecdStat"] = $rows[$ctr]["cRecdStat"];
}

$json["result"] = "success";
$json["detail"] = $detail;
echo json_encode($json);

?>
