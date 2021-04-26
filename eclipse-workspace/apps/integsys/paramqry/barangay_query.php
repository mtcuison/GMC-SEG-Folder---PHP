<?php
    require_once 'config.php';
    require_once APPPATH.'/core/CommonUtil.php';
    require_once 'WSHeaderValidatorFactory.php';
    include APPPATH.'/core/Nautilus.php';
    include APPPATH.'/lib/integsys/paramqry/KwikSearch.php';
    
    $app = new Nautilus(APPPATH);
    $myheader = apache_request_headers();
    
    $json = array();
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
    
    if($logno == ""){
        $json["result"] = "error";
        $json["error"]["message"] = "Invalid LOG NO detected";
        echo json_encode($json);
        return;
    }
    
    $app = new Nautilus(APPPATH);
    if(!$app->LoadEnv($prodctid)){
        $json["result"] = "error";
        $json["error"]["message"] = $app->getMessage();
        echo json_encode($json);
        return;
    }
    
    if(!$app->validLog($prodctid, $userid, $pcname, $logno, $clientid)){
        $json["result"] = "error";
        $json["error"]["message"] = $app->getMessage();
        echo json_encode($json);
        return;
    }
    
    /**************************************************************************/
    /*  Start coding here
    /**************************************************************************/
    
    $data = file_get_contents('php://input');
    $parjson = json_decode($data, true);
    
    if(is_null($parjson)){
        $json["result"] = "error";
        $json["error"]["message"] = "Invalid parameters detected";
        echo json_encode($json);
        return false;
    }
    
    if(!isset($parjson['townidxx'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset TOWN ID detected";
        echo json_encode($json);
        return false;
    } else {$townid = htmlspecialchars($parjson['townidxx']);}
    
    $bsearch = false;
    if(isset($parjson['bsearch'])){
        $bsearch = htmlspecialchars($parjson['bsearch']);
    }
    
    $bbycode = false;
    $search = "";
    if(isset($parjson['brgyidxx'])){
        $bbycode = true;
        $search = htmlspecialchars($parjson['brgyidxx']);
    } else {
        if(isset($parjson['brgyname'])){
            $search = htmlspecialchars($parjson['brgyname']);
        } else {
            $json["result"] = "error";
            $json["error"]["message"] = "Unset BARANGAY NAME detected";
            echo json_encode($json);
            return false;
        }
    }
    
    $instance = new KwikSearch($app);
   
    $sql = "SELECT" .
                "  sBrgyIDxx brgyidxx" .
                ", sBrgyName brgyname" .
            " FROM Barangay" .
            " WHERE cRecdStat = '1'" .
            " AND sTownIDxx = " . CommonUtil::toSQL($townid) .
            " ORDER BY sBrgyName" .
            " LIMIT 15";
    
    
    if ($bsearch == true){
        $sql = CommonUtil::addCondition($sql, "sBrgyName LIKE " . CommonUtil::toSQL($search . "%"));
    } else {
        if ($bbycode == true){
            $sql = CommonUtil::addCondition($sql, "sBrgyIDxx = " . CommonUtil::toSQL($search));
        }else {
            $sql = CommonUtil::addCondition($sql, "sBrgyName = " . CommonUtil::toSQL($search));
        }
    }
    
    echo $sql;
    //System accepts pre-defined columns to be returned;
    //  if not set, system will return the entire statement fields.
    $result = $instance->Find($sql);
    
    if ($result != null){  
        $json["result"] = "success";
        $json["detail"] = $result;
        echo json_encode($json);
    } else {
        $json["result"] = "error";
        $json["error"]["message"] = $instance->getMessage();
        echo json_encode($json);
    }
?>