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
            "  sClientID" .
            ", sClientNm" .
            ", sAddressx" .
            ", sTownName" .
            ", sZippCode" .
            ", sProvName" .
            ", sTelNoxxx" .
            ", sFaxNoxxx" .
            ", sApproved" .
            ", sBranchCd" .
            ", dImportxx" .
            ", dExportxx" .
            ", sExportNo" .
            ", sServerIP" .
            ", vTimeStmp" . 
        " FROM xxxSysClient" .
        " ORDER BY vTimeStmp";

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage() . "(" . $app->getErrorCode() . ")";
    echo json_encode($json);
    return;
} elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["message"] = "No branches found.";
    echo json_encode($json);
    return;
}

$rows_found = sizeof($rows);

$detail = array();
for($ctr=0;$ctr<$rows_found;$ctr++){
    $detail[$ctr]["sClientID"] = mb_convert_encoding($rows[$ctr]["sClientID"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sClientNm"] = mb_convert_encoding($rows[$ctr]["sClientNm"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sAddressx"] = mb_convert_encoding($rows[$ctr]["sAddressx"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sTownName"] = mb_convert_encoding($rows[$ctr]["sTownName"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sZippCode"] = mb_convert_encoding($rows[$ctr]["sZippCode"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sProvName"] = mb_convert_encoding($rows[$ctr]["sProvName"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sTelNoxxx"] = mb_convert_encoding($rows[$ctr]["sTelNoxxx"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sFaxNoxxx"] = mb_convert_encoding($rows[$ctr]["sFaxNoxxx"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sApproved"] = mb_convert_encoding($rows[$ctr]["sApproved"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sBranchCd"] = mb_convert_encoding($rows[$ctr]["sBranchCd"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["dImportxx"] = mb_convert_encoding($rows[$ctr]["dImportxx"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["dExportxx"] = mb_convert_encoding($rows[$ctr]["dExportxx"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sExportNo"] = mb_convert_encoding($rows[$ctr]["sExportNo"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sServerIP"] = mb_convert_encoding($rows[$ctr]["sServerIP"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["vTimeStmp"] = mb_convert_encoding($rows[$ctr]["vTimeStmp"], 'UTF-8', 'ISO-8859-1');
}

$json["result"] = "success";
$json["detail"] = $detail;
echo json_encode($json);
return;
?>