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
            "  a.sBranchCd" .
            ", a.sBranchNm" .
            ", b.cPromoDiv" .
            ", b.sAreaCode" .
            ", a.cRecdStat" .
            ", a.dTimeStmp" .
        " FROM Branch a" .
            ", Branch_Others b" .
        " WHERE a.sBranchCd = b.sBranchCD" .
            " AND b.cPromoDiv IN ('0', '1', '2', '3', '4', '5', '6')" .
        " ORDER BY a.dTimeStmp";
//" AND IFNULL(b.sAreaCode, '') <> ''" .

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
    $detail[$ctr]["sBranchCd"] = mb_convert_encoding($rows[$ctr]["sBranchCd"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sBranchNm"] = mb_convert_encoding($rows[$ctr]["sBranchNm"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["cPromoDiv"] = mb_convert_encoding($rows[$ctr]["cPromoDiv"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["sAreaCode"] = mb_convert_encoding($rows[$ctr]["sAreaCode"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["cRecdStat"] = mb_convert_encoding($rows[$ctr]["cRecdStat"], 'UTF-8', 'ISO-8859-1');
    $detail[$ctr]["dTimeStmp"] = mb_convert_encoding($rows[$ctr]["dTimeStmp"], 'UTF-8', 'ISO-8859-1');
}

$json["result"] = "success";
$json["detail"] = $detail;
echo json_encode($json);
return;
?>