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

$sql = "SELECT sGCardNox, cCardType, cCardStat, cDigitalx" .
    " FROM G_Card_Master" .
    " WHERE sCardNmbr = '$cardnmbr'";
$rows = $app->fetch($sql);

if($rows === null){
    //test if sql causes an error or no record was found
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = "Error found during loading..." . $app->getErrorMessage();
        echo json_encode($json);
        return;
}
elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "GCard information not found...";
    echo json_encode($json);
    return;
}

if($parjson["cardstat"] == "1"){
    if($rows[0]["cCardStat"] == "1"){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
        $json["error"]["message"] = "GCard was already PRINTED...";
        echo json_encode($json);
        return;
    }
    
    if($rows[0]["cDigitalx"] == "1"){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
        $json["error"]["message"] = "Digital GCard does not need PRINTING...";
        echo json_encode($json);
        return;
    }
    else{
        if($rows[0]["cCardStat"] != "0"){
            $json["result"] = "error";
            $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
            $json["error"]["message"] = "GCard status is no longer for PRINTING...";
            echo json_encode($json);
            return;
        }
    }
}
elseif($parjson["cardstat"] == "2"){
    if($rows[0]["cCardStat"] == "2"){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
        $json["error"]["message"] = "GCard was already ENCODED...";
        echo json_encode($json);
        return;
    }
    
    if($rows[0]["cDigitalx"] == "1"){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
        $json["error"]["message"] = "Digital GCard does not need ENCODING...";
        echo json_encode($json);
        return;
    }
    else{
        if($rows[0]["cCardStat"] != "1"){
            $json["result"] = "error";
            $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
            $json["error"]["message"] = "GCard status is no longer for ENCODING...";
            echo json_encode($json);
            return;
        }
    }
}
elseif($parjson["cardstat"] == "3"){
    if($rows[0]["cCardStat"] == "3"){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
        $json["error"]["message"] = "GCard was already released...";
        echo json_encode($json);
        return;
    }
    
    if($rows[0]["cDigitalx"] == "1"){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
        $json["error"]["message"] = "Digital GCard does not need RELEASING...";
        echo json_encode($json);
        return;
    }
    else{
        if($rows[0]["cCardStat"] != "2"){
            $json["result"] = "error";
            $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
            $json["error"]["message"] = "GCard status is no longer for RELEASING...";
            echo json_encode($json);
            return;
        }
    }
}
elseif($parjson["cardstat"] == "4"){
    if($rows[0]["cCardStat"] == "4"){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
        $json["error"]["message"] = "GCard was already activated...";
        echo json_encode($json);
        return;
    }
    
    if(strpos("01235", $parjson["cardstat"]) == false){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
        $json["error"]["message"] = "Current GCard status does not allow ACTIVATION..";
        echo json_encode($json);
        return;
    }
}
elseif($parjson["cardstat"] == "5"){
    if($rows[0]["cCardStat"] == "5"){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
        $json["error"]["message"] = "GCard was already SUSPENDED...";
        echo json_encode($json);
        return;
    }
    elseif($parjson["cardstat"] == "6"){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
        $json["error"]["message"] = "GCard was already REPLACED...";
        echo json_encode($json);
        return;
    }
    elseif($parjson["cardstat"] == "7"){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
        $json["error"]["message"] = "GCard was already RENEWED...";
        echo json_encode($json);
        return;
    }
}
elseif($parjson["cardstat"] == "6"){
    if($rows[0]["cCardStat"] == "6"){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
        $json["error"]["message"] = "GCard was already REPLACED...";
        echo json_encode($json);
        return;
    }
    if($rows[0]["cCardStat"] == "7"){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
        $json["error"]["message"] = "GCard was already RENEWED...";
        echo json_encode($json);
        return;
    }
}
elseif($parjson["cardstat"] == "7"){
    if($rows[0]["cCardStat"] == "6"){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
        $json["error"]["message"] = "GCard was already REPLACED...";
        echo json_encode($json);
        return;
    }
    elseif($rows[0]["cCardStat"] == "7"){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_STATUS;
        $json["error"]["message"] = "GCard was already RENEWED...";
        echo json_encode($json);
        return;
    }
}

$gcardnox = $rows[0]["sGCardNox"];

$app->beginTrans();

$date = new DateTime('now');
$stamp = $date->format(CommonUtil::format_timestamp);
$actvte = $date->format(CommonUtil::format_date);

$sql = "UPDATE G_Card_Master" +
" SET cCardStat = " . CommonUtil::toSQL($parjson["cardstat"]) .
    $parjson["cardstat"] != "4" ? "" : ", dActivate = '$actvte'" . 
    ", sModified = '$userid'" . 
    ", dModified = '$stamp'" .
" WHERE sGCardNox = '$gcardnox'";

if($app->execute($sql, "G_Card_Master") <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Unable to update master record. " . $app->getErrorMessage();
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

$sql = "INSERT INTO G_Card_History" . 
      " SET sGCardNox = '$gcardnox'" . 
         ", dTransact = '$actvte'" .
         ", cCardStat = " . CommonUtil::toSQL($parjson["cardstat"]) . 
         ", cTranStat = '0'" .
         ", sModified = '$userid'" .
         ", dModified = '$stamp'";

if($app->execute($sql, "G_Card_History") <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Unable to update GCard History record. " . $app->getErrorMessage();
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

$app->commitTrans();

$json["result"] = "SUCCESS";
echo json_encode($json);

?>
