<?php
/* Entry of OTP for encoded health checklist.
 *
 * /system/health_checklist/checklist_otp_entry.php
 *
 * mac 2020.12.28
 *  started creating this object.
 */
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';

date_default_timezone_set('Asia/Manila');

$sTransNox = "";
$sOTPNoxxx = "";//validate fields

$param_field = array("sOTPNoxxx", "sTransNox");

foreach ($param_field as $value){
    if(!isset($_GET[$value])){
        $json["result"] = "error";
        $json["error"]["message"] = "Invalid parameter detected. " . $value;
        echo json_encode($json);
        return;
    } elseif(empty($_GET[$value])){
        $json["result"] = "error";
        $json["error"]["message"] = "Some parameters are invalid. " . $value;
        echo json_encode($json);
        return;
    }
}

$sTransNox = $_GET['sTransNox'];
$sOTPNoxxx = $_GET['sOTPNoxxx'];

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

$sql =  "SELECT sOTPNoxxx FROM Health_Checklist WHERE sTransNox = " . CommonUtil::toSQL($sTransNox);

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage() . "(" . $app->getErrorCode() . ")";
    echo json_encode($json);
    return;
} elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["message"] = "No checklist found for the given transaction.";
    echo json_encode($json);
    return;
}

if ($rows[0]["sOTPNoxxx"] != $sOTPNoxxx){
    $json["result"] = "error";
    $json["error"]["message"] = "OTP did not matched.";
    echo json_encode($json);
    return;
}

date_default_timezone_set('Asia/Manila');
$date = new DateTime('now');
$year = $date->format("y");

$datetime = $date->format(CommonUtil::format_timestamp);

$sql = "UPDATE Health_Checklist SET" .
            "  cRecdStat = '1'" . 
            ", dSubmittd = " . CommonUtil::toSQL($datetime) . 
        " WHERE sTransNox = " . CommonUtil::toSQL($sTransNox);

$app->beginTrans();

if($app->execute($sql) <= 0){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage() . $sql;
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

$app->commitTrans();

$json["result"] = "success";
echo json_encode($json);
return;
