<?php
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'WebClient.php';
require_once 'WSHeaderValidatorFactory.php';

$myheader = apache_request_headers();

if(!isset($myheader['g-api-id'])){
    echo "anggapoy nagawaan ti labat awa!";
    return;
}

if(stripos(APPSYSX, $myheader["g-api-id"]) === false){
    echo "anto la ya... sika lamet!";
    return;
}

$validator = (new WSHeaderValidatorFactory())->make($myheader['g-api-id']);
//var_dump($myheader);
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
$pcname 	= $myheader['g-api-imei'];
//SysClient ID
$clientid = $myheader['g-api-client'];
//Log No
$logno 		= $myheader['g-api-log'];
//User ID
$userid		= $myheader['g-api-user'];
//mobile no
$mobile = $myheader['g-api-mobile'];

$app = new Nautilus(APPPATH);
if(!$app->LoadEnv($prodctid)){
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

$data = file_get_contents('php://input');

//parse into json the PARAMETERS
$parjson = json_decode($data, true);

$compnyid = $parjson["compnyid"];
$transact = $parjson["transact"];
$sourceno = $parjson["sourceno"];
$sourcecd = $parjson["sourcecd"];

$sql = "SELECT *" .
      " FROM G_Card_Application" . 
      " WHERE sCompnyID = '$compnyid'" . 
        " AND dTransact = '$transact'" . 
        " AND sSourceNo = '$sourceno'" . 
        " AND sSourceCD = '$sourcecd'" .
        " AND cTranStat <> '4'" .
      " ORDER BY cTranStat DESC, sTransNox DESC";
$rows = $app->fetch($sql);

//immediately leave if we found an error during loading of GCard applications.
if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "GCard application not found!" . $app->getErrorMessage();
    echo json_encode($json);
    return;
}

$posted = false;
$applicno = "";

//perform cleaning operations if there are other applications with the same information as this one...
if(!empty($rows)){
    for($ctr=0;$ctr<sizeof($rows);$ctr++){
        if($rows[$ctr]["cTranStat"] == "2"){
            $applicno = $rows[$ctr]["sTransNox"];
            $posted = true;
        }
        elseif($rows[$ctr]["cTranStat"] == "1"){
            $applicno = $rows[$ctr]["sTransNox"];
            $posted = true;
        }
        elseif($rows[$ctr]["cTranStat"] == "0"){
            if($rows[$ctr]["sTransNox"] != $parjson["transnox"]){
                if(!voidApplication($rows[$ctr]["sTransNox"], $rows[$ctr]["sCompnyID"])){
                    echo json_encode($json);
                    return;
                }
            }
        }
    }
}

//if already posted then just return the gcard information...
//the client will void the GCard application if sapplicno of GCard 
//is different from the GCard info returned otherwise verify the GCard application.
if($posted){
    loadGCard($applicno);    
    echo json_encode($json);
    return;
}
    
$pnYellowxx = 0;
$pnWhitexxx = 0;
$pnPointsxx = 0;
$psSerialID = $parjson["serialid"];
$applicno = $parjson["transnox"];

