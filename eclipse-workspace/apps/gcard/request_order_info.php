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

if(!$app->loaduser($prodctid, $userid)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

$data = file_get_contents('php://input');
//parse into json the PARAMETERS
$parjson = json_decode($data, true);

$aes = new MySQLAES($pcname);
$transno = $aes->decrypt(htmlspecialchars($parjson["uuid"]));

//get information from the Order table
$sql = "SELECT *" .
    " FROM G_Card_Order_Master" .
    " WHERE sTransNox = '$transno'";
$rows = $app->fetch($sql);
if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Failed on loading the Order record! " . $app->getErrorMessage() ;
    echo json_encode($json);
    return;
}
elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "Order is not existing!";
    echo json_encode($json);
    return;
}

$json["result"] = "success";
$json["sTransNox"] = $rows[0]["sTransNox"];
$json["sBranchCd"] = $rows[0]["sBranchCd"];
$json["sGCardNox"] = $rows[0]["sGCardNox"];
$json["sRemarksx"] = $rows[0]["sRemarksx"];
$json["sReferNox"] = $rows[0]["sReferNox"];
$json["dPlacOrdr"] = $rows[0]["dPlacOrdr"];
$json["nPointsxx"] = $rows[0]["nPointsxx"];
$json["cTranStat"] = $rows[0]["cTranStat"];
$json["cPlcOrder"] = $rows[0]["cPlcOrder"];

//get information from the Order table
$sql = "SELECT *" .
    " FROM G_Card_Order_Detail" .
    " WHERE sTransNox = '$transno'" .
    " ORDER BY nEntryNox";
$rows = $app->fetch($sql);
if($rows === null){
    $json = array();
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Failed on loading the Order record!";
    echo json_encode($json);
    return;
}
elseif(empty($rows)){
    $json = array();
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "Order is not existing!";
    echo json_encode($json);
    return;
}

$detail = array();
$rows_found = size($rows);
for($ctr=0;$ctr<$rows_found; $ctr++){
    $detail[$ctr]["nEntryNox"] = $rows[$ctr]["nEntryNox"];    
    $detail[$ctr]["sPromoIDx"] = $rows[$ctr]["sPromoIDx"];
    $detail[$ctr]["nItemQtyx"] = $rows[$ctr]["nItemQtyx"];
    $detail[$ctr]["nPointsxx"] = $rows[$ctr]["nPointsxx"];
}

$json["detail"] = $detail;
echo json_encode($json);
return;
?>

