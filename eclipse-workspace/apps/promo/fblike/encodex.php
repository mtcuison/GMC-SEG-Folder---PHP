<?php
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'WebClient.php';
require_once 'WSHeaderValidatorFactory.php';
require_once 'MySQLAES.php';

$param = file_get_contents('php://input');
$parjson = mb_convert_encoding(json_decode($param, true), 'ISO-8859-1', 'UTF-8');

if(is_null($parjson)){
    $json["result"] = "error";
    $json["error"]["code"] = "099";
    $json["error"]["message"] = "Invalid parameters detected";
    echo json_encode($json);
    return;
}

if(!isset($parjson['brc'])){
    $json["result"] = "error";
    $json["error"]["code"] = "100";
    $json["error"]["message"] = "Invalid Parameter Detected.";
    echo json_encode($json);
    return;
}

$branchcd = $parjson['brc'];

if(!isset($parjson['typ'])){
    $json["result"] = "error";
    $json["error"]["code"] = "101";
    $json["error"]["message"] = "Invalid Parameter Detected.";
    echo json_encode($json);
    return;
}

$doctypex = $parjson['typ'];

if(!isset($parjson['nox'])){
    $json["result"] = "error";
    $json["error"]["code"] = "102";
    $json["error"]["message"] = "Invalid Parameter Detected.";
    echo json_encode($json);
    return;
}

$docnoxxx = $parjson['nox'];

if(!isset($parjson['mob'])){
    $json["result"] = "error";
    $json["error"]["code"] = "103";
    $json["error"]["message"] = "Mobile Number is not set."; //
    echo json_encode($json);
    return;
}

$mobileno = $parjson['mob'];

if (!CommonUtil::isValidMobile($mobileno)){
    $json["result"] = "error";
    $json["error"]["code"] = "104";
    $json["error"]["message"] = "Invalid Mobile Number Detected.";
    echo json_encode($json);
    return;
}

if(!isset($parjson['nme'])){
    $json["result"] = "error";
    $json["error"]["code"] = "105";
    $json["error"]["message"] = "Invalid Parameter Detected.";
    echo json_encode($json);
    return;
}

$clientnm = $parjson['nme'];

if(!isset($parjson['twn'])){
    $json["result"] = "error";
    $json["error"]["code"] = "106";
    $json["error"]["message"] = "Invalid Parameter Detected.";
    echo json_encode($json);
    return;
}

$townidxx = $parjson['twn'];

if(!isset($parjson['prv'])){
    $json["result"] = "error";
    $json["error"]["code"] = "107";
    $json["error"]["message"] = "Invalid Parameter Detected.";
    echo json_encode($json);
    return;
}

$providxx = $parjson['prv'];

if(!isset($parjson['add'])){
    $json["result"] = "error";
    $json["error"]["code"] = "108";
    $json["error"]["message"] = "Invalid Parameter Detected.";
    echo json_encode($json);
    return;
}

$addressx = $parjson['add'];

if(!isset($parjson['dte'])){
    $json["result"] = "error";
    $json["error"]["code"] = "109";
    $json["error"]["message"] = "Invalid Parameter Detected.";
    echo json_encode($json);
    return;
}

$trandate = $parjson['dte'];

$entryby = "";
if(isset($parjson['ent'])){
    $entryby = $parjson['ent'];
}

$clientid = "";

if(isset($parjson['cid'])){
    $clientid = $parjson['cid'];
}

$brcdivxx = "";

if(isset($parjson['div'])){
    $brcdivxx = $parjson['div'];
}

$createxxx = "1";

if(isset($parjson['new'])){
    $createxxx = $parjson['new'];
}

//account credentials
$prodctid = RAFFLE_PRODUCT;
$userid = RAFFLE_USER;

