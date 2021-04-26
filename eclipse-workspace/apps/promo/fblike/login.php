<?php
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


$mobile = $myheader['g-api-mobile'];
$otp =  $myheader['g-api-token'];

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

//create hotline outgoing
createOutgoing($logno, $otp, $mobile, $app);

$json["result"] = "success";
echo json_encode($json);
return;

function createOutgoing($branchcd, $otp, $mobileno, $app){
    date_default_timezone_set('Asia/Manila');
    $date = new DateTime('now');
    $year = $date->format("y");
    
    $message = "Your facebook promo encoder login OTP for log date " . $date->format(CommonUtil::format_timestamp) . " is " . $otp . ".";
    
    $tranx = CommonUtil::GetNextCode("HotLine_Outgoing", "sTransNox", $year, $app->getConnection(), "MX01");
        
    $sql = "INSERT INTO HotLine_Outgoing SET" .
        "  sTransNox = " . CommonUtil::toSQL($tranx) .
        ", dTransact = " . CommonUtil::toSQL($date->format(CommonUtil::format_date)) .
        ", sDivision = 'MP'" .
        ", sMobileNo = " . CommonUtil::toSQL($mobileno) .
        ", sMessagex = " . CommonUtil::toSQL($message) .
        ", cSubscrbr = " . CommonUtil::toSQL(CommonUtil::getMobileNetwork($mobileno)) .
        ", dDueUntil = " . CommonUtil::toSQL($date->format(CommonUtil::format_date)) .
        ", cSendStat = '2'" .
        ", nNoRetryx = '0'" .
        ", sUDHeader = ''" .
        ", sReferNox = " . CommonUtil::toSQL($branchcd) .
        ", sSourceCd = " . CommonUtil::toSQL("APPX") .
        ", cTranStat = '0'" .
        ", nPriority = 0" .
        ", sModified = " . CommonUtil::toSQL("fblogin") .
        ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp));
    if($app->execute($sql) <= 0){
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = $app->getErrorMessage();
        echo json_encode($json);
        return false;
    }
        
    $textsms = sendAsOthers("GUANZON", $message, $mobileno);
        
    if($textsms["result"] == "success"){
        $sql = "UPDATE HotLine_Outgoing SET cTranStat = '1' WHERE sTransNox = " . CommonUtil::toSQL($tranx);
        
        if($app->execute($sql) <= 0){
            $json["result"] = "error";
            $json["error"]["code"] = $app->getErrorCode();
            $json["error"]["message"] = $app->getErrorMessage() . $sql;
            echo json_encode($json);
            return false;
        }
    } else{
        echo json_encode($textsms);
        return false;
    }
    
    return true;
}

function sendAsOthers($maskname, $message, $mobileno){
    $header = array();
    $header[] = "Accept: application/json";
    $header[] = "Content-Type: application/json";
    
    $xparam = array();
    $xparam["username"] = MASK_USER1;
    $xparam["password"] = MASK_PASS1;
    $xparam["text"] = $message;
    $xparam["destination"] = $mobileno;
    $xparam["source"] = $maskname;
    
    $url = "https://messagingsuite.smart.com.ph/cgphttp/servlet/sendmsg";
    
    $result = WebClient::httpRequest("GET", $url, $xparam, $header);
    $result = str_replace("\r\n", "", $result);
    
    if (strpos($result, 'Message-ID:') !== false) {
        $json["result"] = "success";
        $json["message"] = "Message sent successfully.";
        $json["maskname"] = $maskname;
        $json["id"] = substr($result, 20);
        return $json;
        return;
    } else {
        $json["result"] = "error";
        $json["error"]["code"] = substr($result, 0, 5);
        $json["error"]["message"] = substr($result, 14);
        return $json;
    }
}
?>