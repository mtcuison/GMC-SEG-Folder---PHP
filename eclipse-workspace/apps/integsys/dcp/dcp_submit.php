<?php
//integsys/dcp/dcp_submit.php

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
$pcname	= $myheader['g-api-imei'];
//SysClient ID
$clientid = $myheader['g-api-client'];
//Log No
$logno = $myheader['g-api-log'];
//User ID
$userid	= $myheader['g-api-user'];

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

$userid	= $myheader['g-api-user'];

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
if(!$app->validLog($prodctid, $userid, $pcname, $logno)){
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
$par4sql = json_decode($param, true);

//detect the encoding used in the parameter...
//we perform the detection here so that we can properly handle characters
//such as (ñ). These characters are received as two part ASCII characters
//but can be detected once decoded(?) and encoded(?) again...
$enc_param = json_encode($parjson, JSON_UNESCAPED_UNICODE);
$encoding = mb_detect_encoding($enc_param);

//set the encoding to UTF-8/ISO-8859-1 if not ASCII
if($encoding !== "ASCII"){
    //primarily used by JAVA/PHP
    if($encoding !== "UTF-8"){
        $parjson = mb_convert_encoding($parjson, "UTF-8", $encoding);
    }
    
    //Possibly VB6/We used as default encoding for MySQL
    if($encoding !== "ISO-8859-1"){
        $par4sql = mb_convert_encoding($par4sql, "ISO-8859-1", $encoding);
    }
}

if(is_null($parjson)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Invalid parameters detected";
    echo json_encode($json);
    return;
}

$params = array("sTransNox", "nEntryNox", "sAcctNmbr", "sRemCodex", "sJsonData", "sUserIDxx", "sDeviceID");

$rows_found = sizeof($params);
for($ctr=0; $ctr<$rows_found; $ctr++){
    //check if parameter was passsed
    if(!isset($parjson[$params[$ctr]])){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
        $json["error"]["message"] = "Unset PARAMETER detected.";
        echo json_encode($json);
        return;
    }
    //check if parameter was empty
    if(empty($parjson[$params[$ctr]])){
        if ($params[$ctr] != "sJsonData" && $params[$ctr] != "sRemCodex"){
            $json["result"] = "error";
            $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
            $json["error"]["message"] = "Empty PARAMETER detected.";
            echo json_encode($json);
            return;
        }
    }
    
    switch ($params[$ctr]){
        case "sTransNox":
            $transnox = $parjson[$params[$ctr]];
            break;
        case "nEntryNox":
            $entrynxo = $parjson[$params[$ctr]];
            
            if (!is_numeric($entrynxo)){
                $json["result"] = "error";
                $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
                $json["error"]["message"] = "Entry No. must be NUMERIC.";
                echo json_encode($json);
                return;
            }
            
            break;
        case "sAcctNmbr":
            $acctnmbr = $parjson[$params[$ctr]];
            break;
        case "sRemCodex":
            $remcodex = $parjson[$params[$ctr]];
            break;
        case "sJsonData":
            $jsondata = mb_convert_encoding($parjson[$params[$ctr]], "ISO-8859-1", $encoding);
            break;
        case "dReceived":
            $received = $parjson[$params[$ctr]];
            break;
        case "sUserIDxx":
            $useridxx = $parjson[$params[$ctr]];
            break;
        case "sDeviceID":
            $deviceid = $parjson[$params[$ctr]];
            break;
    }
}

date_default_timezone_set('Asia/Manila');
$date = new DateTime('now');
$stamp = $date->format(CommonUtil::format_timestamp);

//check if the transaction was already saved
$sql = "SELECT" .
            "  sTransNox" .
            ", nEntryNox" .
            ", sAcctNmbr" .
        " FROM LR_DCP_Collection_Detail_Android" .
        " WHERE sTransNox = " . CommonUtil::toSQL($transnox) .
            " AND nEntryNox = $entrynxo" .
            " AND sAcctNmbr = " . CommonUtil::toSQL($acctnmbr);

if(null === $rows = $app->fetch($sql)){ //exception detected
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
} elseif(!empty($rows)){ //found record
    $sql = "UPDATE LR_DCP_Collection_Detail_Android SET" .
                "  sRemCodex = " . CommonUtil::toSQL($remcodex) .
                ", sJsonData = " . CommonUtil::toSQL(json_encode($jsondata)) .
                ", dReceived = " . CommonUtil::toSQL($stamp) .
                ", sUserIDxx = " . CommonUtil::toSQL($useridxx) .
                ", sDeviceID = " . CommonUtil::toSQL($deviceid) .
                ", dModified = " . CommonUtil::toSQL($stamp) .
            " WHERE sTransNox = " . CommonUtil::toSQL($transnox) .
                " AND nEntryNox = $entrynxo " .
                " AND sAcctNmbr = " . CommonUtil::toSQL($acctnmbr);
} else { //record not found
    $sql = "INSERT INTO LR_DCP_Collection_Detail_Android SET" .
            "  sTransNox = " . CommonUtil::toSQL($transnox) .
            ", nEntryNox = $entrynxo" .
            ", sAcctNmbr = " . CommonUtil::toSQL($acctnmbr) .
            ", sRemCodex = " . CommonUtil::toSQL($remcodex) .
            ", sJsonData = " . CommonUtil::toSQL(json_encode($jsondata)) .
            ", dReceived = " . CommonUtil::toSQL($stamp) .
            ", sUserIDxx = " . CommonUtil::toSQL($useridxx) .
            ", sDeviceID = " . CommonUtil::toSQL($deviceid) .
            ", dModified = " . CommonUtil::toSQL($stamp);
}

$app->beginTrans(); //begin database transaction

if($app->execute($sql) <= 0){ //execute to replication
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Unable to save LR_DCP_Collection_Detail_Android:" . $app->getErrorMessage();
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

//create payment acknowledgement SMS if remarks code is PAY
if (strtoupper($remcodex) == "PAY"){
    //get client mobile number
    $sql = "SELECT '09260375777' sMobileNo" . //IFNULL(b.sMobileNo, '')
            " FROM MC_AR_Master a" .
                " LEFT JOIN Client_Master b" .
                " ON a.sClientID = b.sClientID" .
            " WHERE a.sAcctNmbr = " . CommonUtil::toSQL($acctnmbr);
    
    if(null === $rows = $app->fetch($sql)){ //exception detected
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = $app->getErrorMessage();
        echo json_encode($json);
        return;
    } elseif(!empty($rows)){
        //is valid mobile number
        if (CommonUtil::isValidMobile($rows[0]["sMobileNo"])){            
            //convert mobile number to local format
            $mobileno = CommonUtil::fixMobile($rows[0]["sMobileNo"]);
            $amntpaid = $jsondata["nTranAmtx"] + $jsondata["nOthersxx"];
            $prnumber = $jsondata["sPRNoxxxx"];
            
            //compose message
            $message = "THANK YOU for your payment amounting to " . $amntpaid . " with PR No. " . $prnumber . " for account no. " . $acctnmbr . " -GUANZON Group";
            
            $year = $date->format("y");
            $nextcd = CommonUtil::GetNextCode("HotLine_Outgoing", "sTransNox", $year, $app->getConnection(), "MX01");
            
            //insert Hotline_Outgoing_OTP entry
            $sql = "INSERT INTO HotLine_Outgoing SET" .
                    "  sTransNox = " . CommonUtil::toSQL($nextcd) .
                    ", dTransact = " . CommonUtil::toSQL($date->format(CommonUtil::format_date)) .
                    ", sDivision = 'CSS'" .
                    ", sMobileNo = " . CommonUtil::toSQL($mobileno) .
                    ", sMessagex = " . CommonUtil::toSQL($message) .
                    ", cSubscrbr = " . CommonUtil::toSQL(CommonUtil::getMobileNetwork($mobileno)) .
                    ", dDueUntil = " . CommonUtil::toSQL($date->format(CommonUtil::format_date)) .
                    ", cSendStat = '0'" .
                    ", nNoRetryx = '0'" .
                    ", sUDHeader = ''" .
                    ", sReferNox = " . CommonUtil::toSQL($transnox) .
                    ", sSourceCd = " . CommonUtil::toSQL("DCPa") .
                    ", cTranStat = '0'" .
                    ", nPriority = 1" .
                    ", sModified = " . CommonUtil::toSQL($userid) .
                    ", dModified = " . CommonUtil::toSQL($stamp);
            
            if($app->execute($sql) <= 0){ //do not execute to replication
                $json["result"] = "error";
                $json["error"]["code"] = $app->getErrorCode();
                $json["error"]["message"] = "Unable to save HotLine_Outgoing:" . $app->getErrorMessage();
                $app->rollbackTrans();
                echo json_encode($json);
                return;
            }
        }
    }    
}

$app->commitTrans(); //end database transaction

$json["result"] = "success";
echo json_encode($json);
return;
?>