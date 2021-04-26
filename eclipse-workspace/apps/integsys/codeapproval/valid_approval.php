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
    
    if(!isset($parjson['apprvlcd'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset APPROVAL CODE detected.";
        echo json_encode($json);
        return;
    }
    
    if(!isset($parjson['systemcd'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset SYSTEM CODE detected.";
        echo json_encode($json);
        return;
    }
    
    if(!isset($parjson['reqstdby'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset REQUESTING BRANCH detected.";
        echo json_encode($json);
        return;
    }
    
    if(!isset($parjson['issuedby'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset ISSUEE detected.";
        echo json_encode($json);
        return;
    }
    
    if(!isset($parjson['reqstdxx'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset REQUESTED DATE detected.";
        echo json_encode($json);
        return;
    }
    
    if(!isset($parjson['miscinfo'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset MISCELLENEOUS INFO detected.";
        echo json_encode($json);
        return;
    }
    
    $apprvlcd = htmlentities($parjson['apprvlcd']);
    $systemcd = htmlentities($parjson['systemcd']);
    $reqstdby = htmlentities($parjson['reqstdby']);
    $issuedby = htmlentities($parjson['issuedby']); //initially, userid will be passed by the user
    $reqstdxx = htmlentities($parjson['reqstdxx']);
    $miscinfo = $parjson['miscinfo'];
    
    if ($logno == ""){
        $codeapproval = new ApprovalCode($app, null, 0, substr($clientid, 5, 8));
    } else {
        $codeapproval = new ApprovalCode($app, null, 0, "GAP0");
    }   
      
    if ($codeapproval->IsValidApprovalCode($apprvlcd, $systemcd, $reqstdby, $issuedby, $reqstdxx, $miscinfo)){                   
        $json["result"] = "success";
    } else {
        $json["result"] = "error";
        $json["error"]["message"] = $codeapproval->getMessage();
    };
    
    echo json_encode($json);