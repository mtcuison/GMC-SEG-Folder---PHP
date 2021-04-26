<?php
/* G-Card Application OTP Request Object
 *
 * /gcard/ms/request_otp_validation.php
 *
 * mac 2020.06.16
 *  started creating this object.
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

define("SOURCE_CODE", "JO");

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

if(!isset($parjson['cardnmbr'])){
    $json["result"] = "error";
    $json["error"]["code"] = "102";
    $json["error"]["message"] = "Unset G-Card NUMBER detected.";
    echo json_encode($json);
    return;
}

$cardnmbr = $parjson['cardnmbr'];

if(!isset($parjson['sourceno'])){
    $json["result"] = "error";
    $json["error"]["code"] = "102";
    $json["error"]["message"] = "Unset SOURCE NUMBER detected.";
    echo json_encode($json);
    return;
}

$sourceno = $parjson['sourceno'];

//get the branch code of the requesting client
$branchcd = substr($clientid, 5);
if (strlen($branchcd) != 4){
    $json["result"] = "error";
    $json["error"]["code"] = "109";
    $json["error"]["message"] = "Unset OTP detected.";
    echo json_encode($json);
    return;
    
}

//is the card exists?
$sql = "SELECT" .
    "  a.sGCardNox" .
    ", a.sCardNmbr" .
    ", a.cDigitalx" .
    ", a.cCardStat" .
    ", IFNULL(b.sMobileNo, '') sMobileNo" .
    " FROM G_Card_Master a" .
    " LEFT JOIN Client_Master b ON a.sClientID = b.sClientID" .
    " WHERE a.sCardNmbr = " . CommonUtil::toSQL($cardnmbr) ;

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Error found during loading..." . $app->getErrorMessage();
    echo json_encode($json);
    return;
} elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = "103";
    $json["error"]["message"] = "G-Card is not found on record.";
    echo json_encode($json);
    return;
} else {
    if ($rows[0]["cCardStat"] != "4"){
        $json["result"] = "error";
        $json["error"]["code"] = "104";
        $json["error"]["message"] = "G-Card is not yet activated.";
        echo json_encode($json);
        return;
    }
    
    if ($rows[0]["cDigitalx"] != "2"){
        $json["result"] = "error";
        $json["error"]["code"] = "105";
        $json["error"]["message"] = "G-Card is registered as DIGITAL/SMARTCARD. Unable to use SMS OTP feature.";
        echo json_encode($json);
        return;
    }
}

$mobileno = $rows[0]["sMobileNo"];

if ($mobileno == "") {
    $json["result"] = "error";
    $json["error"]["code"] = "106";
    $json["error"]["message"] = "Mobile number of the client was NOT SET.";
    echo json_encode($json);
    return;
}

$gcardnox = $rows[0]["sGCardNox"];

date_default_timezone_set('Asia/Manila');
$date = new DateTime('now');
$year = $date->format("y");

//check if existing request exists
$sql = "SELECT sTransNox, sOTPasswd FROM G_Card_OTP_History" .
    " WHERE  dTransact = " . CommonUtil::toSQL($date->format(CommonUtil::format_date)) .
    " AND sGCardNox = " . CommonUtil::toSQL($gcardnox) .
    " AND sSourceCd = " . CommonUtil::toSQL(SOURCE_CODE) .
    " AND sSourceNo = " . CommonUtil::toSQL($sourceno);

$rows = $app->fetch($sql);

$otpasswd = "";
$transno = "";

//begin database transaction
$app->beginTrans();

if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Error found during loading..." . $app->getErrorMessage();
    echo json_encode($json);
    return;
} elseif(empty($rows)){
    //insert a new record
    $otpasswd = CommonUtil::GenerateOTP(6);
    
    $transno = CommonUtil::GetNextCode("G_Card_OTP_History", "sTransNox", $year, $app->getConnection(), $branchcd);
    
    $sql = "INSERT INTO G_Card_OTP_History SET" .
        "  sTransNox = " . CommonUtil::toSQL($transno) .
        ", dTransact = " . CommonUtil::toSQL($date->format(CommonUtil::format_date)) .
        ", sGCardNox = " . CommonUtil::toSQL($gcardnox) .
        ", sSourceNo = " . CommonUtil::toSQL($sourceno) .
        ", sSourceCd = " . CommonUtil::toSQL(SOURCE_CODE) .
        ", nTranAmtx = 0.00" .
        ", nPointsxx = 0.00" .
        ", sOTPasswd = " . CommonUtil::toSQL($otpasswd) .
        ", nRequestd = 1" .
        ", nSMSSentx = 0" .
        ", cRecdStat = '0'" .
        ", sModified = " . CommonUtil::toSQL($userid) .
        ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp));
} else {
    $transno = $rows[0]["sTransNox"];
    $otpasswd = $rows[0]["sOTPasswd"];
    
    //cancel them on Hotline_Outgoing_OTP
    $sql = "UPDATE Hotline_Outgoing_OTP SET" .
        "  cSendStat = '3'" .
        ", sModified = " . CommonUtil::toSQL($userid) .
        ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp)) .
        " WHERE sSourceCd = " . CommonUtil::toSQL(SOURCE_CODE) .
        " AND sReferNox = " . CommonUtil::toSQL($transno) .
        " AND cSendStat = '0'";
    
    $app->execute($sql, "Hotline_Outgoing_OTP");
    
    //update the existing record, increase the number requested field by 1
    $sql = "UPDATE G_Card_OTP_History SET" .
        "  nRequestd = nRequestd + 1" .
        ", sModified = " . CommonUtil::toSQL($userid) .
        ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp)) .
        " WHERE sTransNox = " . CommonUtil::toSQL($transno);
}

if($app->execute($sql, "G_Card_OTP_History") <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = "106";
    $json["error"]["message"] = "Unable to save G-Card OTP History! ";
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

$message = "Your G-Card OTP for your JOB ORDER transaction is " . $otpasswd . ". " .
    "Kindly provide this PIN to our branch associate to post your transaction. Thank you. \n\n Guanzon Group";

//insert Hotline_Outgoing_OTP entry
$nextcd = CommonUtil::GetNextCode("Hotline_Outgoing_OTP", "sTransNox", $year, $app->getConnection(), "MX01");

$sql = "INSERT INTO Hotline_Outgoing_OTP SET" .
    "  sTransNox = " . CommonUtil::toSQL($nextcd) .
    ", dTransact = " . CommonUtil::toSQL($date->format(CommonUtil::format_date)) .
    ", sDivision = 'MP'" . //marketing and promotions
    ", sMobileNo = " . CommonUtil::toSQL($mobileno) .
    ", sMessagex = " . CommonUtil::toSQL($message) .
    ", cSubscrbr = " . CommonUtil::toSQL(CommonUtil::getMobileNetwork($mobileno)) .
    ", dDueUntil = " . CommonUtil::toSQL($date->format(CommonUtil::format_date)) .
    ", cSendStat = '2'" .
    ", nNoRetryx = '0'" .
    ", sUDHeader = ''" .
    ", sReferNox = " . CommonUtil::toSQL($transno) .
    ", sSourceCd = " . CommonUtil::toSQL(SOURCE_CODE) .
    ", cTranStat = '0'" .
    ", nPriority = 1" .
    ", sModified = " . CommonUtil::toSQL($userid) .
    ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp));

if($app->execute($sql, "Hotline_Outgoing_OTP") <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = "107";
    $json["error"]["message"] = "Unable to save Hotline Outgoing Message! ";
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

//end database transaction
$app->commitTrans();
/*
$token = getAccessToken();

if($token == null){
    $json["result"] = "error";
    $json["error"]["code"] = "0001";
    $json["error"]["message"] = "Error updating token.";
    echo json_encode($json);
    return false;
}

$header = array();
$header[] = "Accept: application/json";
$header[] = "Content-Type: application/json";
$header[] = "Authorization: Bearer ".$token; // Prepare the authorisation token

$xparam["message"]["text"] = $message;
$xparam["endpoints"] = array($mobileno);

$url = "https://messagingsuite.smart.com.ph/rest/messages/sms";

$result = WebClient::httpsPostJson($url, json_encode($xparam), $header);

if($result == ""){
    $sql = "UPDATE Hotline_Outgoing_OTP" .
        " SET cTranStat = '1'" .
        " WHERE sTransNox = '$nextcd'";
    if($app->execute($sql, "Hotline_Outgoing_OTP") <= 0){
        $json["result"] = "error";
        $json["error"]["code"] = "111";
        $json["error"]["message"] = "Unable to update Hotline Outgoing Message! ";
        echo json_encode($json);
        return;
    }
    
    $json["result"] = "success";
    $json["otpasswd"] = $otpasswd;
    $json["message"] = "OTP Requested Succesfully.";
}
else{
    $token = json_decode($result, true);
    $json["result"] = "error";
    $json["error"]["code"] = $token["code"];
    $json["error"]["message"] = $token["title"] . "/" . $token["type"] . "/" . $token["detail"];
}
*/

