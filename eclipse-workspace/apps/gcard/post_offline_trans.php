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
$transnox = "";

if(sizeof($parjson["transnox"]) < 1){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "No transaction to be posted...";
    echo json_encode($json);
    return;
}

$offline_sent = sizeof($parjson["transnox"]);

for($ctr=0;$ctr<$offline_sent; $ctr++){
    $transnox .= ", '" . $parjson["transnox"][$ctr] . "'";
}

$sql = "SELECT a.sTransNox, a.sGCardNox, a.nPointsxx, a.cTranStat, b.cCardStat, IF(ISNULL(c.sEmployID), 0, 1) cEmployee" . 
      " FROM G_Card_Detail_Offline a" .
            " LEFT JOIN G_Card_Master b ON a.sGCardNox = b.sGCardNox" . 
            " LEFT JOIN Employee_Master001 c ON b.sClientID = c.sEmployID" . 
      " WHERE b.sCardNmbr = '$cardnmbr'" .  
        " AND a.sTransNox IN (" . substr($transnox, 1) . ")";
$rows = $app->fetch($sql);

//Validate if transno are valid...
if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Error found during loading..." . $app->getErrorMessage();
    echo json_encode($json);
    return null;
}

if(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "No offline transaction was found...";
    echo json_encode($json);
    return;
}

//Employees are not allowed to earn points...
if($rows[0]["cEmployee"] == "1"){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
    $json["error"]["message"] = "GCard of employees are not allowed to earn points.";
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

$gcardnox = $rows[0]["sGCardNox"];
$rows_found = sizeof($rows);
$total_points = 0;
$valid_rows=0;
$atrans=array();
$otpasswd = $parjson["otpasswd"];

$app->beginTrans();

$xctr=0;
for($ctr=0;$ctr<$rows_found; $ctr++){
    if($rows[$ctr]["cTranStat"] == 1){
        $transnox = $rows[$ctr]["sTransNox"];
        $date = new DateTime('now');
        $stamp = $date->format(CommonUtil::format_timestamp);
        
        $sql_detail = "UPDATE G_Card_Detail_Offline" .
            " SET cTranStat = '2'" .
            ", sOTPasswd = '$otpasswd'" .
            ", sPostedxx = '$userid'" .
            ", dPostedxx = " . CommonUtil::toSQL($stamp) .
            " WHERE sTransNox = '$transnox'";
        if($app->execute($sql_detail, "G_Card_Detail_Offline", substr($rows[0]["sTransNox"], 0, 4)) <= 0){
            $json["result"] = "error";
            $json["error"]["code"] = $app->getErrorCode();
            $json["error"]["message"] = "Unable to update offline record. " . $app->getErrorMessage();
            $app->rollbackTrans();
            echo json_encode($json);
            return;
        }
        
        //if from qrcode then save the source device
        //qrcode is determine thru the 
        if($otpasswd != ""){
            $imei = $parjson['imeinoxx'];
            $mobile = $parjson['mobileno'];
            $qrdate = $parjson['qrdatetm'];
            $sql_detail = "INSERT INTO G_Card_Detail_Offline_Digital" .
                " SET sTransNox = '$transnox'" .
                ", sIMEINoxx = '$imei'" .
                ", sUserIDxx = '$userid'" . 
                ", sMobileNo = '$mobile'" . 
                ", dQRDateTm = '$qrdate'";    
            if($app->execute($sql_detail, "G_Card_Detail_Offline_Digital", substr($rows[0]["sTransNox"], 0, 4)) <= 0){
                $json["result"] = "error";
                $json["error"]["code"] = $app->getErrorCode();
                $json["error"]["message"] = "Unable to update offline record. " . $app->getErrorMessage();
                $app->rollbackTrans();
                echo json_encode($json);
                return;
            }
        }
        
        $valid_rows++;
        $total_points += $rows[$ctr]["nPointsxx"];
        $atrans[$xctr] = $transnox;
        $xctr++;
    }
}

$sql_master = "UPDATE G_Card_Master" . 
             " SET nAvlPoint = nAvlPoint + " . $total_points . 
             " WHERE sGCardNox = '$gcardnox'";

if($app->execute($sql_master, "G_Card_Master") <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Unable to update master record. " . $app->getErrorMessage();
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

$app->commitTrans();
//$app->rollbackTrans();

$json["result"] = "SUCCESS";
$json["transnox"] = $atrans;
$json["message"] = "Updated $valid_rows record(s) with a total points of $total_points.";
echo json_encode($json);
?>
