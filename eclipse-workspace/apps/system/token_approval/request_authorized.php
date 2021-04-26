<?php
/* Request authorized personnel to approved the token approval request.
 *
 * /system/token_approval/request_authorized.php
 *
 * mac 2020.11.27
 *  started creating this object.
 */

require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'WebClient.php';
require_once 'WSHeaderValidatorFactory.php';
require_once 'MySQLAES.php';
require_once 'Tokenize.php';


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
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Invalid parameters detected";
    echo json_encode($json);
    return;
}

if(!isset($parjson['sRqstType'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Unset REQUEST TYPE.";
    echo json_encode($json);
    return;
}

$rqsttype = $parjson['sRqstType'];

$levelxxx = 0;

if(isset($parjson['nLevelxxx'])){
    if (!is_numeric($parjson['nLevelxxx'])){
        $json["result"] = "error";
        $json["error"]["code"] = "101";
        $json["error"]["message"] = "USER LEVEL is not numeric.";
        echo json_encode($json);
        return;
    }
    
    $levelxxx = $parjson['nLevelxxx'];
}

$sql = "SELECT" . 
            "  sEmployID" . 
            ", sMobileNo" . 
            ", sAuthCode" . 
            ", sAuthTokn" . 
        " FROM System_Code_Mobile" .
        " WHERE sAuthCode LIKE " . CommonUtil::toSQL("%" . $rqsttype . "%") . 
            " AND IFNULL(sAuthTokn, '') <> ''";

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Error found during loading..." . $app->getErrorMessage();
    echo json_encode($json);
    return;
} elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "No record found.";
    echo json_encode($json);
    return;
}

$detail = array();
$rows_found = sizeof($rows);
$row = 0;

for($ctr=0;$ctr<$rows_found; $ctr++){
    if ($levelxxx > 0) {
        //decrypt the auth token to get the level
        $sql = Tokenize::DecryptToken($rows[$ctr]["sAuthTokn"], $rows[$ctr]["sEmployID"]);
        $arr = explode(":", $sql);
                
        //check if the level is equal or greater than the requested level
        if ($arr[2] >= $levelxxx){
            $detail[$row]["sEmployID"] = $rows[$ctr]["sEmployID"];
            $detail[$row]["sMobileNo"] = $rows[$ctr]["sMobileNo"];
            $detail[$row]["sAuthTokn"] = $rows[$ctr]["sAuthTokn"];
            $row += 1;
        }
    } else {
        $detail[$row]["sEmployID"] = $rows[$ctr]["sEmployID"];
        $detail[$row]["sMobileNo"] = $rows[$ctr]["sMobileNo"];
        $detail[$row]["sAuthTokn"] = $rows[$ctr]["sAuthTokn"];
        $row += 1;
    }
}

$json["result"] = "success";
$json["detail"] = $detail;
echo json_encode($json);
