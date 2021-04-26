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
#$parjson = json_decode($param, true);

//echo $param;

if(!isset($parjson['branch'])){
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::INVALID_PARAMETERS;
    $json["error"]["message"] = "Invalid parameter detected.";
    echo json_encode($json);
    return;
}

//$branch = $parjson['branch'];
$field = '';
$value = '';

//validate what parameter is used by the user
if(isset($parjson['account'])) {
    $value = $parjson['account'];
    $field = 'a.sAcctNmbr';
}
elseif (isset($parjson['name'])){
    $value = $parjson['name'];
    $field = 'b.sCompnyNm';
}
else{
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::INVALID_PARAMETERS;
    $json["error"]["message"] = "Invalid parameter detected.";
    echo json_encode($json);
    return;
}

//$value = mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
//$value = utf8_decode($value);
$token = $validator->getGToken();
$xdata = $token->getData();
$product = $xdata["obj"];
$client = $xdata["clt"];

$gconf = new GDBConfig(APPPATH, $product);
$gconn = new GConn($gconf);

//get the api code of this URL
$apicode = GAnalytics::getAPICode($gconn, $_SERVER['PHP_SELF']);

//echo "GAnalytics::getAPICode <br> \n" ;

//save the analytics of this request...
if(null === GAnalytics::saveAnalytics($gconn, $xdata['logno'], CommonUtil::get_ip_address(), $_SERVER['SERVER_NAME'], $apicode, mb_convert_encoding($param, 'ISO-8859-1', 'UTF-8'))){
    $json["result"] = "error";
    $json["error"]["code"] = $gconn->getErrorCode();
    $json["error"]["message"] = $gconn->getMessage();
    echo json_encode($json);
    return;
}

//echo "GAnalytics::saveAnalytics <br> \n" ;

if(null === GAnalytics::saveViewInfo($gconn, $client, $apicode)){
    $json["result"] = "error";
    $json["error"]["code"] = $gconn->getErrorCode();
    $json["error"]["message"] = $gconn->getMessage();
    echo json_encode($json);
    return;
}

//echo "GAnalytics::saveViewInfo <br> \n" ;

//determine maximum account info request
$max = ($xdata["env"] == 0) ? 30 : 15;

//check if total account info request without saving is more than maximum request...
if(GAnalytics::countViewInfo($gconn, $client, $apicode) > $max){
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::UNUSUAL_DATA_ACCESS;
    $json["error"]["message"] = "Unusual data access detected. Please try again after a few minutes.";
    echo json_encode($json);
    return;
}

//echo "GAnalytics::countViewInfo <br>\n" ;

$sql = "SELECT a.sAcctNmbr, b.sCompnyNm, CONCAT(REPLACE(REPLACE(b.sAddressx, '\r', ''), '\n', ''), ', ', c.sTownName, ', ', d.sProvName) sAddressx, a.nMonAmort" . 
      " FROM MC_AR_Master a" . 
           " LEFT JOIN Client_Master b ON a.sClientID = b.sClientID" .
           " LEFT JOIN TownCity c ON b.sTownIDxx = c.sTownIDxx" .
           " LEFT JOIN Province d ON c.sProvIDxx = d.sProvIDxx" .
      " WHERE $field = '$value'" .   
      " ORDER BY a.dPurchase DESC LIMIT 1";

//        " AND a.cAcctstat = '0'" . 

//echo $field;
//echo $value;
//echo $sql;

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
    $json["error"]["message"] = "Query is not in the database.";
    echo json_encode($json);
    return false;
}

//echo "ANO ANGYARI";
//var_dump($rows);

//$json["payload"]["name"] = utf8_encode($rows[0]["sCompnyNm"]);
//$json["payload"]["address"] = utf8_encode($rows[0]["sAddressx"]);

$json["result"] = "success";
$json["payload"]["account"] = $rows[0]["sAcctNmbr"];
$json["payload"]["name"] = mb_convert_encoding($rows[0]["sCompnyNm"], 'UTF-8', 'ISO-8859-1');
$json["payload"]["address"] = mb_convert_encoding($rows[0]["sAddressx"], 'UTF-8', 'ISO-8859-1');
$json["payload"]["amount"] = $rows[0]["nMonAmort"];
echo json_encode($json, JSON_UNESCAPED_UNICODE );
?>

