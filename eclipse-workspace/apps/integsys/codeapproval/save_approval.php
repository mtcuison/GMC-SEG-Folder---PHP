<?php
/*
 * save_approval.php
 * mac - 2020.05.22 08:00pm
 * use this API in saving the CODE APPROVAL created using an ANDROID device(Integsys/Telecom).
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
$dTransact = $par4sql["dTransact"];
$sSystemCD = $par4sql["sSystemCD"];
$sReqstdBy = $par4sql["sReqstdBy"];
$dReqstdxx = $par4sql["dReqstdxx"];
$cIssuedBy = $par4sql["cIssuedBy"];
$sMiscInfo = $par4sql["sMiscInfo"];
$sRemarks1 = $par4sql["sRemarks1"];
$sRemarks2 = $par4sql["sRemarks2"];
$sApprCode = $par4sql["sApprCode"];
$sEntryByx = $par4sql["sEntryByx"];
$sApprvByx = $par4sql["sApprvByx"];
$sReasonxx = $par4sql["sReasonxx"];
$sReqstdTo = $par4sql["sReqstdTo"];
$cTranStat = $par4sql["cTranStat"];

//check if Approval was already saved previously...
$sql = "SELECT sTransNox" .
        " FROM System_Code_Approval" .
        " WHERE dTransact = '$dTransact'" .
            " AND sSystemCD = '$sSystemCD'" .
            " AND sReqstdBy = '$sReqstdBy'" .
            " AND dReqstdxx = '$dReqstdxx'" .
            " AND cIssuedBy = '$cIssuedBy'" . 
            " AND sMiscInfo = '$sMiscInfo'" . 
            " AND sApprCode = '$sApprCode'";

if(null === $rows = $app->fetch($sql)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getMessage();
    echo json_encode($json);
    return;
} elseif(!empty($rows)){
    $json["result"] = "success";
    $json["sTransNox"] = $rows[0]["sTransNox"];
    echo json_encode($json);
    return;
}

date_default_timezone_set('Asia/Manila');
$date = new DateTime('now');
$stamp = $date->format(CommonUtil::format_timestamp);

$transno = CommonUtil::GetNextCode("System_Code_Approval", "sTransNox", true, $app->getConnection(), "MX01"); //substr($clientid, 5)

$sql = "INSERT INTO System_Code_Approval" . 
        " SET sTransNox = '$transno'" .
            ", dTransact = '$dTransact'" . 
            ", sSystemCD = '$sSystemCD'" . 
            ", sReqstdBy = '$sReqstdBy'" . 
            ", dReqstdxx = '$dReqstdxx'" . 
            ", cIssuedBy = '$cIssuedBy'" . 
            ", sMiscInfo = '$sMiscInfo'" . 
            ", sRemarks1 = '$sRemarks1'" . 
            ", sRemarks2 = '$sRemarks2'" . 
            ", sApprCode = '$sApprCode'" . 
            ", sEntryByx = '$sEntryByx'" . 
            ", sApprvByx = '$sApprvByx'" . 
            ", sReasonxx = '$sReasonxx'" .
            ", sReqstdTo = '$sReqstdTo'" . 
            ", cSendxxxx = '1'" .
            ", cTranStat = '$cTranStat'" . 
            ", sModified = '$sEntryByx'" . 
            ", dModified = '$stamp'";

$app->beginTrans();

if($app->execute($sql) <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Unable to save System Approval! " . $app->getErrorMessage();
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

$app->commitTrans();

$json["result"] = "success";
$json["sTransNox"] = $transno;
echo json_encode($json);
?>

