<?php
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'FBPromoValidatorMC.php';

class FBPromoValidatorFactory{
    protected $_validator;
    
    public function make($app, $branchcd, $doctype, $docnmbr, $mobileno){
        if ($app == null){
            $this->_sErrorCde = "100";
            $this->_sErrorMsg = "Application driver is not set.";
            return null;
        }
        
        //validate branch if exists
        $sql = "SELECT" .
                    "  a.sBranchCd" .
                    ", a.sBranchNm" .
                    ", b.cDivision" .
                " FROM Branch a" .
                    " LEFT JOIN Branch_Others b ON a.sBranchCd = b.sBranchCd" .
                " WHERE a.cRecdStat = '1'" .
                    " AND a.sBranchCd = " . CommonUtil::toSQL($branchcd);
        
        $rows = $app->fetch($sql);
        
        if($rows === null){
            return null;
        } elseif(empty($rows)){
            return null;
        }
        
        $_cDivision = $rows[0]["cDivision"];
        
        //validate mobile number
        if (!CommonUtil::isValidMobile($mobileno)){
            return null;
        }
        
        $this->_validator = null;
        
        //get division
        switch ($_cDivision){
            case 0: //mobile phone
                break;
            case 1: //motorcycle
                $this->_validator = new FBPromoValidatorMC($app, $branchcd, $doctype, $docnmbr, $mobileno);
                break;
            case 2: //auto group
                break;
            case 3: //hospitality
                break;
            case 4: //pedritos           
        }
        
        return $this->_validator;
    }
}
?>