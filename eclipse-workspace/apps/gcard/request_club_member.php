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

$sql = "SELECT b.sGCardNox, b.sCardNmbr, c.sNmOnCard, b.nAvlPoint, b.nTotPoint" .
    " FROM G_Card_Master a" .
       " LEFT JOIN G_Card_Master b ON a.sGroupIDx = b.sGroupIDx" . 
       " LEFT JOIN G_Card_Application c ON b.sApplicNo = c.sTransNox" .
    " WHERE a.sCardNmbr = '$cardnmbr'" . 
      " AND b.cCardStat = '4'" .
      " AND b.nAvlPoint > 0" .
      " AND b.nAvlPoint = b.nTotPoint" .
      " AND b.cMainGrpx = '0'" . 
      " AND b.cIndvlPts = '0'";

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
    $detail[0]["sGCardNox"] = "";
    $detail[0]["sCardNmbr"] = "";
    $detail[0]["sNmOnCard"] = "";
    $detail[0]["nAvlPoint"] = 0;
}
else{
    $rows_found = sizeof($rows);
    for($ctr=0;$ctr<$rows_found; $ctr++){
        $detail[$ctr]["sGCardNox"] = $rows[$ctr]["sGCardNox"];
        $detail[$ctr]["sCardNmbr"] = $rows[$ctr]["sCardNmbr"];
        $detail[$ctr]["sNmOnCard"] = $rows[$ctr]["sNmOnCard"];
        $detail[$ctr]["nAvlPoint"] = $rows[$ctr]["nAvlPoint"];
    }
}

$json["result"] = "SUCCESS";
$json["detail"] = $detail;
echo json_encode($json);
?>
