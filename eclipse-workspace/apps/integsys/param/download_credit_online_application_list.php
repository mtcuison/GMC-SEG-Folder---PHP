<?php    
    require_once 'config.php';
    require_once 'Nautilus.php';
    require_once 'CommonUtil.php';
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
    $pcname = $myheader['g-api-imei'];
    //SysClient ID
    $clientid = $myheader['g-api-client'];
    //Log No
    $logno 		= $myheader['g-api-log'];
    //User ID
    $userid		= $myheader['g-api-user'];
    
    $app = new Nautilus(APPPATH);
    if(!$app->LoadEnv($prodctid)){
        $json["result"] = "error";
        $json["error"]["message"] = $app->getErrorMessage()();
        echo json_encode($json);
        return;
    }
    
    if ($logno != ""){
        if(!$app->validLog($prodctid, $userid, $pcname, $logno, $clientid, true)){
            $json["result"] = "error";
            $json["error"]["message"] = $app->getErrorMessage();
            echo json_encode($json);
            return;
        }
    }
    
    $param = file_get_contents('php://input');
    $parjson = mb_convert_encoding(json_decode($param, true), 'ISO-8859-1', 'UTF-8');
    
    if(is_null($parjson)){
        $json["result"] = "error";
        $json["error"]["message"] = "Invalid parameters detected.";
        echo json_encode($json);
        return false;
    }
    
    if(!isset($parjson['value'])){
        $json["result"] = "error";
        $json["error"]["message"] = "Invalid parameters detected.";
        echo json_encode($json);
        return false;
    }
    
    $value = trim($parjson['value']);
    
    $bycode = false;
    if(isset($parjson['bycode'])){
        $bycode = htmlspecialchars($parjson['bycode']);
    }

    $search = "0=1";
    if ($bycode == true){
        $search = "a.sBranchCd = " . CommonUtil::toSQL($value);
    } else {
        $search = "(a.sCredInvx = " . CommonUtil::toSQL("") . 
                    " OR a.sCreatedx = " . CommonUtil::toSQL($userid) . ")";
    }
    
    //get the date of past 30 days
    $sql = date("Y-m-d", strtotime("-30 days"));
    
    $sql = "SELECT" .
                "  a.sTransNox" .
                ", a.sBranchCd" .
                ", a.dTransact" .
                ", IFNULL(a.sCredInvx, '') sCredInvx" .
                ", a.sClientNm sCompnyNm" .
                ", IFNULL(a.sCatInfox, a.sDetlInfo) sCatInfox" .
                ", IFNULL(a.sQMAppCde, '') sQMAppCde" .
                ", IFNULL(a.nDownPayF, a.nDownPaym) nDownPaym" .
                ", IFNULL(a.cTranStat, '') cTranStat" .
                ", a.dTimeStmp" .
                ", a.sCreatedx" .
            " FROM Credit_Online_Application a" .
            " WHERE $search" .
                " AND a.dTransact >= '$sql'" . 
            " ORDER BY a.dTimeStmp";
    
    $rows = $app->fetch($sql);
    
    if(null === $rows = $app->fetch($sql)){ //exception detected
        $json["result"] = "error";
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = $app->getErrorMessage();
        echo json_encode($json);
        return;
    } elseif(empty($rows)){
        $json["result"] = "error";
        $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
        $json["error"]["message"] = "No record found.";
        echo json_encode($json);
        return;
        
    }
    
    $rows_found = sizeof($rows);
    
    $detail = array();
    for($ctr=0;$ctr<$rows_found;$ctr++){        
        $detail[$ctr]["sTransNox"] = $rows[$ctr]["sTransNox"];
        $detail[$ctr]["sBranchCd"] = $rows[$ctr]["sBranchCd"];
        $detail[$ctr]["dTransact"] = $rows[$ctr]["dTransact"];
        $detail[$ctr]["sCredInvx"] = $rows[$ctr]["sCredInvx"];
        $detail[$ctr]["sCompnyNm"] = mb_convert_encoding($rows[$ctr]["sCompnyNm"], 'UTF-8', 'ISO-8859-1');
        $detail[$ctr]["sQMAppCde"] = $rows[$ctr]["sQMAppCde"];
        $detail[$ctr]["nDownPaym"] = $rows[$ctr]["nDownPaym"];
        $detail[$ctr]["sCreatedx"] = $rows[$ctr]["sCreatedx"];
        $detail[$ctr]["cTranStat"] = $rows[$ctr]["cTranStat"];
        $detail[$ctr]["dTimeStmp"] = mb_convert_encoding($rows[$ctr]["dTimeStmp"], 'UTF-8', 'ISO-8859-1');
        
        $parjson = json_decode($rows[$ctr]["sCatInfox"], true);
        
        //get spouse name
        $detail[$ctr]["sSpouseNm"] = "";
        if (isset($parjson["spouse_info"])){
            $jsoninfo = $parjson["spouse_info"];    
            
            if (isset($jsoninfo["personal_info"])){
                $jsoninfo = $jsoninfo["personal_info"];
             
                $sql = $jsoninfo["sLastName"] . ", " . $jsoninfo["sFrstName"];
                
                if ($jsoninfo["sSuffixNm"] != ""){
                    $sql = $sql . " " . $jsoninfo["sSuffixNm"];
                }
                
                $sql = $sql . " " . $jsoninfo["sMiddName"];
                
                $detail[$ctr]["sSpouseNm"] = $sql;
            }
        }
        //end - get spouse name
        
        //get address of the customer
        $detail[$ctr]["sAddressx"] = "";
        if (isset($parjson["residence_info"])){
            $jsoninfo = $parjson["residence_info"];
            $jsoninfo = $jsoninfo["present_address"];
            
            $sql = trim($jsoninfo["sAddress1"] . " " . $jsoninfo["sAddress2"]);
            $sql = trim($jsoninfo["sHouseNox"] . " " . $sql);
            
            $detail[$ctr]["sAddressx"] = $sql;
            
            //get barangay name
            if ($jsoninfo["sBrgyIDxx"] != ""){
                $sql = "SELECT sBrgyName FROM Barangay WHERE sBrgyIDxx = " . CommonUtil::toSQL($jsoninfo["sBrgyIDxx"]);
                
                if(null === $detrows = $app->fetch($sql)){
                    $json["result"] = "error";
                    $json["error"]["code"] = $app->getErrorCode();
                    $json["error"]["message"] = $app->getErrorMessage();
                    echo json_encode($json);
                    return;
                } elseif(!empty($rows)){
                    $detail[$ctr]["sAddressx"] = $detail[$ctr]["sAddressx"] . ", " . $detrows[0]["sBrgyName"];
                }
            }
            //end - get barangay name
            
            //get town name
            if ($jsoninfo["sTownIDxx"] != ""){
                $sql = "SELECT sTownName FROM TownCity WHERE sTownIDxx = " . CommonUtil::toSQL($jsoninfo["sTownIDxx"]);
                
                if(null === $detrows = $app->fetch($sql)){
                    $json["result"] = "error";
                    $json["error"]["code"] = $app->getErrorCode();
                    $json["error"]["message"] = $app->getErrorMessage();
                    echo json_encode($json);
                    return;
                } elseif(!empty($rows)){
                    $detail[$ctr]["sAddressx"] = $detail[$ctr]["sAddressx"] . ", " . $detrows[0]["sTownName"];
                }
            }
            //end - get town name
        }
        //end - get address of the customer
        
        //get model name
        $detail[$ctr]["sModelNme"] = "";
        if (isset($parjson["sModelIDx"])){
            if ($parjson["sModelIDx"] != ""){
                $sql = "SELECT sModelNme FROM MC_Model WHERE sModelIDx = " . CommonUtil::toSQL($parjson["sModelIDx"]);
                
                if(null === $detrows = $app->fetch($sql)){
                    $json["result"] = "error";
                    $json["error"]["code"] = $app->getErrorCode();
                    $json["error"]["message"] = $app->getErrorMessage();
                    echo json_encode($json);
                    return;
                } elseif(!empty($rows)){
                    $detail[$ctr]["sModelNme"] = $detrows[0]["sModelNme"];
                }
            }
        }
        //end - get model name
        
        //get account term
        $detail[$ctr]["nAcctTerm"] = 0;
        if (isset($parjson["nAcctTerm"])){
            $detail[$ctr]["nAcctTerm"] = $parjson["nAcctTerm"];
        }
        //end - get account term
        
        //get mobile no
        $detail[$ctr]["sMobileNo"] = 0;
        if (isset($parjson["applicant_info"])){
            $jsoninfo = $parjson["applicant_info"];
            $jsoninfo = $jsoninfo["mobile_number"];
            $jsoninfo = $jsoninfo[0];
            
            $detail[$ctr]["sMobileNo"] = $jsoninfo["sMobileNo"];
        }
    }
    
    $json["result"] = "success";
    $json["detail"] = $detail;
    echo json_encode($json);
    return;
?>