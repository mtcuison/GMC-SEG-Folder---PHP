<?php
/* Submission of encoded health checklist.
 *
 * /system/health_checklist/checklist_submit.php
 *
 * mac 2020.12.28
 *  started creating this object.
 */

require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';

//init fields
$sTransNox = "";
$sBranchCd = "";
$sOTPNoxxx = "";
$nTemprtre = "";
$sLastName = "";
$sFrstName = "";
$sMiddName = "";
$sSuffixNm = "";
$cGenderxx = "0";
$nCltAgexx = "";
$sMobileNo = "";
$sAddressx = "";
$sTownIDxx = "";
$cWithSore = "0";
$cWithPain = "0";
$cWithCghx = "0";
$cWithCold = "0";
$cWithHdch = "0";
$cStayedxx = "0";
$cContactx = "0";
$cTravelld = "0";
$cTravlNCR = "0";

//validate fields
$param_field = array("sBranchCd", "sOTPNoxxx", "nTemprtre", "sLastName", "sFrstName", "sMiddName", "sSuffixNm", 
                        "cGenderxx", "nCltAgexx", "sMobileNo", "sAddressx", "sTownIDxx", "cWithSore", "cWithPain", 
                        "cWithCghx", "cWithCold", "cWithHdch", "cStayedxx", "cContactx", "cTravelld", "cTravlNCR",
                        "sTransNox");

foreach ($param_field as $value){
    if(!isset($_GET[$value])){
        $json["result"] = "error";
        $json["error"]["message"] = "Invalid parameter detected. ";
        echo json_encode($json);
        return;
    } elseif(empty($_GET[$value])){
        if (substr($value, 0, 1) != "c"){
            $message = "";
            
            switch($value){
                case "sSuffixNm":
                case "sTransNox":
                    break;
                case "nTemprtre":
                    $message = "Temparature must not be empty."; break;
                case "sLastName":
                    $message = "Last name must not be empty."; break;
                case "sFrstName":
                    $message = "First name must not be empty."; break;
                case "sMiddName":
                    $message = "Middle name must not be empty."; break;
                case "cGenderxx":
                    $message = "Gender must not be empty."; break;
                case "nCltAgexx":
                    $message = "Age must not be empty."; break;
                case "sMobileNo":
                    $message = "Mobile number must not be empty."; break;
                case "sAddressx":
                    $message = "Address must not be empty."; break;
                case "sTownIDxx":
                    $message = "Town/city must not be empty."; break;
                default:
                    $message = "Some parameters are invalid. " . $value;
            }
            
            if ($message != ""){
                $json["result"] = "error";
                $json["error"]["message"] = $message;
                echo json_encode($json);
                return;
            }
        }
    }
}

//get fields
$sTransNox = $_GET['sTransNox'];
$sBranchCd = $_GET['sBranchCd'];
$sOTPNoxxx = $_GET['sOTPNoxxx'];
$nTemprtre = $_GET['nTemprtre'];
$sLastName = $_GET['sLastName'];
$sFrstName = $_GET['sFrstName'];
$sMiddName = $_GET['sMiddName'];
$sSuffixNm = $_GET['sSuffixNm'];
$cGenderxx = $_GET['cGenderxx'];
$nCltAgexx = $_GET['nCltAgexx'];
$sMobileNo = $_GET['sMobileNo'];
$sAddressx = $_GET['sAddressx'];
$sTownIDxx = $_GET['sTownIDxx'];
$cWithSore = $_GET['cWithSore'];
$cWithPain = $_GET['cWithPain'];
$cWithCghx = $_GET['cWithCghx'];
$cWithCold = $_GET['cWithCold'];
$cWithHdch = $_GET['cWithHdch'];
$cStayedxx = $_GET['cStayedxx'];
$cContactx = $_GET['cContactx'];
$cTravelld = $_GET['cTravelld'];
$cTravlNCR = $_GET['cTravlNCR'];


//validate other fields
if ($sBranchCd == ""){
    $json["result"] = "error";
    $json["error"]["message"] = "Branch must not be empty.";
    echo json_encode($json);
    return;
}

if (!is_numeric($nTemprtre)){
    $json["result"] = "error";
    $json["error"]["message"] = "Temparature must be numeric.";
    echo json_encode($json);
    return;
} else{
    if ($nTemprtre > 50){
        $json["result"] = "error";
        $json["error"]["message"] = "Maximum temperature reached.";
        echo json_encode($json);
        return;
    }
    
}

if (!is_numeric($nCltAgexx)){
    $json["result"] = "error";
    $json["error"]["message"] = "Age must be numeric.";
    echo json_encode($json);
    return;
}else {
    if ($nCltAgexx > 100){
        $json["result"] = "error";
        $json["error"]["message"] = "Maximum age reached.";
        echo json_encode($json);
        return;
    }
}

$sMobileNo = str_replace("+639", "09", $sMobileNo);

