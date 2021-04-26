<?php
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';

if(isset($_GET['div'])){
    $division = htmlspecialchars($_GET['div']);
} else {
    $json["result"] = "error";
    $json["error"]["message"] = "Access of this page is not allowed.";
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

//validate branch if exists
$sql = "SELECT" .
            "  sReferCde" .
            ", sReferNme" .
        " FROM FB_Raffle_Transaction_Basis" .
        " WHERE cRecdStat = '1'" .
            " AND sDivision = " . CommonUtil::toSQL($division) .
        " GROUP BY sReferNme" .
        " ORDER BY sReferNme";

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage() . "(" . $app->getErrorCode() . ")";
    echo json_encode($json);
    return;
} elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["message"] = "No raffle basis found for the given division.";
    echo json_encode($json);
    return;
}

$rows_found = sizeof($rows);

$detail = array();
for($ctr=0;$ctr<$rows_found;$ctr++){
    $detail[$ctr]["sReferCde"] = mb_convert_encoding($rows[$ctr]["sReferCde"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sReferNme"] = mb_convert_encoding($rows[$ctr]["sReferNme"], 'UTF-8', 'ISO-8859-1');
}

$json["result"] = "success";
$json["detail"] = $detail;
echo json_encode($json);
return;
?>