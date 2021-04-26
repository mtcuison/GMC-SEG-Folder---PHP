<?php
/*
 * place_order.php
 * kalyptus - 2019.06.18 11:14am
 * use this API placing an order(GuanzonApp).
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
$cardnmbr = $aes->decrypt(htmlspecialchars($parjson["secureno"]));

//get information about the gcard first
$sql = "SELECT b.sCompnyNm, b.dBirthDte, a.sGCardNox, a.dMemberxx, a.nAvlPoint, a.cCardStat" .
    " FROM G_Card_Master a" .
    " LEFT JOIN Client_Master b ON a.ClientID = b.sClientID" .
    " WHERE sCardNmbr = '$cardnmbr'";
$rows = $app->fetch($sql);
if($rows == null){
    $json["result"] = "error";
    $json["error"]["message"] = "Failed on loading the GCard information!";
    echo json_encode($json);
    return null;
}
elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["message"] = "GCard information is not existing!";
    echo json_encode($json);
    return null;
}

//Check if Card was activated
//after all the activation setting this is actually impossible
if($rows[0]["cCardStat"] != "4"){
    $json["result"] = "error";
    $json["error"]["message"] = "GCard was not active!";
    echo json_encode($json);
    return null;
}

//TODO: I was wondering do we still need to validate the membership date
//      just like during the first implementation of GCard

//save the data to our variable
$cardno = $rows[0]["sGCardNox"];
$availp = $rows[0]["nAvlPoint"];
$orderx = 0;
$branch  = $parjson["branchcd"];

//get total points for unclaimed pre-order...
$sql = "SELECT SUM(nPointsxx) nPointsxx" . 
      " FROM G_Card_Order_Master" . 
      " WHERE sGCardNox = '$cardno'" . 
        " AND cTranStat = '1'";
$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["message"] = "Failed loading unclaim orders!";
    echo json_encode($json);
    return;
}
if(!empty($rows)){
    $orderx = $rows[0]["nPointsxx"];
}

date_default_timezone_set('Asia/Manila');
$date = new DateTime('now');
$year = $date->format("y");
$stamp = $date->format(CommonUtil::format_timestamp);
$referno = strtoupper(uniqid('G-'. ''));

$app->beginTrans();

//create the record to at least minimize duplicate
$transno = $app->GetNextCode("G_Card_Order_Master", "sTransNox", $year, $app->getConnection(), "MX01");
$sql = "INSERT INTO G_Card_Order_Master" .
    " SET sTransNox = '$transno'" .
    ", sGCardNox = '$cardno'" .
    ", sBranchCd = '$branch'" .
    ", sReferNox = '$referno'" .
    ", dOrderedx = '$stamp'" .
    ", dPlacOrdr = '$stamp'" .
    ", cTranStat = '1'" .
    ", cPlcOrder = '1'" .
    ", cPointSnt = '0'" .
    ", cMonitorx = '0'" .
    ", cVerified = '0'";

if($app->execute($sql, "G_Card_Order_Master", $branch, "") <= 0){
    $json["result"] = "error";
    $json["error"]["message"] = "Failed saving order...";
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

//evaluate the detail...
$detail_sent = sizeof($parjson["detail"]);
$orderc = 0;
for($ctr=0;$ctr<$detail_sent; $ctr++){
    $promoid = $parjson["detail"][$ctr]["promoidx"];
    $itemqty = $parjson["detail"][$ctr]["itemqtyx"];
    //load information of the ordered item...
    $sql = "SELECT sTransNox, sPromCode, sPromDesc, nPointsxx, sCardType, dDateFrom, dDateThru, cTranstat" .
        " FROM G_Card_Promo_Master" .
        " WHERE NOW() BETWEEN dDateFrom AND IFNULL(dDateThru, NOW())" .
        " AND sTransNox = '$promoid'" .
        " AND cTranStat = '1'";
    $rows = $app->fetch($sql);
    
    if($rows === null){
        $json["result"] = "error";
        $json["error"]["message"] = "Failed loading redeemable items!";
        $app->rollbackTrans();
        echo json_encode($json);
        return;
    }
    if(empty($rows)){
        $json["result"] = "error";
        $json["error"]["message"] = "Item is no longer available for the period!";
        $app->rollbackTrans();
        echo json_encode($json);
        return;
    }

    //compute current order
    $orderc += ($rows[0]["nPointsxx"] * $itemqty);
    
    //save detail part
    $sql = "INSERT INTO G_Card_Order_Detail" . 
          " SET sTransNox = '$transno'" . 
             ", nEntryNox = " . ($ctr+1) .
             ", sPromoIDx = '$promoid'" . 
             ", nItemQtyx = $itemqty" . 
             ", nPointsxx = " . $rows[0]["nPointsxx"];
    if($app->execute($sql, "G_Card_Order_Detail", $branch, "") <= 0){
        $json["result"] = "error";
        $json["error"]["message"] = "Failed saving order...";
        $app->rollbackTrans();
        echo json_encode($json);
        return;
    }
}

//check if orders is no higher than the placed orders...
if($availp - ($orderx + $orderc) < 0){
    $json["result"] = "error";
    $json["error"]["message"] = "Total available points is less than the placed orders!";
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

//update the total points here...
$sql = "UPDATE G_Card_Order_Master" . 
      " SET nPointsxx = $orderc" . 
      " WHERE sTransNox = '$transno'";
if($app->execute($sql, "G_Card_Order_Master", $branch, "") <= 0){
    $json["result"] = "error";
    $json["error"]["message"] = "Failed saving order...";
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

$app->commitTrans();

$json["result"] = "success";
$json["sTransNox"] = $transno;
$json["dPlacOrdr"] = $stamp;
$json["sReferNox"] = $referno;
echo json_encode($json);
return;

?>