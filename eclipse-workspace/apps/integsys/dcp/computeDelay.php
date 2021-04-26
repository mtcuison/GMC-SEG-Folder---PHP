<?php 
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'WebClient.php';
require_once 'WSHeaderValidatorFactory.php';
require_once 'MySQLAES.php';

$myheader = apache_request_headers();

if(!isset($myheader['g-api-id'])){
    echo "anggapoy nagawaan ti labat awa!";
    return;
}

if(stripos(APPSYSX, $myheader["g-api-id"]) === false){
    echo "anto la ya... sika lamet!";
    return;
}

//default-request charset
$chr_rqst = "UTF-8";
if(isset($myheader['g-char-request'])){
    $chr_rqst = $myheader['g-char-request'];
}
header("Content-Type: text/html; charset=$chr_rqst");

$validator = (new WSHeaderValidatorFactory())->make($myheader['g-api-id']);
$json = array();
if(!$validator->isHeaderOk($myheader)){
    $json["result"] = "error";
    $json["error"]["code"] = $validator->getErrorCode();
    $json["error"]["message"] = $validator->getMessage();
    echo json_encode($json);
    return;
}

//GET HEADERS HERE
//Product ID
$prodctid = $myheader['g-api-id'];
//Computer Name / IEMI No
$pcname	= $myheader['g-api-imei'];
//SysClient ID
$clientid = $myheader['g-api-client'];
//Log No
$logno = $myheader['g-api-log'];
//User ID
$userid	= $myheader['g-api-user'];

