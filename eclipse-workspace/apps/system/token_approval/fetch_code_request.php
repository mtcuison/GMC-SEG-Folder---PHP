<?php
/* Fetch the code request for the approving officer / specific request.
 *
 * /system/token_approval/fetch_code_request.php
 *
 * mac 2020.12.01
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


//search by Requested To and Transaction Status
if(isset($parjson['sReqstdTo']) && 
    isset($parjson['cTranStat'])){
    
    $reqstdto = $parjson['sReqstdTo'];
    $transtat = $parjson['cTranStat'];
    
    $sql = "SELECT" .
                "  sTransNox" .
                ", dTransact" .
                ", sSourceNo" .
                ", sSourceCD" .
                ", sRqstType" .
                ", sReqstInf" .
                ", sReqstdBy" .
                ", sReqstdTo" .
                ", sApprCode" .
                ", dApproved" .
                ", cTranStat" . 
            " FROM Tokenized_Approval_Request" .
            " WHERE sReqstdTo = " . CommonUtil::toSQL($reqstdto);
    
    if ($transtat != "all") $sql = CommonUtil::addcondition($sql, "cTranStat = " . CommonUtil::toSQL($transtat));
} else { //Search by Transaction Primary Info
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
    
    $sql = "SELECT" .
                "  sTransNox" .
                ", dTransact" .
                ", sSourceNo" .
                ", sSourceCD" .
                ", sRqstType" .
                ", sReqstInf" .
                ", sReqstdBy" .
                ", sReqstdTo" .
                ", sApprCode" .
                ", dApproved" .
                ", cTranStat" .
            " FROM Tokenized_Approval_Request" .
            " WHERE sSourceNo = " . CommonUtil::toSQL($sourceno) .
                " AND sSourceCd = " . CommonUtil::toSQL($sourcecd) .
                " AND sRqstType = " . CommonUtil::toSQL($rqsttype);
}

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

$detail = array();
$rows_found = sizeof($rows);
$row = 0;

for($ctr=0;$ctr<$rows_found; $ctr++){
    $detail[$ctr]["sTransNox"] = mb_convert_encoding($rows[$ctr]["sTransNox"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["dTransact"] = mb_convert_encoding($rows[$ctr]["dTransact"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sSourceNo"] = mb_convert_encoding($rows[$ctr]["sSourceNo"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sSourceCD"] = mb_convert_encoding($rows[$ctr]["sSourceCD"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sRqstType"] = mb_convert_encoding($rows[$ctr]["sRqstType"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sReqstInf"] = mb_convert_encoding($rows[$ctr]["sReqstInf"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sReqstdBy"] = mb_convert_encoding($rows[$ctr]["sReqstdBy"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sReqstdTo"] = mb_convert_encoding($rows[$ctr]["sReqstdTo"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sApprCode"] = mb_convert_encoding($rows[$ctr]["sApprCode"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["dApproved"] = mb_convert_encoding($rows[$ctr]["dApproved"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["cTranStat"] = mb_convert_encoding($rows[$ctr]["cTranStat"], 'UTF-8', 'ISO-8859-1');
}

$json["result"] = "success";
$json["detail"] = $detail;
echo json_encode($json);