<?php
/*******************************************************************************
 *  OR CR Status Object 
 *  ----------------------------------------------------------------------------   
 *  RMJ Solutions © 2019
 *  ----------------------------------------------------------------------------
 *  iMac [2019-01-05]
 *      Started creating this object
*******************************************************************************/
    class ORCRStatus{
        public $app;
        
        public $sWarngMsg = "";
        
        function __construct($app){
            $this->app = $app;
	}

	function __destruct() {
            $this->app = null;
	}
        
        function getMessage(){
	    return $this->sWarngMsg;
	}
        
        function Request($cardno){
            if ($cardno == ""){
                $this->sWarngMsg = "Empty CARD NO. detected.";
                return null;
            }
            
            $cardno = str_replace(" ", "", $cardno);
            $cardno = str_replace("-", "", $cardno);
            
            $sql = "SELECT sGCardNox, cCardStat FROM G_Card_Master" . 
                    " WHERE sCardNmbr = " . Nautilus::toSQL($cardno);
            
            //check if GCard exists.
            $rows = $this->app->fetch($sql);
            
            if ($rows == null){
                $this->sWarngMsg = "Empty CARD NO. detected.";
                return null;
            }
            
            if ($rows[0] < CardStat::Activated){
                $this->sWarngMsg = "G-CARD is not activated.";
                return null;
            }
                    
            $sql = "SELECT sSerialID" .
                    " FROM MC_Serial_Service" .
                    " WHERE sGCardNox = " . Nautilus::toSQL($rows[0]["sGCardNox"]);
            
            //check the registered mc's to the for the GCard
            $rows = $this->app->fetch($sql);
            
            if ($rows == null){
                $this->sWarngMsg = "No MOTORCYCLE was registered to that G-Card.";
                return null;
            }
            
            $cond = "";
            for($ctr=0; $ctr < sizeof($rows); $ctr++){
                $cond .= ", " . Nautilus::toSQL($rows[$ctr]["sSerialID"]);
            }
            $cond = "c.sSerialID IN (" . substr($cond, 2) . ")";
            
            $sql = "SELECT c.sSerialID" .
                        ", CONCAT(b.sLastName, ', ', b.sFrstName, ' ', b.sMiddName) sClientNm" .
                        ", c.sEngineNo" .
                        ", c.sFrameNox" .
                        ", a.sFileNoxx" .
                        ", a.sRegORNox" .
                        ", a.sCRENoxxx" .
                        ", a.sCRNoxxxx" .
                        ", a.sPlateNoP" .
                        ", a.sPlateNoH" .
                        ", a.sStickrNo" .
                        ", c.nYearModl" .
                        ", IFNULL(a.dRegister, CAST('1901-01-01' AS DATE)) dRegister" .
                        ", a.sLocatnCR" .
                        ", a.sSerialID xSerialID" .
                        ", CONCAT(REPLACE(REPLACE(b.sAddressx, '\r', ''), '\n', ''), ', ', d.sTownName, ', ', e.sProvName) xAddressx" .
                        ", '' sBranchNm" .
                        ", CAST('1901-01-01' AS DATE) dPurchase" .
                        ", '0' cPaymForm" .
                        ", f.sBranchNm sLocation" .
                        ", '0' cUpdatedx" .
                        ", '' sLTONamex" .
                        ", '' xTransNox" .
                        ", g.sBranchNm sMCLocatn" . 
                        ", c.cLocation" .
                    " FROM MC_Serial c" .
                        " LEFT JOIN MC_Serial_Registration a ON c.sSerialID = a.sSerialID" .
                        " LEFT JOIN Client_Master b ON c.sClientID = b.sClientID" .
                        " LEFT JOIN TownCity d ON b.sTownIDxx = d.sTownIDxx" .
                        " LEFT JOIN Province e ON d.sProvIDxx = e.sProvIDxx" .
                        " LEFT JOIN Branch f ON a.sLocatnCR = f.sBranchCD" . 
                        " LEFT JOIN Branch g ON c.sBranchCd = g.sBranchCD";
            
            $sql = Nautilus::addcondition($sql, $cond);
            
            echo $sql;
            $rows = $this->app->fetch($sql);
            
            if ($rows == null){
                $this->sWarngMsg = "No RECORD found.";
                return null;
            }
            
            $master = array();
            for($ctr=0; $ctr < sizeof($rows); $ctr++){
                $master[$ctr] = array();
                
                $master[$ctr]["engine"] = $rows[$ctr]["sEngineNo"];
                $master[$ctr]["frame"] = $rows[$ctr]["sFrameNox"];
                
                switch ($rows[$ctr]["cLocation"]){
                    case MCLocation::Warehouse:
                    case MCLocation::Branch:
                        $master[$ctr]["location"] = $rows[$ctr]["sMCLocatn"]; break;
                    case MCLocation::Supplier:
                        $master[$ctr]["location"] = "Supplier"; break;
                    case MCLocation::Customer:
                        $master[$ctr]["location"] = "Customer"; break;
                    case MCLocation::Unknown:
                        $master[$ctr]["location"] = "Unknown"; break;
                    case MCLocation::ServiceCenter:
                        $master[$ctr]["location"] = "Service Center";
                }
                
                if ($rows[$ctr]["sCRNoxxxx"] == null || $rows[$ctr]["sCRNoxxxx"] == ""){
                    $master[$ctr]["status"] = "ON PROCESS";
                    $master[$ctr]["original"] = "UNKNOWN";
                } else {
                    $master[$ctr]["status"] = "REGISTERED";
                    $master[$ctr]["original"] = $rows[$ctr]["sLocation"];
                }               
            }
            
            return $master;
        }
    }
?>
