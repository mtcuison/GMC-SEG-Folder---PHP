<?php
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';

if(!isset($_GET['doctype'])){
    $json["result"] = "error";
    $json["error"]["message"] = "UNSET document type.";
    echo json_encode($json);
    return;
}
if(!isset($_GET['docnmbr'])){
    $json["result"] = "error";
    $json["error"]["message"] = "UNSET document number.";
    echo json_encode($json);
    return;
}

if(!isset($_GET['mobilex'])){
    $json["result"] = "error";
    $json["error"]["message"] = "UNSET mobile number.";
    echo json_encode($json);
    return;
}

$branch = htmlspecialchars($_GET['branch']); //decode this
$stamp = htmlspecialchars($_GET['stamp']); //decode this

$doctype = htmlspecialchars($_GET["doctype"]);
$docnmbr = htmlspecialchars($_GET["docnmbr"]);
$mobilex = htmlspecialchars($_GET["mobilex"]);

//validate document type
if (strlen($doctype) != 2){
    $json["result"] = "error";
    $json["error"]["message"] = "Invalid document type format.";
    echo json_encode($json);
    return;
}

//validate mobile number
if (!CommonUtil::isValidMobile($mobilex)){
    $json["result"] = "error";
    $json["error"]["message"] = "Invalid mobile number format.";
    echo json_encode($json);
    return;
}

if (!is_numeric($docnmbr)){
    $json["result"] = "error";
    $json["error"]["message"] = "Invalid document number.";
    echo json_encode($json);
    return;
}

//account credentials
$prodctid = RAFFLE_PRODUCT;
$userid = RAFFLE_USER;

//initialize application driver
$app = new Nautilus(APPPATH);
if(!$app->LoadEnv($prodctid)) {
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

if(!$app->loaduser($prodctid, $userid)){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

//todo:
//validate branch + timestamp, return error if exists
//validate document parameters return error if exists

$json["result"] = "success";
echo json_encode($json);
return;

//$json["result"] = "error";
//$json["error"]["message"] = $branch . " " . $stamp . " " . $doctype . " " . $docnmbr . " " . $mobilex;
//echo json_encode($json);
?>