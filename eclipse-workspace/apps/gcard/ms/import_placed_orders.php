<?php
/*
 * request_order_info.php
 * kalyptus - 2019.06.18 11:14am
 * use this API requesting information of placed order.
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

//get mobile no
if(isset($myheader['g-api-mobile'])){
    $mobile = $myheader['g-api-mobile'];
}
else{
    $mobile = "";
}

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

//echo $data;

$aes = new MySQLAES(APPKEYX);
$cardnmbr = $aes->decrypt(htmlspecialchars($parjson["secureno"]));

//get information from the Order table
$sql = "SELECT a.sTransNox, a.dOrderedx, a.dPickupxx, a.sBranchCD, a.sReferNox, a.cTranStat, a.cPlcOrder, b.sPromoIDx, b.nItemQtyx, b.nPointsxx, a.sGCardNox" . 
      " FROM G_Card_Order_Master a" . 
            " LEFT JOIN G_Card_Order_Detail b ON a.sTransNox = b.sTransNox" . 
            " LEFT JOIN G_Card_Master c ON a.sGCardNox = c.sGCardNox" . 
      " WHERE c.sCardNmbr = '$cardnmbr'" . 
        " AND a.cTranStat = '1'" .
      " ORDER BY sTransNox";

//echo $sql;

$rows = $app->fetch($sql);
if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Failed on loading the Order record! " . $app->getErrorMessage();
    echo json_encode($json);
    return;
}
elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "Order is not existing!";
    //$json["error"]["query"] = $sql;
    echo json_encode($json);
    return;
}

$detail = array();
$rows_found = sizeof($rows);
for($ctr=0;$ctr<$rows_found; $ctr++){
    $detail[$ctr]["sTransNox"] = $rows[$ctr]["sTransNox"];
    $detail[$ctr]["dOrderedx"] = $rows[$ctr]["dOrderedx"];
    $detail[$ctr]["dPickupxx"] = $rows[$ctr]["dPickupxx"];
    $detail[$ctr]["sBranchCD"] = $rows[$ctr]["sBranchCD"];
    $detail[$ctr]["sReferNox"] = $rows[$ctr]["sReferNox"];
    $detail[$ctr]["cTranStat"] = $rows[$ctr]["cTranStat"];
    $detail[$ctr]["cPlcOrder"] = $rows[$ctr]["cPlcOrder"];
    $detail[$ctr]["sPromoIDx"] = $rows[$ctr]["sPromoIDx"];
    $detail[$ctr]["nItemQtyx"] = $rows[$ctr]["nItemQtyx"];
    $detail[$ctr]["nPointsxx"] = $rows[$ctr]["nPointsxx"];
    $detail[$ctr]["nPointsxx"] = $rows[$ctr]["nPointsxx"];
    $detail[$ctr]["sGCardNox"] = $rows[$ctr]["sGCardNox"];
}

$json["result"] = "success";
$json["detail"] = $detail;
echo json_encode($json);
return;
?>

