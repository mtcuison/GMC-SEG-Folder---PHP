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

$sql = "SELECT" . 
              "  a.sGCardNox" .
              ", a.sCardNmbr" .
              ", a.cCardType" .
              ", b.sNmOnCard" .
              ", a.sClientID" .
              ", c.sCompnyNm" .
              ", CONCAT(c.sAddressx, ', ', d.sTownName, ', ', e.sProvName) sAddressx" .
              ", CONCAT(f.sTownName, ', ', g.sProvName) sBirthPlc" .
              ", c.dBirthDte" .
              ", a.dMemberxx" .
              ", a.dActivate" .
              ", a.nTotPoint" .
              ", a.nAvlPoint" .
              ", a.sGroupIDx" .
              ", a.cIndvlPts" .
              ", a.cMainGrpx" .
              ", a.cCardStat" .
              ", IF(ISNULL(h.sEmployID), 0, 1) cEmployee" .
              ", a.cDigitalx" . 
        " FROM G_Card_Master a" .
             " LEFT JOIN G_Card_Application b ON a.sApplicNo = b.sTransNox" .
             " LEFT JOIN Client_Master c ON a.sClientID = c.sClientID" .
             " LEFT JOIN TownCity d ON c.sTownIDxx = d.sTownIDxx" .
             " LEFT JOIN Province e ON d.sProvIDxx = e.sProvIDxx" .
             " LEFT JOIN TownCity f ON c.sBirthPlc = f.sTownIDxx" .  
             " LEFT JOIN Province g ON f.sProvIDxx = g.sProvIDxx" .
             " LEFT JOIN Employee_Master001 h ON a.sClientID = h.sEmployID AND h.cRecdStat = '1'" .
        " WHERE a.sCardNmbr = '$cardnmbr'";
$rows = $app->fetch($sql);

//Validate if transno are valid...
if($rows === null){
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = "Error found during loading..." . $app->getErrorMessage();
        echo json_encode($json);
        return;
}
elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "No gcard information was found..." . $sql;
    echo json_encode($json);
    return;
}

$json["result"] = "SUCCESS";
$json["sGCardNox"] = $rows[0]["sGCardNox"];
$json["sCardNmbr"] = $rows[0]["sCardNmbr"];
$json["cCardType"] = $rows[0]["cCardType"];
$json["sNmOnCard"] = $rows[0]["sNmOnCard"];
$json["sClientID"] = $rows[0]["sClientID"];
$json["sCompnyNm"] = $rows[0]["sCompnyNm"];
$json["sAddressx"] = $rows[0]["sAddressx"];
$json["sBirthPlc"] = $rows[0]["sBirthPlc"];
$json["dBirthDte"] = $rows[0]["dBirthDte"];
$json["dMemberxx"] = $rows[0]["dMemberxx"];
$json["dActivate"] = $rows[0]["dActivate"];
$json["nTotPoint"] = $rows[0]["nTotPoint"];
$json["nAvlPoint"] = $rows[0]["nAvlPoint"];
$json["sGroupIDx"] = $rows[0]["sGroupIDx"];
$json["cIndvlPts"] = $rows[0]["cIndvlPts"];
$json["cMainGrpx"] = $rows[0]["cMainGrpx"];
$json["cCardStat"] = $rows[0]["cCardStat"];
$json["cEmployee"] = $rows[0]["cEmployee"];
$json["cDigitalx"] = $rows[0]["cDigitalx"];
echo json_encode($json);
?>
