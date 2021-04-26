<?php
namespace xapi\core\v100;

require_once 'x-api/v1.0/CommonUtil.php';

class GAnalytics{
    public static function getLogNo(&$conn, $product, $client, $userid){
        $transno = CommonUtil::GetNextCode("xxxAPIUserLog", "sTransNox", true, $conn->getDBCon(), "GAP1");
        $sql = "INSERT INTO xxxAPIUserLog" . 
              " SET sTransNox = '$transno'" . 
                 ", sProdctID = '$product'" . 
                 ", sClientID = '$client'" . 
                 ", sUserIDxx = '$userid'";
        if($conn->execute($sql) == -1){
            return null;            
        }
        
        return $transno;
    }
    
    public static function getAPICode(&$conn, $apiurl){
        $sql = "SELECT sXAPICode" . 
              " FROM xxxAPIList" . 
              " WHERE sPHPSelfx = '$apiurl'";
        if(null === $rows = $conn->fetch($sql)){
            return null;
        }
        
        if(empty($rows)){
            $transno = CommonUtil::GetNextCode("xxxAPIList", "sXAPICode", false, $conn->getDBCon(), "GAP1");
            $sql = "INSERT INTO xxxAPIList" . 
                  " SET sXAPICode = '$transno'" . 
                     ", sPHPSelfx = '$apiurl'";
            if($conn->execute($sql) == -1){
                return null;
            }
            return $transno;
        }
        
        return $rows[0]["sXAPICode"];
    }
    
    public static function saveAnalytics(&$conn, $logno, $ip, $server, $apicode, $param){
        $transno = CommonUtil::GetNextCode("xxxAPIUsageAnalytics", "sTransNox", true, $conn->getDBCon(), "GAP1");
        $sql = "INSERT INTO xxxAPIUsageAnalytics" .
            " SET sTransNox = '$transno'" .
            ", sLogNoxxx = '$logno'" .
            ", sIPAddres = '$ip'" .
            ", sServerNm = '$server'" .
            ", sXAPICode = '$apicode'" . 
            ", sParamtrs = '$param'";
        if($conn->execute($sql) == -1){
            return null;
        }
        
        return true;
    }
    
    public static function saveViewInfo(&$conn, $clientid, $apicode){
        $sql = "UPDATE xxxAPIUsage" .
              " SET nUsageCtr = nUsageCtr + 1" .
              " WHERE sClientID = '$clientid'" . 
                " AND sXAPICode = '$apicode'";
        if($conn->execute($sql) == -1){
            return null;
        }
        
        return true;
    }
        
    public static function resetViewInfo(&$conn, $clientid, $apicode){
        $sql = "UPDATE xxxAPIUsage" .
            " SET nUsageCtr = 0" .
            " WHERE sClientID = '$clientid'" .
            " AND sXAPICode = '$apicode'";
        if($conn->execute($sql) == -1){
            return null;
        }
        
        return true;
    }
    
    public static function countViewInfo(&$conn, $clientid, $apicode){
        $sql = "SELECT nUsageCtr" . 
              " FROM xxxAPIUsage" . 
              " WHERE sClientID = '$clientid'" .
              " AND sXAPICode = '$apicode'";
        
        if(null === $rows = $conn->fetch($sql)){
            return null;
        }
        
        if(empty($rows)){
            $sql = "INSERT INTO xxxAPIUsage" .
                " SET sClientID = '$clientid'" .
                ", sXAPICode = '$apicode'" .
                ", nUsageCtr = 0";
            if($conn->execute($sql) == -1){
                return null;
            }
            return 0;
        }
        
        return $rows[0]["nUsageCtr"];
    }

    //returns 
    //  null  - error
    //  false - duplicate
    //  true  - success
    public static function saveAPITrans(&$conn, $logno, $client, $apicode, $referno, $data){
        $sql = "SELECT *" .
            " FROM XAPITrans" .
            " WHERE sClientID = '$client'" .
              " AND sXAPICode = '$apicode'" .
              " AND sReferNox = '$referno'" . 
            " ORDER BY sTransNox DESC LIMIT 1"; 

        //" WHERE sClientID = '$client'" .
        //" AND sXAPICode = '$apicode'" .
        //" AND sReferNox = '$referno'" .
        
        if(null === $rows = $conn->fetch($sql)){
            return null;
        }

        if(!empty($rows)){
            if($rows[0]["cTranStat"] != "3"){
                return false;
            }
        }
        
        $transno = CommonUtil::GetNextCode("XAPITrans", "sTransNox", true, $conn->getDBCon(), "GAP1");
        $date = (new \DateTime('now'))->format('Y-m-d H:i:s');

        
        $sql = "INSERT INTO XAPITrans" . 
              " SET sTransNox = '$transno'" . 
                 ", sLogNoxxx = '$logno'" . 
                 ", sClientID = '$client'" .
                 ", sXAPICode = '$apicode'" . 
                 ", sReferNox = '$referno'" . 
                 ", sPayloadx = '$data'" . 
                 ", cTranStat = '0'" . 
                 ", dReceived = '$date'";
        if($conn->execute($sql) == -1){
            return null;
        }
        
        return true;
    }
}
