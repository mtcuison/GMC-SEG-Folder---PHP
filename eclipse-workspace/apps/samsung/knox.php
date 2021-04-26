<?php

require_once 'config.php';
require_once APPPATH.'/lib/samsung/knox_constant.php';
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

if($logno == ""){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_AUTH_LOG;
    $json["error"]["message"] = "Invalid LOG NO detected";
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

if(!$app->validLog($prodctid, $userid, $pcname, $logno, $clientid)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

$data = file_get_contents('php://input');

//parse into json the PARAMETERS
$parjson = json_decode($data, true);	

$action = "KNOX_URL_" . $parjson["request"];
$url="";
$xparam="";

if(!defined($action)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "API request is not available...";
    echo json_encode($json);
    return;
    //$url = constant($data);
}

$xparam = $parjson["param"];
if(substr($parjson["request"], 1, 3) == "KDP"){
   $url = KNOX_BASE.KNOX_KDP_VERSION.constant($action);
}
else{ 
   $url = KNOX_BASE.KNOX_VERSION.constant($action);
}
$token = "";

//Load approve token
$token = json_decode(file_get_contents(APPPATH.'/apps/samsung/apitoken.json'), true) ;
//Chek token date
$current = new DateTime("now");
$created = new DateTime($token["created"]);
//$interval = $current->diff($created);
//echo $token["created"];
//echo $interval->i . "-";
//echo $interval;
//echo $current->format(CommonUtil::format_timestamp) . "-";

//Convert difference in minutes
//$minutes = $interval->days * 24 * 60;
//$minutes += $interval->h * 60;
//$minutes += $interval->i;

//echo ($current->getTimestamp() - $created->getTimestamp()) . "-";
$minutes = ($current->getTimestamp() - $created->getTimestamp()) / 60;

if($minutes > 25){
    $return = 0;
    
    //create new access token
    $command = "java -Xmx1g -cp " . APPPATH . "/GGC_Java_Systems/knox-token.jar org.rmj.knox.token.GenKnoxToken " . APPPATH . "/apps/samsung";
    //echo $command . "-";
    exec($command, $return);
    //var_dump($return);
    
    //Reload approve token
    $token = json_decode(file_get_contents(APPPATH.'/apps/samsung/apitoken.json'), true) ;
    //echo $current->getTimestamp() - $created->getTimestamp();

    //Compare again
    $created = new DateTime($token["created"]);
    $current = new DateTime("now");
    $minutes = ($current->getTimestamp() - $created->getTimestamp()) / 60;
    //echo $minutes;
    if($current->getTimestamp() - $created->getTimestamp() > 25){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
        $json["error"]["message"] = "API token can't be updated...";
        echo json_encode($json);
        return;
    }
    
    //$token = json_decode(file_get_contents(APPPATH.'/apps/samsung/apitoken.json'), true) ;
}

//echo $token["created"] . "-";

$header = array();
$header[] = "Accept: application/json";
$header[] = "Content-Type: application/json";
//$header[] = "x-knox-apikey: " . $token['signed_token'];
//$header[] = "x-knox-transactionId: M00118000001";
$header[] = "x-knox-apitoken: " . $token['signed_token'];
$header[] = "x-knox-transactionId: M00118000001";

$result = WebClient::httpsPostJson($url, $xparam, $header);
echo $result;

?>
