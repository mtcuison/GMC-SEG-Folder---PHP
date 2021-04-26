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
    
    
    //login user if log no is not set
    if ($logno == ""){       
        if(!isset($parjson['email'])){
            $json["result"] = "error";
            $json["error"]["message"] = "Unset EMAIL ADDRESS detected.";
            echo json_encode($json);
            return;
        }
        
        //login
        $email = htmlspecialchars($parjson['email']);
        $json["result"] = "error";
        $json["error"]["message"] = $email;
        echo json_encode($json);
        return;
        
        
        if (!$app->Login($email, "", $prodctid, $pcname, "", $clientid)){
            $json["result"] = "error";
            $json["error"]["message"] = $app->getErrorMessage();
            echo json_encode($json);
            return;
        }
    }
    
    $codeapproval = new ApprovalCode($app, null, -1, "GAP0");  
    
    if(!isset($parjson['transnox'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset TRANSACTION NO. detected.";
        echo json_encode($json);
        return;
    }
    
    if(!isset($parjson['reasonxx'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset REASON detected.";
        echo json_encode($json);
        return;
    }
    
    if(!isset($parjson['approved'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset APPROVE STATUS detected.";
        echo json_encode($json);
        return;
    }
    
    $transnox = htmlspecialchars($parjson['transnox']);
    $reasonxx = htmlspecialchars($parjson['reasonxx']);
    $approved = htmlspecialchars($parjson['approved']);
    
    if (strtolower($approved) == "yes"){
        if ($codeapproval->CloseTransaction($transnox, $reasonxx) == true){
            $json["result"] = "success";
			$json["apprcode"] = $codeapproval->getMaster("sApprCode");
        } else{
            $json["result"] = "error";
            $json["error"]["message"] = $codeapproval->getMessage();
        }
    } else{
        if ($codeapproval->CancelTransaction($transnox, $reasonxx) == true){
            $json["result"] = "success";
			$json["apprcode"] = "";
        } else{
            $json["result"] = "error";
            $json["error"]["message"] = $codeapproval->getMessage();
        }
    }
    
    echo json_encode($json);
