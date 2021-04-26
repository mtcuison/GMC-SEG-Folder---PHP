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
    
    //$data = file_get_contents('php://input');
    //$parjson = json_decode($data, true);
    
    $param = file_get_contents('php://input');
    $parjson = mb_convert_encoding(json_decode($param, true), 'ISO-8859-1', 'UTF-8');
    
    if(is_null($parjson)){
        $json["result"] = "error";
        $json["error"]["message"] = "Invalid parameters detected";
        echo json_encode($json);
        return;
    }
        
	$codeapproval = new ApprovalCode($app, null, 0, "GAP0"); 
    
    if ($codeapproval->NewTransaction()){       
        foreach ($parjson as $key => $value){
            switch ($key){
                case "trandate": $codeapproval->setMaster("dTransact", htmlentities($value)); break;
                case "systemcd": $codeapproval->setMaster("sSystemCD", htmlentities($value)); break;
                case "reqstdby": $codeapproval->setMaster("sReqstdBy", htmlentities($value)); break;
                case "reqstdxx": $codeapproval->setMaster("dReqstdxx", htmlentities($value)); break;
                case "miscinfo": $codeapproval->setMaster("sMiscInfo", $value); break;
                case "remarks1": $codeapproval->setMaster("sRemarks1", htmlentities($value)); break;
                case "remarks2": $codeapproval->setMaster("sRemarks2", htmlentities($value)); break;
                case "reqstdto": $codeapproval->setMaster("sReqstdTo", htmlentities($value)); break;
                case "entrybyx": $codeapproval->setMaster("sEntryByx", htmlentities($value)); break;
            }
        }
        
        if ($codeapproval->SaveTransaction() == true){            
            $json["result"] = "success";
            $json["transnox"] = $codeapproval->getMaster("sTransNox");
            $json["branchcd"] = $codeapproval->getMaster("sReqstdBy");
        } else{           
            $json["result"] = "error";
            $json["error"]["message"] = $codeapproval->getMessage();
        }
    } else {
        $json["result"] = "error";
        $json["error"]["message"] = $codeapproval->getMessage();
    };
    echo json_encode($json);