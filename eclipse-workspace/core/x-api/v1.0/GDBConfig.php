<?php
namespace xapi\core\v100;

require_once 'x-api/v1.0/api-config.php';
require_once 'x-api/v1.0/api-const.php';

use xapi\config\v100\APIErrCode as err_code;

class GDBConfig{
    private $_apppath;
    private $_product;
    
    private $_dbhost;
    private $_dbname;
    private $_dbdrvr;
    private $_dbuser;
    private $_dbpswd;
    private $_dbport;
    
    private $_sErrorMsg;
    private $_sErrorCde;
    private $_bConfigLd;

    public function __construct($doc_path, $product){
        $this->_product = $product;
        $this->_apppath = $doc_path;
        $this->_bConfigLd = $this->loadConfig($product);
    }
    
    public function getDBDriver(){
        return $this->_dbdrvr;
    }

    public function getDBHost(){
        return $this->_dbhost;
    }

    public function getPort(){
        return $this->_dbport;
    }
    
    public function getDBName(){
        return $this->_dbname;
    }
    
    public function getUser(){
        return $this->_dbuser;
    }

    public function getPassword(){
        return $this->_dbpswd;
    }
    
    private function loadConfig($product){
        //echo "INSIDE loadConfig...\n";
        $this->_bConfigLd = false;
        
        if(!file_exists($this->_apppath . "/config/gRider.ini")){
            $this->_sErrorCde = err_code::FILE_NOT_FOUND;
            $this->_sErrorMsg = "Config file not found!";
            return false;
        }
        
        $main_config = parse_ini_file($this->_apppath . "/config/gRider.ini", true);
        $this->_dbhost = $main_config[$product]["ServerName"];
        $this->_dbname = $main_config[$product]["Database"];
        
        //var_dump($main_config[$product]);
        
        if (array_key_exists("DBDriver", $main_config[$product])) {
            $this->_dbdrvr = $main_config[$product]["DBDriver"];
        }
        else{
            $this->_dbdrvr = "mysql";
        }
        
        if (array_key_exists("UserName", $main_config[$product])) {
            $this->_dbuser = $main_config[$product]["UserName"];
        }
        else{
            $this->_dbuser = "";
        }
        
        if (array_key_exists("Password", $main_config[$product])){
            $this->_dbpswd = $main_config[$product]["Password"];
        }
        else{
            $this->_dbpswd = "";
        }
        
        //echo 'bago port';
        if (array_key_exists("Port", $main_config[$product])){
            $this->_dbport = $main_config[$product]["Port"];
        }
        else{
            $this->_dbport = "3306";
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

?>
