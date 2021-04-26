<?php
    require_once 'config.php';
    require_once APPPATH.'/core/CommonUtil.php';
    require_once 'WSHeaderValidatorFactory.php';
    include APPPATH.'/core/Nautilus.php';
    include APPPATH.'/lib/integsys/paramqry/KwikSearch.php';
    include APPPATH.'/lib/integsys/codeapproval/ApprovalCode.php';
    
    
    $app = new Nautilus(APPPATH);
    $myheader = apache_request_headers();
    
    $json = array();
    $validator = (new WSHeaderValidatorFactory())->make($myheader['g-api-id']);
    
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
    
    $app = new Nautilus(APPPATH);
    if(!$app->LoadEnv($prodctid)){
        $json["result"] = "error";
        $json["error"]["message"] = $app->getErrorMessage();
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
    
    if(!isset($parjson['transnox'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset TRANSACITION NO. detected.";
        echo json_encode($json);
        return;
    }
    
    if(!isset($parjson['apprvlcd'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset APPROVAL CODE detected.";
        echo json_encode($json);
        return;
    }
        
    $apprvlcd = htmlentities($parjson['apprvlcd']);
    $transnox = htmlentities($parjson['transnox']);
    
    $sql = "SELECT * FROM System_Code_Approval WHERE sTransNox = '$transnox'";
    
    $rows = $app->fetch($sql);
    
    if($rows === null){
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = "Failed loading approval! " . $app->getErrorMessage();
        echo json_encode($json);
        return;
    }
    if(empty($rows)){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
        $json["error"]["message"] = "There are no approval detected! ";
        echo json_encode($json);
        return;
    }

    $systemcd = $rows[0]["sSystemCD"];
    $reqstdby = $rows[0]["sReqstdBy"];
    $issuedby = $rows[0]["sApprvByx"];
    $reqstdxx = $rows[0]["dReqstdxx"];
    $miscinfo = $rows[0]["sRemarks1"];
    
    if ($logno == ""){
        $codeapproval = new ApprovalCode($app, null, 0, substr($clientid, 5, 8));
    } else {
        $codeapproval = new ApprovalCode($app, null, 0, "GAP0");
    }   
      
    if ($codeapproval->IsValidApprovalCode($apprvlcd, $systemcd, $reqstdby, $issuedby, $reqstdxx, $miscinfo)){                   
        $json["result"] = "success";
    } else {
        $json["result"] = "error";
        $json["error"]["message"] = $codeapproval->getMessage() . "\t" . $apprvlcd . "\t" . $miscinfo;
    };
    
    echo json_encode($json);