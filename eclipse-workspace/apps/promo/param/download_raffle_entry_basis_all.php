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
            "  sDivision" .
            ", sTableNme" . 
            ", sReferCde" . 
            ", sReferNme" . 
            ", sFieldNme" .
            ", cRecdStat" .
            ", dTimeStmp dModified" . 
        " FROM FB_Raffle_Transaction_Basis" .
        " ORDER BY dTimeStmp";

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage() . "(" . $app->getErrorCode() . ")";
    echo json_encode($json);
    return;
} elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["message"] = "No raffle basis found.";
    echo json_encode($json);
    return;
}

$rows_found = sizeof($rows);

$detail = array();
for($ctr=0;$ctr<$rows_found;$ctr++){
    $detail[$ctr]["sDivision"] = mb_convert_encoding($rows[$ctr]["sDivision"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sTableNme"] = mb_convert_encoding($rows[$ctr]["sTableNme"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sReferCde"] = mb_convert_encoding($rows[$ctr]["sReferCde"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sReferNme"] = mb_convert_encoding($rows[$ctr]["sReferNme"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sFieldNme"] = mb_convert_encoding($rows[$ctr]["sFieldNme"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["cRecdStat"] = mb_convert_encoding($rows[$ctr]["cRecdStat"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["dModified"] = mb_convert_encoding($rows[$ctr]["dModified"], 'UTF-8', 'ISO-8859-1');
}

$json["result"] = "success";
$json["detail"] = $detail;
echo json_encode($json);
return;
?>