<?php
/*
 * import_trans_redemption.php
 * kalyptus - 2019.06.22 10:24am
 * use this API in requesting list of redemption transaction(GuanzonApp).
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
    $json["error"]["message"] = "Invalid mobile number detected";
    echo json_encode($json);
    return;
}

$userid		= $myheader['g-api-user'];

$app = new Nautilus(APPPATH);
if(!$app->LoadEnv($prodctid)){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

if($logno != ""){
    if(!$app->validLog($logno, $prodctid, $clientid, $userid, $pcname)){
        $json["result"] = "error";
        $json["error"]["message"] = $app->getErrorMessage();
        echo json_encode($json);
        return;
    }
}

//Google FCM token
$token = $myheader['g-api-token'];
if(!$app->loaduserClient($prodctid, $userid, $pcname, $token, $mobile)){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

$data = file_get_contents('php://input');
//parse into json the PARAMETERS
$parjson = json_decode($data, true);
$aes = new MySQLAES(APPKEYX);
$cardnmbr = $aes->decrypt($parjson["secureno"]);

//load information of the ordered item...
$sql = "SELECT" .
             "  a.sGCardNox" .
             ", a.dTransact" .
             ", a.sTransNox sReferNox" .
             ", b.sPromDesc sTranType" .
             ", b.sPromCode sSourceNo" .
             ", a.nPointsxx" .
    " FROM G_Card_Redemption a" .
    " LEFT JOIN G_Card_Promo_Master b ON a.sPromoIDx = b.sTransNox"  .
    " WHERE sCardNmbr = '$cardnmbr'";

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["message"] = "Failed loading redemption transaction!";
    echo json_encode($json);
    return;
}
if(empty($rows)){
    $json["result"] = "error";
    $json["error"]["message"] = "There are no redemption transaction detected!";
    echo json_encode($json);
    return;
}

$rows_found = size($rows);
$detail = array();
for($ctr=0;$ctr<$rows_found;$ctr++){
    $detail[$ctr]["sGCardNox"] = $rows[$ctr]["sGCardNox"];
    $detail[$ctr]["dTransact"] = $rows[$ctr]["dTransact"];
    $detail[$ctr]["sReferNox"] = $rows[$ctr]["sReferNox"];
    $detail[$ctr]["sTranType"] = $rows[$ctr]["sTranType"];
    $detail[$ctr]["sSourceNo"] = $rows[$ctr]["sSourceNo"];
    $detail[$ctr]["nPointsxx"] = $rows[$ctr]["nPointsxx"];
}

$json["result"] = "success";
$json["detail"] = $detail;
echo json_encode($json);
return;

?>