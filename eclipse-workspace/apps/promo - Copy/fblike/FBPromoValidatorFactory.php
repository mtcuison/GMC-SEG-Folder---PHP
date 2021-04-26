<?php
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'FBPromoValidatorMC.php';
require_once 'FBPromoValidatorMP.php';
require_once 'FBPromoValidatorMonarch.php';
require_once 'FBPromoValidatorPedritos.php';
require_once 'FBPromoValidatorAutoGroup.php';

class FBPromoValidatorFactory{
    protected $_validator;
    
    public function make($app, $branchcd, $visittme){
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
                $this->_validator = new FBPromoValidatorMP($app, $branchcd, $visittme);
                break;
            case 1: //motorcycle
                $this->_validator = new FBPromoValidatorMC($app, $branchcd, $visittme);
                break;
            case 2: //auto group
                $this->_validator = new FBPromoValidatorAutoGroup($app, $branchcd, $visittme);
                break;
            case 3: //hospitality
                $this->_validator = new FBPromoValidatorMonarch($app, $branchcd, $visittme);
                break;
            case 4: //pedritos
                $this->_validator = new FBPromoValidatorAutoGroup($app, $branchcd, $visittme);
        }
        
        return $this->_validator;
    }
}
?>