<?php
namespace xapi\core\v100;

require_once 'x-api/v1.0/api-config.php';
require_once 'x-api/v1.0/api-const.php';
require_once 'x-api/v1.0/GToken.php';
require_once 'x-api/v1.0/MySQLAES.php';

use xapi\config\v100\APIErrCode as err_code;
use \DateTime;

class TokenValidator{
    private $_dbconnx;
    private $_token;
    private $_sErrorCde;
    private $_sErrorMsg;
    
    public function __construct($conn){
        $this->_dbconnx = $conn;
    }
    
    public function getGToken(){
        return $this->_token;
    }
    
    public function isValidClientKey($jwt){
        $this->_token = new GToken();
        
        //check if $jwt is a valid token
        if(!$this->_token->decode($jwt)){
            $this->_sErrorCde = err_code::INVALID_TOKEN;
            $this->_sErrorMsg = "Invalid token detected. " . $this->_token->getMessage();
            return false;
        }

        //check issue information...
        if(strcmp($this->_token->getIssuer(), "guanzongroup.com.ph") != 0){
            $this->_sErrorCde = err_code::INVALID_TOKEN;
            $this->_sErrorMsg = "Invalid token detected. Invalid issuer.";
            return false;
        }

        //get datetime
        $date = new DateTime("NOW");
        
        //check access key expiration date...
        if(null !== $exp = $this->_token->getExpiry()){
            if($exp < $date->format("U")){
                $this->_sErrorCde = err_code::EXPIRED_CLIENT_KEY;
                $this->_sErrorMsg = "Expired client key detected.";
                return false;
            }
        }
        
        //get payload
        $payload = $this->_token->getData();
        
        //check payload
        if($payload["token"] == "1"){
            $this->_sErrorCde = err_code::INVALID_CLIENT_KEY;
            $this->_sErrorMsg = "Invalid client key detected.";
            return false;
        }
        
        return true;
    }

    public function isValidAccessKey($jwt){
        $this->_token = new GToken();
        
        //check if $jwt is a valid token
        if(!$this->_token->decode($jwt)){
            $this->_sErrorCde = err_code::INVALID_TOKEN;
            $this->_sErrorMsg = "Invalid token detected. " . $this->_token->getMessage();
            return false;
        }
        
        //check issue information...
        if(strcmp($this->_token->getIssuer(), "guanzongroup.com.ph") != 0){
            $this->_sErrorCde = err_code::INVALID_TOKEN;
            $this->_sErrorMsg = "Invalid token detected. Invalid issuer.";
            return false;
        }
        
        //get datetime
        $date = new DateTime("NOW");
        
        //check access key expiration date...
        if(null !== $exp = $this->_token->getExpiry()){
            if($exp < $date->format("U")){
                $this->_sErrorCde = err_code::EXPIRED_ACCESS_KEY;
                $this->_sErrorMsg = "Expired access key detected.";
                return false;
            }
        }
        
        //get payload
        $payload = $this->_token->getData();

        //where u = microseconds
        //where U = seconds
        //echo $date->format("U") . "---\n";
        //echo $this->_token->getExpiry() . "\n";
        //echo $date->format("Y-m-d H:i:s") . "---\n";
        //$xdate = new DateTime($this->_token->getExpiry());
        //echo $xdate->format('Y-m-d H:i:s');        
        //$xdate = new DateTime($this->_token->getNotBefore());
        //echo $xdate->format('Y-m-d H:i:s');
        
        //check access key expiration date...
        if(null !== $exp = $payload["exp"]){
            if($exp < $date->format("U")){
                $this->_sErrorCde = err_code::EXPIRED_ACCESS_KEY;
                $this->_sErrorMsg = "Expired license key detected.";
                return false;
            }
        }
        
        //check payload
        if($payload["token"] == "0"){
            $this->_sErrorCde = err_code::INVALID_ACCESS_KEY;
            $this->_sErrorMsg = "Invalid client key detected.";
            return false;
        }
        
        return true;
    }
    
    public function getErrorCode(){
        return $this->_sErrorCde;
    }
    public function getMessage(){
        return $this->_sErrorMsg;
    }
}
    