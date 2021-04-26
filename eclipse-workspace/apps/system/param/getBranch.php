<?php
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';

$branchcd = "";
if(isset($_GET['branchcd'])){
    $branchcd = $_GET['branchcd'];
} else {
    $json["result"] = "error";
    $json["error"]["message"] = "Invalid parameter.";
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
$sql =  "SELECT" .
            "  a.sBranchCd" .
            ", a.sBranchNm" .
            ", IFNULL(a.sAddressx, '-') sAddressx" .
            ", CONCAT(b.sTownName, ', ', c.sProvName) sTownCity" .
            ", a.sTelNumbr" . 
            ", a.sEMailAdd" . 
        " FROM Branch a" .
            " LEFT JOIN TownCity b" .
                " ON a.sTownIDxx = b.sTownIDxx" .
            " LEFT JOIN Province c" . 
                " ON b.sProvIDxx = c.sProvIDxx" . 
        " WHERE a.cRecdStat = '1'" .
            " AND a.sBranchCd = " . CommonUtil::toSQL($branchcd);

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage() . "(" . $app->getErrorCode() . ")";
    echo json_encode($json);
    return;
} elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["message"] = "No branch found.";
    echo json_encode($json);
    return;
}


$json["result"] = "success";
$json["branchcd"] = mb_convert_encoding($rows[0]["sBranchCd"], 'UTF-8', 'ISO-8859-1');
$json["branchnm"] = mb_convert_encoding($rows[0]["sBranchNm"], 'UTF-8', 'ISO-8859-1');
$json["addressx"] = mb_convert_encoding($rows[0]["sAddressx"], 'UTF-8', 'ISO-8859-1');
$json["towncity"] = mb_convert_encoding($rows[0]["sTownCity"], 'UTF-8', 'ISO-8859-1');
$json["landline"] = mb_convert_encoding($rows[0]["sTelNumbr"], 'UTF-8', 'ISO-8859-1');
$json["emailadd"] = mb_convert_encoding($rows[0]["sEMailAdd"], 'UTF-8', 'ISO-8859-1');
echo json_encode($json);
return;
?>