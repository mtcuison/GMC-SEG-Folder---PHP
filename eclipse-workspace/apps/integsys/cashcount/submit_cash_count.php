<?php
    require_once 'config.php';
    require_once APPPATH.'/core/CommonUtil.php';
    require_once APPPATH.'/core/DBUtil.php';
    require_once 'WSHeaderValidatorFactory.php';
    include APPPATH.'/core/Nautilus.php';
    include APPPATH.'/lib/integsys/paramqry/KwikSearch.php';
    include APPPATH.'/lib/integsys/cashcount/CashCount.php';
    
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
    //var_dump($myheader);
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
    
    $app = new Nautilus(APPPATH);
    if(!$app->LoadEnv($prodctid)){
        $json["result"] = "error";
        $json["error"]["message"] = $app->getMessage();
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
    
    //$instance = new CashCount($app, null, 0);
    
    if ($logno == ""){
        //get branch code of the client
        $kwiksearch = new KwikSearch($app);
        $branchcd = $kwiksearch->Find("SELECT sBranchCd FROM xxxSysClient WHERE sClientID = " . CommonUtil::toSQL($clientid));        
        
        $instance = new CashCount($app, null, 0, $branchcd[0]["sBranchCd"]);
    } else {
        $instance = new CashCount($app, null, 0, $app->Env("sBranchCD"));
    } 
                    
    if ($instance->NewTransaction()){
        $oldtran = "";
        
        foreach ($parjson as $key => $value){                           
            //validate user input
            switch (strtolower($key)){
            case "cn0001cx":
            case "cn0005cx":
            case "cn0025cx":
            case "cn0001px":
            case "cn0005px":
            case "cn0010px":
            case "nte0020p":
            case "nte0050p":
            case "nte0100p":
            case "nte0200p":
            case "nte0500p":
            case "nte1000p":
                if (!is_numeric($value)){
                    $json["result"] = "error";
                    $json["error"]["message"] = "Input for $value must be numeric.";
                    echo json_encode($json);
                    return false;
                }
                break;
            case "ornoxxxx":
            case "sinoxxxx":
            case "prnoxxxx":
            case "crnoxxxx":
                if(strlen($value) > 10){
                    $json["result"] = "error";
                    $json["error"]["message"] = "Input for $value exceeds the max length.";
                    echo json_encode($json);
                    return false;
                }
            }

            switch (strtolower($key)){
            case "transnox": $oldtran = $value; break;
            case "trandate": $instance->setMaster("dTransact", $value); break;
            case "cn0001cx": $instance->setMaster("nCn0001cx", $value); break;
            case "cn0005cx": $instance->setMaster("nCn0005cx", $value); break;
            case "cn0025cx": $instance->setMaster("nCn0025cx", $value); break;
            case "cn0001px": $instance->setMaster("nCn0001px", $value); break;
            case "cn0005px": $instance->setMaster("nCn0005px", $value); break;
            case "cn0010px": $instance->setMaster("nCn0010px", $value); break;
            case "nte0020p": $instance->setMaster("nNte0020p", $value); break;
            case "nte0050p": $instance->setMaster("nNte0050p", $value); break;
            case "nte0100p": $instance->setMaster("nNte0100p", $value); break;
            case "nte0200p": $instance->setMaster("nNte0200p", $value); break;
            case "nte0500p": $instance->setMaster("nNte0500p", $value); break;
            case "nte1000p": $instance->setMaster("nNte1000p", $value); break;
            case "ornoxxxx": $instance->setMaster("sORNoxxxx", $value); break;
            case "sinoxxxx": $instance->setMaster("sSINoxxxx", $value); break;
            case "prnoxxxx": $instance->setMaster("sPRNoxxxx", $value); break;
            case "crnoxxxx": $instance->setMaster("sCRNoxxxx", $value); break;
            case "entrytme": $instance->setMaster("dEntryDte", $value); break;
            case "reqstdid": $instance->setMaster("sReqstdBy", $value);
            }
        }

        if ($instance->SaveTransaction() == true){
            $json["result"] = "success";
            $json["realnox"] = $instance->getMaster("sTransNox");
            $json["transno"] = $oldtran;
            $json["received"] = $instance->getMaster("dReceived");
        } else{
            $json["result"] = "error";
            $json["error"]["message"] = $instance->getMessage();
        }
    }
                
    echo json_encode($json);
?>
