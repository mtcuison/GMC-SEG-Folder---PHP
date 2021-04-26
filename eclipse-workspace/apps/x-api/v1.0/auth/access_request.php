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
use xapi\core\v100\GAuth;
//use xapi\core\v100\GToken;
use xapi\core\v100\GDBConfig;
use xapi\core\v100\GConn;
use xapi\core\v100\TokenValidator;
use xapi\core\v100\GAnalytics;

$myheader = apache_request_headers();

$json = array();
if(!isset($myheader['g-client-key'])){
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::UNAUTHORIZED_ACCESS;
    $json["error"]["message"] = "Invalid authorization key detected.";
    echo json_encode($json);
    return;
}

$jwt = $myheader['g-client-key'];

$validator = new TokenValidator(null);

if(!$validator->isValidClientKey($jwt)){
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::UNAUTHORIZED_ACCESS;
    $json["error"]["message"] = $validator->getMessage();
    echo json_encode($json);
    return;
}

//echo "Dito ba #1-b";

//load the client info
//we need this so that we can set the expiry date in our payload
$token = $validator->getGToken();
/*
if(!$token->decode($jwt)){
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::UNAUTHORIZED_ACCESS;
    $json["error"]["message"] = $token->getMessage();
    echo json_encode($json);
    return;
}
*/

//get the data from the token here...
$xdata = $token->getData();
$product = $xdata["obj"];
$client = $xdata["clt"];
$userid = $xdata["user"];

$gconf = new GDBConfig(APPPATH, $product);
$gconn = new GConn($gconf);
$gauth = new GAuth($gconn);
if(!$gauth->loadClientInfo($product, $client, $userid)){
    $json["result"] = "error";
    $json["error"]["code"] = $gauth->getErrorCode();
    $json["error"]["message"] = $gauth->getMessage();
    echo json_encode($json);
    return;
}

//get the current date
$date = new DateTime("now");

//check access key expiration date...
if(null !== $exp = $gauth->getLicenseExpiry()){
    if($exp < $date->format("u")){
        $json["result"] = "error";
        $json["error"]["code"] = APIErrCode::EXPIRED_CLIENT_KEY;
        $json["error"]["message"] = "Expired license key for client detected...";
        echo json_encode($json);
        return;
    }
}

//change the value for the header...
$date->add(new DateInterval('PT10S'));
$token->setNotBefore($date->format("Y-m-d H:i:s"));
$date->add(new DateInterval('PT30M'));
$token->setExpiry($date->format("Y-m-d H:i:s"));

//change the value for the payload
$xdata["token"] = "1";
$xdata["exp"] = $gauth->getLicenseExpiry();
//echo "Dito ba #1";

if(null === $xdata["logno"] = GAnalytics::getLogNo($gconn, $product, $client, $userid)){
    $json["result"] = "error";
    $json["error"]["code"] = $gconn->getErrorCode();
    $json["error"]["message"] = $gconn->getMessage();
    echo json_encode($json);
    return;
}

//$xdata["logno"] = "GAP19000001";
$token->setData($xdata);
//echo "Dito ba #2";

//encode data into JWT
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

//$token->decode($token->getToken());

?>