//validate entries for MC Sales and MC 2H Sales
if($sourcecd == "M02910000005" || $sourcecd == "M02910000012"){
    //should have MC Serial
    if($psSerialID == null || $psSerialID == ""){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
        $json["error"]["message"] = "Please include the Engine Number in the GCard application.";
        echo json_encode($json);
        return;
    }
    
    //should be new
    if($parjson["appltype"] != "1"){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
        $json["error"]["message"] = "GCard Application from MC Sales/MC 2H Sales should be new only.";
        echo json_encode($json);
        return;
    }
    
    //set default FSEP COUNT
    if($sourcecd == "M02910000005"){
        //MC sales
        $pnYellowxx = 2;
        //is purchase mode an installment
        if($parjson["purcmode"] == 1){
            $pnWhitexxx = 10;
        }
        else{
            $pnWhitexxx = 5;
        }
    }
    elseif($sourcecd == "M02910000012"){
        //MC 2H sales
        $pnYellowxx = 2;
        $pnWhitexxx = 5;
    }
}
//eReplacement or Officer
elseif($sourcecd == "M02910000010" || $sourcecd == "M02910000009"){
    //should be new
    if($parjson["appltype"] != "1"){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
        $json["error"]["message"] = "GCard Application from MC Sales/MC 2H Sales should be new only.";
        echo json_encode($json);
        return;
    }
    
    //eReplacement application should have a serialid
    if ($sourcecd == "M02910000010"){
        if($parjson["serialid"] == null || $parjson["serialid"]  == ""){
            $json["result"] = "error";
            $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
            $json["error"]["message"] = "Please include the Engine Number in the GCard application.";
            echo json_encode($json);
            return;
        }
        $pnYellowxx = $parjson["yellowxx"];
        $pnWhitexxx = $parjson["whitexxx"];
        $pnPointsxx = $parjson["pointsxx"];
    }
    else{
        $psSerialID = "";
    }
}
//Receipt
elseif($sourcecd == "M02910000007"){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "New/Renewal/Replacement is not allowed using the Digital GCard.";
    echo json_encode($json);
    return;
}

//create a new gcard no(sGCardNox)
$date = new DateTime('now');
$year = $date->format("Y");
$cardno = CommonUtil::GetNextCode("G_Card_Master", "sGCardNox", $year, $app->getConnection(), $compnyid);

$app->beginTrans();

if($psSerialID != ''){
    $sql = "SELECT *" .
        " FROM MC_Serial_Service" .
        " WHERE sSerialID = '$psSerialID'";
    $rows = $app->fetch($sql);
        
    if($rows === null){
        //kalyptus - 2019.06.21 05:23pm
        //please change the logic according to this...
        if($app->getErrorMessage() != ""){
            $json["result"] = "error";
            $json["error"]["code"] = $app->getErrorCode();
            $json["error"]["message"] = "MC Not loaded. " . $app->getErrorMessage();
            $app->rollbackTrans();
            echo json_encode($json);
            return;
        }
        else{
            $sql = "INSERT INTO MC_Serial_Service" .
                " SET sSerialID = '$psSerialID'" .
                ", sGCardNox = '$cardno'" .
                ", nYellowxx = $pnYellowxx" .
                ", nYlwCtrxx = 0" .
                ", nWhitexxx = '$pnWhitexxx'" .
                ", nWhtCtrxx = 0" .
                ", dTransact = " . CommonUtil::toSQL($parjson['transact']) .
                ", cDigitalx = '1'" .
                ", cRecdStat = '1'";
        }
    }
    elseif(empty($rows)){
            $sql = "INSERT INTO MC_Serial_Service" . 
                  " SET sSerialID = '$psSerialID'" .
                     ", sGCardNox = '$cardno'" . 
                     ", nYellowxx = $pnYellowxx" .
                     ", nYlwCtrxx = 0" .
                     ", nWhitexxx = '$pnWhitexxx'" .
                     ", nWhtCtrxx = 0" .
                     ", dTransact = " . CommonUtil::toSQL($parjson['transact']) . 
                     ", cDigitalx = '1'" .
                     ", cRecdStat = '1'";
    }
    //EUREKA!!! we found an MC Serial service record...
    else{
        //eReplacement
        //Not allowed to have a record in MC_Serial_Service...
        if($sourcecd == "M02910000010"){
            $json["result"] = "error";
            $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
            $json["error"]["message"] = "eReplacement GCard Application request is invalid since MC has a service record!";
            $app->rollbackTrans();
            echo json_encode($json);
            return;
        }
        //MC Sales...
        elseif($sourcecd == "M02910000005"){
            //there are entries in MC_Serial_Service
            if($rows[0]["sGCardNox"] != ""){
                //try to load the possible GCard Master using the current Application No
                if(!loadGCard($applicno)){
                    $json = array();
                    $json["result"] = "error";
                    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
                    $json["error"]["message"] = "MC Sales GCard Application request is invalid since MC has a service record!";
                    $app->rollbackTrans();
                    echo json_encode($json);
                    return;
                }
                else{
                    if($json["sApplicNo"] != $applicno){
                        $json = array();
                        $json["result"] = "error";
                        $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
                        $json["error"]["message"] = "MC Sales GCard Application request is invalid since MC has a service record!";
                        $app->rollbackTrans();
                        echo json_encode($json);
                        return;
                    }
                    else{
                        $app->rollbackTrans();
                        echo json_encode($json);
                        return;
                    }
                }
            }
        }
        
        $sql = "UPDATE MC_Serial_Service" . 
            " SET sGCardNox = '$cardno'" .
            ", nYellowxx = $pnYellowxx" .
            ", nYlwCtrxx = 0" .
            ", nWhitexxx = '$pnWhitexxx'" .
            ", nWhtCtrxx = 0" .
            ", dTransact = " . CommonUtil::toSQL($parjson['transact']) .
            ", cDigitalx = '1'" .
            ", cRecdStat = '1'" .  
            " WHERE sSerialID = '$psSerialID'"; 
    }
    
    if($app->execute($sql, "MC_Serial_Service") <= 0){
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = "Unable to update MC Serial Service record..." . $app->getErrorMessage();
        $app->rollbackTrans();
        echo json_encode($json);
        return;
    }
}