$jsonSMS = sendSMS("GUANZON", $message, $mobileno);

if ($jsonSMS["result"] == "success"){
    $sql = "UPDATE Hotline_Outgoing_OTP" .
        " SET cTranStat = '1'" .
        " WHERE sTransNox = '$nextcd'";
    if($app->execute($sql, "Hotline_Outgoing_OTP") <= 0){
        $json["result"] = "error";
        $json["error"]["code"] = "111";
        $json["error"]["message"] = "Unable to update Hotline Outgoing Message! ";
        echo json_encode($json);
        return;
    }
    
    $json["result"] = "success";
    $json["otpasswd"] = $otpasswd;
    $json["message"] = "OTP Requested Succesfully.";
    
    echo json_encode($json);
} else {
    echo json_encode($jsonSMS);
}

return;
function getAccessToken(){
    $token = json_decode(file_get_contents(APPPATH . "/GGC_Java_Systems/config/smartsuite.token"), true) ;
    $token = json_decode($token, true);
    
    //decode the refreshToken
    $str_token = base64_decode($token["refreshToken"]);
    //clean the token so that we can convert it to json
    $tokens = explode("}.{", str_replace("}{", "}}.{{", $str_token));
    //convert the token to json
    $refresh_token = json_decode(substr($tokens[1], 0, strpos($tokens[1], "}") + 1), true);
    
    $expiry = $refresh_token["iat"] + $token["expiresIn"] - 120;
    
    //echo "expiry:" . $expiry;
    //echo ">> date: " . date('Y-m-d\TH:i:s', $expiry);
    //echo ">>time :" . time();
    
    if($expiry <= time()){
        $header = array();
        $header[] = "Accept: application/json";
        $header[] = "Content-Type: application/json";
        
        $url = "https://messagingsuite.smart.com.ph/rest/auth/login";
        
        $xparam["username"] = "masayson@guanzongroup.com.ph";
        $xparam["password"] = "Gu9nz0nx";
        
        $result = WebClient::httpsPostJson($url, json_encode($xparam), $header);
        
        $token = json_decode($result, true);
        if($token != null){
            $fp = fopen(APPPATH . "/GGC_Java_Systems/config/smartsuite.token", 'w');
            fwrite($fp, json_encode($result));
            fclose($fp);
        }
        
        //reload saved token
        $token = json_decode(file_get_contents(APPPATH . "/GGC_Java_Systems/config/smartsuite.token"), true) ;
        $token = json_decode($token, true);
        
        //decode the refreshToken
        $str_token = base64_decode($token["refreshToken"]);
        //clean the token so that we can convert it to json
        $tokens = explode("}.{", str_replace("}{", "}}.{{", $str_token));
        //convert the token to json
        $refresh_token = json_decode(substr($tokens[1], 0, strpos($tokens[1], "}") + 1), true);
    }
    
    $expiry = $refresh_token["iat"] + $token["expiresIn"] - 120;
    
    if($expiry <= time()){
        return null;
    }
    
    //return the accessToken
    return $token["accessToken"];
}

function sendSMS($maskname, $message, $mobileno){
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
    } else {
        $json["result"] = "error";
        $json["error"]["code"] = substr($result, 0, 5);
        $json["error"]["message"] = substr($result, 14);
        
        return $json;
    }
}
?>