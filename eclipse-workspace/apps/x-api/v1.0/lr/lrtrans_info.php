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

$param_field = array("branch", "referno");

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


$branch = $parjson['branch'];
$referno = $parjson['referno'];

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

$apicode = "GAP10002";
$sql = "SELECT sPayloadx, cTranStat" .
       " FROM XAPITrans" . 
       " WHERE sXAPICode = '$apicode'" .
         " AND sReferNox = '$referno'" .
         " AND sClientID = '$client'" .
       " ORDER BY dReceived DESC LIMIT 1";

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

//echo json_decode(mb_convert_encoding($rows[0]["sPayloadx"], 'UTF-8', 'ISO-8859-1'), true);
$data = json_decode(mb_convert_encoding($rows[0]["sPayloadx"], 'UTF-8', 'ISO-8859-1'), true);
//echo "ANO ANGYARI";
//var_dump($data);
$json["result"] = "success";
$json["payload"]["branch"] = $data["branch"];
$json["payload"]["referno"] = $data["referno"];
$json["payload"]["datetime"] = $data["datetime"];
$json["payload"]["account"] = $data["account"];
$json["payload"]["name"] = $data["name"];
$json["payload"]["address"] = $data["address"];
$json["payload"]["amount"] = $data["amount"];
$json["payload"]["status"] = $rows[0]["cTranStat"];
echo json_encode($json, JSON_UNESCAPED_UNICODE );
?>
