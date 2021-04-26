<?php
/*
 * import_gcardnumber.php
 * kalyptus - 2019.06.18 08:30am
 * use this API in registering a GCard Number to a particular user(GuanzonApp)
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

$sql = "SELECT b.sCompnyNm, b.dBirthDte, a.sGCardNox, c.sNmOnCard, a.nAvlPoint, a.nTotPoint, a.dMemberxx, a.cDigitalx, a.cCardStat" . 
      " FROM G_Card_Master a" . 
        " LEFT JOIN Client_Master b ON a.ClientID = b.sClientID" . 
        " LEFT JOIN G_Card_Application c on a.sApplicNo = c.sTransNox" .
      " WHERE a.sCardNmbr = '$cardnmbr'";
$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["message"] = "Request to add GCard to user's account failed!";
    echo json_encode($json);
    return;
}
elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["message"] = "Invalid G-card Detected!";
    echo json_encode($json);
    return;
}

//not sending the birthday means that the gcard was SCANNED...
if(isset($parjson["bday"])){
    $birthday = htmlspecialchars($parjson["bday"]);
    if($rows[0]["dBirthDte"] != $birthday){
        $json["result"] = "error";
        $json["error"]["message"] = "Invalid parameter detected!";
        echo json_encode($json);
        return;
    }
}

if($rows[0]["cCardStat"] >= 5){
    $json["result"] = "error";
    $json["error"]["message"] = "Invalid Card Status detected!";
    echo json_encode($json);
    return null;
}

$gcardno = $rows[0]["sGCardNox"];
$sql = "SELECT *" . 
      " FROM G_Card_App_User_Device" .
      " WHERE sGCardNox = '$gcardno'";
$rows = $app->fetch($sql);

$app->beginTrans();

if($rows === null){
    $json["result"] = "error";
    $json["error"]["message"] = "Request to add GCard to user's account failed!";
    echo json_encode($json);
    $app->rollbackTrans();
    return null;
}
elseif(empty($rows)){
    $sql = "INSERT INTO G_Card_App_User_Device" .
          " SET sUserIDxx = '$userid'" .
             ", sIMEINoxx = '$pcname'" . 
             ", sMobileNo = '$mobile'" . 
             ", sGCardNox = '$gcardno'" . 
             ", cRecdStat = '1'";
    if($app->execute($sql, "G_Card_App_User_Device", "") <= 0){
        $json["result"] = "error";
        $json["error"]["message"] = "Unable to add GCard to user's account!";
        $app->rollbackTrans();
        echo json_encode($json);
        return;
    }
    
    //be sure to activate/set digital the gcard...
    if($rows[0]["cCardStat"] != "4" || $rows[0]["cDigitalx"] == "0"){
        $sql = "UPDATE G_Card_Master" .
              " SET cCardStat = '4'" . 
                 ", cDigitalx = '1'" .
              " WHERE sGCardNox = '$gcardno'";
        if($app->execute($sql, "G_Card_Master", "") <= 0){
            $json["result"] = "error";
            $json["error"]["message"] = "Unable to activate GCard!";
            $app->rollbackTrans();
            echo json_encode($json);
            return;
        }
    }

    //search for Inactive FSEP
    $sql = "SELECT *" . 
          " FROM MC_Serial_Service" .  
          " WHERE sGCardNox = '$gcardno'" . 
            " AND dTransact >= DATE_ADD(NOW(), INTERVAL -3 YEAR)" . 
            " AND cRecdStat = '0'";
    if($rows === null){
        $json["result"] = "error";
        $json["error"]["message"] = "Unable to load MCs belonging to this GCARD Account!";
        echo json_encode($json);
        $app->rollbackTrans();
        return null;
    }
    
    //Activate inactive FSEP
    if(!empty($rows)){
        $rows_found = size($rows);
        for($ctr=0;$ctr<$rows_found;$ctr++){
            $serial = $rows[$ctr]["sSerialID"];
            $sql = "UPDATE MC_Serial_Service" .
                " SET cRecdStat = '1'" .
                " WHERE sSerialID = '$serial'";
            if($app->execute($sql, "MC_Serial_Service", "") <= 0){
                $json["result"] = "error";
                $json["error"]["message"] = "Unable to activate FSEP!";
                $app->rollbackTrans();
                echo json_encode($json);
                return;
            }
        }
    }
    
    $json["result"] = "success";
    $json["sGCardNox"] = $gcardno;
    $json["sCardNmbr"] = $cardnmbr;
    $json["dMemberxx"] = $rows[0]["dMemberxx"];
    $json["nAvlPoint"] = $rows[0]["nAvlPoint"];
    $app->commitTrans();
    echo json_encode($json);
    return;
}

$rows_found = size($rows);
for($ctr=0; $ctr<$rows_found; $ctr++){
    if($rows[$ctr]["cRecdStat"] != "1"){
        if($rows[$ctr]["sUserIDxx"] == $userid && 
            $rows[$ctr]["sIMEINoxx"] == $pcname){
                $sql = "UPDATE G_Card_App_User_Device" .
                    " SET cRecdStat = '1'" .
                       ", sMobileNo = '$mobile'" .
                    " WHERE sUserIDxx = '$userid'" .
                     " AND sIMEINoxx = '$pcname'" . 
                     " AND sGCardNox = '$gcardno'";
                if($app->execute($sql, "G_Card_App_User_Device", "") <= 0){
                    $json["result"] = "error";
                    $json["error"]["message"] = "Unable to activate USER ACCOUNT INFO!";
                    $app->rollbackTrans();
                    echo json_encode($json);
                    return;
                }
        }
    }
    else{
        if(!($rows[$ctr]["sUserIDxx"] == $userid &&
            $rows[$ctr]["sIMEINoxx"] == $pcname)){
            //TODO: Inform this users that someone has registered the gcard to their device...
            //TODO: Ask the user if he/she wants to deactivate usage of his account to other devices...
        }
        else{
            if($rows[$ctr]["sMobileNo"] != $mobile && $mobile != ""){
                $sql = "UPDATE G_Card_App_User_Device" .
                    " SET sMobileNo = '$mobile'" .
                    " WHERE sUserIDxx = '$userid'" .
                    " AND sIMEINoxx = '$pcname'" .
                    " AND sGCardNox = '$gcardno'";
                if($app->execute($sql, "G_Card_App_User_Device", "") <= 0){
                    $json["result"] = "error";
                    $json["error"]["message"] = "Unable to add mobile to USER ACCOUNT INFO!";
                    $app->rollbackTrans();
                    echo json_encode($json);
                    return;
                }
            }
        }
    }
}

$app->commitTrans();

$json["result"] = "success";
$json["sGCardNox"] = $gcardno;
$json["sCardNmbr"] = $cardnmbr;
$json["sNmOnCard"] = $rows[0]["sNmOnCard"];
$json["cCardType"] = $rows[0]["cCardType"];
$json["dMemberxx"] = $rows[0]["dMemberxx"];
$json["nAvlPoint"] = $rows[0]["nAvlPoint"];
$json["nTotPoint"] = $rows[0]["nTotPoint"];
$json["cCardStat"] = $rows[0]["cCardStat"];
echo json_encode($json);
return;

?>