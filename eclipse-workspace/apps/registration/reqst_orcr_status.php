<?php
    require_once 'config.php';
    include APPPATH.'/core/Nautilus.php';
    include APPPATH.'/lib/registration/ORCRStatus.php';
    
    $app = new Nautilus(APPPATH);
    $myheader = apache_request_headers();

    if(!$app->isHeaderOk($myheader)){
        $json["result"] = "error";
        $json["result"] = array();
        $json["error"]["message"] = $app->getErrorMessage();
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
    $logno = $myheader['g-api-log'];	
    //User ID
    $userid = $myheader['g-api-user'];	

    if($logno == ""){
        $json["result"] = "error";
        $json["result"] = array();
        $json["error"]["message"] = "Invalid LOG NO detected";
        echo json_encode($json);			
        return;		
    }
    
    if(!$app->Reload($clientid, $pcname, $prodctid, $userid)){
        $json["result"] = "error";
        $json["result"] = array();
        $json["error"]["message"] = $app->getErrorMessage();
        echo json_encode($json);
        return;		
    }
    
    if(!$app->validproduct($clientid, $pcname, $prodctid)){
        $json["result"] = "error";
        $json["result"] = array();
        $json["error"]["message"] = $app->getErrorMessage();
        echo json_encode($json);
        return;		
    }        

    if(!$app->validLog($logno, $prodctid, $clientid, $userid, $pcname)){
        $json["result"] = "error";
        $json["result"] = array();
        $json["error"]["message"] = $app->getErrorMessage();
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
        $json["result"] = array();
        $json["error"]["message"] = "Invalid parameters detected";
        echo json_encode($json);
        return false;
    }
    
    if(!isset($parjson['card-no'])){
        $json["result"] = "error";
        $json["result"] = array();
        $json["error"]["message"] = "Unset CARD NO. detected";
        echo json_encode($json);
        return false;
    }
    
    $cardno = htmlspecialchars($parjson['card-no']);
    
    $instance = new ORCRStatus($app);

    $result = $instance->Request($cardno);
    
    if ($result != null){  
        $json["result"] = "success";
        $json["detail"] = $result;
        echo json_encode($json);
    } else {
        $json["result"] = "error";
        $json["result"] = array();
        $json["error"]["message"] = $instance->getMessage();
        echo json_encode($json);
    }
?>
