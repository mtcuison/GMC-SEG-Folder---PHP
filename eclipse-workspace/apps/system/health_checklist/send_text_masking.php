<?php
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'WebClient.php';
require_once 'WSHeaderValidatorFactory.php';
require_once 'MySQLAES.php';

$transno = "";
if(isset($_GET['transno'])){
    $transno = $_GET['transno'];
} else {
    $json["result"] = "error";
    $json["error"]["message"] = "Invalid parameter.";
    echo json_encode($json);
    return;
}

//account credentials
$prodctid = RAFFLE_PRODUCT;
$userid = RAFFLE_USER;

//initialize application driver
$app = new Nautilus(APPPATH);
if(!$app->LoadEnv($prodctid)) {
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

if(!$app->loaduser($prodctid, $userid)){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

//validate transaction if exists
$sql =  "SELECT sTransNox, sOTPNoxxx, sMobileNo" .
        " FROM Health_Checklist" .
        " WHERE sTransNox = " . CommonUtil::toSQL($transno) .
            " AND cRecdStat = '0'";

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage() . "(" . $app->getErrorCode() . ")";
    echo json_encode($json);
    return;
} elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["message"] = "No transaction found.";
    echo json_encode($json);
    return;
}

date_default_timezone_set('Asia/Manila');
$date = new DateTime('now');
$year = $date->format("y");

$mobileno = $rows[0]["sMobileNo"];

//todo: message for otp issuance
$message = "Your health checklist OTP is " . $rows[0]["sOTPNoxxx"] . ". Thank you for patronizing us.\n\n" .
            "You may also visit our website at https://www.guanzongroup.com.ph/";

$nextcd = CommonUtil::GetNextCode("Hotline_Outgoing_OTP", "sTransNox", $year, $app->getConnection(), "MX01");



//insert Hotline_Outgoing_OTP entry
$sql = "INSERT INTO Hotline_Outgoing_OTP SET" .
    "  sTransNox = " . CommonUtil::toSQL($nextcd) .
    ", dTransact = " . CommonUtil::toSQL($date->format(CommonUtil::format_date)) .
    ", sDivision = 'HCM'" . //human capital management
    ", sMobileNo = " . CommonUtil::toSQL($mobileno) .
    ", sMessagex = " . CommonUtil::toSQL($message) .
    ", cSubscrbr = " . CommonUtil::toSQL(CommonUtil::getMobileNetwork($mobileno)) .
    ", dDueUntil = " . CommonUtil::toSQL($date->format(CommonUtil::format_date)) .
    ", cSendStat = '2'" .
    ", nNoRetryx = '0'" .
    ", sUDHeader = ''" .
    ", sReferNox = " . CommonUtil::toSQL($transno) .
    ", sSourceCd = " . CommonUtil::toSQL("HLTH") .
    ", cTranStat = '0'" .
    ", nPriority = 1" .
    ", sModified = " . CommonUtil::toSQL($userid) .
    ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp));

//begin database transaction
$app->beginTrans();

if($app->execute($sql, "Hotline_Outgoing_OTP") <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = "111";
    $json["error"]["message"] = "Unable to save Hotline Outgoing Message! ";
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

//end database transaction
$app->commitTrans();

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
    $sql = "UPDATE Hotline_Outgoing_OTP SET cTranStat = '1' WHERE sTransNox = " . CommonUtil::toSQL($nextcd);
    
    //begin database transaction
    $app->beginTrans();

    if($app->execute($sql, "Hotline_Outgoing_OTP") <= 0){
        $json["result"] = "error";
        $json["error"]["code"] = "111";
        $json["error"]["message"] = "Unable to update Hotline Outgoing Message! ";
        $app->rollbackTrans();
        echo json_encode($json);
        return;
    }
    
    //end database transaction
    $app->commitTrans();
    
    $json["result"] = "success";
    $json["message"] = "Message sent successfully.";
}
else{
    $token = json_decode($result, true);
    $json["result"] = "error";
    $json["error"]["code"] = $token["code"];
    $json["error"]["message"] = ["title"] . "/" . $token["type"] . "/" . $token["detail"];
}

echo json_encode($json);
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
?>