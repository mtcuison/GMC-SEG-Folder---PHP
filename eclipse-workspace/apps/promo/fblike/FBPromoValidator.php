<?php
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'FBPromoValidatorInterface.php';

class FBPromoValidator{
    protected $_app;
    protected $_transnox;
    protected $_division;
    
    protected $_sErrorMsg;
    protected $_sErrorCde;
    
    public function __construct($app, $transnox){
        $this->_app = $app;
        $this->_transnox = $transnox;
        
        date_default_timezone_set('Asia/Manila');
    }
    
    public function isSessionOK(){
        $app = $this->_app;
        
        $sql = "SELECT nEntryNox, sBranchCd FROM FB_Raffle_Promo_Master WHERE sTransNox = " . CommonUtil::toSQL($this->_transnox);
        
        $rows = $app->fetch($sql);
        
        if($rows === null){
            $this->_sErrorCde = $app->getErrorCode();
            $this->_sErrorMsg = $app->getErrorMessage();
            return false;
        } elseif(empty($rows)){
            $this->_sErrorCde = "100";
            $this->_sErrorMsg = "Reference transaction is not found on record.";
            return false;
        }
        
        if ($rows[0]["nEntryNox"] >= 7){
            $this->_sErrorCde = "101";
            $this->_sErrorMsg = "All pages are already visited. Thank you.";
            return false;
        }
        
        $sql = "SELECT IFNULL(cPromoDiv, '') cPromoDiv FROM Branch_Others WHERE sBranchCd = " . CommonUtil::toSQL($rows[0]["sBranchCd"]);
        
        $rows = $app->fetch($sql);
        
        if($rows === null){
            $this->_sErrorCde = $app->getErrorCode();
            $this->_sErrorMsg = $app->getErrorMessage();
            return false;
        } elseif(empty($rows)){
            $this->_sErrorCde = "102";
            $this->_sErrorMsg = "Branch is not found on record.";
            return false;
        }
        
        $this->_division = $rows[0]["cPromoDiv"];
        
        return true;
    }
 
    public function getDivision(){return $this->_division;}
    public function getErrorCode(){return $this->_sErrorCde;}
    public function getMessage(){return $this->_sErrorMsg;}    
}
?>