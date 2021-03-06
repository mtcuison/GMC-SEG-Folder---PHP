<?php    
    require_once 'config.php';
    require_once 'Nautilus.php';
    require_once 'CommonUtil.php';
    require_once 'WSHeaderValidatorFactory.php';
    
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
    $pcname = $myheader['g-api-imei'];
    //SysClient ID
    $clientid = $myheader['g-api-client'];
    //Log No
    $logno 		= $myheader['g-api-log'];
    //User ID
    $userid		= $myheader['g-api-user'];
    
    $app = new Nautilus(APPPATH);
    if(!$app->LoadEnv($prodctid)){
        $json["result"] = "error";
        $json["error"]["message"] = $app->getErrorMessage()();
        echo json_encode($json);
        return;
    }
    
    if ($logno != ""){
        if(!$app->validLog($prodctid, $userid, $pcname, $logno, $clientid, true)){
            $json["result"] = "error";
            $json["error"]["message"] = $app->getErrorMessage();
            echo json_encode($json);
            return;
        }
    }
    
    $param = file_get_contents('php://input');
    $parjson = mb_convert_encoding(json_decode($param, true), 'ISO-8859-1', 'UTF-8');
    
    if(is_null($parjson)){
        $json["result"] = "error";
        $json["error"]["message"] = "Invalid parameters detected.";
        echo json_encode($json);
        return false;
    }
    
    $bsearch = false;
    if(isset($parjson['bsearch'])){
        $bsearch = htmlspecialchars($parjson['bsearch']);
    }
    
    $bbycode = false;
    $search = "";
    if(isset($parjson['id'])){
        $bbycode = true;
        $search = htmlspecialchars($parjson['id']);
    } else {
        if(isset($parjson['descript'])){
            $search = htmlspecialchars($parjson['descript']);
        } else {
            $json["result"] = "error";
            $json["error"]["message"] = "Unset DESCRIPTION detected";
            echo json_encode($json);
            return false;
        }
    }
    
    //timestamp of the most updated record.
    $timestmp = "";
    if(isset($parjson['timestamp'])){
        $bbycode = true;
        $timestmp = htmlspecialchars($parjson['timestamp']);
    }
    
    $sql = "SELECT" .
                "  sAreaCode" .
                ", sAreaDesc" .
            " FROM Branch_Area" .
            " WHERE cRecdStat = '1'";
    
    //search all town
    if (strtolower($search) == "all"){
        $search = "%";
        $bsearch = true;   
    }
    
    if ($search == ""){
        $json["result"] = "error";
        $json["error"]["message"] = "Search value is empty.";
        echo json_encode($json);
        return false;
    }
    
    if ($bsearch == true){
        $sql = CommonUtil::addCondition($sql, "sAreaDesc LIKE " . CommonUtil::toSQL($search . "%"));
    } else {
        if ($bbycode == true){
            $sql = CommonUtil::addCondition($sql, "sAreaCode = " . CommonUtil::toSQL($search));
        }else {
            $sql = CommonUtil::addCondition($sql, "sAreaDesc = " . CommonUtil::toSQL($search));
        }
    }
    
    //check for latest updates
    if ($timestmp != "")
        $sql = CommonUtil::addCondition($sql, "a.dTimeStmp > " . CommonUtil::toSQL($timestmp));
    
    $rows = $app->fetch($sql);
    
    if($rows === null){
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = "Failed loading Branch Info.! " . $app->getErrorMessage();
        echo json_encode($json);
        return;
    }
    
    if(empty($rows)){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
        $json["error"]["message"] = "There are no Branch record detected! ";
        echo json_encode($json);
        return;
    }
    
    $rows_found = sizeof($rows);
    
    $detail = array();
    for($ctr=0;$ctr<$rows_found;$ctr++){
        $detail[$ctr]["sAreaCode"] = mb_convert_encoding($rows[$ctr]["sAreaCode"], 'UTF-8', 'ISO-8859-1');
        $detail[$ctr]["sAreaDesc"] = mb_convert_encoding($rows[$ctr]["sAreaDesc"], 'UTF-8', 'ISO-8859-1');
    }
    
    $json["result"] = "success";
    $json["detail"] = $detail;
    echo json_encode($json);
    return;
?>