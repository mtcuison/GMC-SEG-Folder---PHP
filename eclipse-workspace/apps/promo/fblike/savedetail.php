<?php
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';

if(!isset($_GET['ref'])){
    $json["result"] = "error";
    $json["error"]["message"] = "UNSET transaction reference.";
    echo json_encode($json);
    return false;
}

if(!isset($_GET['link'])){
    $json["result"] = "error";
    $json["error"]["message"] = "UNSET facebook page link.";
    echo json_encode($json);
    return false;
}

if(!isset($_GET['stat'])){
    $json["result"] = "error";
    $json["error"]["message"] = "UNSET like status.";
    echo json_encode($json);
    return false;
}

$transno = $_GET['ref'];
$link = $_GET['link'];
$stat = $_GET['stat'];

//account credentials
$prodctid = RAFFLE_PRODUCT;
$userid = RAFFLE_USER;

//initialize application driver
$app = new Nautilus(APPPATH);
if(!$app->LoadEnv($prodctid)) {
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return false;
}

if(!$app->loaduser($prodctid, $userid)){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return false;
}

//check if master exists
$sql = "SELECT * FROM FB_Raffle_Promo_Master" . 
        " WHERE sTransNox = " . CommonUtil::toSQL($transno);

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return false;
} elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["message"] = "Transaction discrepancy detected.";
    echo json_encode($json);
    return false;
}

$mobileno = $rows[0]["sMobileNo"];

//get page id
$sql = "SELECT sFBPageID, sDescript FROM Facebook_Page WHERE sPageLink = " . CommonUtil::toSQL($link);

$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return false;
} elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["message"] = "Facebook page id is not found on record.";
    echo json_encode($json);
    return false;
}

$fbid = $rows[0]["sFBPageID"];
$company = $rows[0]["sDescript"];

//check if the page already exists on detail table
$sql = "SELECT * FROM FB_Raffle_Promo_Detail" .
        " WHERE sTransNox = " . CommonUtil::toSQL($transno) .
            " AND sFBPageID = " . CommonUtil::toSQL($fbid);

$rows = $app->fetch($sql);

$exist = false;
$oldstat = "0";

if($rows === null){
    $json["result"] = "error";
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return false;
} elseif(!empty($rows)){
    $exist = true;
    $oldstat = $rows[0]["cStatusxx"];
    $raffle = $rows[0]["sRaffleNo"];
}

$date = new DateTime('now');

