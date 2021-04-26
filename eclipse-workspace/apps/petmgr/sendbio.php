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
//$rghtthmb = base64_decode($parjson['rghtthmb']);
//$leftthmb = base64_decode($parjson['leftthmb']);
$rghtthmb = $parjson['rghtthmb'];
$leftthmb = $parjson['leftthmb'];

$sql = "SELECT *" .
      " FROM Employee_ThumbMark" . 
      " WHERE sEmployId = '$employid'";
$rows = $app->fetch($sql);

if($rows == null){
    $sql = "INSERT INTO Employee_ThumbMark" . 
          " SET sEmployId = '$employid'" .
             ", sRghtThmb = '$rghtthmb'" . 
             ", sLeftThmb = '$leftthmb'";
}
else{
    $sql = "UPDATE Employee_ThumbMark" .
          " SET sRghtThmb = '$rghtthmb'" .
             ", sLeftThmb = '$leftthmb'" .
          " WHERE sEmployId = '$employid'";
}
$result = $app->execute($sql);
if($result <= 0){
    $json["result"] = "error";
    $json["error"]["message"] = "Error sending BIO:" . $app->getErrorMessage();
    echo json_encode($json);
    return;
}

$json["result"] = "success";
echo json_encode($json);
    
    
?>