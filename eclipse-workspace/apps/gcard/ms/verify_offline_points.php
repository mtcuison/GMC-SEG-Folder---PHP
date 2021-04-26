<?php
/* Client Mobile Update API
 *
 * /gcard/ms/verify_offline_points.php
 *
 * mac 2020.06.19
 *  started creating this object.
 * */

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

$param = file_get_contents('php://input');
$parjson = mb_convert_encoding(json_decode($param, true), 'ISO-8859-1', 'UTF-8');

if(is_null($parjson)){
    $json["result"] = "error";
    $json["error"]["message"] = "Invalid parameters detected";
    echo json_encode($json);
    return;
}

if(!isset($parjson['transno'])){
    $json["result"] = "error";
    $json["error"]["message"] = "Unset TRANSACTION NO. detected.";
    echo json_encode($json);
    return;
}

$transno = $parjson['transno'];

//transaction number branch must be the same as the verifying client branch
if (substr($transno, 0, 4) != substr($clientid, 5)){
    $json["result"] = "error";
    $json["error"]["code"] = "100";
    $json["error"]["message"] = "Verifying branch is NOT ALLOWED to confirm this transaction.";
    echo json_encode($json);
    return;
}

//check if the offline transaction exists
$sql = "SELECT * FROM G_Card_Detail_Offline WHERE sTransNox = " . CommonUtil::toSQL($transno);

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Error found during loading..." . $app->getErrorMessage();
    echo json_encode($json);
    return;
}elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Transaction does not exist.";
    echo json_encode($json);
    return;
}

//is the transaction is still open?
if ($rows[0]["cTranStat"] != "0"){
    $json["result"] = "error";
    $json["error"]["code"] = "102";
    $json["error"]["message"] = "Transaction was already VERIFIED/POSTED/CANCELLED.";
    echo json_encode($json);
    return;
}

$app->beginTrans();

$sql = "UPDATE G_Card_Detail_Offline SET" .
            "  cTranStat = " . CommonUtil::toSQL(TransactionStatus::Closed) .
            ", sPostedxx = " . CommonUtil::toSQL($userid) .
            ", dPostedxx = " . CommonUtil::toSQL(date('Y-m-d H:i:s')) .
        " WHERE sTransNox = " . CommonUtil::toSQL($transno);

if($app->execute($sql, "G_Card_Detail_Offline") <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = "103";
    $json["error"]["message"] = "Unable to update G_Card_Detail_Offline! " . $app->getErrorMessage();;
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

$sql = "UPDATE G_Card_Master" .
        " SET nTotPoint = nTotPoint + " . $rows[0]["nPointsxx"] .
        " WHERE sGCardNox = " . CommonUtil::toSQL($rows[0]["sGCardNox"]);

if($app->execute($sql, "G_Card_Master") <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = "104";
    $json["error"]["message"] = "Unable to update master record. " . $app->getErrorMessage();
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

$app->commitTrans();

$json["result"] = "success";
$json["message"] = "Transaction was verified successfully.";
echo json_encode($json);
?>
