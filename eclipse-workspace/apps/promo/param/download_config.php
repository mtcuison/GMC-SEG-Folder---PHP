<?php
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

//validate branch if exists
$sql = "SELECT" .
            "  sConfigCd" .
            ", sConfigDs" .
            ", sConfigVl" .
            ", dTimeStmp" .
        " FROM xxxSysConfig" .
        " WHERE sConfigCd LIKE 'fb.raffle.%'".
        " ORDER BY dTimeStmp";

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage() . "(" . $app->getErrorCode() . ")";
    echo json_encode($json);
    return;
} elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["message"] = "No config found.";
    echo json_encode($json);
    return;
}

$rows_found = sizeof($rows);

$detail = array();
for($ctr=0;$ctr<$rows_found;$ctr++){
    $detail[$ctr]["sConfigCd"] = mb_convert_encoding($rows[$ctr]["sConfigCd"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sConfigDs"] = mb_convert_encoding($rows[$ctr]["sConfigDs"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sConfigVl"] = mb_convert_encoding($rows[$ctr]["sConfigVl"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["dTimeStmp"] = mb_convert_encoding($rows[$ctr]["dTimeStmp"], 'UTF-8', 'ISO-8859-1');
}

$json["result"] = "success";
$json["detail"] = $detail;
echo json_encode($json);
return;
?>