//initialize application driver
$app = new Nautilus(APPPATH);
if(!$app->LoadEnv($prodctid)) {
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}
if(!$app->loaduser($prodctid, $userid)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
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
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
} elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = "110";
    $json["error"]["message"] = "Branch info is not detected.";
    echo json_encode($json);
    return;
}

$division = $rows[0]["cPromoDiv"];

if ($division == null){
    $json["result"] = "error";
    $json["error"]["code"] = "111";
    $json["error"]["message"] = "Invalid division detected.";
    echo json_encode($json);
    return;
}

//validate if entry is okay
$sql = "SELECT" .
            "  sTransNox" .
            ", sMobileNo" . 
        " FROM FB_Raffle_Promo_Master" . 
        " WHERE sBranchCd = " . CommonUtil::toSQL($branchcd) .
            " AND dTransact = " . CommonUtil::toSQL($trandate) . 
            " AND sReferCde = " . CommonUtil::toSQL($doctypex) . 
            " AND sReferNox = " . CommonUtil::toSQL($docnoxxx);

$rows = $app->fetch($sql);

date_default_timezone_set('Asia/Manila');
$date = new DateTime('now');
$year = $date->format("y");

if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
} elseif(!empty($rows)){
    $nextcode = $rows[0]["sTransNox"];
    
    if ($rows[0]["sMobileNo"] == $mobileno){
        $createxxx = "0";
    } else {
        $sql = "UPDATE FB_Raffle_Promo_Master SET" .
            "  sMobileNo = " . CommonUtil::toSQL($mobileno) .
            ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp)) .
            " WHERE sTransNox = " . CommonUtil::toSQL($nextcode);
        
        $app->execute($sql);
    }
} else {
    $nextcode = CommonUtil::GetNextCode("FB_Raffle_Promo_Master", "sTransNox", $year, $app->getConnection(), "MX01");
    
    $sql = "INSERT INTO FB_Raffle_Promo_Master SET" .
            "  sTransNox = " . CommonUtil::toSQL($nextcode) .
            ", dTransact = " . CommonUtil::toSQL($trandate) .
            ", sBranchCd = " . CommonUtil::toSQL($branchcd) .
            ", cDivision = " . CommonUtil::toSQL($brcdivxx) .
            ", sClientID = " . CommonUtil::toSQL($clientid) .
            ", sClientNm = " . CommonUtil::toSQL(str_replace("'", "", $clientnm)) .
            ", sReferCde = " . CommonUtil::toSQL($doctypex) .
            ", sReferNox = " . CommonUtil::toSQL($docnoxxx) .
            ", sMobileNo = " . CommonUtil::toSQL($mobileno) .
            ", sAddressx = " . CommonUtil::toSQL(str_replace("'", "", $addressx)) .
            ", sTownIDxx = " . CommonUtil::toSQL($townidxx) .
            ", sProvIDxx = " . CommonUtil::toSQL($providxx) .
            ", nEntryNox = 0" .
            ", sEntryByx = " . CommonUtil::toSQL($entryby) .
            ", cInvalidx = '0'" . 
            ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp));
    
    if($app->execute($sql) <= 0){
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = $app->getErrorMessage() . $sql;
        echo json_encode($json);
        return;
    }
    
    $createxxx = "1";
}

switch($division){
    case "0": $nextURL = RAFFLE_URL . "/promo/fblike/mp.php?ref="; break;
    case "1": $nextURL = RAFFLE_URL . "/promo/fblike/mc.php?ref="; break;
    case "2": $nextURL = RAFFLE_URL . "/promo/fblike/cartrade.php?ref="; break;
    case "3": $nextURL = RAFFLE_URL . "/promo/fblike/monarch.php?ref="; break;
    case "4": $nextURL = RAFFLE_URL . "/promo/fblike/pedritos.php?ref="; break;
    case "5": $nextURL = RAFFLE_URL . "/promo/fblike/nissan.php?ref="; break;
    case "6": $nextURL = RAFFLE_URL . "/promo/fblike/honda.php?ref="; break;
    default: $nextURL = "";
}

