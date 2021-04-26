<?php
require_once 'x-api/v1.0/api-config.php';
require_once 'x-api/v1.0/api-const.php';

require_once 'x-api/v1.0/GAuth.php';
//require_once 'x-api/v1.0/GToken.php';
require_once 'x-api/v1.0/GDBConfig.php';
require_once 'x-api/v1.0/GConn.php';
require_once 'x-api/v1.0/TokenValidator.php';
require_once 'x-api/v1.0/GAnalytics.php';

use xapi\config\v100\APIErrCode;
//use xapi\core\v100\GAuth;
//use xapi\core\v100\GToken;
use xapi\core\v100\GDBConfig;
use xapi\core\v100\GConn;
use xapi\core\v100\TokenValidator;
use xapi\core\v100\GAnalytics;
use xapi\core\v100\CommonUtil;

$myheader = apache_request_headers();

//perform the initial checking of header
$json = array();
if(!isset($myheader['g-access-token'])){
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::UNAUTHORIZED_ACCESS;
    $json["error"]["message"] = "Invalid authorization key detected.";
    echo json_encode($json);
    return;
}

$jwt = $myheader['g-access-token'];
$validator = new TokenValidator(null);

if(!$validator->isValidAccessKey($jwt)){
    $json["result"] = "error";
    $json["error"]["code"] = $validator->getErrorCode();
    $json["error"]["message"] = $validator->getMessage();
    echo json_encode($json);
    return;
}

//check if parameters are valid
$param = file_get_contents('php://input');

//parse into json the PARAMETERS
$parjson = mb_convert_encoding(json_decode($param, true), 'ISO-8859-1', 'UTF-8');

$param_field = array("branch", "referno", "datetime", "account", "name", "address", "mobile", "amount");


foreach ($param_field as $value){
    if(!isset($parjson[$value])){        
        $json["result"] = "error";
        $json["error"]["code"] = APIErrCode::INVALID_PARAMETERS;
        $json["error"]["message"] = "Invalid parameter detected." . $value;
        echo json_encode($json);
        return;
    }
    elseif(empty($parjson[$value])){
        $json["result"] = "error";
        $json["error"]["code"] = APIErrCode::INVALID_PARAMETERS;
        $json["error"]["message"] = "Some parameters are invalid."  . $value;
        echo json_encode($json);
        return;
    }
}

if(null === CommonUtil::toDate($parjson['datetime'])){
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::INVALID_PARAMETERS;
    $json["error"]["message"] = "Invalid date parameter detected.";
    echo json_encode($json);
    return;
}

if(0 >= CommonUtil::toDecimal($parjson['amount'])){
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::INVALID_PARAMETERS;
    $json["error"]["message"] = "Invalid amount parameter detected.";
    echo json_encode($json);
    return;
}

$referno = $parjson['referno'];
$account = $parjson['account'];

$token = $validator->getGToken();
$xdata = $token->getData();
$product = $xdata["obj"];
$client = $xdata["clt"];
$logno = $xdata["logno"];

$gconf = new GDBConfig(APPPATH, $product);
$gconn = new GConn($gconf);

//get the api code of this URL
$apicode = GAnalytics::getAPICode($gconn, $_SERVER['PHP_SELF']);

//save the analytics of this request...
if(null === GAnalytics::saveAnalytics($gconn, $xdata['logno'], CommonUtil::get_ip_address(), $_SERVER['SERVER_NAME'], $apicode, mb_convert_encoding($param, 'ISO-8859-1', 'UTF-8'))){
    $json["result"] = "error";
    $json["error"]["code"] = $gconn->getErrorCode();
    $json["error"]["message"] = $gconn->getMessage();
    echo json_encode($json);
    return;
}

$sql = "SELECT a.sAcctNmbr, b.sCompnyNm, CONCAT(REPLACE(REPLACE(b.sAddressx, '\r', ''), '\n', ''), ', ', c.sTownName, ', ', d.sProvName) sAddressx, a.nMonAmort" .
    " FROM MC_AR_Master a" .
    " LEFT JOIN Client_Master b ON a.sClientID = b.sClientID" .
    " LEFT JOIN TownCity c ON b.sTownIDxx = c.sTownIDxx" .
    " LEFT JOIN Province d ON c.sProvIDxx = d.sProvIDxx" .
    " WHERE a.sAcctNmbr = '$account'" .
    " ORDER BY a.dPurchase DESC LIMIT 1";

//    " AND a.cAcctstat = '0'" .

if(null === $rows = $gconn->fetch($sql)){
    $json["result"] = "error";
    $json["error"]["code"] = $gconn->getErrorCode();
    $json["error"]["message"] = $gconn->getMessage();
    echo json_encode($json);
    return;
}
elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "Account is not in the database.";
    echo json_encode($json);
    return false;
}

if(null === $result = GAnalytics::saveAPITrans($gconn, $logno, $client, $apicode, $referno, mb_convert_encoding($param, 'ISO-8859-1', 'UTF-8'))){
    $json["result"] = "error";
    $json["error"]["code"] = $gconn->getErrorCode();
    $json["error"]["message"] = $gconn->getMessage();
    echo json_encode($json);
    return;
}

if($result == false){
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::DUPLICATE_DATA;
    $json["error"]["message"] = "Duplicate error detected";
    echo json_encode($json);
    return;
}

//set the API Code of lraccount_info.php
$apicode = "GAP10001";
if(null === GAnalytics::resetViewInfo($gconn, $client, $apicode)){
    $json["result"] = "error";
    $json["error"]["code"] = $gconn->getErrorCode();
    $json["error"]["message"] = $gconn->getMessage();
    echo json_encode($json);
    return;
}

$json["result"] = "success";
echo json_encode($json);

?>
