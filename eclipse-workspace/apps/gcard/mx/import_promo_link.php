<?php
/*
 * import_trans_offline.php
 * kalyptus - 2019.06.22 10:24am
 * use this API placing in requesting link of PROMOS(GuanzonApp).
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

//load information of the ordered item...
$sql = "SELECT" . 
              "  sTransNox" .
              ", dTransact" . 
              ", sImageURL" . 
              ", IF(IFNULL(sPromoURL, 'https://www.guanzongroup.com.ph/') = '', 'https://www.guanzongroup.com.ph/', IFNULL(sPromoURL, 'https://www.guanzongroup.com.ph/')) sPromoURL" . 
              ", sCaptionx" . 
              ", dDateFrom" . 
              ", dDateThru" .
           " FROM G_Card_Promo_Link" .
           " WHERE dDateThru > Now()" . 
           " ORDER BY dTransact DESC" . 
           " LIMIT 5";

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["message"] = "Failed loading branches!";
    echo json_encode($json);
    return;
}
if(empty($rows)){
    $json["result"] = "error";
    $json["error"]["message"] = "There are no branches detected!";
    echo json_encode($json);
    return;
}

$rows_found = size($rows);
$detail = array();
for($ctr=0;$ctr<$rows_found;$ctr++){
    $detail[$ctr]["sTransNox"] = $rows[$ctr]["sTransNox"];
    $detail[$ctr]["dTransact"] = $rows[$ctr]["dTransact"];
    $detail[$ctr]["sImageURL"] = $rows[$ctr]["sImageURL"];
    $detail[$ctr]["sPromoURL"] = $rows[$ctr]["sPromoURL"];
    $detail[$ctr]["sCaptionx"] = $rows[$ctr]["sCaptionx"];
    $detail[$ctr]["dDateFrom"] = $rows[$ctr]["dDateFrom"];
    $detail[$ctr]["dDateThru"] = $rows[$ctr]["dDateThru"];
}

$json["result"] = "success";
$json["detail"] = $detail;
echo json_encode($json);
return;

?>