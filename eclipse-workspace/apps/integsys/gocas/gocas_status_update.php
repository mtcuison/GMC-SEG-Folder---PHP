<?php
/*
 * gocas_status_update.php
 * kalyptus - 2019.12.13 02:43pm
 * use this API in updating the status of a GOCAS..
 * Note:
 *     Anroid  -> for request to tagged as VOID the APPLICATION.
 *     Desktop -> for informing to the main office that the APPLICATION was ALREADY TAGGED AS POSTED.
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

$param = file_get_contents('php://input');

//parse into json the PARAMETERS
$parjson = json_decode($param, true);

if(!(isset($parjson['refernox']) && isset($parjson['status']))){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Please specify the the correct parameter!";
    echo json_encode($json);
    return;
}

//retrieve the value of parameters
$value = $parjson['refernox'];
$status = $parjson['status'];

//Verify that status update request is either used or tagged as void 
//Is status update for 2->posting(used in application) or 4->tag as void
if(!($status == '2' || $status == '4')){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Status update request is invalid!";
    echo json_encode($json);
    return;
}

$sql = "SELECT sTransNox" .
    ", IFNULL(cTranStat, '') " .
    " FROM Credit_Online_Application" .
    " WHERE sTransNox LIKE '$value'" .
    " ORDER BY sTransNox DESC" .
    " LIMIT 1";

if(null === $rows = $app->fetch($sql)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getMessage();
    echo json_encode($json);
    return;
}
elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "Record not found";
    echo json_encode($json);
    return false;
}

//Check if GOCAS was already cancelled or used or void
//is status already used
if($status === '2'){
    if($rows[0]["status"] === "2"){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
        $json["error"]["message"] = "Application was already POSTED!";
        echo json_encode($json);
        return;
    }
    //is status already disapproved
    elseif($rows[0]["status"] === "3"){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
        $json["error"]["message"] = "Application was already DISAPPROVED!";
        echo json_encode($json);
        return;
    }
    //is status already void
    elseif($rows[0]["status"] === "4"){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
        $json["error"]["message"] = "Application was already TAGGED as VOID!";
        echo json_encode($json);
        return;
    }
}
elseif($status === "4"){
    if($rows[0]["cTranStat"] !== ""){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
        $json["error"]["message"] = "Only unverified APPLICATION can be TAGGED as VOID!";
        echo json_encode($json);
        return;
    }
}

$app->beginTrans();

$sql = "UPDATE Credit_Online_Application" . 
      " SET cTranStat = '$status'" . 
      " WHERE sTransNox = '$value'";

if($app->execute($sql) <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Unable to save Credit Application! " . $app->getErrorMessage();
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

$app->commitTrans();

$json["result"] = "success";
echo json_encode($json, JSON_UNESCAPED_UNICODE );
?>