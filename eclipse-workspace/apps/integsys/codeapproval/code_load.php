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
    
    if($logno == ""){
        $json["result"] = "error";
        $json["error"]["message"] = "Invalid LOG NO detected";
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
    
    if(!$app->validLog($prodctid, $userid, $pcname, $logno, $clientid, true)){
        $json["result"] = "error";
        $json["error"]["message"] = $app->getErrorMessage()();
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
    
    $codeapproval = new ApprovalCode($app, null, 0);  
    
    if(!isset($parjson['systemcd'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset SYSTEM CODE detected.";
        echo json_encode($json);
        return;
    }
    
    if(!isset($parjson['branchcd'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset BRANCH CODE detected.";
        echo json_encode($json);
        return;
    }
    
    if(!isset($parjson['transnox'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset TRANSACTION NO. detected.";
        echo json_encode($json);
        return;
    }
    
    $systemcd = htmlspecialchars($parjson['systemcd']);
    $branchcd = htmlspecialchars($parjson['branchcd']);
    $transnox = htmlspecialchars($parjson['transnox']);
    
                 
    if ($codeapproval->LoadTransaction($systemcd, $branchcd, $transnox) == true){
        $json["result"] = "success";
        $json["detail"]["transnox"] = $codeapproval->getMaster("sTransNox");
        $json["detail"]["trandate"] = $codeapproval->getMaster("dTransact");
        $json["detail"]["systemcd"] = $codeapproval->getMaster("sSystemCD");
        $json["detail"]["reqstdby"] = $codeapproval->getMaster("sReqstdBy");
        $json["detail"]["reqstdxx"] = $codeapproval->getMaster("dReqstdxx");
        $json["detail"]["miscinfo"] = mb_convert_encoding(CommonUtil::Hex2String($codeapproval->getMaster("sRemarks1")), "UTF-8", "ISO-8859-1");
        $json["detail"]["remarks1"] = $codeapproval->getMaster("sRemarks1");
        $json["detail"]["remarks2"] = $codeapproval->getMaster("sRemarks2");
        $json["detail"]["rqstinfo"] = $codeapproval->getMaster("xBranchNm");
    } else{
        $json["result"] = "error";
        $json["error"]["message"] = $codeapproval->getMessage();
    }
		
    echo json_encode($json);
