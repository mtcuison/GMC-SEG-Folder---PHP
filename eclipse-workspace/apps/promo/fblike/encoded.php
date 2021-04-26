<?php
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';

if(isset($_GET['brc'])){
    $branchcd = $_GET['brc'];
} else{
    $json["result"] = "error";
    $json["error"]["code"] = "100";
    $json["error"]["message"] = "Invalid Parameter Detected.";
    echo json_encode($json);
    return;
}

if(isset($_GET['typ'])){
    $doctypex = htmlspecialchars($_GET['typ']);
} else {
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Invalid Parameter Detected.";
    echo json_encode($json);
    return;
}

if(isset($_GET['nox'])){
    $docnoxxx = htmlspecialchars($_GET['nox']);
} else {
    $json["result"] = "error";
    $json["error"]["code"] = "102";
    $json["error"]["message"] = "Invalid Parameter Detected.";
    echo json_encode($json);
    return;
}

if(isset($_GET['mob'])){
    $mobileno = htmlspecialchars($_GET['mob']);
    
    if (!CommonUtil::isValidMobile($mobileno)){
        $json["result"] = "error";
        $json["error"]["code"] = "104";
        $json["error"]["message"] = "Invalid Mobile Number Detected.";
        echo json_encode($json);
        return;
    }
} else {
    $json["result"] = "error";
    $json["error"]["code"] = "103";
    $json["error"]["message"] = "Invalid Parameter Detected.";
    echo json_encode($json);
    return;
}

if(isset($_GET['nme'])){
    $clientnm = htmlspecialchars($_GET['nme']);
} else {
    $json["result"] = "error";
    $json["error"]["code"] = "104";
    $json["error"]["message"] = "Invalid Parameter Detected.";
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
    $json["error"]["code"] = $app->getErrorMessage();
    $json["error"]["message"] = $app->getErrorCode();
    echo json_encode($json);
    return;
}
if(!$app->loaduser($prodctid, $userid)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorMessage();
    $json["error"]["message"] = $app->getErrorCode();
    echo json_encode($json);
    return;
}

//validate branch if exists
$sql = "SELECT" .
            "  a.sBranchCd" .
            ", a.sBranchNm" .
            ", b.cPromoDiv" .
        " FROM Branch a" .
            " LEFT JOIN Branch_Others b ON a.sBranchCd = b.sBranchCd" .
        " WHERE a.cRecdStat = '1'" .
            " AND a.sBranchCd = " . CommonUtil::toSQL($branchcd);

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorMessage();
    $json["error"]["message"] = $app->getErrorCode();
    echo json_encode($json);
    return;
} elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorMessage();
    $json["error"]["message"] = $app->getErrorCode();
    echo json_encode($json);
    return;
}

$division = $rows[0]["cPromoDiv"];

if ($division == null){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Invalid division detected.";
    echo json_encode($json);
    return;
}

date_default_timezone_set('Asia/Manila');
$date = new DateTime('now');
$stamp = $date->format(CommonUtil::format_timestamp);

//convert to unix time
$stamp = strtotime($stamp);

//convert to base64
//$stamp = base64_encode($stamp);

switch($division){
    case "0": $nextURL = RAFFLE_URL . "/promo/fblike/fbpage-mp.php?brc="; break;
    case "1": $nextURL = RAFFLE_URL . "/promo/fblike/fbpage-mc.php?brc="; break;
    case "2": $nextURL = RAFFLE_URL . "/promo/fblike/fbpage-cartrade.php?brc="; break;
    case "3": $nextURL = RAFFLE_URL . "/promo/fblike/fbpage-monarch.php?brc="; break;
    case "4": $nextURL = RAFFLE_URL . "/promo/fblike/fbpage-pedritos.php?brc="; break;
    case "5": $nextURL = RAFFLE_URL . "/promo/fblike/fbpage-nissan.php?brc="; break;
    case "6": $nextURL = RAFFLE_URL . "/promo/fblike/fbpage-honda.php?brc="; break;
    default: $nextURL = "";
}

if ($nextURL == ""){
    $json["result"] = "error";
    $json["error"]["code"] = "102";
    $json["error"]["message"] = "No URL found for the branch division.";
    echo json_encode($json);
    return;
} 

$nextURL = $nextURL . base64_encode($branchcd) . "&stmp=" . $stamp;
$nextURL = $nextURL . "&typ=" . base64_encode($doctypex) . "&nox=" . base64_encode($docnoxxx) . "&mob=" . base64_encode($mobileno) . "&nme=" . base64_encode($clientnm);

$json["result"] = "success";
$json["url"] = $nextURL;
echo json_encode($json);
return;
?>