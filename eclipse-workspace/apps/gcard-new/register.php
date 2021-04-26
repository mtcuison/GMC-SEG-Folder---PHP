<?php 
/*******************************************************************************
 *  Sign Up API for Guanzon App
 *  ----------------------------------------------------------------------------   
 *  RMJ Business Solutions
 *  ----------------------------------------------------------------------------
 *  iMac [2019-06-12]
 *      Started creating API.
*******************************************************************************/

    require_once 'config.php';
    require_once APPPATH.'/core/CommonUtil.php';
    require_once 'WSHeaderValidatorFactory.php';
    include APPPATH.'/core/Nautilus.php';
    include APPPATH.'/core/DBUtil.php';
    include APPPATH.'/core/MySQLAES.php';
    include APPPATH.'/lib/gcard/SignUp.php';

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
    $pcname = $myheader['g-api-imei'];
    //SysClient ID
    $clientid = $myheader['g-api-client'];
    //Log No
    $logno = $myheader['g-api-log'];
    //User ID
    $userid = $myheader['g-api-user'];
    
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
    
    /**
     * START CODING HERE
     * 
     * Required parameters
     *      name
     *      birthday
     *      mobileno
     *      email
     *      password
     *      gcardno
     *      iemi
     * TODO:
     *      where do I save the mobile number
     *          can i save it as GCard_App_Device
     *      where do I use the birthday
     */
    
    $data = file_get_contents('php://input');
    $parjson = json_decode($data, true);
    
    if(is_null($parjson)){
        $json["result"] = "error";
        $json["error"]["message"] = "Invalid parameters detected";
        echo json_encode($json);
        return false;
    }
    
    if(!isset($parjson['name'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset NAME detected.";
        echo json_encode($json);
        return;
    }
    
    if(!isset($parjson['birthday'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset BIRTHDATE detected.";
        echo json_encode($json);
        return;
    }
    
    if(!isset($parjson['mobileno'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset MOBILE NO. detected.";
        echo json_encode($json);
        return;
    }
    
    if(!isset($parjson['email'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset EMAIL ADDRESS detected.";
        echo json_encode($json);
        return;
    }
    
    if(!isset($parjson['password'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset PASSWORD detected.";
        echo json_encode($json);
        return;
    }
    
    if(!isset($parjson['gcardno'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Unset GCARD NO. detected.";
        echo json_encode($json);
        return;
    }
    
    
    //assign parameters to variables
    $name = htmlspecialchars($parjson['name']);
    $mobileno = htmlspecialchars($parjson['mobileno']);
    $email = htmlspecialchars($parjson['email']);
    $password = htmlspecialchars($parjson['password']);
    $gcardno = htmlspecialchars($parjson['gcardno']);
    
    //initiate class
    $instance = new SignUp($app, null, "GAP0");
    $mysqlAES = new MySQLAES("20190625");
    
    if ($instance->NewTransaction()){
        $instance->setMaster("sClientNm", $name);
        $instance->setMaster("sMobileNo", $mobileno);
        $instance->setMaster("sEmailAdd", $email);
        $instance->setMaster("sPassword", $mysqlAES->encrypt($password));
        $instance->setMaster("sCardNmbr", $gcardno);       
        
        if ($instance->SaveTransaction() == true){
            $json["result"] = "success";
            
            if ($instance->LoadTransaction($instance->getMaster("sTransNox"))){
                $json["transno"] = $instance->getMaster("sTransNox");
                $json["name"] = $instance->getMaster("sClientNm");
                $json["gcardno"] = $instance->getMaster("sCardNmbr");
                $json["created"] = $instance->getMaster("dModified");
            } else {
                $json["result"] = "error";
                $json["error"]["message"] = $instance->getMessage();
            }
             
        } else{
            $json["result"] = "error";
            $json["error"]["message"] = $instance->getMessage();
        }
    } else {
        $json["result"] = "error";
        $json["error"]["message"] = $instance->getMessage();
    }
    
    echo json_encode($json);
?>