<?php
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

$transnox = $parjson["sTransNox"];
$trandate = $parjson["dTransact"];
$gcardnox = $parjson["sGCardNox"];
$pointsxx = $parjson["nPointsxx"];
$compnyid = $parjson["sCompnyID"];

$offline_sent = sizeof($parjson["transnox"]);

for($ctr=0;$ctr<$offline_sent; $ctr++){
    $transnox .= ", '" . $parjson["transnox"][$ctr] . "'";
}

$sql = "SELECT b.sCompnyNm, b.dBirthDte, a.sGCardNox, c.sNmOnCard, a.nAvlPoint, a.nTotPoint, a.dMemberxx, a.cDigitalx, a.cCardStat" .
    " FROM G_Card_Master a" .
    " LEFT JOIN Client_Master b ON a.ClientID = b.sClientID" .
    " LEFT JOIN G_Card_Application c on a.sApplicNo = c.sTransNox" .
    " WHERE a.sGCardNox = '$gcardnox'";
$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Request transfer points to master! " . $app->getErrorMessage();
    echo json_encode($json);
    return;
}
elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "Invalid G-card Detected!";
    echo json_encode($json);
    return;
}

//Validate if card is active...
if($rows[0]["cCardStat"] != "4"){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
    $json["error"]["message"] = "Transaction is not allowed if GCard is not active.";
    echo json_encode($json);
    return;
}

$app->beginTrans();

$date = new DateTime('now');
$year = $date->format("Y");
$batchno = CommonUtil::GetNextCode("G_Card_Detail", "sTransNox", $year, $app->getConnection(), "MX01");

$stamp = $date->format(CommonUtil::format_timestamp);
$sql = "INSERT INTO G_Card_Detail" . 
       " SET sTransNox = '$batchno'" . 
          ", sGCardNox = '$gcardnox'" .
          ", sCompnyID = '$compnyid'" . 
          ", dTransact = '$trandate'" . 
          ", sSourceNo = '$transnox'" . 
          ", sSourceCd = 'M00119000001'" . 
          ", nTranAmtx = $pointsxx" . 
          ", nPointsxx = $pointsxx" . 
          ", sOTPasswd = ''" . 
          ", cPointSnt = '1'" .
          ", sModified = '$userid'" . 
          ", dModified = '$stamp'";
if($app->execute($sql, "G_Card_Detail", $compnyid) <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Unable to insert point transfer to master. " . $app->getErrorMessage();
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

$sql = "UPDATE G_Card_Master" . 
      " SET nTotPoint = nTotPoint + $pointsxx" . 
         ", nAvlPoint = nAvlPoint + $pointsxx" . 
         ", sLastLine = '$batchno'" . 
         ", dModified = '$stamp'" .
      " WHERE sGCardNox = '$gcardnox'";   
if($app->execute($sql, "G_Card_Detail", $compnyid) <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Unable to insert point transfer to master. " . $app->getErrorMessage();
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

$item = json_decode($parjson["detail"], true);
$rows_found = sizeof($item);
for($ctr=0;$ctr<$rows_found; $ctr++){
    $gcardnox = $item["sGCardNox"][$ctr];    
    $pointsxx = $item["nPointsxx"][$ctr];

    //TODO: Insert detail here...
}

$app->commitTrans();
//$app->rollbackTrans();

$json["result"] = "SUCCESS";
$json["transnox"] = $atrans;
$json["message"] = "Updated $valid_rows record(s) with a total points of $total_points.";
echo json_encode($json);
?>
