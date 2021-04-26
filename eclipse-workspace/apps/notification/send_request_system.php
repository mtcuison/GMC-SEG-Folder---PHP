<?php
/*
 * import_request_system.php
 * kalyptus - 2019.07.19 02:00pm
 * use this API when a SYSTEM MONITOR request for notification.
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

$xdata = file_get_contents('php://input');
//parse into json the PARAMETERS
$parjson = json_decode($xdata, true);

$notification = new NotificationRequest($app);
$notification->setType($parjson["type"]);
$notification->setParent($parjson["parent"]);
$notification->setTitle($parjson["title"]);
$notification->setMessage($parjson["message"]);

$notification->setSourceApp("SYSTEM");
$notification->setSourceUser($parjson["type"]);

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