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

if(isset($myheader['g-api-mobile'])){
    $mobile = CommonUtil::fixMobile($myheader['g-api-mobile']);;
}
else{
    $mobile = "";
}

//Assumed that this API is always requested by Android devices
if(!CommonUtil::isValidMobile($mobile)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_AUTH_MOBILE;
    $json["error"]["message"] = "Invalid mobile number detected.";
    echo json_encode($json);
    return;
}

$app = new Nautilus(APPPATH);
if(!$app->LoadEnv($prodctid)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

//kalyptus - 2019.06.29 01:39pm
//follow the validLog of new Nautilus;
if(!$app->validLog($prodctid, $userid, $pcname)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

//Google FCM token
$token = $myheader['g-api-token'];
if(!$app->loaduserClient($prodctid, $userid, $pcname, $token, $mobile)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

$data = file_get_contents('php://input');

//parse into json the PARAMETERS
$parjson = json_decode($data, true);
$aes = new MySQLAES(APPKEYX);

$cardnmbr = $aes->decrypt(htmlspecialchars($parjson["secureno"]));

$sql = "SELECT" .
            "  b.sCompnyNm" .
            ", b.dBirthDte" .
            ", a.sGCardNox" .
            ", c.sNmOnCard" . 
            ", a.nAvlPoint" .
            ", a.nTotPoint" .
            ", a.dMemberxx" . 
            ", a.cDigitalx" .
            ", a.cCardStat" . 
            ", a.cCardType" .
      " FROM G_Card_Master a" . 
        " LEFT JOIN Client_Master b ON a.sClientID = b.sClientID" . 
        " LEFT JOIN G_Card_Application c on a.sApplicNo = c.sTransNox" .
      " WHERE a.sCardNmbr = '$cardnmbr'";
$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Request to add GCard to user's account failed! " . $app->getErrorMessage();
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

//not sending the birthday means that the gcard was SCANNED...
if(isset($parjson["bday"])){
    $birthday = htmlspecialchars($parjson["bday"]);
    if($rows[0]["dBirthDte"] != $birthday){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
        $json["error"]["message"] = "Please enter your birthday!";
        echo json_encode($json);
        return;
    }
}

if($rows[0]["cCardStat"] >= 5){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
    $json["error"]["message"] = "Invalid Card Status detected!";
    echo json_encode($json);
    return null;
}

//mac 2020.08.07
$newdevce = "";
if(isset($parjson["newdevce"])){$newdevce = $parjson["newdevce"];}
//end - mac 2020.08.07

$gcardno = $rows[0]["sGCardNox"];
$dmember = $rows[0]["dMemberxx"];
$avlpoint = $rows[0]["nAvlPoint"];
$nmoncard = mb_convert_encoding($rows[0]["sNmOnCard"], "UTF-8", "ISO-8859-1"); //$rows[0]["sNmOnCard"];
$cardtype = $rows[0]["cCardType"];
$totlpoint = $rows[0]["nTotPoint"];
$cardstat = $rows[0]["cCardStat"];
$digital = $rows[0]["cDigitalx"];

$app->beginTrans();

//mac 2020.09.09
//  check if user is new
$sql = "SELECT *" .
        " FROM G_Card_App_User_Device" .
        " WHERE sUserIDxx = '$userid'";
$rows = $app->fetch($sql);

$newaccount = false;

if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Request to add GCard to user's account failed! " . $app->getErrorMessage();
    echo json_encode($json);
    $app->rollbackTrans();
    return null;
} elseif(empty($rows)){
    $newaccount = true;
}
//end - mac 2020.09.09


$sql = "SELECT *" . 
      " FROM G_Card_App_User_Device" .
      " WHERE sGCardNox = '$gcardno'";
$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Request to add GCard to user's account failed! " . $app->getErrorMessage();
    echo json_encode($json);
    $app->rollbackTrans();
    return null;
}
elseif(empty($rows) || $newaccount == true){
    if (empty($rows)) {
        $sql = "INSERT INTO G_Card_App_User_Device" .
                " SET sUserIDxx = '$userid'" .
                ", sIMEINoxx = '$pcname'" .
                ", sMobileNo = '$mobile'" .
                ", sGCardNox = '$gcardno'" .
                ", cRecdStat = '1'";
    } else{
        $sql = "INSERT INTO G_Card_App_User_Device" .
                " SET sUserIDxx = '$userid'" .
                ", sIMEINoxx = '$pcname'" .
                ", sMobileNo = '$mobile'" .
                ", sGCardNox = '$gcardno'" .
                ", cRecdStat = '0'";
    }
    
    if($app->execute($sql, "G_Card_App_User_Device", "") <= 0){
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = "Unable to add GCard to user's account! " . $app->getErrorMessage();
        $app->rollbackTrans();
        echo json_encode($json);
        return;
    }
    
    //be sure to activate/set digital the gcard...
    if($cardstat != "4" || $digital == "0"){
        $date = new DateTime('now');
        $stamp = $date->format(CommonUtil::format_timestamp);
        
        $sql = "UPDATE G_Card_Master" .
                " SET cCardStat = '4'" .
                    ", dActivate = " . CommonUtil::toSQL($stamp) . 
                    ", cDigitalx = '1'" .
                " WHERE sGCardNox = '$gcardno'";
        if($app->execute($sql, "G_Card_Master", "") <= 0){
            $json["result"] = "error";
            $json["error"]["code"] = $app->getErrorCode();
            $json["error"]["message"] = "Unable to activate GCard! " . $app->getErrorMessage();
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
    
    $rows = $app->fetch($sql);
    
    if($rows === null){
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = "Unable to load MCs belonging to this GCARD Account! " . $app->getErrorMessage();
        echo json_encode($json);
        $app->rollbackTrans();
        return null;
    }
    
    //Activate inactive FSEP
    if(!empty($rows)){
        $rows_found = sizeof($rows);
        for($ctr=0;$ctr<$rows_found;$ctr++){
            $serial = $rows[$ctr]["sSerialID"];
            $sql = "UPDATE MC_Serial_Service" .
                " SET cRecdStat = '1'" .
                " WHERE sSerialID = '$serial'";
            if($app->execute($sql, "MC_Serial_Service", "") <= 0){
                $json["result"] = "error";
                $json["error"]["code"] = $app->getErrorCode();
                $json["error"]["message"] = "Unable to activate FSEP! " . $app->getErrorMessage();
                $app->rollbackTrans();
                echo json_encode($json);
                return;
            }
        }
    }
    
    $json["result"] = "success";
    //$json["sGCardNox"] = $gcardno;
    //$json["sCardNmbr"] = $cardnmbr;
    //$json["dMemberxx"] = $dmember;
    //$json["nAvlPoint"] = $avlpoint;
    $json["sGCardNox"] = $gcardno;
    $json["sCardNmbr"] = $cardnmbr;
    $json["sNmOnCard"] = $nmoncard;
    $json["cCardType"] = $cardtype;
    $json["dMemberxx"] = $dmember;
    $json["nAvlPoint"] = $avlpoint;
    $json["nTotPoint"] = $totlpoint;
    $json["cCardStat"] = $cardstat;
    $app->commitTrans();
    echo json_encode($json);
    return;
}

$rows_found = sizeof($rows);
for($ctr=0; $ctr<$rows_found; $ctr++){
    if($rows[$ctr]["cRecdStat"] != "1"){;
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
                $json["error"]["code"] = $app->getErrorCode();
                $json["error"]["message"] = "Unable to activate USER ACCOUNT INFO! " . $app->getErrorMessage();
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
            
            //mac 2020.08.07            
            if ($newdevce == "0"){
                $json["result"] = "error";
                $json["error"]["code"] = "CNF";
                $json["error"]["message"] = "Someone has registered the card from other device. Do you want to deactivate usage of card from other device?";
                $app->rollbackTrans();
                echo json_encode($json);
                return;
            } else if ($newdevce == "1"){
                $sql = "UPDATE G_Card_App_User_Device" .
                            " SET cRecdStat = '0'" .
                        " WHERE sUserIDxx = " . CommonUtil::toSQL($rows[$ctr]["sUserIDxx"]) .
                            " AND sIMEINoxx = " . CommonUtil::toSQL($rows[$ctr]["sIMEINoxx"]) .
                            " AND sGCardNox = '$gcardno'";
                
                if($app->execute($sql, "G_Card_App_User_Device", "") <= 0){
                    $json["result"] = "error";
                    $json["error"]["code"] = $app->getErrorCode();
                    $json["error"]["message"] = "Unable to DEACTIVATE GCard from other devices! " . $app->getErrorMessage();
                    $app->rollbackTrans();
                    echo json_encode($json);
                    return;
                }
                
                $sql = "INSERT INTO G_Card_App_User_Device" .
                        " SET sUserIDxx = '$userid'" .
                            ", sIMEINoxx = '$pcname'" .
                            ", sMobileNo = '$mobile'" .
                            ", sGCardNox = '$gcardno'" .
                            ", cRecdStat = '1'" . 
                        " ON DUPLICATE KEY UPDATE" .
                            " cRecdStat = '1'";
                if($app->execute($sql, "G_Card_App_User_Device", "") <= 0){
                    $json["result"] = "error";
                    $json["error"]["code"] = $app->getErrorCode();
                    $json["error"]["message"] = "Unable to add GCard to user's account! " . $app->getErrorMessage();
                    $app->rollbackTrans();
                    echo json_encode($json);
                    return;
                }
            }
            //end - mac 2020.08.07
        }
        else{            
            if($rows[$ctr]["sMobileNo"] != $mobile && $mobile != "" &&
                $rows[$ctr]["sUserIDxx"] == $userid &&
                $rows[$ctr]["sIMEINoxx"] == $pcname &&
                $rows[$ctr]["sGCardNox"] == $gcardno){
                
                $sql = "UPDATE G_Card_App_User_Device" .
                            " SET sMobileNo = '$mobile'" .
                        " WHERE sUserIDxx = '$userid'" .
                            " AND sIMEINoxx = '$pcname'" .
                            " AND sGCardNox = '$gcardno'";
                
                if($app->execute($sql, "G_Card_App_User_Device", "") <= 0){
                    $json["result"] = "error";
                    $json["error"]["code"] = $app->getErrorCode();
                    $json["error"]["message"] = "Unable to add mobile to USER ACCOUNT INFO! " . $app->getErrorMessage();
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
$json["sNmOnCard"] = $nmoncard;
$json["cCardType"] = $cardtype;
$json["dMemberxx"] = $dmember;
$json["nAvlPoint"] = $avlpoint;
$json["nTotPoint"] = $totlpoint;
$json["cCardStat"] = $cardstat;
echo json_encode($json);
return;

?>