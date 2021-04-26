<?php 
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'FBPromoValidatorInterface.php';

class FBPromoValidatorMC implements FBPromoValidatorInterface{
    protected $_app;
    
    protected $_branchcd;
    protected $_visittme;
    protected $_division;
    
    protected $_clientnm;
    protected $_refercde;
    protected $_refernox;
    protected $_mobileno;
    
    protected $_sErrorMsg;
    protected $_sErrorCde;
    
    public function __construct($app, $branchcd, $visittme){        
        $this->_app = $app;
        $this->_branchcd = $branchcd;
        $this->_visittme = $visittme;
        
        date_default_timezone_set('Asia/Manila');
    }
    
    public function IsDocumentValid(){
        if ($this->_clientnm == ""){
            $this->_sErrorCde = "101";
            $this->_sErrorMsg = "UNSET client name detected.";
            return false;
        }
        
        if ($this->_refercde == ""){
            $this->_sErrorCde = "101";
            $this->_sErrorMsg = "UNSET reference document detected.";
            return false;
        }
        
        if ($this->_refernox == ""){
            $this->_sErrorCde = "101";
            $this->_sErrorMsg = "UNSET reference number detected.";
            return false;
        }        
        
        if ($this->_mobileno == ""){
            $this->_sErrorCde = "101";
            $this->_sErrorMsg = "UNSET mobile number detected.";
            return false;
        }
        
        if (!CommonUtil::isValidMobile($this->_mobileno)){
            $this->_sErrorCde = "101";
            $this->_sErrorMsg = "INVALID mobile number detected.";
            return false;
        }
        
        $app = $this->_app;
        
        $sql = "SELECT cPromoDiv FROM Branch_Others WHERE sBranchCd = " . CommonUtil::toSQL($this->_branchcd);
        
        $rows = $app->fetch($sql);
        
        if($rows === null){
            $this->_sErrorCde = $app->getErrorCode();
            $this->_sErrorMsg = $app->getErrorMessage();
            return false;
        } elseif(empty($rows)){
            $this->_sErrorCde = "101";
            $this->_sErrorMsg = "Invalid branch of transaction.";
            return false;
        }
        
        $this->_division = $rows[0]["cPromoDiv"];
        
        $sql = "SELECT" .
                    "  sReferCde" .
                    ", sReferNme" .
                " FROM FB_Raffle_Transaction_Basis" .
                " WHERE cRecdStat = '1'" .
                    " AND sDivision = " . CommonUtil::toSQL($this->_division) .
                    " AND sReferCde = " . CommonUtil::toSQL($this->_refercde);
        
        $rows = $app->fetch($sql);
        
        if($rows === null){
            $this->_sErrorCde = $app->getErrorCode();
            $this->_sErrorMsg = $app->getErrorMessage();
            return false;
        } elseif(empty($rows)){
            $this->_sErrorCde = "101";
            $this->_sErrorMsg = "No raffle basis found for the given division. " . $this->_refercde;
            return false;
        }
        
        return true;
    }
    
    public function SaveMaster(){
        $app = $this->_app;
        
        //check master record if exists
        $sql = "SELECT" .
                    "  nEntryNox" .
                " FROM FB_Raffle_Promo_Master" .
                " WHERE sBranchCd = " . CommonUtil::toSQL($this->_branchcd) .
                    " AND nVisitTme = " . CommonUtil::toSQL($this->_visittme);
        
        $rows = $app->fetch($sql);
        
        if($rows === null){
            $this->_sErrorCde = $app->getErrorCode();
            $this->_sErrorMsg = $app->getErrorMessage();
            return false;
        } elseif(empty($rows)){
            //check if referece docs exists            
            $sql = "SELECT" .
                        "  nEntryNox" .
                    " FROM FB_Raffle_Promo_Master" .
                    " WHERE sReferCde = " . CommonUtil::toSQL($this->_refercde) .
                        " AND sReferNox = " . CommonUtil::toSQL($this->_refernox);
            
            $rows = $app->fetch($sql);
            
            if($rows === null){
                $this->_sErrorCde = $app->getErrorCode();
                $this->_sErrorMsg = $app->getErrorMessage();
                return false;
            } elseif(empty($rows)){
                $date = new DateTime('now');
                
                $sql = "INSERT INTO FB_Raffle_Promo_Master SET" .
                        "  sBranchCd = " . CommonUtil::toSQL($this->_branchcd) .
                        ", nVisitTme = " . $this->_visittme .
                        ", sClientNm = " . CommonUtil::toSQL($this->_clientnm) .
                        ", sReferCde = " . CommonUtil::toSQL($this->_refercde) .
                        ", sReferNox = " . CommonUtil::toSQL($this->_refernox) .
                        ", sMobileNo = " . CommonUtil::toSQL($this->_mobileno) .
                        ", nEntryNox = 0" .
                        ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp));
                
                if($app->execute($sql) <= 0){
                    $this->_sErrorCde = $app->getErrorCode();
                    $this->_sErrorMsg = $app->getErrorMessage();
                    return false;
                }
                
                return true;
            }
            
