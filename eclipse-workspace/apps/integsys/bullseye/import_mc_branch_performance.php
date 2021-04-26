<?php
/* https://restgk.guanzongroup.com.ph/integsys/bullseye/import_mc_branch_performance.php
 * 
 *  use this API in requesting the MC branch performance for a given period.
 * 
 * mac - 2020.05.27 01:52 pm
 *  started this API.     
 * 
 * Note:
 */

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
if(!$app->validLog($prodctid, $userid, $pcname, $logno, $clientid)){
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

$data = file_get_contents('php://input');
//parse into json the PARAMETERS
$parjson = json_decode($data, true);


if(!isset($parjson["period"])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Please pass the period needed!";
    echo json_encode($json);
    return;
}

$period = $parjson["period"];

if(!isset($parjson["areacd"])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Please specify the area needed.";
    echo json_encode($json);
    return;
}

$areacd = $parjson["areacd"];

$sql = "SELECT" .
            "  a.sBranchCd" .
            ", b.sBranchNm" .
            ", a.nMCGoalxx" .
            ", a.nSPGoalxx" .
            ", a.nJOGoalxx" .
            ", a.nLRGoalxx" .
            ", a.nMCActual" .
            ", a.nSPActual" .
            ", a.nJOActual" .
            ", a.nLRActual" .
        " FROM MC_Branch_Performance a" .
            ", Branch b" .
            ", Branch_Others c" .
        " WHERE a.sBranchCd = b.sBranchCd" .
            " AND b.sBranchCd = c.sBranchCd" .
            " AND a.sPeriodxx = " . CommonUtil::toSQL($period) . 
            " AND c.sAreaCode = " . CommonUtil::toSQL($areacd);

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Failed loading MC branches performance record.";
    echo json_encode($json);
    return;
}
if(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "MC branches performance for the period is not available!";
    echo json_encode($json);
    return;
}


$rows_found = sizeof($rows);
$detail = array();
for($ctr=0; $ctr < $rows_found; $ctr++){    
    $detail[$ctr]["sBranchCd"] = $rows[$ctr]["sBranchCd"];
    $detail[$ctr]["sBranchNm"] = $rows[$ctr]["sBranchNm"];
    $detail[$ctr]["nMCGoalxx"] = $rows[$ctr]["nMCGoalxx"];
    $detail[$ctr]["nSPGoalxx"] = $rows[$ctr]["nSPGoalxx"];
    $detail[$ctr]["nJOGoalxx"] = $rows[$ctr]["nJOGoalxx"];
    $detail[$ctr]["nLRGoalxx"] = $rows[$ctr]["nLRGoalxx"];
    $detail[$ctr]["nJOGoalxx"] = $rows[$ctr]["nJOGoalxx"];
    $detail[$ctr]["nMCActual"] = $rows[$ctr]["nMCActual"];
    $detail[$ctr]["nSPActual"] = $rows[$ctr]["nSPActual"];
    $detail[$ctr]["nLRActual"] = $rows[$ctr]["nLRActual"];
}

$json["result"] = "success";
$json["detail"] = $detail;
echo json_encode($json);
return;
?>