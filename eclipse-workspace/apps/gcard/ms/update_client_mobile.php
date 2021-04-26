<?php
/* Client Mobile Update API
 * 
 * /gcard/ms/update_client_mobile.php
 * 
 * mac 2020.06.05
 *  started creating this object.
 * */

require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'WebClient.php';
require_once 'WSHeaderValidatorFactory.php';
require_once 'MySQLAES.php';

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
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

if(!$app->loaduser($prodctid, $userid)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}


$param = file_get_contents('php://input');
$parjson = mb_convert_encoding(json_decode($param, true), 'ISO-8859-1', 'UTF-8');

if(is_null($parjson)){
    $json["result"] = "error";
    $json["error"]["message"] = "Invalid parameters detected";
    echo json_encode($json);
    return;
}

if(!isset($parjson['id'])){
    $json["result"] = "error";
    $json["error"]["message"] = "Unset CUSTOMER CLIENT ID detected.";
    echo json_encode($json);
    return;
}

$id = $parjson['id'];

if(!isset($parjson['mobile'])){
    $json["result"] = "error";
    $json["error"]["message"] = "Unset MOBILE NUMBER detected.";
    echo json_encode($json);
    return;
}

$mobile = $parjson['mobile'];
if (substr($mobile, 0, 3) == "+63") $mobile = "0" . substr($mobile, 3);

//is the number a valid mobile?
if(!CommonUtil::isValidMobile($mobile)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_AUTH_MOBILE;
    $json["error"]["message"] = "Invalid mobile number detected.";
    echo json_encode($json);
    return;
}

//check if the mobile number is the set as the client's mobile number in the client master
$sql = "SELECT IFNULL(sMobileNo, '') sMobileNo FROM Client_Master WHERE sClientID = " . CommonUtil::toSQL($id);
$rows = $app->fetch($sql);

//validate client id if valid
if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Error found during loading..." . $app->getErrorMessage();
    echo json_encode($json);
    return;
} elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "No CLIENT found based on the given ID.";
    echo json_encode($json);
    return;
}

$lsCurrent = $rows[0]["sMobileNo"];
$lbCurrent = $lsCurrent == $mobile;
$lbPriority = true;
$lbExists = false;

//database begin transaction
$app->beginTrans();

//check if the mobile was saved on the Client_Mobile table
$sql = "SELECT" .
            "  nPriority" .
            ", sMobileNo" .
        " FROM Client_Mobile" .
        " WHERE sClientID = " . CommonUtil::toSQL($id) .
            " AND sMobileNo = " . CommonUtil::toSQL($mobile);
$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Error found during loading..." . $app->getErrorMessage();
    echo json_encode($json);
    return;
} elseif(empty($rows)){ //the mobile number is not on the client mobile table
    $lbPriority = false;
    
    //check the client mobile table
    $sql = "SELECT" .
                "  nPriority" .
                ", sMobileNo" .
            " FROM Client_Mobile" .
            " WHERE sClientID = " . CommonUtil::toSQL($id);
    
    $rows = $app->fetch($sql);
    $lnRow = 1;
    
    if($rows === null){
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = "Error found during loading..." . $app->getErrorMessage();
        echo json_encode($json);
        return;
    }elseif(empty($rows)){
        if (!$lbCurrent && $lsCurrent != ""){
            //insert the number to the mobile entry list
            $sql = "INSERT INTO Client_Mobile SET" .
                    "  sClientID = " . CommonUtil::toSQL($id);
                    ", nEntryNox = 1" . 
                    ", sMobileNo = " . CommonUtil::toSQL($lsCurrent) .
                    ", nPriority = 2" .
                    ", cIncdMktg = 1" .
                    ", cSubscrbr = " . CommonUtil::toSQL(classifyNetwork($lsCurrent)) .
                    ", cRecdStat = '1'";
            
            if($app->execute($sql, "Client_Mobile") <= 0){
                $json["result"] = "error";
                $json["error"]["code"] = "001";
                $json["error"]["message"] = "Unable to save Client Mobile Entry! ";
                $app->rollbackTrans();
                echo json_encode($json);
                return;
            }
        }
    }
} else{
    $lbPriority = $rows[0]["nPriority"] == 1;
    $lbExists = true;
}

