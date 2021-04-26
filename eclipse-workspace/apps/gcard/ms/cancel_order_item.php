<?php
/*
 * cancel_order_item.php
 * kalyptus - 2019.06.18 11:14am
 * use this API requesting a cancellation of placed order(GuanzonApp).
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

$data = file_get_contents('php://input');
//parse into json the PARAMETERS
$parjson = json_decode($data, true);

$aes = new MySQLAES(APPKEYX);
$transno = $aes->decrypt(htmlspecialchars($parjson["uuid"]));

//get information from the Order table
$sql = "SELECT *" .
    " FROM G_Card_Order_Master" .
    " WHERE sTransNox = '$transno'";
$rows = $app->fetch($sql);
if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Failed on loading the Order record! " . $app->getErrorMessage();
    echo json_encode($json);
    return;
}
elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "Order is not existing!";
    echo json_encode($json);
    return;
}

// 1 = points
if($rows[0]["cTranStat"] == "2"){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
    $json["error"]["message"] = "Order was already redeem!";
    echo json_encode($json);
    return null;
}
elseif($rows[0]["cTranStat"] == "3"){
    $json["result"] = "success";
    echo json_encode($json);
    return null;
}

$sql = "UPDATE G_Card_Order_Master" .
    " SET cTranStat = '3'" .
    " WHERE sTransNox = '$transno'";

$app->beginTrans();
if($app->execute($sql, "G_Card_Order_Master", $rows[0]["sBranchCd"], "") <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Order cancellation failed... " . $app->getErrorMessage();
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}
$app->commitTrans();

$json["result"] = "success";
echo json_encode($json);
return;

?>