$cardnmbr = createGCardNo($parjson['compnyid'] , $parjson['cardtype']);
$pinnmbrx = sprintf('%02d', rand(1, 99)) . sprintf('%02d', rand(1, 99));
$date = new DateTime('now');
$stamp = $date->format(CommonUtil::format_timestamp);
$sql = "INSERT INTO G_Card_Master" .
    " SET sGCardNox = '$cardno'" .
    ", sCompnyID = '$compnyid'" .
    ", cLocation = '0'" .
    ", sClientID = " . CommonUtil::toSQL($parjson['clientid']) .
    ", dMemberxx = " . CommonUtil::toSQL($parjson['transact']) .
    ", cCardType = " . CommonUtil::toSQL($parjson['cardtype']) .
    ", sCardNmbr = '$cardnmbr'" .
    ", sPINumber = '$pinnmbrx'" . 
    ", dActivate = null" .
    ", dCardExpr = null" .
    ", nPointsxx = 0" .
    ", nTotPoint = $pnPointsxx" .
    ", nAvlPoint = $pnPointsxx" . 
    ", dLastRedm = null" .
    ", sLastLine = null" . 
    ", cCardStat = '0'" .
    ", cForUpdte = '0'" .
    ", sApplicNo = '$applicno'" . 
    ", sGroupIDx = ''" . 
    ", cIndvlPts = '1'" .
    ", cMainGrpx = '0'" . 
    ", cPostedxx = '0'" .
    ", cDigitalx = '1'" . 
    ", sModified = '$userid'" . 
    ", dModified = '$stamp'";

