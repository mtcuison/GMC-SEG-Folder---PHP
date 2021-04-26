<?php
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';

$transno = "";
if(isset($_GET['transno'])){
    $transno = $_GET['transno'];
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
            "  CONCAT(a.sLastName, ', ', a.sFrstName, ' ', a.sSuffixNm, ' ', a.sMiddName) sClientNm" .
            ", a.nTemprtre" .
            ", a.nCltAgexx" .
            ", CASE" .
                " WHEN a.cGenderxx = '0' THEN 'Male'" . 
                " WHEN a.cGenderxx = '1' THEN 'Female'" .
                " WHEN a.cGenderxx = '2' THEN 'LGBTQ'" .
                " ELSE 'UNKNOWN'" .
                " END cGenderxx" .
            ", a.sMobileNo" . 
            ", a.sAddressx" . 
            ", CONCAT(b.sTownName, ' ', b.sZippCode, ', ', c.sProvName) sTownName" .
            ", IF(a.cWithSore = '1', 'YES', 'NO') cWithSore" .
            ", IF(a.cWithPain = '1', 'YES', 'NO') cWithPain" .
            ", IF(a.cWithCghx = '1', 'YES', 'NO') cWithCghx" .
            ", IF(a.cWithCold = '1', 'YES', 'NO') cWithCold" .
            ", IF(a.cWithHdch = '1', 'YES', 'NO') cWithHdch" .
            ", IF(a.cStayedxx = '1', 'YES', 'NO') cStayedxx" .
            ", IF(a.cContactx = '1', 'YES', 'NO') cContactx" .
            ", IF(a.cTravelld = '1', 'YES', 'NO') cTravelld" .
            ", IF(a.cTravlNCR = '1', 'YES', 'NO') cTravlNCR" .
            ", a.dSubmittd" .
        " FROM Health_Checklist a" .
            " LEFT JOIN TownCity b ON a.sTownIDxx = b.sTownIDxx" .
            " LEFT JOIN Province c ON b.sProvIDxx = c.sProvIDxx" .
        " WHERE a.sTransNox = " . CommonUtil::toSQL($transno) . 
            " AND a.cRecdStat = '1'";

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage() . "(" . $app->getErrorCode() . ")";
    echo json_encode($json);
    return;
} elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["message"] = "No transaction found.";
    echo json_encode($json);
    return;
}

$date = new DateTime($rows[0]["dSubmittd"]);
$result = $date->format('g:ia \o\n l jS F Y');

$json["result"] = "success";
$json["clientnm"] = mb_convert_encoding($rows[0]["sClientNm"], 'UTF-8', 'ISO-8859-1');
$json["temprtre"] = mb_convert_encoding($rows[0]["nTemprtre"], 'UTF-8', 'ISO-8859-1');
$json["cltagexx"] = mb_convert_encoding($rows[0]["nCltAgexx"], 'UTF-8', 'ISO-8859-1');
$json["genderxx"] = mb_convert_encoding($rows[0]["cGenderxx"], 'UTF-8', 'ISO-8859-1');
$json["mobileno"] = mb_convert_encoding($rows[0]["sMobileNo"], 'UTF-8', 'ISO-8859-1');
$json["addressx"] = mb_convert_encoding($rows[0]["sAddressx"], 'UTF-8', 'ISO-8859-1');
$json["townname"] = mb_convert_encoding($rows[0]["sTownName"], 'UTF-8', 'ISO-8859-1');
$json["withsore"] = mb_convert_encoding($rows[0]["cWithSore"], 'UTF-8', 'ISO-8859-1');
$json["withpain"] = mb_convert_encoding($rows[0]["cWithPain"], 'UTF-8', 'ISO-8859-1');
$json["withcghx"] = mb_convert_encoding($rows[0]["cWithCghx"], 'UTF-8', 'ISO-8859-1');
$json["withcold"] = mb_convert_encoding($rows[0]["cWithCold"], 'UTF-8', 'ISO-8859-1');
$json["withhdch"] = mb_convert_encoding($rows[0]["cWithHdch"], 'UTF-8', 'ISO-8859-1');
$json["stayedxx"] = mb_convert_encoding($rows[0]["cStayedxx"], 'UTF-8', 'ISO-8859-1');
$json["contactx"] = mb_convert_encoding($rows[0]["cContactx"], 'UTF-8', 'ISO-8859-1');
$json["travelld"] = mb_convert_encoding($rows[0]["cTravelld"], 'UTF-8', 'ISO-8859-1');
$json["travlncr"] = mb_convert_encoding($rows[0]["cTravlNCR"], 'UTF-8', 'ISO-8859-1');
$json["submittd"] = mb_convert_encoding($result, 'UTF-8', 'ISO-8859-1');
echo json_encode($json);
return;
?>