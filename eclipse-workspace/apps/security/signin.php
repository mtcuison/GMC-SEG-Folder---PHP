<?php
require_once 'config.php';
require_once APPPATH.'/core/Nautilus.php';
require_once APPPATH.'/core/WSHeaderValidatorFactory.php';

//fetch pass headers
$myheader = apache_request_headers();

if(!isset($myheader['g-api-id'])){
    echo "anggapoy nagawaan ti labat awa!";
    return;
}

if(stripos(APPSYSX, $myheader["g-api-id"]) === false){
    echo "anto la ya... sika lamet!";
    return;
}

//verify headers
//isHeaderOkApp is use for verifying headers of Apps for Guanzon Clients
$factory = new WSHeaderValidatorFactory();
$validator = $factory->make($myheader['g-api-id']);

$json = array();
if(!$validator->isHeaderOk($myheader)){
    $json["result"] = "error";
    $json["error"]["code"] = $validator->getErrorCode();
    $json["error"]["message"] = $validator->getMessage();
    echo json_encode($json);
    return;
}

//echo "how";
$data = file_get_contents('php://input');
//echo $data;
$parjson = json_decode($data, true);
//echo 'and2 na';
if(is_null($parjson)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Invalid parameters detected";
    echo json_encode($json);
    return false;
}
//echo 'bakit';
if(!isset($parjson['user'])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Unset User AUTH ACCOUNT detected.";
    echo json_encode($json);
    return false;
}

if(!isset($parjson['pswd'])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Unset User AUTH PASSWORD detected.";
    echo json_encode($json);
    return false;
}
//echo 'and2 na';
//GET HEADERS HERE
//Product ID
$prodctid = $myheader['g-api-id'];
//Computer Name / IEMI No
$pcname 	= $myheader['g-api-imei'];
//Google FCM token
$token = $myheader['g-api-token'];

//Mobile No

//if(in_array("g-api-mobile", $myheader)){
if(isset($myheader['g-api-mobile'])){
    $mobile = $myheader['g-api-mobile'];
}
else{
    $mobile = "UNKNOWN";
}

//TODO: perform the validation of mobile no here...

//Device Model
//if(in_array("g-api-model", $myheader)){
if(isset($myheader['g-api-model'])){
    $model = $myheader['g-api-model'];
}
else{
    $model = "UNKNOWN";
}

//GET PARAMETERS HERE
$username = $parjson['user'];
$password = $parjson['pswd'];

//initialize driver to use
$app = new Nautilus(APPPATH);
//echo 'and2 ba1';


//load the driver
if(!$app->LoadEnv($prodctid)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}
//echo 'and2 ba2';

//kalyptus - 2019.06.29 11:00am
//updated to use the adjusted parameter for Nautilus->login
if(!$app->Login($username,$password,$prodctid, $pcname, $token, "", $mobile, $model)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    
    //mac 2019.10.10
    //  generate OTP on unactivated account.
    if ($prodctid == "GuanzonApp"){
        if ($app->getErrorCode() == AppErrorCode::UNACTIVATED_ACCOUNT){
            $otp = generateOTP($app, $username, $prodctid, $password);
            
            if ($otp == "XPASSX"){
                $json["error"]["code"] = AppErrorCode::INVALID_PASSWORD;
                $json["error"]["message"] = "Invalid password detected.";
            } else if ($otp == "FAILED"){
                $json["error"]["code"] = $otp;
                $json["error"]["message"] = "Unable to update record.";
            } else if ($otp == "NORECD"){
                $json["error"]["code"] = $otp;
                $json["error"]["message"] = "No record found.";
            } else {
                $json["otp"] = $otp;
                $json["mobile"] = $mobile;
                $json["verify"] = "https://restgk.guanzongroup.com.ph/security/account_verify.php?email=$username&hash=$password";
            }
        }
    }
    
    echo json_encode($json);
    return;
}

$json["result"] = "success";
$json["sUserIDxx"] = $app->Env("sUserIDxx");
$json["sEmailAdd"] = $app->Env("sEmailAdd");
$json["sUserName"] = $app->Env("sUserName");
$json["sMobileNo"] = $app->Env("sMobileNo");
$json["dCreatedx"] = $app->Env("dCreatedx");
$json["header"] = $myheader;
echo json_encode($json, JSON_PARTIAL_OUTPUT_ON_ERROR);
return;

//mac 2019.10.10
//generate otp for sms verification
function generateOTP($app, $email, $product, &$password){
    $sql = "SELECT e.sProdctNm, a.*" .
        " FROM App_User_Master a" .
        " LEFT JOIN xxxSysObject e ON a.sProdctID = e.sProdctID" .
        " WHERE a.sEmailAdd = '$email'" .
        " AND (a.sProdctID = '$product' || (a.sProdctID != '$product' && a.cGloblAct = '1'))" .
        " ORDER BY a.cGloblAct DESC";
    $rows = $app->fetch($sql);
    
    //do we have row result
    if($rows == null) return "NORECD";
    
    $xpassword = CommonUtil::app_decrypt($rows[0]["sPassword"], $rows[0]["sItIsASIN"]);
    
    //is the given password correct
    if($password !== $xpassword) return "XPASSX";
    
    $password = $rows[0]["sItIsASIN"];
    
    //generate otp
    $otp = CommonUtil::GenerateOTP(6);
    if ($otp == "") return "";
    
    //update user info for the otp
    $sql = "UPDATE App_User_Master SET" .
        " sOTPasswd = '$otp'" .
        " WHERE sUserIDxx = " . CommonUtil::toSQL($rows[0]["sUserIDxx"]);
    
    $result = $app->execute($sql, "App_User_Master", "GAP0");
    if($result <= 0) return "FAILED";
    
    //create textblast
    $date = new DateTime('now');
    $year = $date->format("Y");
    $stamp = $date->format(CommonUtil::format_timestamp);
    $datex = $date->format(CommonUtil::format_date);
    $mobile = CommonUtil::fixMobile($rows[0]["sMobileNo"]);
    $network = CommonUtil::getMobileNetwork($mobile);
    
    $lsMessage = "GUANZON" . "\r" .
                    "Good day! Your verification code for Guanzon App is $otp. Please input this code to your app to continue signing-in. Thank you.";
    
    $sql = "INSERT INTO HotLine_Outgoing SET" .
        "  sTransNox = " . CommonUtil::toSQL(CommonUtil::GetNextCode("HotLine_Outgoing", "sTransNox", $year, $app->getConnection(), "GAP0")) .
        ", dTransact = '$datex'" .
        ", sDivision = 'TLM'" .
        ", sMobileNo = '$mobile'" .
        ", sMessagex = '$lsMessage'" .
        ", cSubscrbr = '$network'" .
        ", dDueUntil = '$datex'" .
        ", cSendStat = '0'" .
        ", nNoRetryx = 0" .
        ", sUDHeader = ''" .
        ", sReferNox = " . CommonUtil::toSQL($rows[0]["sUserIDxx"]) .
        ", sSourceCd = 'APP0'" .
        ", cTranStat = '0'" .
        ", nPriority = 0" .
        ", sModified = 'M001111122'" . //mac user id
        ", dModified = '$stamp'";
    
    $result = $app->execute($sql, "HotLine_Outgoing", "GAP0");
    if($result <= 0) return "FAILED";
    
    return $otp;
}
?>