if($app->execute($sql, "G_Card_Master") <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Unable to update MC Serial Service record... " . $app->getErrorMessage();
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

//create textblast
$stamp = $date->format(CommonUtil::format_timestamp);
$datex = $date->format(CommonUtil::format_date);
$date5 = date_add($date,date_interval_create_from_date_string("5 days"));
$date5 = $date5->format(CommonUtil::format_date);

$lsMessage = "GUANZON GROUP: Good day! Thank you for applying on our digital G-Card. Your Guanzon Card Number is $cardnmbr. You can now add this card to your Guanzon App.";

$sql = "INSERT INTO HotLine_Outgoing SET" .
    "  sTransNox = " . CommonUtil::toSQL(CommonUtil::GetNextCode("HotLine_Outgoing", "sTransNox", $year, $app->getConnection(), "MX01")) .
    ", dTransact = '$datex'" .
    ", sDivision = 'TLM'" .
    ", sMobileNo = '$mobile'" .
    ", sMessagex = '$lsMessage'" .
    ", cSubscrbr = '1'" .
    ", dDueUntil = '$date5'" .
    ", cSendStat = '0'" .
    ", nNoRetryx = 0" .
    ", sUDHeader = ''" .
    ", sReferNox = '$cardno'" .
    ", sSourceCd = 'SMS0'" .
    ", cTranStat = '0'" .
    ", nPriority = 1 " .
    ", sModified = " . CommonUtil::toSQL($app->env("sUserIDxx")) .
    ", dModified = '$stamp'";
if($app->execute($sql, "HotLine_Outgoing") <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Unable to create text message for customer. " . $app->getErrorMessage();
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

$app->commitTrans();

$json["result"] = "SUCCESS";
$json["sGCardNox"] = $cardno;
$json["sCompnyID"] = $compnyid;
$json["cLocation"] = "0";
$json["sClientID"] = $parjson['clientid'];
$json["dMemberxx"] = $parjson['transact'];
$json["cCardType"] = $parjson['cardtype'];
$json["sCardNmbr"] = $cardnmbr;
$json["sPINumber"] = $pinnmbrx;
$json["dActivate"] = null;
$json["dCardExpr"] = null;
$json["nPointsxx"] = 0;
$json["nTotPoint"] = $pnPointsxx;
$json["nAvlPoint"] = $pnPointsxx;
$json["dLastRedm"] = null;
$json["sLastLine"] = null;
$json["cCardStat"] = "0";
$json["cForUpdte"] = "0";
$json["sApplicNo"] = $applicno;
$json["sGroupIDx"] = "";
$json["cIndvlPts"] = "1";
$json["cMainGrpx"] = "0";
$json["cPostedxx"] = "0";
$json["cDigitalx"] = 1;
$json["sModified"] = $userid;
$json["dModified"] = $stamp;
echo json_encode($json);

return;


function createGCardNo($branch, $type){
    global $app;
    global $json;
    
    //extract the last 3 character of branch code
    $branch = substr($branch, 1);
    
    //get the yy of year of current date
    $date = new DateTime('now');
    $year = $date->format("y");
    
    //generate new gcard number
    $newgcard = "";
    $sql = "SELECT sCardNmbr" .
          " FROM G_Card_Master" . 
          " WHERE sCardNmbr LIKE " . CommonUtil::toSQL($branch . $year . "%") . 
          " ORDER BY sCardNmbr DESC" .
          " LIMIT 1";
    //echo $sql;
    $rows = $app->fetch($sql);
    if($rows === null){
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = "Error during creations of new GCARD! " . $app->getErrorMessage();
        return null;
    }
    elseif(empty($rows)){
        //set the series at the beginning
        $newgcard = $branch . $year . sprintf('%06d', 1) . $type . rand(0, 9);
    }
    else{
        //create the new gcard number based 
        $series = intval(substr($rows[0]["sCardNmbr"], 5, 6)) + 1;
        $newgcard = $branch . $year . sprintf('%06d', $series) . $type . rand(0, 9);
    }

    return $newgcard;
}


//start functions and procedures here...
function getPrevGCard($fsGCardNo){
    global $app;
    $sql = "SELECT sPrevGCrd" .
          " FROM G_Card_Application a" .
                " LEFT JOIN G_Card_Master b ON a.sTransNox = b.sApplicNo" .
          " WHERE b.sCardNmbr = '$fsGCardNo'";
    $rows = $app->fetch($sql);
    
    if($rows === null){
        return '';    
    }
    
    if($rows[0]["sPrevGCrd"] == NULL){
        return $fsGCardNo;
    }
    else{
        return getPrevGCard($fsGCardNo);
    }
}

function getPrevGCardStatus($fsGCardNo){
    global $app;
    $sql = "SELECT cCardStat" . 
          " FROM G_Card_Master" .
          " WHERE sCardNmbr = '$fsGCardNo'";
    $rows = $app->fetch($sql);
    
    if($rows == null){
        return '';
    }
    
    return $rows[0]["cCardStat"];
}

function isImpounded($fsGCardNo){
    global $app;
    
    $prevcard = getPrevGCard($fsGCardNo);
    
    $sql = "SELECT c.sAcctNmbr, c.sTransNox, c.dRedeemxx" .
          " FROM G_Card_Master d" .
                " LEFT JOIN G_Card_Application a ON d.sApplicNo = a.sTransNox" .
                " LEFT JOIN MC_AR_Master b ON a.sSerialID = b.sSerialID AND a.dTransact = b.dPurchase" . 
                " LEFT JOIN Impound c ON b.sAcctNmbr = c.sAcctNmbr" . 
          " WHERE d.sCardNmbr = '$prevcard'" . 
            " AND c.dRedeemxx IS NULL" .
          " ORDER BY c.sAcctNmbr, c.dImpoundx DESC;";
    $rows = $app->fetch($sql);
    
    //if there seems to be an error then just say not impounded...
    if($rows === null){
        return false;
    }
    
    //if record has impound record and not redeemed then its impounded
    if($rows[0]["sTransNox"] != null || $rows[0]["dRedeemxx"] == null){
        return true;
    }
    
    return false;
}

function voidApplication($transno, $branch){
    global $json;
    global $app;
    global $userid;
    
    $date = new DateTime('now');
    $stamp = $date->format(CommonUtil::format_timestamp);
    
    $sql = "UPDATE G_Card_Application" .
        " SET cTranStat = '4'" .
        ", sModified = '$userid'" .
        ", dModified = " . CommonUtil::toSQL($stamp) .
        " WHERE sTransNox = " . CommonUtil::toSQL($transno);
    $result = $app->execute($sql, "G_Card_Application", $branch);
    if($result <= 0){
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = "Unable to void application. " . $sql . " " . $app->getErrorMessage();
        return false;
    }
    
    return true;
}

function loadGCard($applicno){
    global $app;
    global $json;
    $sql = "SELECT *" .
        " FROM G_Card_Master" .
        " WHERE sApplicNo = '$applicno'";
    $rows = $app->fetch($sql);
    
    if($rows === null || empty($rows)){
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = "Error loading GCard Information... " . $app->getErrorMessage();
        return false;
    }

    $json["result"] = "SUCCESS";
    $json["sGCardNox"] = $rows[0]["sGCardNox"];
    $json["sCompnyID"] = $rows[0]["sCompnyID"];
    $json["cLocation"] = $rows[0]["cLocation"];
    $json["sClientID"] = $rows[0]["sClientID"];
    $json["dMemberxx"] = $rows[0]["dMemberxx"];
    $json["cCardType"] = $rows[0]["cCardType"];
    $json["sCardNmbr"] = $rows[0]["sCardNmbr"];
    $json["sPINumber"] = $rows[0]["sPINumber"];
    $json["dActivate"] = $rows[0]["dActivate"];
    $json["dCardExpr"] = $rows[0]["dCardExpr"];
    $json["nPointsxx"] = $rows[0]["nPointsxx"];
    $json["nTotPoint"] = $rows[0]["nTotPoint"];
    $json["nAvlPoint"] = $rows[0]["nAvlPoint"];
    $json["dLastRedm"] = $rows[0]["dLastRedm"];
    $json["sLastLine"] = $rows[0]["sLastLine"];
    $json["cCardStat"] = $rows[0]["cCardStat"];
    $json["cForUpdte"] = $rows[0]["cForUpdte"];
    $json["sApplicNo"] = $rows[0]["sApplicNo"];
    $json["sGroupIDx"] = $rows[0]["sGroupIDx"];
    $json["cIndvlPts"] = $rows[0]["cIndvlPts"];
    $json["cMainGrpx"] = $rows[0]["cMainGrpx"];
    $json["cPostedxx"] = $rows[0]["cPostedxx"];
    $json["sModified"] = $rows[0]["sModified"];
    $json["dModified"] = $rows[0]["dModified"];
    return true;
}

?>
