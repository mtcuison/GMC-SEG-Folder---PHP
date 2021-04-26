<?php
/*
 * import_trans_offline.php
 * kalyptus - 2019.06.22 10:24am
 * use this API in requesting list of branches(GuanzonApp).
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
if(!$app->validLog($prodctid, $userid, $pcname)){
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

$sql = "SELECT b.sCompnyNm, b.dBirthDte, a.sGCardNox, a.sCardNmbr, a.cCardType, c.sNmOnCard, a.nAvlPoint, a.nTotPoint, a.dMemberxx, a.cDigitalx, a.cCardStat" .
    " FROM G_Card_Master a" .
    " LEFT JOIN Client_Master b ON a.sClientID = b.sClientID" .
    " LEFT JOIN G_Card_Application c on a.sApplicNo = c.sTransNox" .
    " LEFT JOIN G_Card_App_User_Device d ON a.sGCardNox = d.sGCardNox AND d.cRecdStat = '1'" .
    " WHERE d.sUserIDxx = '$userid'" . 
      " AND a.cCardStat = '4'";

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Failed loading GCARD Accounts! " . $app->getErrorMessage();
    echo json_encode($json);
    return;
}
if(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "There are no GCARD Account detected! ";
    echo json_encode($json);
    return;
}

$rows_found = sizeof($rows);
$detail = array();
for($ctr=0;$ctr<$rows_found;$ctr++){
    $detail[$ctr]["sGCardNox"] = $rows[$ctr]["sGCardNox"];
    $detail[$ctr]["sCardNmbr"] = $rows[$ctr]["sCardNmbr"];
    $detail[$ctr]["sNmOnCard"] = $rows[$ctr]["sNmOnCard"];
    $detail[$ctr]["cCardType"] = $rows[$ctr]["cCardType"];
    $detail[$ctr]["dMemberxx"] = $rows[$ctr]["dMemberxx"];
    $detail[$ctr]["nAvlPoint"] = $rows[$ctr]["nAvlPoint"];
    $detail[$ctr]["nTotPoint"] = $rows[$ctr]["nTotPoint"];
    $detail[$ctr]["cCardStat"] = $rows[$ctr]["cCardStat"];
}

$json["result"] = "success";
$json["detail"] = $detail;
echo json_encode($json);
return;

?>