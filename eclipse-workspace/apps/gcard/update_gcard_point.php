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
$aes = new MySQLAES($pcname);
$cardnmbr = $aes->decrypt($parjson["secureno"]);

$sql = "SELECT sGCardNox, cCardType, cCardStat" . 
      " FROM G_Card_Master" . 
      " WHERE sCardNmbr = '$cardnmbr'";
$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Error found during loading..." . $app->getErrorMessage();
    echo json_encode($json);
    return null;
}
else if(empty($rows)) {
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "GCard information not found...";
    echo json_encode($json);
    return null;
}

//make sure GCard is activated
if($rows[0]["cCardStat"] != "4"){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
    $json["error"]["message"] = "Only activated GCard can earn a point...";
    echo json_encode($json);
    return null;
}

//make sure GCard is premium
if($rows[0]["cCardType"] != "0"){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
    $json["error"]["message"] = "Only premium type of GCard can earn a point...";
    echo json_encode($json);
    return null;
}

$pointsxx = $parjson["pointsxx"];
$descript = $parjson["descript"];
$refernox = $parjson["refernox"];
$gcardnox = $rows[0]["sGCardNox"];

if(!is_numeric($pointsxx)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Points earn should be numeric...";
    echo json_encode($json);
    return null;
}

$app->beginTrans();

$sql = "SELECT *" . 
      " FROM G_Card_Ledger" . 
      " WHERE sGCardNox = '$gcardnox'" . 
        " AND sSourceDs = '$descript'" . 
        " AND sReferNox = '$refernox'";
$rows = $app->fetch($sql);
if($rows === null){
    //test if sql causes an error or no record was found
    if($app->getErrorMessage() != ""){
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = "Error found during loading..." . $app->getErrorMessage();
        echo json_encode($json);
        return null;
    }
}

if(!empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "Transaction was already submitted!";
    echo json_encode($json);
    return null;
}

if($descript == "REDEMPTION" || $descript == "PREORDER"){
    $pointsxx = $pointsxx * -1;
}

//Determine the nEntryNox of ledger here...
$sql = "SELECT *" .
    " FROM G_Card_Ledger" .
    " WHERE sGCardNox = '$gcardnox'" . 
    " ORDER BY nEntryNox DESC LIMIT 1";
$rows = $app->fetch($sql);
if($rows === null){
    //test if sql causes an error or no record was found
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Error found during creation of series..." . $app->getErrorMessage();
    echo json_encode($json);
    return null;
}
if(empty($rows)){
    $entry = 1;    
}
else{
    $entry = $rows[0]["nEntryNox"] + 1;
}

//insert the gcard transaction history here...
$sql = "INSERT INTO G_Card_Ledger" . 
      " SET sGCardNox = '$gcardnox'" . 
         ", nEntryNox = $entry" . 
         ", sSourceDs = '$descript'" . 
         ", sReferNox = '$refernox'";
if($app->execute($sql, "G_Card_Ledger") <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Unable to create ledger. " . $app->getErrorMessage();
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

//updae the master here
$sql = "UPDATE G_Card_Master" .
" SET nAvlPoint = nAvlPoint + " . $pointsxx .
   ", nTotPoint = nTotPoint + " . $pointsxx .
" WHERE sGCardNox = '$gcardnox'";

if($app->execute($sql, "G_Card_Master") <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Unable to update master record. " . $app->getErrorMessage();
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

$app->commitTrans();

$json["result"] = "SUCCESS";
echo json_encode($json);
?>
