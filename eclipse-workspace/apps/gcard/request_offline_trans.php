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

//echo $parjson["secureno"];
//echo $pcname;
//echo $cardnmbr;

$sql = "SELECT a.sTransNox, a.dTransact, a.sSourceNo, a.sSourceCD, c.sDescript, a.nPointsxx, a.cTranStat" .
      " FROM G_Card_Detail_Offlinea" .
            " LEFT JOIN G_Card_Master b ON a.sGCardNox = b.sGCardNox" .
            " LEFT JOIN G_Card_Points_Basis c ON a.sSourceCd = c.sSourceCd" .
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
    return;
}
elseif(empty($rows)){
    $detail[0]["transnox"] = "";
    $detail[0]["transact"] = null;
    $detail[0]["sourceno"] = "";
    $detail[0]["sourcecd"] = "";
    $detail[0]["descript"] = "";
    $detail[0]["pointsxx"] = 0;
    $detail[0]["transtat"] = "0";
}
else{
    $rows_found = sizeof($rows);
    for($ctr=0;$ctr<$rows_found; $ctr++){
        $detail[$ctr]["transnox"] = $rows[$ctr]["sTransNox"];
        $detail[$ctr]["transact"] = $rows[$ctr]["dTransact"];
        $detail[$ctr]["sourceno"] = $rows[$ctr]["sSourceNo"];
        $detail[$ctr]["sourcecd"] = $rows[$ctr]["sSourceCD"];
        $detail[$ctr]["descript"] = $rows[$ctr]["sDescript"];
        $detail[$ctr]["pointsxx"] = $rows[$ctr]["nPointsxx"];
        $detail[$ctr]["transtat"] = $rows[$ctr]["cTranStat"];
    }
}

$json["result"] = "SUCCESS";
$json["detail"] = $detail;
echo json_encode($json);
?>