//if the number was not the most priority
if (!$lbPriority){
    //re-arrange the mobile history and save the number to list
    $sql = "SELECT" .
            "  nPriority" .
            ", sMobileNo" .
        " FROM Client_Mobile" .
        " WHERE sClientID = " . CommonUtil::toSQL($id) .
            " AND sMobileNo <> " . CommonUtil::toSQL($mobile) .
        " ORDER BY nPriority DESC";
    $rows = $app->fetch($sql);
    
    if($rows === null){
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = "Error found during loading..." . $app->getErrorMessage();
        echo json_encode($json);
        return;
    }elseif(!empty($rows)){
        $rows_found = sizeof($rows);
        
        //update the old ones to least priority
        for($ctr=0;$ctr<$rows_found;$ctr++){
            $sql = "UPDATE Client_Mobile SET" .
                "  nPriority = " . ($rows_found - $ctr + 1)  .
                " WHERE sClientID = " . CommonUtil::toSQL($id) .
                " AND sMobileNo = " . CommonUtil::toSQL($rows[$ctr]["sMobileNo"]);
            
            $app->execute($sql, "Client_Mobile");
        }
        
        $lnRow = $rows_found;
    }else {
        $lnRow = 0;
    }
    
    
    if ($lbExists){
        //update the priority
        $sql = "UPDATE Client_Mobile SET" .
                    "  nPriority = 1" .
                    ", cIncdMktg = '1'" .
                    ", cRecdStat = '1'" .
                    ", nUnreachx = NULL" .
                    ", dLastVeri = NULL" . 
                    ", dInactive = NULL" .
                    ", nNoRetryx = NULL" . 
                    ", cInvalidx = '0'" .
                    ", cConfirmd = '0'" .
                    ", dConfirmd = NULL" .
                " WHERE sClientID = " . CommonUtil::toSQL($id) .
                    " AND sMobileNo = " . CommonUtil::toSQL($mobile);
    }else {
        //insert the new mobile to the record and set as the most priority
        $sql = "INSERT INTO Client_Mobile SET" .
                    "  sClientID = " . CommonUtil::toSQL($id) .
                    ", nEntryNox = " . ($lnRow + 1) .
                    ", sMobileNo = " . CommonUtil::toSQL($mobile) .
                    ", nPriority = 1" .
                    ", cIncdMktg = '1'" .
                    ", cSubscrbr = " . CommonUtil::toSQL(CommonUtil::getMobileNetwork($mobile)) .
                    ", cRecdStat = '1'";
    }
    
    if($app->execute($sql, "Client_Mobile") <= 0){
        $json["result"] = "error";
        $json["error"]["code"] = "002";
        $json["error"]["message"] = "Unable to save Client Mobile Entry! ";
        $app->rollbackTrans();
        echo json_encode($json);
        return;
    }
    
    //save to client_master as the default number
    if (!$lbCurrent){
        $sql = "UPDATE Client_Master SET" .
            "  sMobileNo = " . CommonUtil::toSQL($mobile) .
            ", sModified = " . CommonUtil::toSQL($app->Env("sUserIDxx")) .
            ", dModified = " . CommonUtil::toSQL(date('Y/m/d H:i:s')) .
            " WHERE sClientID = " . CommonUtil::toSQL($id);
        
        if($app->execute($sql, "Client_Master") <= 0){
            $json["result"] = "error";
            $json["error"]["code"] = "004";
            $json["error"]["message"] = "Unable to save Client Master Entry! ";
            $app->rollbackTrans();
            echo json_encode($json);
            return;
        }
    }
    
    //end database transaction
    $app->commitTrans();
    
    $json["result"] = "success";
    $json["message"] = "Mobile number saved successfully.";
    $json["mobile"] = $mobile;
    echo json_encode($json);
    return;
} else {
    $json["result"] = "success";
    $json["message"] = "Mobile number was currently the most prioritized.";
    echo json_encode($json);
    return;
}
?>