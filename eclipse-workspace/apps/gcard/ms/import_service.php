<?php
/*
 * import_service.php
 * kalyptus - 2019.06.22 10:24am
 * use this API in requesting next service date of MC associated with the GCard(GuanzonApp).
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
if(!$app->validLog($prodctid, $userid, $pcname)){
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

$data = file_get_contents('php://input');
//parse into json the PARAMETERS
$parjson = json_decode($data, true);
$aes = new MySQLAES(APPKEYX);
$cardnmbr = $aes->decrypt($parjson["secureno"]);

$sql = "SELECT sGCardNox, cCardType, cCardStat, cDigitalx" .
    " FROM G_Card_Master" .
    " WHERE sCardNmbr = '$cardnmbr'";
$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Failed loading GCard information!";
    echo json_encode($json);
    return;
}
if(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "GCard Account is not available!";
    echo json_encode($json);
    return;
}

if($rows[0]["cCardStat"] != "4"){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
    $json["error"]["message"] = "GCard account is not active...";
    echo json_encode($json);
    return null;
}

$cardno = $rows[0]["sGCardNox"];
//kalyptus - 2019.06.29 02:06pm
//Added dPurchase, nYellowxx, and nWhitexxx
$sql = "SELECT a.sSerialID, a.sEngineNo, a.sFrameNox, c.sModelNme, b.dTransact dPurchase, (b.nYellowxx - b.nYlwCtrxx) nYellowxx, (b.nWhitexxx - b.nWhtCtrxx) nWhitexxx,  b.cRecdStat cFSEPStat" .
    " FROM MC_Serial a" .
    " LEFT JOIN MC_Serial_Service b ON a.sSerialID = b.sSerialID" .
    " LEFT JOIN MC_Model c ON a.sModelIDx = c.sModelIDx" .
    " WHERE sGCardNox = '$cardno'";
$rows = $app->fetch($sql);

$rows_found = sizeof($rows);
$detail = array();
for($ctr=0;$ctr<$rows_found;$ctr++){
    $serial = $rows[$ctr]["sSerialID"];
    
    $sql = "SELECT dPurchase, dLastSrvc, nMilagexx, dNxtRmndS" .
          " FROM Hotline_Reminder_Source" . 
          " WHERE sSerialID = '$serial'" ;
    $rows_reg = $app->fetch($sql);
    
    $detail[$ctr]["sSerialID"] = $serial;
    $detail[$ctr]["sEngineNo"] = $rows[$ctr]["sEngineNo"];
    $detail[$ctr]["sFrameNox"] = $rows[$ctr]["sFrameNox"];
    $detail[$ctr]["sModelNme"] = $rows[$ctr]["sModelNme"];
    $detail[$ctr]["cFSEPStat"] = $rows[$ctr]["cFSEPStat"];
    $detail[$ctr]["dPurchase"] = $rows[$ctr]["dPurchase"];
    $detail[$ctr]["nYellowxx"] = $rows[$ctr]["nYellowxx"];
    $detail[$ctr]["nWhitexxx"] = $rows[$ctr]["nWhitexxx"];
    
    if($rows_reg == null){
        $detail[$ctr]["dLastSrvc"] = null;
        $detail[$ctr]["nMilagexx"] = 0;
        $detail[$ctr]["dNxtRmndS"] = null;
    }
    else{
        $detail[$ctr]["dLastSrvc"] = $rows_reg[$ctr]["dLastSrvc"];
        $detail[$ctr]["nMilagexx"] = $rows_reg[$ctr]["nMilagexx"];
        $detail[$ctr]["dNxtRmndS"] = $rows_reg[$ctr]["dNxtRmndS"];
    }
}

$json["result"] = "success";
$json["detail"] = $detail;
echo json_encode($json);
return;

?>