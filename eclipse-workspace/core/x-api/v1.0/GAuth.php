<?php
namespace xapi\core\v100;

/*
 * GAuth.php
 * kalyptus - 2019.10.01 01:30pm
 * Used in validating the authenticity of the user from the database.
 * Note:
 */


require_once 'x-api/v1.0/api-config.php';
require_once 'x-api/v1.0/api-const.php';

use xapi\config\v100\APIErrCode as err_code;

class GAuth{
    
    private $_prdctid;
    private $_prdctnm;
    
    private $_cltidxx;
    private $_cltname;
    private $_license;
    private $_expiryx;
    private $_lictype;
    
    private $_useridx;
    private $_usernme;
    
    private $_dbconnx;
    
    private $_sErrorCde;
    private $_sErrorMsg;
    
    public function __construct($conn){
        $this->_dbconnx = $conn;
        $this->resetInfo();
    }

    public function getProductID(){
        return $this->_prdctid;
    }

    public function getProductName(){
        return $this->_prdctnm;
    }
    
    public function getClientID(){
        return $this->_cltidxx;
    }
    
    public function getClientName(){
        return $this->_cltname;
    }
    
    public function getLicenseDate(){
        return $this->_license;
    }
    
    public function getLicenseExpiry(){
        return $this->_expiryx;
    }
    
    public function getLicenseType(){
        return $this->_lictype;
    }
    
    public function getUserID(){
        return $this->_useridx;
    }
    
    public function getUserName(){
        return $this->_usernme;
    }
    
    public function loadClientInfo($prdctid, $cltid, $userid){
        $sql = "SELECT b.sProdctID, b.sProdctNm, c.sClientID, c.sClientNm, d.sUserIDxx, d.sUserName, a.dLicencex, a.dExpiryDt, a.cLicTypex" . 
              " FROM xxxSysApplication a" . 
                 " LEFT JOIN xxxSysObject b ON a.sProdctID = b.sProdctID" .
                 " LEFT JOIN xxxSysClient c ON a.sClientID = c.sClientID" .
                 " LEFT JOIN App_User_Master d ON a.sProdctID = d.sProdctID" . 
              " WHERE b.sProdctID = '$prdctid'" . 
                " AND c.sClientID = '$cltid'" . 
                " AND d.sUserIDxx = '$userid'";
        if(null === $rows = $this->_dbconnx->fetch($sql)){
            $this->_sErrorCde = $this->_dbconnx->getErrorCode();
            $this->_sErrorMsg = $this->_dbconnx->getMessage();
            $this->resetInfo();
            return false;
        }
        elseif(empty($rows)){
            $this->_sErrorCde = err_code::RECORD_NOT_FOUND;
            $this->_sErrorMsg = "Keys passed are not in the database." . $sql;
            $this->resetInfo();
            return false;
        }
        
        $this->_prdctid = $prdctid;
        $this->_prdctnm = $rows[0]["sProdctNm"];

        $this->_useridx = $userid;
        $this->_usernme = $rows[0]["sUserName"];

        $this->_cltidxx = $cltid;
        $this->_cltname = $rows[0]["sClientNm"];
        $this->_license = $rows[0]["dLicencex"];
        $this->_expiryx = $rows[0]["dExpiryDt"];
        $this->_lictype = $rows[0]["cLicTypex"];
        
        return true;
    }

    private function resetInfo(){
        $this->_prdctid = null;
        $this->_prdctnm = null;
        
        $this->_cltidxx = null;
        $this->_cltname = null;
        $this->_license = null;
        $this->_expiryx = null;
        $this->_lictype = null;
        
        $this->_useridx = null;
        $this->_usernme = null;
    }
    
    public function getErrorCode(){
        return $this->_sErrorCde;
    }
    public function getMessage(){
        return $this->_sErrorMsg;
    }
    
}

?>