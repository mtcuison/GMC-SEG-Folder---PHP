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
        $json["error"]["message"] = $app->getErrorMessage();
        echo json_encode($json);
        return;
    }
    
    /*if(!$app->validLog($prodctid, $userid, $pcname, $logno, $clientid)){
        $json["result"] = "error";
        $json["error"]["message"] = $app->getErrorMessage();
        echo json_encode($json);
        return;
    }*/
    
    if ($logno != ""){
        if(!$app->validLog($prodctid, $userid, $pcname, $logno, $clientid, true)){
            $json["result"] = "error";
            $json["error"]["message"] = $app->getErrorMessage() . " bakit ey?";
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
        
    $scatypex = "";
    if(isset($parjson['scatypex'])){
        $scatypex = htmlspecialchars($parjson['scatypex']);
    } else {
        $json["result"] = "error";
        $json["error"]["message"] = "Unset SCA TYPE detected";
        echo json_encode($json);
        return false;
    }
    
    $emplevid = "";
    if(isset($parjson['emplevid'])){
        $emplevid = htmlspecialchars($parjson['emplevid']);
    } else {
        $json["result"] = "error";
        $json["error"]["message"] = "Unset EMPLOYEE LEVEL ID detected";
        echo json_encode($json);
        return false;
    }
    
    $deptidxx = "";
    if(isset($parjson['deptidxx'])){
        $deptidxx = htmlspecialchars($parjson['deptidxx']);
    } else {
        $json["result"] = "error";
        $json["error"]["message"] = "Unset DEPARTMENT ID detected";
        echo json_encode($json);
        return false;
    }
    
    $positnid = "";
    if(isset($parjson['positnid'])){
        $positnid = htmlspecialchars($parjson['positnid']);
    } else {
        $json["result"] = "error";
        $json["error"]["message"] = "Unset POSITION ID detected";
        echo json_encode($json);
        return false;
    }
    
    $instance = new KwikSearch($app);
   
    $sql = "SELECT" .
                "  sSCACodex scacodex" .
                ", sSCATitle scatitle" .
                ", cSCATypex scatypex" .
            " FROM xxxSCA_Request" .
            " WHERE cSCATypex = " . CommonUtil::toSQL($scatypex)  .
                " AND cRecdStat = '1'" . 
            " ORDER BY sSCATitle";
    
    $lsCondition = "";
    if ($emplevid == "4"){
        //area head
        $lsCondition = "cAreaHead = '1'";
    } else {
        switch ($deptidxx){
            case "021": //hcm
                $lsCondition = "cHCMDeptx = '1'"; break;
            case "022": //css
                $lsCondition = "cCSSDeptx = '1'"; break;
            case "034": //cm
                $lsCondition = "cComplnce = '1'"; break;
            case "025": //m&p
                $lsCondition = "cMktgDept = '1'"; break;
            case "027": //asm
                $lsCondition = "cASMDeptx = '1'"; break;
            case "035": //tele
                $lsCondition = "cTLMDeptx = '1'"; break;
            case "024": //scm
                $lsCondition = "cSCMDeptx = '1'"; break;
            case "026": //mis
                 break;
            case "015": //sales
                if ($positnid == "091"){
                    //field specialist
                    $lsCondition = "sSCACodex = 'CA'";
                } else {
                    $lsCondition = "0=1";
                }
                break;
            default: $lsCondition = "0=1";
        }
    }
    
    if ($lsCondition != ""){
        $sql = CommonUtil::addcondition($sql, $lsCondition);
    }
    
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