            $this->_sErrorCde = "100";
            $this->_sErrorMsg = "Reference used. User is not allowed to access this page.";
            return false;
        } else {
            //check if reference was used
            if ($rows[0]["sReferCde"] == $this->_refercde &&
                $rows[0]["sReferNox"] == $this->_refernox){
                
                $this->_sErrorCde = "101";
                $this->_sErrorMsg = "User was not allowed to access this page.";
                return false;
            }
            
            $date = new DateTime('now');
            
            $sql = "UPDATE FB_Raffle_Promo_Master SET" .    
                        "  dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp)) .
                    " WHERE sBranchCd = " . CommonUtil::toSQL($this->_branchcd) .
                        " AND nVisitTme = " . $this->_visittme;
            
            if($app->execute($sql) <= 0){
                $this->_sErrorCde = $app->getErrorCode();
                $this->_sErrorMsg = $app->getErrorMessage();
                return false;
            }
        }
                
        return true;
    }
    
    public function SaveDetail($pagelink, $cstatusxx){
        $app = $this->_app;
        
        $date = new DateTime('now');
        
        $sql = "SELECT" . 
                    "  a.nEntryNox" .
                    ", a.sFBPageID" . 
                    ", a.sRaffleNo" . 
                    ", a.cStatusxx" .
                    ", b.sPageLink" . 
                " FROM FB_Raffle_Promo_Detail a" .
                    ", Facebook_Page b" .
                " WHERE a.sFBPageID = b.sFBPageID" .
                    " AND a.sBranchCd = " . CommonUtil::toSQL($this->_branchcd) .
                    " AND a.nVisitTme = " . CommonUtil::toSQL($this->_visittme) . 
                    " AND b.sPageLink = " . CommonUtil::toSQL($pagelink);
        
        $rows = $app->fetch($sql);
        
        if($rows === null){
            $this->_sErrorCde = $app->getErrorCode();
            $this->_sErrorMsg = $app->getErrorMessage();
            return false;
        } elseif(!empty($rows)){
            $sql = "UPDATE FB_Raffle_Promo_Detail SET" . 
                        "  cStatusxx = " . CommonUtil::toSQL($cstatusxx) .
                        ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp)) . 
                    " WHERE sBranchCd = " . CommonUtil::toSQL($this->_branchcd) .
                        " AND nVisitTme = " . CommonUtil::toSQL($this->_visittme) .
                        " AND sFBPageID = " . CommonUtil::toSQL($rows[0]["sFBPageID"]);
            
            if($app->execute($sql) <= 0){
                $this->_sErrorCde = $app->getErrorCode();
                $this->_sErrorMsg = $app->getErrorMessage();
                return false;
            }
        } else{
            $rowcount = 1;
            
            $sql = "SELECT nEntryNox FROM FB_Raffle_Promo_Detail" .
                    " WHERE sBranchCd = " . CommonUtil::toSQL($this->_branchcd) .
                        " AND nVisitTme = " . CommonUtil::toSQL($this->_visittme) .
                    " ORDER BY nEntryNox DESC LIMIT 1";
            
            $rows = $app->fetch($sql);
            
            if($rows === null){
                $this->_sErrorCde = $app->getErrorCode();
                $this->_sErrorMsg = $app->getErrorMessage();
                return false;
            } elseif(!empty($rows)){
                $rowcount = $rows[0]["nEntryNox"] + 1;
            }
            
            $fbid = "";
            
            $sql = "SELECT sFBPageID FROM Facebook_Page WHERE sPageLink = " . CommonUtil::toSQL($pagelink);
            
            $rows = $app->fetch($sql);
            
            if($rows === null){
                $this->_sErrorCde = $app->getErrorCode();
                $this->_sErrorMsg = $app->getErrorMessage();
                return false;
            } elseif(!empty($rows)){
                $fbid = $rows[0]["sFBPageID"];
            }
            
            if ($fbid == "") {
                $this->_sErrorCde = "100";
                $this->_sErrorMsg = "Unknown Facebook Page ID.";
                return false;
            }
           
            $raffle = CommonUtil::GetNextReference("FB_Raffle_Promo_Detail", "sRaffleNo", "sRaffleNo", "sBranchCd", $this->_branchcd, $app->getConnection());
            
            $sql = "INSERT INTO FB_Raffle_Promo_Detail SET" .
                    "  sBranchCd = " . CommonUtil::toSQL($this->_branchcd) .
                    ", nVisitTme = " . CommonUtil::toSQL($this->_visittme) .
                    ", nEntryNox = " . $rowcount .
                    ", sFBPageID = " . CommonUtil::toSQL($fbid) .
                    ", sRaffleNo = " . $raffle . 
                    ", cStatusxx = " . CommonUtil::toSQL($cstatusxx) .
                    ", dModified = " . CommonUtil::toSQL($date->format(CommonUtil::format_timestamp));
            
            if($app->execute($sql) <= 0){
                $this->_sErrorCde = $app->getErrorCode();
                $this->_sErrorMsg = $app->getErrorMessage();
                return false;
            }
        }

        return true;
    }
    
    public function setMaster($index, $value){
        switch ($index){
            case "clientnm": $this->_clientnm = $value; break;
            case "refercde": $this->_refercde = $value; break;
            case "refernox": $this->_refernox = $value; break;
            case "mobileno": $this->_mobileno = $value; break;
        }
    }

    public function getErrorCode(){return $this->_sErrorCde;}
    public function getMessage(){return $this->_sErrorMsg;}
    public function getDivision(){return $this->_division;}
}
?>