if ($exist == true){    
    $sql = "UPDATE FB_Raffle_Promo_Detail SET" .
                "  cStatusxx = " . CommonUtil::toSQL($stat) .
                ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp)) .
            " WHERE sTransNox = " . CommonUtil::toSQL($transno) .
                " AND sFBPageID = " . CommonUtil::toSQL($rows[0]["sFBPageID"]);
    
    if($app->execute($sql) <= 0){
        $app->rollbackTrans();
        
        $json["result"] = "error";
        $json["error"]["message"] = $app->getErrorMessage();
        echo json_encode($json);
        return false;
    }
} else {
    $rowcount = 1;
    
    $sql = "SELECT nEntryNox FROM FB_Raffle_Promo_Detail" .
            " WHERE sTransNox = " . CommonUtil::toSQL($transno) .
            " ORDER BY nEntryNox DESC LIMIT 1";
    
    $rows = $app->fetch($sql);
    
    if($rows === null){
        $json["result"] = "error";
        $json["error"]["message"] = $app->getErrorMessage();
        echo json_encode($json);
        return false;
    } elseif(!empty($rows)){
        $rowcount = sizeof($rows) + 1;
    }
     
    $entrynox = 1;
    
    $sql = "SELECT nEntryNox FROM FB_Raffle_Promo_Detail ORDER BY nEntryNox DESC LIMIT 1";
    
    $rows = $app->fetch($sql);
    
    if($rows === null){
        $json["result"] = "error";
        $json["error"]["message"] = $app->getErrorMessage();
        echo json_encode($json);
        return false;
    } elseif(!empty($rows)){
        $entrynox = $rows[0]["nEntryNox"] + 1;
    }
    
    $raffle = "";
    
    $sql = "SELECT sRaffleNo FROM FB_Raffle_Ticket WHERE nEntryNox = " . $entrynox;
    
    $rows = $app->fetch($sql);
    
    if($rows === null){
        $json["result"] = "error";
        $json["error"]["message"] = $app->getErrorMessage();
        echo json_encode($json);
        return false;
    } elseif(empty($rows)){
        $json["result"] = "error";
        $json["error"]["message"] = "No raffle ticket was available.";
        echo json_encode($json);
        return false;
    } else {
        $raffle = $rows[0]["sRaffleNo"];
    }
    
    //$raffle = CommonUtil::GetNextReference("FB_Raffle_Promo_Detail", "sRaffleNo", "sRaffleNo", "sTransNox", "MX01", $app->getConnection());
    
    $app->beginTrans();
    
    $sql = "INSERT INTO FB_Raffle_Promo_Detail SET" .
            "  sTransNox = " . CommonUtil::toSQL($transno) .
            ", nEntryNox = " . $entrynox .
            ", sFBPageID = " . CommonUtil::toSQL($fbid) .
            ", sRaffleNo = " . CommonUtil::toSQL($raffle) .
            ", cStatusxx = " . CommonUtil::toSQL($stat) .
            ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp));
    
    if($app->execute($sql) <= 0){
        $app->rollbackTrans();
        
        $json["result"] = "error";
        $json["error"]["message"] = $app->getErrorMessage();
        echo json_encode($json);
        return false;
    }
    
    //update master process companies
    $sql = "UPDATE FB_Raffle_Promo_Master SET" .
                "  nEntryNox = nEntryNox + 1 " .  
            " WHERE sTransNox = " . CommonUtil::toSQL($transno);

    if($app->execute($sql) <= 0){
        $app->rollbackTrans();
        
        $json["result"] = "error";
        $json["error"]["message"] = $app->getErrorMessage()  . $sql;
        echo json_encode($json);
        return false;
    }
    
    //tag the raffle number as issued
    $sql = "UPDATE FB_Raffle_Ticket SET" .
        "  cIssuedxx = '1'".
        " WHERE nEntryNox = " . $entrynox;
    
    if($app->execute($sql) <= 0){
        $app->rollbackTrans();
        
        $json["result"] = "error";
        $json["error"]["message"] = $app->getErrorMessage();
        echo json_encode($json);
        return false;
    }
    
    $app->commitTrans();
}

if ($stat == "1" && $oldstat != $stat){
    createOutgoing($transno, $company, $raffle, $mobileno, $app);
}

$json["result"] = "success";
echo json_encode($json);
return true;

function createOutgoing($transno, $fbpage, $raffleno, $mobileno, $app){
    date_default_timezone_set('Asia/Manila');
    $date = new DateTime('now');
    $year = $date->format("y");
    
    $message = "You earned raffle ticket # " . $raffleno . " fr. " .
                $fbpage . ". Like our partner page to earn more tickets. Promo runs from Jan 3 - Jun 30, 2021." .
                "\n\nGUANZON GROUP";
        
    $sql = "INSERT INTO HotLine_Outgoing SET" .
            "  sTransNox = " . CommonUtil::toSQL(CommonUtil::GetNextCode("HotLine_Outgoing", "sTransNox", $year, $app->getConnection(), "MX01")) .
            ", dTransact = " . CommonUtil::toSQL($date->format(CommonUtil::format_date)) .
            ", sDivision = 'MP'" .
            ", sMobileNo = " . CommonUtil::toSQL($mobileno) .
            ", sMessagex = " . CommonUtil::toSQL($message) .
            ", cSubscrbr = " . CommonUtil::toSQL(CommonUtil::getMobileNetwork($mobileno)) .
            ", dDueUntil = " . CommonUtil::toSQL($date->format(CommonUtil::format_date)) .
            ", cSendStat = '0'" .
            ", nNoRetryx = '0'" .
            ", sUDHeader = ''" .
            ", sReferNox = " . CommonUtil::toSQL($transno) .
            ", sSourceCd = " . CommonUtil::toSQL("APPX") .
            ", cTranStat = '0'" .
            ", nPriority = 1" .
            ", sModified = " . CommonUtil::toSQL("fbpromo") .
            ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp));
    
    $app->execute($sql);
    
    /*
    if($app->execute($sql) <= 0){
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = $app->getErrorMessage();
        echo json_encode($json);
        return;
    }
    */
    
    return true;
}
?>