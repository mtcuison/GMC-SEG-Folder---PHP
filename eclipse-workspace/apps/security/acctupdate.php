<?php

require_once 'config.php';
require_once APPPATH.'/lib/samsung/knox_constant.php';
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
$sql = "SELECT * FROM App_User_Master WHERE sUserIDxx = '$userid' AND sProdctID = '$prodctid'";
$rows = $app->fetch($sql);

//get the parameter
$data = file_get_contents('php://input');

//parse into json the PARAMETERS
$parjson = json_decode($data, true);
$oldpswd = $parjson["oldpswd"];
$newpswd = $parjson["newpswd"];

//since we have already validated the identity of the user, just assume that user is really existing...
$xpassword = CommonUtil::app_decrypt($rows[0]["sPassword"], $rows[0]["sItIsASIN"]);
if($xpassword != $oldpswd){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PASSWORD;
    $json["error"]["message"] = "Please make sure to give the correct previous password!";
    echo json_encode($json);
    return;
}

$xnewpswd = CommonUtil::app_encrypt($newpswd, $rows[0]["sItIsASIN"]);
$sql = "UPDATE App_User_Master SET sPassword = '$xnewpswd' WHERE sUserIDxx = '$userid' AND sProdctID = '$prodctid'";
$result = $app->execute($sql);

if($result <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Account update failed! Cannot update account information." . $app->getErrorMessage();
    echo json_encode($json);
    return;
}

$json["result"] = "success";
echo json_encode($json);

?>
