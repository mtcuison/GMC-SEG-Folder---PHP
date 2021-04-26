<?php
/* Get the employee information of particular id
 *
 * /system/param/reply_code_request.php
 *
 * mac 2020.12.15
 *  started creating this object.
 */


require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';

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

$param = file_get_contents('php://input');
$parjson = mb_convert_encoding(json_decode($param, true), 'ISO-8859-1', 'UTF-8');

if(is_null($parjson)){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Invalid parameters detected";
    echo json_encode($json);
    return;
}

if(!isset($parjson['sEmployID'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Unset EMPLOYEE ID.";
    echo json_encode($json);
    return;
}

$employid = $parjson['sEmployID'];

//validate branch if exists
$sql = "SELECT" .
        "  a.sEmployID" .
        ", b.sCompnyNm sClientNm" .
        ", c.sDeptName" .
    " FROM Employee_Master001 a" .
            " LEFT JOIN Department c ON a.sDeptIDxx = c.sDeptIDxx" . 
        ", Client_Master b" .
    " WHERE a.sEmployID = b.sClientID" . 
        " AND a.sEmployID = " . CommonUtil::toSQL($employid);

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage() . "(" . $app->getErrorCode() . ")";
    echo json_encode($json);
    return;
} elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["message"] = "No record found.";
    echo json_encode($json);
    return;
}

$json["result"] = "success";
$json["sEmployID"] = mb_convert_encoding($rows[0]["sEmployID"], 'UTF-8', 'ISO-8859-1');
$json["sClientNm"] = mb_convert_encoding($rows[0]["sClientNm"], 'UTF-8', 'ISO-8859-1');
$json["sDeptName"] = mb_convert_encoding($rows[0]["sDeptName"], 'UTF-8', 'ISO-8859-1');
echo json_encode($json);
return;
?>