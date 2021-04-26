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
$transtat = $parjson["transtat"];

$sql = "SELECT a.*" .
    " FROM G_Card_Detail_Offline a" .
    " LEFT JOIN G_Card_Master b ON a.sGCardNox = b.sGCardNox" .
    " WHERE b.sCardNmbr = '$cardnmbr'";
if($transtat != "all"){
    $sql .= " AND a.cTranStat = '$transtat'";
}

$rows = $app->fetch($sql);

//echo $sql;

$detail = array();

if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Error found during loading..." . $app->getErrorMessage();
    echo json_encode($json);
    return null;
}
elseif(empty($rows)){
    $detail[0]["sTransNox"] = "";
    $detail[0]["sGCardNox"] = "";
    $detail[0]["sCompnyID"] = "";
    $detail[0]["dTransact"] = null;
    $detail[0]["sSourceNo"] = "";
    $detail[0]["sSourceCd"] = "";
    $detail[0]["nTranAmtx"] = 0;
    $detail[0]["nPointsxx"] = 0;
    $detail[0]["sRemarksx"] = "";
    $detail[0]["sOTPasswd"] = "";
    $detail[0]["cTranStat"] = "0";
    $detail[0]["sPostedxx"] = "";
    $detail[0]["dPostedxx"] = null;
    $detail[0]["sModified"] = "";
    $detail[0]["dModified"] = null;
}
else{
    $rows_found = sizeof($rows);
    for($ctr=0;$ctr<$rows_found; $ctr++){
        $detail[$ctr]["sTransNox"] = $rows[$ctr]["sTransNox"];
        $detail[$ctr]["sGCardNox"] = $rows[$ctr]["sGCardNox"];
        $detail[$ctr]["sCompnyID"] = $rows[$ctr]["sCompnyID"];
        $detail[$ctr]["dTransact"] = $rows[$ctr]["dTransact"];
        $detail[$ctr]["sSourceNo"] = $rows[$ctr]["sSourceNo"];
        $detail[$ctr]["sSourceCd"] = $rows[$ctr]["sSourceCd"];
        $detail[$ctr]["nTranAmtx"] = $rows[$ctr]["nTranAmtx"];
        $detail[$ctr]["nPointsxx"] = $rows[$ctr]["nPointsxx"];
        $detail[$ctr]["sRemarksx"] = $rows[$ctr]["sRemarksx"];
        $detail[$ctr]["sOTPasswd"] = $rows[$ctr]["sOTPasswd"];
        $detail[$ctr]["cTranStat"] = $rows[$ctr]["cTranStat"];
        $detail[$ctr]["sPostedxx"] = htmlspecialchars($rows[$ctr]["sPostedxx"]);
        $detail[$ctr]["dPostedxx"] = $rows[$ctr]["dPostedxx"];
        $detail[$ctr]["sModified"] = htmlspecialchars($rows[$ctr]["sModified"]);
        $detail[$ctr]["dModified"] = $rows[$ctr]["dModified"];
    }
}

$json["result"] = "SUCCESS";
$json["detail"] = $detail;
echo json_encode($json);
?>
