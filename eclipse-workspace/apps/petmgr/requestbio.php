<?php
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'WSHeaderValidatorFactory.php';

$myheader = apache_request_headers();

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

$app = new Nautilus(APPPATH);
if(!$app->LoadEnv($prodctid)){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

$data = file_get_contents('php://input');

//parse into json the PARAMETERS
$parjson = json_decode($data, true);

$employid = $parjson['employid'];


$sql = "SELECT *" .
    " FROM Employee_ThumbMark" .
    " WHERE sEmployId = '$employid'";
$rows = $app->fetch($sql);

if($rows == null){
    $json["result"] = "error";
//    if($app->getErrorMessage() == null || $app->getErrorMessage() = ""){
        $json["error"]["code"] = "0";
        $json["error"]["message"] = "Record is not yet available";
//    }
//    else{
//        $json["error"]["code"] = "1";
//        $json["error"]["message"] = "Error requesting BIO:" . $app->getErrorMessage();
//    }
    echo json_encode($json);
    return;
}

//$rghtthmb = base64_encode($rows[0]['sRghtThmb']);
//$leftthmb = base64_encode($rows[0]['sLeftThmb']);

$rghtthmb = $rows[0]['sRghtThmb'];
$leftthmb = $rows[0]['sLeftThmb'];

$json["result"] = "success";
$json["detail"] = array();
$json["detail"]["rghtthmb"] = $rghtthmb;
$json["detail"]["leftthmb"] = $leftthmb;
$json["detail"]["employid"] = $employid;

echo json_encode($json);

?>