$app = new Nautilus(APPPATH);
if(!$app->LoadEnv($prodctid)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

if(!$app->validLog($prodctid, $userid, $pcname, $logno)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

$token = $myheader['g-api-token'];
if(!$app->loaduserClient($prodctid, $userid, $pcname, $token, $mobile)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

$param = file_get_contents('php://input');
$parjson = json_decode($param, true);

if(is_null($parjson)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Invalid parameters detected";
    echo json_encode($json);
    return;
}

if(!isset($parjson['sAcctNmbr'])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Unset ACCOUNT detected.";
    echo json_encode($json);
    return;
}

$acctnmbr = $parjson['sAcctNmbr'];

$sql = "SELECT" .
            "  a.sAcctNmbr" .
            ", a.sApplicNo" .
            ", CONCAT(b.sLastName, ', ', b.sFrstName, IF(IFNull(b.sSuffixNm, '') = '', ' ', CONCAT(' ', b.sSuffixNm, ' ')), b.sMiddName) xFullName" .
            ", CONCAT(b.sAddressx, ', ', c.sTownName, ', ', d.sProvName, ' ', c.sZippCode) xAddressx" .
            ", a.sRemarksx" .
            ", CONCAT(g.sBrandNme, ' ', f.sModelNme) as xModelNme" .
            ", e.sEngineNo" .
            ", e.sFrameNox" .
            ", h.sColorNme" .
            ", CONCAT(b.sLastName, ', ', b.sFrstName, ' ', b.sMiddName) xCCounNme" .
            ", j.sRouteNme" .
            ", CONCAT(p.sLastName, ', ', p.sFrstName, ' ', p.sMiddName) xCollectr" .
            ", CONCAT(q.sLastName, ', ', q.sFrstName, ' ', q.sMiddName) xManagerx" .
            ", m.sBranchNm xCBranchx" .
            ", a.dPurchase" .
            ", a.dFirstPay" .
            ", a.nAcctTerm" .
            ", a.dDueDatex" .
            ", a.nGrossPrc" .
            ", a.nDownPaym" .
            ", a.nCashBalx" .
            ", a.nPNValuex" .
            ", a.nMonAmort" .
            ", a.nPenaltyx" .
            ", a.nRebatesx" .
            ", a.nLastPaym" .
            ", a.dLastPaym" .
            ", a.nPaymTotl" .
            ", a.nRebTotlx" .
            ", a.nDebtTotl" .
            ", a.nCredTotl" .
            ", a.nAmtDuexx" .
            ", a.nABalance" .
            ", a.nDownTotl" .
            ", a.nCashTotl" .
            ", a.nDelayAvg" .
            ", a.cRatingxx" .
            ", a.cAcctstat" .
            ", a.sClientID" .
            ", a.sExAcctNo" .
            ", a.sSerialID" .
            ", a.cMotorNew" .
            ", a.dClosedxx" .
            ", a.cActivexx" .
            ", a.nLedgerNo" .
            ", a.cLoanType" .
            ", b.sTownIDxx" .
            ", a.sRouteIDx" .
            ", a.nPenTotlx" .
            ", i.sTransNox" .
            ", a.sModified" .
            ", a.dModified" .
            ", CONCAT(n.sLastName, ', ', n.sFrstName, IF(IFNull(n.sSuffixNm, '') = '', ' ', CONCAT(' ', n.sSuffixNm, ' ')), n.sMiddName) xCoCltNm1" .
            ", CONCAT(o.sLastName, ', ', o.sFrstName, IF(IFNull(o.sSuffixNm, '') = '', ' ', CONCAT(' ', o.sSuffixNm, ' ')), o.sMiddName) xCoCltNm2" .
            ", a.sCoCltID1" .
            ", a.sCoCltID2" .
            ", CONCAT(r.sLastName, ', ', r.sFrstName, ' ', r.sMiddName) zCollectr" .
            ", CONCAT(s.sLastName, ', ', s.sFrstName, ' ', s.sMiddName) zManagerx" .
            ", t.nLatitude" .
            ", t.nLongitud" .
            ", b.sBrgyIDxx" .
        " FROM MC_AR_Master  a" .
                " LEFT JOIN MC_Serial e" .
                    " LEFT JOIN MC_Model f" .
                        " LEFT JOIN Brand g ON f.sBrandIDx = g.sBrandIDx" .
                    " ON e.sModelIDx = f.sModelIDx" .
                    " LEFT JOIN Color h ON e.sColorIDx = h.sColorIDx" .
                    " ON a.sSerialID = e.sSerialID" .
                " LEFT JOIN MC_Credit_Application i ON a.sApplicNo = i.sTransNox" .
                " LEFT JOIN Client_Master n ON a.sCoCltID1 = n.sClientID" .
                " LEFT JOIN Client_Master o ON a.sCoCltID2 = o.sClientID" .
                " LEFT JOIN Client_Coordinates t ON a.sClientID = t.sClientID" .
                " LEFT JOIN Route_Area j" .
                " LEFT JOIN Employee_Master001 k" .
                    " LEFT JOIN Client_Master p" .
                        " ON k.sEmployID = p.sClientID" .
                    " ON j.sCollctID = k.sEmployID" .
                " LEFT JOIN Employee_Master001 l" .
                    " LEFT JOIN Client_Master q" .
                        " ON l.sEmployID = q.sClientID" .
                    " ON j.sManagrID = l.sEmployID" .
                " LEFT JOIN Branch m ON j.sBranchCd = m.sBranchCd" .
                " LEFT JOIN Employee_Master r ON j.sCollctID = r.sEmployID" .
                    " LEFT JOIN Employee_Master s" .
                        " ON j.sManagrID = s.sEmployID" .
                    " ON a.sRouteIDx = j.sRouteIDx" .
            ", Client_Master b" .
            ", TownCity c" .
            ", Province d" .
        " WHERE a.sClientID = b.sClientID" .
            " AND b.sTownIDxx = c.sTownIDxx" .
            " AND c.sProvIDxx = d.sProvIDxx" .
            " AND a.cLoanType <> '4'" . 
            " AND a.cAcctStat = '0'" .
            " AND a.sAcctNmbr = " . CommonUtil::toSQL($acctnmbr);

if(null === $rows = $app->fetch($sql)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}
elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "Record not found";
    echo json_encode($json);
    return;
}

date_default_timezone_set('Asia/Manila');
$date = new DateTime('now');

$current = $date->format("Y-m-d");
$year = $date->format("y");

if ($rows[0]["nAcctTerm"] == 0){
    if ($rows[0]["dDueDatex"] > $current)
        $rows[0]["nDelayAvg"] = 0;
    else
        $rows[0]["nDelayAvg"] = 1;    
}

if (CommonUtil::dateDiff("d", $current, $rows[0]["dFirstPay"]) > 0){
    $lnAcctTerm = 0;
} else {
    $lnAcctTerm = CommonUtil::dateDiff("m", $rows[0]["dFirstPay"], $current) + 1;
    
    if ($lnAcctTerm > $rows[0]["nAcctTerm"]){
        $lnAcctTerm = $rows[0]["nAcctTerm"];
    } else {
        $currentday = $date->format("d");
        
        $firstpay = strtotime($rows[0]["dFirstPay"]);
        $firstpayday = date('d', $ts1);
        
        if ($currentday < $firstpayday){
            $lnAcctTerm = $lnAcctTerm - 1;
        } elseif (CommonUtil::dateDiff("d", $rows[0]["dFirstPay"], $current) < 30) {
            $lnAcctTerm = $lnAcctTerm - 1;
        }
    }
}

//compute account balance
$rows[0]["nABalance"] = $rows[0]["nGrossPrc"] + $rows[0]["nDebtTotl"] - 
    ($rows[0]["nDownTotl"] + $rows[0]["nCashTotl"] + $rows[0]["nPaymTotl"] + 
    $rows[0]["nRebTotlx"] + $rows[0]["nCredTotl"]);

//compute amount due
$lnAmtDuexx = ($lnAcctTerm * $rows[0]["nMonAmort"] + $rows[0]["nDownPaym"] + $rows[0]["nCashBalx"]) - 
    ($rows[0]["nGrossPrc"] - $rows[0]["nABalance"]); 

//compute delay
if ($lnAmtDuexx > 0){
    if ($rows[0]["nMonAmort"] > 0){
        $rows[0]["nDelayAvg"] = round($lnAmtDuexx / $rows[0]["nMonAmort"], 2);
    } else {
        if ($rows[0]["dDueDatex"] < $current){
            $rows[0]["nDelayAvg"] = 1;
        }
    }
}else {
    $rows[0]["nDelayAvg"] = round($lnAmtDuexx / $rows[0]["nMonAmort"], 2);
}

if ($lnAmtDuexx > $rows[0]["nABalance"]) $lnAmtDuexx = $rows[0]["nABalance"];

$json["result"] = "success";
$json["acctterm"] = $lnAcctTerm;
$json["abalance"] = $rows[0]["nABalance"];
$json["amtduexx"] = $lnAmtDuexx;
$json["delayavg"] = $rows[0]["nDelayAvg"];
echo json_encode($json);
return;
?>