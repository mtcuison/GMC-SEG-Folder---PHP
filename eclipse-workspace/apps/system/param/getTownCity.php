<?php
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';


if(isset($_POST['search'])){
    $provname = $_POST['search'];
    
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
                "  b.sTownIDxx" .
                ", CONCAT(b.sTownName, ' ', b.sZippCode, ', ' , a.sProvName) sTownName" .
            " FROM Province a" .
                ", TownCity b" .
            " WHERE a.sProvIDxx = b.sProvIDxx" .
                " AND b.sTownName LIKE " . CommonUtil::toSQL($provname . "%") .
                " AND a.cRecdStat = '1'" .
                " AND b.cRecdStat = '1'" .
            " ORDER BY b.sTownName, a.sProvName" . 
            " LIMIT 5";
    
    $rows = $app->fetch($sql);
    
    $rows_found = sizeof($rows);
    
    $detail = array();
    for($ctr=0;$ctr<$rows_found;$ctr++){
        $detail[$ctr]["value"] = mb_convert_encoding($rows[$ctr]["sTownIDxx"], 'UTF-8', 'ISO-8859-1');
        $detail[$ctr]["label"] = mb_convert_encoding($rows[$ctr]["sTownName"], 'UTF-8', 'ISO-8859-1');
    }
    
    echo json_encode($detail);
}

exit;
?>