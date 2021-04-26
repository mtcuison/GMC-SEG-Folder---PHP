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
if(is_null($parjson)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Invalid parameters detected";
    echo json_encode($json);
    return false;
}

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

//GET HEADERS HERE
//Product ID
$prodctid = $myheader['g-api-id'];
//Computer Name / IEMI No
$pcname 	= $myheader['g-api-imei'];
//SysClient ID
$clientid = $myheader['g-api-client'];
//Google FCM token
$token = $myheader['g-api-token'];

//Mobile No
#if(in_array("g-api-mobile", $myheader)){
if(isset($myheader['g-api-mobile'])){
    $mobile = $myheader['g-api-mobile'];
}
else{
    $mobile = "UNKNOWN";
}

//TODO: perform the validation of mobile no here...

//Device Model
#if(in_array("g-api-model", $myheader)){
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

//load the driver
if(!$app->LoadEnv($prodctid)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

//kalyptus - 2019.06.29 11:00am
//updated to use the adjusted parameter for Nautilus->login
if(!$app->Login($username,$password,$prodctid, $pcname, $token, $clientid, $mobile, $model)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    
    echo json_encode($json);
    return;
}

if(in_array("g-api-mobile", $myheader)){
    $userid = $app->Env("sUserIDxx");
    
    if(CommonUtil::isValidMobile($mobile)){
        $sql = "SELECT *" . 
              " FROM App_User_Device" .
              " WHERE sUserIDxx = '$userid'" . 
                " AND sProdctID = '$prodctid'" . 
                " AND sIMEINoxx = '$pcname'";
        
        $device_info = $app->fetch($sql);
        if(!empty($device_info)){
            if(strcasecmp($device_info[0]["sMobileNo"], $mobile) != 0){
                $sql = "UPDATE App_User_Device" .
                      " SET sMobileNo = '$mobile'" .
                      " WHERE sUserIDxx = '$userid'" .
                      " AND sProdctID = '$prodctid'" .
                      " AND sIMEINoxx = '$pcname'";
                $app->execute($sql, "App_User_Device", "", "");
            }
            if(in_array("g-api-model", $myheader)){
                if(strcasecmp($device_info[0]["sModelCde"], $myheader["g-api-model"]) != 0){
                    $sql = "UPDATE App_User_Device" .
                        " SET sModelCde = '$model'" .
                        " WHERE sUserIDxx = '$userid'" .
                        " AND sProdctID = '$prodctid'" .
                        " AND sIMEINoxx = '$pcname'";
                    $app->execute($sql, "App_User_Device", "", "");
                }
            }
        }
        
        //TODO: perform the validation/possible notification here...
    }
}

//echo "hi there";
$json["result"] = "success";
//$json["sClientID"] = $clientid == "" ? $app->Env("sClientID") : $clientid;

if($clientid == ""){
    if($app->Env("sClientID") == "GGC_BM001" && $prodctid == "Telecom"){
        $json["sClientID"] = "GTC_BC001";
    }
    else{
        $json["sClientID"] = $app->Env("sClientID");
    }
}
else{
    $json["sClientID"] = $clientid;
}

$json["sBranchCD"] = $app->Env("sBranchCD");
$json["sBranchNm"] = $app->Env("sBranchNm");
$json["sLogNoxxx"] = $app->Env("sLogNoxxx");
$json["sUserIDxx"] = $app->Env("sUserIDxx");
$json["sEmailAdd"] = $app->Env("sEmailAdd");
$json["sUserName"] = $app->Env("sUserName");
$json["nUserLevl"] = $app->Env("nUserLevl");
$json["sDeptIDxx"] = $app->Env("sDeptIDxx");
$json["sPositnID"] = $app->Env("sPositnID");
$json["sEmpLevID"] = $app->Env("sEmpLevID");
$json["sEmployID"] = $app->Env("sEmployNo");
$json["cAllowUpd"] = "1";
echo json_encode($json, JSON_PARTIAL_OUTPUT_ON_ERROR);
?>
