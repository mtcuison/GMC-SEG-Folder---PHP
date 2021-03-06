<?php
/* Fetch the status of request.
 *
 * /system/token_approval/check_code_request.php
 *
 * mac 2020.11.27
 *  started creating this object.
 */

require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'WebClient.php';
require_once 'WSHeaderValidatorFactory.php';
require_once 'MySQLAES.php';
require_once 'Tokenize.php';


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

$param = file_get_contents('php://input');
$parjson = mb_convert_encoding(json_decode($param, true), 'ISO-8859-1', 'UTF-8');

if(is_null($parjson)){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Invalid parameters detected";
    echo json_encode($json);
    return;
}

if(!isset($parjson['sSourceNo'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Unset SOURCE NO.";
    echo json_encode($json);
    return;
}

$sourceno = $parjson['sSourceNo'];

if(!isset($parjson['sSourceCD'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Unset SOURCE CODE.";
    echo json_encode($json);
    return;
}

$sourcecd = $parjson['sSourceCD'];

if(!isset($parjson['sRqstType'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Unset REQUEST TYPE.";
    echo json_encode($json);
    return;
}

$rqsttype = $parjson['sRqstType'];

if(!isset($parjson['sReqstdTo'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Unset REQUESTED TO.";
    echo json_encode($json);
    return;
}

$reqstdto = $parjson['sReqstdTo'];

//check if the record already exists
$sql = "SELECT" .
            "  sMobileNo" .
            ", sAuthTokn" .
            ", sApprCode" .
            ", cTranStat" .
        " FROM Tokenized_Approval_Request" .
        " WHERE sSourceNo = " . CommonUtil::toSQL($sourceno) .
            " AND sSourceCd = " . CommonUtil::toSQL($sourcecd) .
            " AND sRqstType = " . CommonUtil::toSQL($rqsttype) .
            " AND sReqstdTo = " . CommonUtil::toSQL($reqstdto);

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Error found during loading..." . $app->getErrorMessage();
    echo json_encode($json);
    return;
} elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "No record found.";
    echo json_encode($json);
    return;
}

$json["result"] = "success";
$json["sMobileNo"] = $rows[0]["sMobileNo"];
$json["sAuthTokn"] = $rows[0]["sAuthTokn"];
$json["sApprCode"] = $rows[0]["sApprCode"];
$json["cTranStat"] = $rows[0]["cTranStat"];
echo json_encode($json);