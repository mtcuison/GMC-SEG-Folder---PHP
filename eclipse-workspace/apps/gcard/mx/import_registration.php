<?php
/*
 * import_registration.php
 * kalyptus - 2019.06.22 10:24am
 * use this API in requesting status of REGISTRATION of MC associated with the GCard(GuanzonApp).
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
$cardnmbr = $aes->decrypt($parjson["secureno"]);

$sql = "SELECT sGCardNox, cCardType, cCardStat, cDigitalx" .
    " FROM G_Card_Master" .
    " WHERE sCardNmbr = '$cardnmbr'";
$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["message"] = "Failed loading GCard information!";
    echo json_encode($json);
    return;
}
if(empty($rows)){
    $json["result"] = "error";
    $json["error"]["message"] = "GCard Account is not available!";
    echo json_encode($json);
    return;
}

if($rows[0]["cCardStat"] != "4"){
    $json["result"] = "error";
    $json["error"]["message"] = "GCard account is not active...";
    echo json_encode($json);
    return null;
}

$cardno = $rows[0]["sGCardNox"];
$sql = "SELECT a.sSerialID, a.sEngineNo, a.sFrameNox, c.sModelNme, b.cRecdStat cFSEPStat" . 
      " FROM MC_Serial a" . 
           " LEFT JOIN MC_Serial_Service b ON a.sSerialID = b.sSerialID" . 
           " LEFT JOIN MC_Model c ON a.sModelIDx = c.sModelIDx" .
      " WHERE sGCardNox = '$cardno'";
$rows = $app->fetch($sql);

$rows_found = sizeof($rows);
$detail = array();
for($ctr=0;$ctr<$rows_found;$ctr++){
    $serial = $rows[$ctr]["sSerialID"];
    
    $sql = "SELECT IFNULL(sCRENoxxx, '') sCRENoxxx, IFNULL(sCRNoxxxx, '') sCRNoxxxx, b.*" . 
          " FROM MC_Registration a" . 
                " LEFT JOIN MC_Registration_Expense b ON a.sTransNox = b.sReferNox" .  
          " WHERE sSerialID = '$serial'" . 
            " AND b.cMotorNew <= '4'" . 
          " ORDER BY a.dPurchase DESC" ;
    $rows_reg = $app->fetch($sql);
    
    $detail[$ctr]["sSerialID"] = $serial;
    $detail[$ctr]["sEngineNo"] = $rows[$ctr]["sEngineNo"];
    $detail[$ctr]["sFrameNox"] = $rows[$ctr]["sFrameNox"];
    $detail[$ctr]["sModelNme"] = $rows[$ctr]["sModelNme"];
    $detail[$ctr]["cFSEPStat"] = $rows[$ctr]["cFSEPStat"];
    
    if($rows_reg == null){
        $detail[$ctr]["cRegStatx"] = "UNKNOWN";
    }
    else{
        if(!($rows_reg[0]["sCRENoxxx"] == "" || $rows_reg[0]["sCRNoxxxx"] == "")){
            $detail[$ctr]["cRegStatx"] = "PROCESSED";
        }
        else if($rows_reg[0]["cInsurPrc"] == "1" || $rows_reg[0]["cRegisPrc"] == "1"){
            $detail[$ctr]["cRegStatx"] = "PROCESSING";
        }
        else{
            $detail[$ctr]["cRegStatx"] = "UNKNOWN";
        }
    }
}

$json["result"] = "success";
$json["detail"] = $detail;
echo json_encode($json);
return;

?>