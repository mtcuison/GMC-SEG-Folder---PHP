<?php 
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'FBPromoValidatorInterface.php';

class FBPromoValidatorMC implements FBPromoValidatorInterface{
    public $_app;
    
    public $_branchcd;
    public $_visittme;
    public $_division;
    
    public $_clientnm;
    public $_refercde;
    public $_refernox;
    public $_mobileno;
    
    public $_sErrorMsg;
    public $_sErrorCde;
    
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
        
        $app = $this->_app;
        
        $sql = "SELECT sPromoDiv FROM Branch_Others WHERE sBranchCd = " . CommonUtil::toSQL($this->_branchcd);
        
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
        
        $this->_division = $rows[0]["sPromoDiv"];
        
        $sql = "SELECT" .
                    "  sReferCde" .
                    ", sReferNme" .
                " FROM FB_Raffle_Transaction_Basis" .
                " WHERE cRecdStat = '1'" .
                    " AND sDivision = " . CommonUtil::toSQL($this->_division) .
                    " AND sReferCde = " . CommonUtil::toSQL($this->_doctype);
        
        $rows = $app->fetch($sql);
        
        if($rows === null){
            $this->_sErrorCde = $app->getErrorCode();
            $this->_sErrorMsg = $app->getErrorMessage();
            return false;
        } elseif(empty($rows)){
            $this->_sErrorCde = "101";
            $this->_sErrorMsg = "No raffle basis found for the given division.";
            return false;
        }
        
        return true;
    }
    
    public function SaveMaster(){
        //validate branch + unix time
        //validate references
        //save master
        $app = $this->_app;
        
        //check master record if exists
        $sql = "SELECT" .
                    "  nEntryNox" .
                " FROM FB_Raffle_Promo_Master" .
                " WHERE sBranchCd = " . CommonUtil::toSQL($app = $this->_app) .
                    " AND nVisitTme = " . CommonUtil::toSQL($app = $this->_visittme);
        
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
        } 
                
        return true;
    }
    
    public function SaveDetail($pagelink, $cstatusxx){

        
    }
    
    public function setMaster($index, $value){
        return true;
    }

    public function getErrorCode(){return $this->_sErrorCde;}
    public function getMessage(){return $this->_sErrorMsg;}
    public function getDivision(){return $this->_division;}
}
?>