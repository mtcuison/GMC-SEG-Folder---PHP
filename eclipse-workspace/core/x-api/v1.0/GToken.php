<?php
namespace xapi\core\v100;

/*
 * GConn.php
 * kalyptus - 2019.10.01 01:30pm
 * Responsible in handling access token.
 * Note:
 */

require_once 'x-api/v1.0/api-config.php';
require_once 'x-api/v1.0/jwt_helper.php';
require_once 'x-api/v1.0/MySQLAES.php';

use \JWT as JWT;

class GToken{
    private $_jwt;
    private $_key;
    
    private $_iss;  
    private $_aud;
    private $_iat;
    private $_nbf;
    private $_exp;
    private $_data;
    
    private $_sErrorCde;
    private $_sErrorMsg;

    
    public function __construct(){
        $this->_iss = "";
        $this->_aud = "";
        $this->_iat = null;
        $this->_nbf = null;
        $this->_exp = null;
        $this->_data = array();
        $this->_jwt = null;
        
        $this->_key = $this->aes_encryt(APIKEY);
    }
    
    public function getIssuer(){
        return $this->_iss;
    }
    public function setIssuer($iss){
        $this->_iss = $iss;
    }
    
    
    public function getAudience(){
        return $this->_aud;
    }
    public function setAudience($aud){
        $this->_aud = $aud;
    }
    
    public function getIssued(){
        return $this->_iat;
    }
    public function setIssued($iat){
        try{
            $dte = new \DateTime($iat);
            $this->_iat = $dte->format('U');
        } catch (\Exception $e) {
            $this->_iat = null;
        }
    }
    
    public function getNotBefore(){
        return $this->_nbf;
    }
    public function setNotBefore($nbf){
        try{
            $dte = new \DateTime($nbf);
            $this->_nbf = $dte->format('U');
        } catch (\Exception $e) {
            $this->_nbf = null;
        }
    }
    
    public function getExpiry(){
        return $this->_exp;
    }
    public function setExpiry($exp){
        try{
            $dte = new \DateTime($exp);
            $this->_exp = $dte->format('U');
            
            //echo "Expiry:" . $this->_exp;
            //echo "Expiry:" . $dte->format('Y-m-d H:i:s');
            
            
        } catch (\Exception $e) {
            $this->_exp = null;
        }
    }
    
    public function getData(){
        return $this->_data;        
    }
    public function setData($data){
        if(is_array($data)){
            $this->_data = $data;
        }
        else{
            $this->_data = array();
        }
    }

    public function getToken(){
        return $this->_jwt;        
    }
    
    public function encode(){
        $this->_jwt = null;

        if($this->_iss == ""){
            $this->_sErrorMsg = "Invalid key issuer detected";
            return false;
        }

        if($this->_aud == ""){
            $this->_sErrorMsg = "Invalid key recepient detected";
            return false;
        }
        
        if($this->_iat == null){
            $this->_sErrorMsg = "Invalid key issuance date detected";
            return false;
        }
        
        if($this->_nbf == null){
            $this->_sErrorMsg = "Invalid key usage date detected";
            return false;
        }

        if(!is_array($this->_data)){
            $this->_sErrorMsg = "Invalid data payload detected";
            return false;
        }

        if($this->_key == ""){
            $this->_sErrorMsg = "Invalid key detected";
            return false;
        }

        $payload = array();
        $payload["iss"] = $this->_iss;
        $payload["aud"] = $this->_aud;
        $payload["iat"] = $this->_iat;
        $payload["nbf"] = $this->_nbf;
        $payload["exp"] = $this->_exp;
        
        //make sure that payload is encrypted
        $xdata = json_encode($this->_data);
        $payload["data"] = $this->aes_encryt($xdata);
        
        $this->_jwt = JWT::encode($payload, $this->_key);
        
        return true;
    }
    
    public function decode($jwt){
        $this->_jwt = null;
        
        try{
            //echo $this->_key;
            $dcde = JWT::decode($jwt, $this->_key);
            //var_dump($dcde);
            $this->_iss = $dcde["iss"];
            $this->_aud = $dcde["aud"];
            $this->_iat = $dcde["iat"];
            $this->_nbf = $dcde["nbf"];
            $this->_exp = $dcde["exp"];

            //make sure that the payload is decrypted
            $xdata = $this->aes_decrypt($dcde["data"]);
            $this->_data = json_decode($xdata, true);
            //var_dump($this->_data);
            $this->_jwt = $jwt;
        } catch (\Exception $e) {
            $this->_sErrorMsg = $e->getMessage();
            return false;
        }
        
        return true;
    }
    
    
    private function aes_encryt($data){
        $aes = new MySQLAES(APPKEYX);
        return $aes->encrypt($data);
    }

    private function aes_decrypt($data){
        $aes = new MySQLAES(APPKEYX);
        return $aes->decrypt($data);
    }
    
    public function getMessage(){
        return $this->_sErrorMsg;
    }
}
?>