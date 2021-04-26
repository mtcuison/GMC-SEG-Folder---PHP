<?php
/*
 * request_avl_points.php
 * kalyptus - 2019.06.24 11:01am
 * use this API in requesting/getting the updated points(GuanzonApp).
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
    $json["error"]["message"] = "Invalid mobile number detected";
    echo json_encode($json);
    return;
}

$userid		= $myheader['g-api-user'];

$app = new Nautilus(APPPATH);
if(!$app->LoadEnv($prodctid)){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

if($logno != ""){
    if(!$app->validLog($logno, $prodctid, $clientid, $userid, $pcname)){
        $json["result"] = "error";
        $json["error"]["message"] = $app->getErrorMessage();
        echo json_encode($json);
        return;
    }
}

//Google FCM token
$token = $myheader['g-api-token'];
if(!$app->loaduserClient($prodctid, $userid, $pcname, $token, $mobile)){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

$data = file_get_contents('php://input');
//parse into json the PARAMETERS
$parjson = json_decode($data, true);
$aes = new MySQLAES(APPKEYX);
$cardnmbr = $aes->decrypt($parjson["secureno"]);

$sql = "SELECT" .
    "  a.sGCardNox" .
    ", a.sCardNmbr" .
    ", a.cCardType" .
    ", a.nTotPoint" .
    ", a.nAvlPoint" .
    ", a.cCardStat" .
    " FROM G_Card_Master a" .
    " WHERE a.sCardNmbr = '$cardnmbr'";
$rows = $app->fetch($sql);

//Validate if transno are valid...
if($rows === null){
    $json["result"] = "error";
    $json["error"]["message"] = "Error found during loading..." . $app->getErrorMessage();
    echo json_encode($json);
    return;
}
elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["message"] = "No gcard information was found..." . $sql;
    echo json_encode($json);
    return;
}

$json["result"] = "SUCCESS";
$json["nTotPoint"] = $rows[0]["nTotPoint"];
$json["nAvlPoint"] = $rows[0]["nAvlPoint"];
$json["cCardStat"] = $rows[0]["cCardStat"];
echo json_encode($json);
return;
?>