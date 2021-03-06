<?php
/* Get the reply for the approval request(approved or disapproved)
 *
 * /system/token_approval/reply_code_request.php
 *
 * mac 2020.12.02
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

if(!isset($parjson['sTransNox'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Unset TRANSACTION NO.";
    echo json_encode($json);
    return;
}

$transnox = $parjson['sTransNox'];

if(!isset($parjson['cTranStat'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Unset TRANSACTION STATUS";
    echo json_encode($json);
    return;
}

$transtat = $parjson['cTranStat'];

if ($transtat == "1"){
    if(!isset($parjson['cApprType'])){
        $json["result"] = "error";
        $json["error"]["code"] = "101";
        $json["error"]["message"] = "Unset TRANSACTION TYPE";
        echo json_encode($json);
        return;
    }
    
    $apprtype = $parjson['cApprType'];
}

//requested status must not be open
if ($transtat == "0"){
    $json["result"] = "error";
    $json["error"]["code"] = "102";
    $json["error"]["message"] = "OPENING the transaction is not ALLOWED.";
    echo json_encode($json);
    return;
}

$sql = "SELECT" .
            "  sTransNox" .
            ", dTransact" .
            ", sSourceNo" .
            ", sSourceCD" .
            ", sRqstType" .
            ", sReqstInf" .
            ", sReqstdBy" .
            ", sReqstdTo" .
            ", sMobileNo" .
            ", sApprCode" .
            ", dApproved" .
            ", cTranStat" .
        " FROM Tokenized_Approval_Request" .
        " WHERE sTransNox = " . CommonUtil::toSQL($transnox);

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

//requested status is same as the current status
if ($rows[0]["cTranStat"] == $transtat){
    $json["result"] = "success";
    echo json_encode($json);
    return;
}

//transaction is already cancelled or void
if ($rows[0]["cTranStat"] == "2" ||
    $rows[0]["cTranStat"] == "3" ||
    $rows[0]["cTranStat"] == "4"){
    
    $json["result"] = "error";
    $json["error"]["code"] = "102";
    $json["error"]["message"] = "UPDATING the STATUS of POSTED/CANCELLED/VOID TRANSACTION is not ALLOWED.";
    echo json_encode($json);
    return;
}

if ($rows[0]["cTranStat"] > $transtat){
    $json["result"] = "error";
    $json["error"]["code"] = "102";
    $json["error"]["message"] = "DOWNGRADING a transacation status  is not ALLOWED.";
    echo json_encode($json);
    return;
}

date_default_timezone_set('Asia/Manila');
$date = new DateTime('now');

if ($transtat == "1"){ //set status to CLOSED ->> APPROVED
    //tokenize approval code
    $apprvlcd = Tokenize::EncryptApprovalToken($rows[0]["sTransNox"], $apprtype, $rows[0]["sRqstType"], $rows[0]["sReqstdTo"]);
    
    //get auth token
    $sql = "SELECT" .
                "  sAuthTokn" . 
            " FROM System_Code_Mobile" .
            " WHERE sEmployID = " . CommonUtil::toSQL($rows[0]["sReqstdTo"]) .
                " AND sMobileNo = " . CommonUtil::toSQL($rows[0]["sMobileNo"]) . 
                " AND sAuthCode LIKE " . CommonUtil::toSQL("%" . $rows[0]["sRqstType"] . "%");
    
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
        $json["error"]["message"] = "Approvee is not authorized to approve.";
        echo json_encode($json);
        return;
    }
    
    $authtokn = $rows[0]["sAuthTokn"];
    
    $sql = "UPDATE Tokenized_Approval_Request SET" .
                "  cApprType = " . CommonUtil::toSQL($apprtype) .
                ", sApprCode = " . CommonUtil::toSQL($apprvlcd) .
                ", sAuthTokn = " . CommonUtil::toSQL($authtokn) .
                ", dApproved = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp)) .
                ", cTranStat = " . CommonUtil::toSQL($transtat) .
                ", sModified = " . CommonUtil::toSQL($userid) .
                ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp)) .
            " WHERE sTransNox = " . CommonUtil::toSQL($transnox);
} else { //set status to POSTED / CANCELLED / VOID
    $sql = "UPDATE Tokenized_Approval_Request SET" .
                "  cTranStat = " . CommonUtil::toSQL($transtat) . 
                ", sModified = " . CommonUtil::toSQL($userid) .
                ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp)) .
            " WHERE sTransNox = " . CommonUtil::toSQL($transnox);
}

$app->beginTrans();

if ($app->execute($sql) <= 0){
    $app->rollbackTrans();
    
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

$app->commitTrans();

$json["result"] = "success";
echo json_encode($json);