if (!CommonUtil::isValidMobile($sMobileNo)){
    $json["result"] = "error";
    $json["error"]["message"] = "Invalid mobile number.";
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

$sql =  "SELECT" .
            "  b.sTownIDxx" .
            ", CONCAT(b.sTownName, ' ', b.sZippCode, ', ' , a.sProvName) sTownName" .
        " FROM Province a" .
            ", TownCity b" .
        " WHERE a.sProvIDxx = b.sProvIDxx" .
        " AND b.sTownIDxx = " . CommonUtil::toSQL($sTownIDxx);

$rows = $app->fetch($sql);

$sTownName = "N-O-N-E";
if(!empty($rows)){
    $sTownName = $rows[0]["sTownName"];
}
    

date_default_timezone_set('Asia/Manila');
$date = new DateTime('now');
$year = $date->format("y");

$datetime = $date->format(CommonUtil::format_timestamp);

if ($sTransNox == ""){
    $nextcode = CommonUtil::GetNextCode("Health_Checklist", "sTransNox", $year, $app->getConnection(), "MX01");
    
    $sql = "INSERT INTO Health_Checklist SET" .
        "  sTransNox = " . CommonUtil::toSQL($nextcode) .
        ", sBranchcd = " . CommonUtil::toSQL($sBranchCd) .
        ", nTemprtre = " . $nTemprtre .
        ", sLastName = " . CommonUtil::toSQL($sLastName) .
        ", sFrstName = " . CommonUtil::toSQL($sFrstName) .
        ", sMiddName = " . CommonUtil::toSQL($sMiddName) .
        ", sSuffixNm = " . CommonUtil::toSQL($sSuffixNm) .
        ", cGenderxx = " . CommonUtil::toSQL($cGenderxx) .
        ", nCltAgexx = " . $nCltAgexx .
        ", sMobileNo = " . CommonUtil::toSQL($sMobileNo) .
        ", sAddressx = " . CommonUtil::toSQL($sAddressx) .
        ", sTownIDxx = " . CommonUtil::toSQL($sTownIDxx) .
        ", cWithSore = " . CommonUtil::toSQL($cWithSore) .
        ", cWithPain = " . CommonUtil::toSQL($cWithPain) .
        ", cWithCghx = " . CommonUtil::toSQL($cWithCghx) .
        ", cWithCold = " . CommonUtil::toSQL($cWithCold) .
        ", cWithHdch = " . CommonUtil::toSQL($cWithHdch) .
        ", cStayedxx = " . CommonUtil::toSQL($cStayedxx) .
        ", cContactx = " . CommonUtil::toSQL($cContactx) .
        ", cTravelld = " . CommonUtil::toSQL($cTravelld) .
        ", cTravlNCR = " . CommonUtil::toSQL($cTravlNCR) .
        ", sOTPNoxxx = " . CommonUtil::toSQL($sOTPNoxxx) .
        ", cRecdStat = " . CommonUtil::toSQL("0") .
        ", dTransact = " . CommonUtil::toSQL($datetime);
} else {
    $nextcode = $sTransNox;
    
    $sql = "UPDATE Health_Checklist SET" .
                "  nTemprtre = " . $nTemprtre .
                ", sLastName = " . CommonUtil::toSQL($sLastName) .
                ", sFrstName = " . CommonUtil::toSQL($sFrstName) .
                ", sMiddName = " . CommonUtil::toSQL($sMiddName) .
                ", sSuffixNm = " . CommonUtil::toSQL($sSuffixNm) .
                ", cGenderxx = " . CommonUtil::toSQL($cGenderxx) .
                ", nCltAgexx = " . $nCltAgexx .
                ", sMobileNo = " . CommonUtil::toSQL($sMobileNo) .
                ", sAddressx = " . CommonUtil::toSQL($sAddressx) .
                ", sTownIDxx = " . CommonUtil::toSQL($sTownIDxx) .
                ", cWithSore = " . CommonUtil::toSQL($cWithSore) .
                ", cWithPain = " . CommonUtil::toSQL($cWithPain) .
                ", cWithCghx = " . CommonUtil::toSQL($cWithCghx) .
                ", cWithCold = " . CommonUtil::toSQL($cWithCold) .
                ", cWithHdch = " . CommonUtil::toSQL($cWithHdch) .
                ", cStayedxx = " . CommonUtil::toSQL($cStayedxx) .
                ", cContactx = " . CommonUtil::toSQL($cContactx) .
                ", cTravelld = " . CommonUtil::toSQL($cTravelld) .
                ", cTravlNCR = " . CommonUtil::toSQL($cTravlNCR) .
                ", dTransact = " . CommonUtil::toSQL($datetime) . 
            " WHERE sTransNox = " . CommonUtil::toSQL($nextcode);
}

$app->beginTrans();

if($app->execute($sql) <= 0){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage() . $sql;
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

$app->commitTrans();

if ($cGenderxx == "0"){
    $cGenderxx = "Male";
} else if ($cGenderxx == "1"){
    $cGenderxx = "Female";
} else {
    $cGenderxx = "LGBTQ";
}

$json["result"] = "success";
$json["transnox"] = $nextcode;
$json["datetime"] = $datetime;
$json["otp"] = $sOTPNoxxx;

echo json_encode($json);
return;
?>