if ($nextURL == ""){
    $json["result"] = "error";
    $json["error"]["code"] = "112";
    $json["error"]["message"] = "No URL found for the branch division.";
    echo json_encode($json);
    return;
}

$nextURL = $nextURL . base64_encode($nextcode);

//create hotline outgoing
if ($createxxx == "1") createOutgoing($nextcode, $docnoxxx, $nextURL, $mobileno, $app);

$json["result"] = "success";
$json["url"] = $nextURL;
echo json_encode($json);
return;

function createOutgoing($transno, $refer, $url, $mobileno, $app){
    date_default_timezone_set('Asia/Manila');
    $date = new DateTime('now');
    $year = $date->format("y");
    
    $message = "Thank you for your purchase! Promo link for receipt " . $refer . " is: " .
                $url;
    
    $tranx = CommonUtil::GetNextCode("HotLine_Outgoing", "sTransNox", $year, $app->getConnection(), "MX01");
                
    $sql = "INSERT INTO HotLine_Outgoing SET" .
            "  sTransNox = " . CommonUtil::toSQL($tranx) .
            ", dTransact = " . CommonUtil::toSQL($date->format(CommonUtil::format_date)) .
            ", sDivision = 'MP'" .
            ", sMobileNo = " . CommonUtil::toSQL($mobileno) .
            ", sMessagex = " . CommonUtil::toSQL($message) .
            ", cSubscrbr = " . CommonUtil::toSQL(CommonUtil::getMobileNetwork($mobileno)) .
            ", dDueUntil = " . CommonUtil::toSQL($date->format(CommonUtil::format_date)) .
            ", cSendStat = '2'" .
            ", nNoRetryx = '0'" .
            ", sUDHeader = ''" .
            ", sReferNox = " . CommonUtil::toSQL($transno) .
            ", sSourceCd = " . CommonUtil::toSQL("APPX") .
            ", cTranStat = '0'" .
            ", nPriority = 0" .
            ", sModified = " . CommonUtil::toSQL("fbpromo") .
            ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp));
    
    if($app->execute($sql) <= 0){
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = $app->getErrorMessage();
        echo json_encode($json);
        return false;
    }
    
    $textsms = sendAsOthers("GUANZON", $message, $mobileno);
    
    if($textsms["result"] == "success"){
        $sql = "UPDATE HotLine_Outgoing SET cTranStat = '1' WHERE sTransNox = " . CommonUtil::toSQL($tranx);
        
        if($app->execute($sql) <= 0){
            $json["result"] = "error";
            $json["error"]["code"] = $app->getErrorCode();
            $json["error"]["message"] = $app->getErrorMessage() . $sql;
            echo json_encode($json);
            return false;
        }
    } else{
        echo json_encode($textsms);
        return false;
    }
    
    return true;
}

function sendAsOthers($maskname, $message, $mobileno){
    $header = array();
    $header[] = "Accept: application/json";
    $header[] = "Content-Type: application/json";
    
    $xparam = array();
    $xparam["username"] = MASK_USER1;
    $xparam["password"] = MASK_PASS1;
    $xparam["text"] = $message;
    $xparam["destination"] = $mobileno;
    $xparam["source"] = $maskname;
    
    $url = "https://messagingsuite.smart.com.ph/cgphttp/servlet/sendmsg";
    
    $result = WebClient::httpRequest("GET", $url, $xparam, $header);
    $result = str_replace("\r\n", "", $result);
    
    if (strpos($result, 'Message-ID:') !== false) {
        $json["result"] = "success";
        $json["message"] = "Message sent successfully.";
        $json["maskname"] = $maskname;
        $json["id"] = substr($result, 20);
        return $json;
        return;
    } else {
        $json["result"] = "error";
        $json["error"]["code"] = substr($result, 0, 5);
        $json["error"]["message"] = substr($result, 14);
        return $json;
    }
}
?>