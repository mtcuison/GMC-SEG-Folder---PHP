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
    
    $userid		= $myheader['g-api-user'];
    
    $app = new Nautilus(APPPATH);
    if(!$app->LoadEnv($prodctid)){
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = $app->getErrorMessage();
        echo json_encode($json);
        return;
    }

    $sql = "SELECT * FROM xxxSysObjectSub" .
            " WHERE sProdctID = 'IntegSys'" .
                " AND sProjctID = 'GuanzonApp'";
    
    $rows = $app->fetch($sql);
    
    if($rows === null){
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = "Failed retreiving version! " . $app->getErrorMessage();
        echo json_encode($json);
        return;
    }
    
    if(empty($rows)){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
        $json["error"]["message"] = "There is no application on that type detected! ";
        echo json_encode($json);
        return;
    }
    
    $version = $rows["sVersionx"];
    $splittd = explode("»", $version);
    
    $version = $splittd[0];
    $updtlvl = $splittd[1];
    
    $json["result"] = "success";
    $json["version"] = $version;
    $json["level"] = $updtlvl;
    echo json_encode($json);
?>