<?php
/*******************************************************************************
 *  Version Checker API for Guanzon Android Apps
 *  ----------------------------------------------------------------------------
 *  RMJ Business Solutions
 *  ----------------------------------------------------------------------------
 *  iMac [2019-06-13]
 *      Started creating API.
 *******************************************************************************/
    class VersionChecker{
        public $app;
        public $sProdctID;
        
        public $master_table;
        
        public $sWarngMsg="";
        
        function __construct($app, $sProdctID){
            $this->app = $app;
            $this->sProdctID = $sProdctID;
            
            $this->master_table = "xxxSysObjectSub";
        }
        
        function __destruct() {
            $this->app = null;
        }
        
        function getMessage(){
            return $this->sWarngMsg;
        }
        
        function getLatestVersion($sProjctID){
            if ($sProjctID == ""){
                $this->sWarngMsg = "Unset PROJECT ID detected.";
                return "";
            }
            
            $sql = "SELECT sVersionx FROM xxxSysObjectSub" .
                    " WHERE sProdctID = " . CommonUtil::toSQL($this->sProdctID) .
                        " AND sProjctID = " . CommonUtil::toSQL($sProjctID);
            $stmt = $this->app->query($sql);
            
            if ($stmt->rowCount() == 0){
                $this->sWarngMsg = "PROJECT ID was not registered.";
                return "";
            } else if ($stmt->rowCount() > 1){
                $this->sWarngMsg = "Multiple entry of PROJECT ID detected.";
                return "";
            }
            
            if($row = $stmt->fetch()){
                return $row["sVersionx"];
            }
        }
    }
?>