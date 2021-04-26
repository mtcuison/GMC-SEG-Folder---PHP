<?php
/*
 * import_request.php
 * kalyptus - 2019.07.19 02:00pm
 * use this API when a USERS request for notification.
 * Note:
 */

require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'WebClient.php';
require_once 'WSHeaderValidatorFactory.php';
require_once 'MySQLAES.php';
require_once 'NotificationRequest.php';

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
if(!$app->validLog($prodctid, $userid, $pcname, "GAP021012400")){
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

$xdata = file_get_contents('php://input');
//parse into json the PARAMETERS
$parjson = json_decode($xdata, true);

$notification = new NotificationRequest($app);
$notification->setType($parjson["type"]);
$notification->setParent($parjson["parent"]);
$notification->setTitle($parjson["title"]);
$notification->setMessage($parjson["message"]);

$notification->setSourceApp($prodctid);
$notification->setSourceUser($userid);
$notification->setDevice($pcname);

if(isset($parjson["urlimage"])){
    $notification->setAuditor($parjson["urlimage"]);
}

if(isset($parjson["infox"])){
    $notification->setData($parjson["infox"]);
}

$notification->setRecepient($parjson["rcpt"]);

if(isset($parjson["mntr"])){
    $notification->setAuditor($parjson["mntr"]);
}

$json = $notification->saveRequest();

//if saving of request is successfunl then send the notification to recepients...
if(strcasecmp($json["result"], 'success') == 0){
    $notification->sendRequest($json["transno"]);
}

echo json_encode($json);
return;
?>