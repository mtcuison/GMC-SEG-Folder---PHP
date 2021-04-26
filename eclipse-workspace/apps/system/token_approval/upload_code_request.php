<?php
/* Upload the request to the server.
 *
 * /system/token_approval/upload_code_request.php
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

if(!isset($parjson['sTempTNox'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Unset TRANSACTION NO..";
    echo json_encode($json);
    return;
}

$transnox = $parjson['sTempTNox'];

if(!isset($parjson['dTransact'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Unset TRANSACTION DATE.";
    echo json_encode($json);
    return;
}

$transact = $parjson['dTransact'];

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

if(!isset($parjson['sReqstdBy'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Unset REQUESTED BY.";
    echo json_encode($json);
    return;
}

$reqstdby = $parjson['sReqstdBy'];

if(!isset($parjson['sReqstdTo'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Unset REQUESTED TO.";
    echo json_encode($json);
    return;
}

$reqstdto = $parjson['sReqstdTo'];

if(!isset($parjson['sReqstInf'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Unset REQUEST INFO.";
    echo json_encode($json);
    return;
}

$reqstinf = $parjson['sReqstInf'];

if(!isset($parjson['cApprType'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Unset APPROVAL REQUEST TYPE.";
    echo json_encode($json);
    return;
}

$apprtype = $parjson['cApprType'];

date_default_timezone_set('Asia/Manila');
$date = new DateTime('now');
$year = $date->format("y");

//check if the record already exists
$sql = "SELECT" .
            "  sTransNox" .
            ", LEFT(sSourceNo, 4) sBranchCd" .
            ", sReqstInf" .
            ", sReqstdBy" .
            ", sMobileNo" .
            ", cApprType" . 
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
} elseif(!empty($rows)){
    $transnox =  $rows[0]["sTransNox"];
    
    //request was still open
    if ($rows[0]["cTranStat"] == "0"){
        //request type is not the same as the original saved
        if ($rows[0]["cApprType"] == "0" &&  
            $rows[0]["cApprType"] != $apprtype){
            
            $sql = "UPDATE Tokenized_Approval_Request SET" .
                        "  sApprType = " . CommonUtil::toSQL($apprtype) .
                        ", sModified = " . CommonUtil::toSQL($userid) . 
                        ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp)) .
                    " WHERE sTransNox = " . CommonUtil::toSQL($transnox);
            
            $app->beginTrans();
            if ($app->execute($sql) <= 0){
                $app->rollbackTrans();
                
                $json["result"] = "error";
                $json["error"]["code"] = $app->getErrorCode();
                $json["error"]["message"] = "Unable to update REQUEST APPROVAL TYPE. " . $app->getErrorMessage();
                echo json_encode($json);
                return;
            }
            $app->commitTrans();
        }
        
        if ($apprtype == "1"){            
            $message = "APP_RQST EP/" . $rows[0]["sBranchCd"] . "/" . $rows[0]["sReqstInf"] . "/By:" . getEmployee($app, $rows[0]["sReqstdBy"]);
            
            if (!createSMS($app, $transnox, $message, $rows[0]["sMobileNo"], $userid)){
                $json["result"] = "error";
                $json["error"]["code"] = "102";
                $json["error"]["message"] = "Unable to create SMS REQUEST.";
                echo json_encode($json);
                return;
            }
        }
    }
    
    $json["result"] = "success";
    $json["sTransNox"] = $transnox;
    echo json_encode($json);
    return;
}

//get approval info
$sql = "SELECT" .
            "  sMobileNo" .
            ", sAuthTokn" .
        " FROM System_Code_Mobile" .
        " WHERE sEmployID = " . CommonUtil::toSQL($reqstdto) . 
            " AND sAuthCode LIKE " . CommonUtil::toSQL("%" . $rqsttype . "%");

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
    $json["error"]["message"] = "Requested officer has no set approval token.";
    echo json_encode($json);
    return;
}

$transnox = CommonUtil::GetNextCode("Tokenized_Approval_Request", "sTransNox", $year, $app->getConnection(), "MX01");

$app->beginTrans();

//save record to server
$sql = "INSERT INTO Tokenized_Approval_Request SET" . 
        "  sTransNox = " . CommonUtil::toSQL($transnox) .
        ", dTransact = " . CommonUtil::toSQL($transact) .
        ", sSourceNo = " . CommonUtil::toSQL($sourceno) .
        ", sSourceCD = " . CommonUtil::toSQL($sourcecd) .
        ", sRqstType = " . CommonUtil::toSQL($rqsttype) .
        ", sReqstInf = " . CommonUtil::toSQL($reqstinf) .
        ", sReqstdBy = " . CommonUtil::toSQL($reqstdby) .
        ", sReqstdTo = " . CommonUtil::toSQL($reqstdto) .
        ", sMobileNo = " . CommonUtil::toSQL($rows[0]["sMobileNo"]) .
        ", cApprType = " . CommonUtil::toSQL($apprtype) .
        ", sAuthTokn = " . CommonUtil::toSQL("") .
        ", sApprCode = " . CommonUtil::toSQL("") .
        ", dRcvdDate = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp)) . 
        ", cSendxxxx = " . CommonUtil::toSQL("0") .
        ", cTranStat = " . CommonUtil::toSQL("0") .
        ", sModified = " . CommonUtil::toSQL($userid) .
        ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp));

if ($app->execute($sql) <= 0){
    $app->rollbackTrans();
    
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

if ($apprtype == "1"){
    $message = "APP_RQST EP/" . substr($sourceno, 0, 4) . "/" . $reqstinf . "/By:" . getEmployee($app, $reqstdby);
    
    if (!createSMS($app, $transnox, $message, $rows[0]["sMobileNo"], $userid)){
        $json["result"] = "error";
        $json["error"]["code"] = "102";
        $json["error"]["message"] = "Unable to create SMS REQUEST.";
        echo json_encode($json);
        return;
    }
}

$app->commitTrans();

$json["result"] = "success";
$json["sTransNox"] = $transnox;
echo json_encode($json);
return;


function createSMS($app, $transno, $message, $mobileno, $userid){
    date_default_timezone_set('Asia/Manila');
    $date = new DateTime('now');
    $year = $date->format("y");
        
    $sql = "INSERT INTO HotLine_Outgoing SET" .
        "  sTransNox = " . CommonUtil::toSQL(CommonUtil::GetNextCode("HotLine_Outgoing", "sTransNox", $year, $app->getConnection(), "MX01")) .
        ", dTransact = " . CommonUtil::toSQL($date->format(CommonUtil::format_date)) .
        ", sDivision = 'MIS'" .
        ", sMobileNo = " . CommonUtil::toSQL($mobileno) .
        ", sMessagex = " . CommonUtil::toSQL($message) .
        ", cSubscrbr = " . CommonUtil::toSQL(CommonUtil::getMobileNetwork($mobileno)) .
        ", dDueUntil = " . CommonUtil::toSQL($date->format(CommonUtil::format_date)) .
        ", cSendStat = '0'" .
        ", nNoRetryx = '0'" .
        ", sUDHeader = ''" .
        ", sReferNox = " . CommonUtil::toSQL($transno) .
        ", sSourceCd = " . CommonUtil::toSQL("APTK") .
        ", cTranStat = '0'" .
        ", nPriority = 0" .
        ", sModified = " . CommonUtil::toSQL($userid) .
        ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp));
    
    return $app->execute($sql) > 0;
}

function getEmployee($app, $employid){
    $sql = "SELECT CONCAT(sFrstName, ' ', sLastName) xEmployNm FROM Client_Master WHERE sClientID = " . CommonUtil::toSQL($employid);
    
    $rows = $app->fetch($sql);
    
    if($rows === null){
        return "";
    } elseif(empty($rows)){
        return "";
    }
    
    return $rows[0]["xEmployNm"];
}
