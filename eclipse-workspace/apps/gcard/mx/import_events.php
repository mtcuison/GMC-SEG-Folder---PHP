<?php
/*
 * /gcard/mx/import_events.php
 * 
 * mac - 2020.06.08 07:04pm
 * use this API placing in requesting link of Events(GuanzonApp).
 * Note:
 */

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

if(isset($myheader['g-api-mobile'])){
    $mobile = $myheader['g-api-mobile'];
}
else{
    $mobile = "";
}

/*
//Assumed that this API is always requested by Android devices
if(!CommonUtil::isValidMobile($mobile)){
    $json["result"] = "error";
    $json["error"]["message"] = "Invalid mobile number detected";
    echo json_encode($json);
    return;
}
*/

$userid		= $myheader['g-api-user'];

$app = new Nautilus(APPPATH);
if(!$app->LoadEnv($prodctid)){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}


if($logno != ""){
    if(!$app->validLog($logno, $prodctid, $clientid, $userid, $pcname)){
        $json["result"] = "error";
        $json["error"]["message"] = $app->getErrorMessage();
        echo json_encode($json);
        return;
    }
}

/*
//Google FCM token
$token = $myheader['g-api-token'];
if(!$app->loaduserClient($prodctid, $userid, $pcname, $token, $mobile)){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}
*/

$detail = array();

$detail[0]["sTransNox"] = "M00120000001";
$detail[0]["sEventTle"] = "Free Service Check Up";
$detail[0]["sBranchNm"] = "GMC Agoo - Honda";
$detail[0]["dEvntFrom"] = "2020-03-20";
$detail[0]["dEvntThru"] = "2020-03-21";
$detail[0]["sAddressx"] = "51 San Jose Norte, Agoo, La Union";
$detail[0]["sEventURL"] = "https://www.guanzongroup.com.ph/events/guanzon-free-service-and-check-up";
$detail[0]["sImageURL"] = "https://www.guanzongroup.com.ph/wp-content/uploads/2020/03/GMC-AGOO-FSCU_FB.jpg";

$detail[1]["sTransNox"] = "M00120000002";
$detail[1]["sEventTle"] = "Guanzon Byaheng Fiesta";
$detail[1]["sBranchNm"] = "GMC Dagupan - Honda";
$detail[1]["dEvntFrom"] = "2020-03-20";
$detail[1]["dEvntThru"] = "2020-03-21";
$detail[1]["sAddressx"] = "";
$detail[1]["sEventURL"] = "https://www.guanzongroup.com.ph/event/byaheng-fiesta-dagupan";
$detail[1]["sImageURL"] = "https://www.guanzongroup.com.ph/wp-content/uploads/2020/03/GMC-DAGUPAN_GBF-FB.jpg";

$detail[2]["sTransNox"] = "M00120000003";
$detail[2]["sEventTle"] = "Guanzon Byaheng Fiesta";
$detail[2]["sBranchNm"] = "GMC Bayambang - Honda";
$detail[2]["dEvntFrom"] = "2020-03-27";
$detail[2]["dEvntThru"] = "2020-03-28";
$detail[2]["sAddressx"] = "";
$detail[2]["sEventURL"] = "https://www.guanzongroup.com.ph/event/byaheng-fiesta-bayambang";
$detail[2]["sImageURL"] = "https://www.guanzongroup.com.ph/wp-content/uploads/2020/03/GMC-BAYAMBANG-GBF_FB.jpg";

$json["result"] = "success";
$json["detail"] = $detail;

echo json_encode($json);
return;

?>