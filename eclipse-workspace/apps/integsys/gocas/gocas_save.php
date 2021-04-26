<?php
/*
 * gocas_save.php
 * kalyptus - 2019.12.06 09:25am
 * use this API in saving the GOCAS created using an ANDROID device(Integsys/Telecom).
 * Note:
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
$pcname 	= $myheader['g-api-imei'];
//SysClient ID
$clientid = $myheader['g-api-client'];
//Log No
$logno 		= $myheader['g-api-log'];
//User ID
$userid		= $myheader['g-api-user'];

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

$userid		= $myheader['g-api-user'];

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

//get the serial of user to be used as serial
$serialx = CommonUtil::SerializeNumber(substr($userid, 4));
$branch = $par4sql["sBranchCd"];
$trandt = $par4sql["dAppliedx"];
$client = $par4sql["sClientNm"];
$lunitx = $par4sql["cUnitAppl"];
$downpx = $par4sql["nDownPaym"];
$creatd = $par4sql["dCreatedx"];
//$creatx = $creatd->format(CommonUtil::format_date);

//check if GOCAS was already saved previously...
$sql = "SELECT sTransNox" .
    " FROM Credit_Online_Application" .
    " WHERE sTransNox LIKE '$serialx%'" .
    " AND sClientNm = '$client'" .
    " AND dCreatedx = '$creatd'";   
    
    //" AND DATE_FORMAT(dCreatedx, '%Y-%m-%d') = '$creatx'";

if(null === $rows = $app->fetch($sql)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getMessage();
    echo json_encode($json);
    return;
}
elseif(!empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::EXISTING_ACCOUNT;
    $json["error"]["message"] = "Record seems to be already save.";
    echo json_encode($json);
    return false;
}

date_default_timezone_set('Asia/Manila');
$date = new DateTime('now');
$stamp = $date->format(CommonUtil::format_timestamp);

$transno = CommonUtil::GetNextCode("Credit_Online_Application", "sTransNox", true, $app->getConnection(), $serialx);
$sql = "INSERT INTO Credit_Online_Application" . 
      " SET sTransNox = '$transno'" .
         ", sBranchCd = '$branch'" . 
         ", dTransact = '$trandt'" . 
         ", dTargetDt = null" . 
         ", sClientNm = '$client'" . 
         ", sGOCASNox = null" . 
         ", sGOCASNoF = null" . 
         ", cUnitAppl = '$lunitx'" . 
         ", sSourceCD = 'APP'" . 
         ", sDetlInfo = '$enc_param'" . 
         ", sCatInfox = null" . 
         ", sDesInfox = null" . 
         ", sQMatchNo = ''" . 
         ", sQMAppCde = null" . 
         ", nCrdtScrx = null" . 
         ", nDownPaym = $downpx" . 
         ", nDownPayF = null" . 
         ", sRemarksx = ''" . 
         ", sCreatedx = '$userid'" . 
         ", dCreatedx = '$creatd'" . 
         ", dReceived = '$stamp'" . 
         ", sVerified = null" . 
         ", dVerified = null" . 
         ", cTranStat = null" . 
         ", cDivision = null" . 
         ", dModified = '$stamp'";

$app->beginTrans();

if($app->execute($sql) <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Unable to save Credit Application! " . $app->getErrorMessage();
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

$app->commitTrans();

$json["result"] = "success";
$json["sTransNox"] = $transno;
echo json_encode($json);
?>

