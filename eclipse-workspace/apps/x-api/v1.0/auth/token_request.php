<?php
require_once 'x-api/v1.0/api-config.php';
require_once 'x-api/v1.0/api-const.php';
require_once 'x-api/v1.0/GDBConfig.php';
require_once 'x-api/v1.0/GConn.php';
require_once 'x-api/v1.0/GAuth.php';
require_once 'x-api/v1.0/GToken.php';

use xapi\config\v100\APIErrCode;;
use xapi\core\v100\GDBConfig;
use xapi\core\v100\GConn;
use xapi\core\v100\GAuth;
use xapi\core\v100\GToken;

$myheader = apache_request_headers();

$json = array();
if(!isset($myheader['g-api-id'])){
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::INVALID_PRODUCT_ID;
    $json["error"]["message"] = "Product key not set";
    echo json_encode($json);
    return;
}

if(!isset($myheader['g-api-user'])){
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::INVALID_USER_ID;
    $json["error"]["message"] = "User key not set";
    echo json_encode($json);
    return;
}

if(!isset($myheader['g-api-client'])){
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::INVALID_CLIENT_ID;
    $json["error"]["message"] = "Company key not set";
    echo json_encode($json);
    return;
}

$product = $myheader['g-api-id'];
$client = $myheader['g-api-client'];
$userid = $myheader['g-api-user'];

$gconf = new GDBConfig(APPPATH, $myheader['g-api-id']);
$gconn = new GConn($gconf);
$gauth = new GAuth($gconn);

if(!$gauth->loadClientInfo($product, $client, $userid)){
    $json["result"] = "error";
    $json["error"]["code"] = $gauth->getErrorCode();
    $json["error"]["message"] = $gauth->getMessage();
    echo json_encode($json);
    return;
}

$date = new DateTime("now");

$token = new GToken();
$token->setAudience($gauth->getClientName());
$token->setIssuer(APP_DOMAIN);
$token->setIssued($gauth->getLicenseDate());
$date->add(new DateInterval('PT10S'));
$token->setNotBefore($date->format("Y-m-d H:i:s"));

$xdata = array();
$xdata["obj"] = $gauth->getProductID();
$xdata["user"] = $gauth->getUserID();
$xdata["clt"] = $gauth->getClientID();
$xdata["exp"] = $gauth->getLicenseExpiry();
$xdata["token"] = "0";
$xdata["env"] = $gauth->getLicenseType();
$token->setData($xdata);

if(!$token->encode()){
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::INVALID_TOKEN;
    $json["error"]["message"] = $token->getMessage();
    echo json_encode($json);
    return;
}

$json["result"] = "success";
$json["payload"]["token"] = $token->getToken();
echo json_encode($json);

//echo "Hello";
?>