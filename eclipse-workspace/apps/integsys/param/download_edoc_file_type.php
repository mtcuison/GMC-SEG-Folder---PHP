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
    
    if(!isset($parjson['deptidxx'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Invalid parameters detected.";
        echo json_encode($json);
        return false;
    }
    
    $deptidxx = $parjson['deptidxx'];
    
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
                "  b.sFileCode" .
                ", b.sBarrcode" .
                ", b.sBriefDsc" .
                ", a.nEntryNox" .
                ", a.cRecdStat" . 
                ", a.dTimeStmp" .
            " FROM EDocSys_Department_File a" .
                " LEFT JOIN EDocSys_File b" .
                " ON a.sFileCode = b.sFileCode" .
            " WHERE a.sDeptIDxx = " . CommonUtil::toSQL($deptidxx) .
                " AND a.cRecdStat = '1'" .                
            " ORDER BY a.nEntryNox";
    
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
        $sql = CommonUtil::addCondition($sql, "b.sBriefDsc LIKE " . CommonUtil::toSQL($search . "%"));
    } else {
        if ($bbycode == true){
            $sql = CommonUtil::addCondition($sql, "a.sFileCode = " . CommonUtil::toSQL($search));
        }else {
            $sql = CommonUtil::addCondition($sql, "b.sBriefDsc = " . CommonUtil::toSQL($search));
        }
    }
    
    //check for latest updates
    if ($timestmp != "")
        $sql = CommonUtil::addCondition($sql, "dTimeStmp > " . CommonUtil::toSQL($timestmp));
    
    $rows = $app->fetch($sql);
    
    if($rows === null){
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = "Failed loading Religion Info.! " . $app->getErrorMessage();
        echo json_encode($json);
        return;
    }
    
    if(empty($rows)){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
        $json["error"]["message"] = "There are no Religion record detected! ";
        echo json_encode($json);
        return;
    }
    
    $rows_found = sizeof($rows);
    
    $detail = array();
    for($ctr=0;$ctr<$rows_found;$ctr++){
        $detail[$ctr]["sFileCode"] = mb_convert_encoding($rows[$ctr]["sFileCode"], 'UTF-8', 'ISO-8859-1');
        $detail[$ctr]["sBarrcode"] = mb_convert_encoding($rows[$ctr]["sBarrcode"], 'UTF-8', 'ISO-8859-1');
        $detail[$ctr]["sBriefDsc"] = mb_convert_encoding($rows[$ctr]["sBriefDsc"], 'UTF-8', 'ISO-8859-1');
        $detail[$ctr]["nEntryNox"] = $rows[$ctr]["nEntryNox"];
        $detail[$ctr]["cRecdStat"] = mb_convert_encoding($rows[$ctr]["cRecdStat"], 'UTF-8', 'ISO-8859-1');
        $detail[$ctr]["dTimeStmp"] = mb_convert_encoding($rows[$ctr]["dTimeStmp"], 'UTF-8', 'ISO-8859-1');
    }
    
    $json["result"] = "success";
    $json["detail"] = $detail;
    echo json_encode($json);
    return;
?>