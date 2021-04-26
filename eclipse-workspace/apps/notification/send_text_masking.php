<?php
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

if(!isset($parjson['message'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Unset MESSAGE detected.";
    echo json_encode($json);
    return;
}

$message = $parjson['message'];

if(!isset($parjson['mobileno'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Unset MOBILE NO. detected.";
    echo json_encode($json);
    return;
}

$mobileno = $parjson['mobileno'];

$token = getAccessToken();

if($token == null){
    $json["result"] = "error";
    $json["error"]["code"] = "0001";
    $json["error"]["message"] = "Error updating token.";
    echo json_encode($json);
    return false;
}

$header = array();
$header[] = "Accept: application/json";
$header[] = "Content-Type: application/json";
$header[] = "Authorization: Bearer ".$token; // Prepare the authorisation token

$xparam["message"]["text"] = $message;
$xparam["endpoints"] = array($mobileno);

$url = "https://messagingsuite.smart.com.ph/rest/messages/sms";

$result = WebClient::httpsPostJson($url, json_encode($xparam), $header);

if($result == ""){   
    $json["result"] = "success";
    $json["message"] = "Message sent successfully.";
}
else{
    $token = json_decode($result, true);
    $json["result"] = "error";
    $json["error"]["code"] = $token["code"];
    $json["error"]["message"] = $token["title"] . "/" . $token["type"] . "/" . $token["detail"];
}

echo json_encode($json);

function getAccessToken(){
    $token = json_decode(file_get_contents(APPPATH . "/GGC_Java_Systems/config/smartsuite.token"), true) ;
    $token = json_decode($token, true);
    
    //decode the refreshToken
    $str_token = base64_decode($token["refreshToken"]);
    //clean the token so that we can convert it to json
    $tokens = explode("}.{", str_replace("}{", "}}.{{", $str_token));
    //convert the token to json
    $refresh_token = json_decode(substr($tokens[1], 0, strpos($tokens[1], "}") + 1), true);
    
    $expiry = $refresh_token["iat"] + $token["expiresIn"] - 120;
       
    if($expiry <= time()){
        $header = array();
        $header[] = "Accept: application/json";
        $header[] = "Content-Type: application/json";
        
        $url = "https://messagingsuite.smart.com.ph/rest/auth/login";
        
        $xparam["username"] = "masayson@guanzongroup.com.ph";
        $xparam["password"] = "Gu9nz0nx";
        
        $result = WebClient::httpsPostJson($url, json_encode($xparam), $header);
        
        $token = json_decode($result, true);
        if($token != null){
            $fp = fopen(APPPATH . "/GGC_Java_Systems/config/smartsuite.token", 'w');
            fwrite($fp, json_encode($result));
            fclose($fp);
        }
        
        //reload saved token
        $token = json_decode(file_get_contents(APPPATH . "/GGC_Java_Systems/config/smartsuite.token"), true) ;
        $token = json_decode($token, true);
        
        //decode the refreshToken
        $str_token = base64_decode($token["refreshToken"]);
        //clean the token so that we can convert it to json
        $tokens = explode("}.{", str_replace("}{", "}}.{{", $str_token));
        //convert the token to json
        $refresh_token = json_decode(substr($tokens[1], 0, strpos($tokens[1], "}") + 1), true);
    }
    
    $expiry = $refresh_token["iat"] + $token["expiresIn"] - 120;
    
    if($expiry <= time()){
        return null;
    }
    
    //return the accessToken
    return $token["accessToken"];
}
?>