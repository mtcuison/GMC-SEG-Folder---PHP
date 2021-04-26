<?php
namespace xapi\core\v100;

/*
 * GConn.php
 * kalyptus - 2019.10.01 01:30pm
 * Responsible in handling database access and connectivity.
 * Note:
 */

require_once 'x-api/v1.0/api-config.php';
require_once 'x-api/v1.0/api-const.php';

require_once  'x-api/v1.0/MySQLAES.php';
require_once  'x-api/v1.0/CommonUtil.php';

use xapi\config\v100\APIErrCode as err_code;
use \PDO;
use \PDOException;

class GConn{
    private $_connection;
    private $_acidenabld;
    
    private $_sErrorCde;
    private $_sErrorMsg;

    public function __construct($config = null){
        if($config != null){
            $driver = $config->getDBDriver();
            $dbhost = $config->getDBHost();
            $dbname = $config->getDBName();
            $dbport = $config->getPort();
            $user = $config->getUser();
            $password = $config->getPassword();
            
	    //echo $dbname;	

            if($user != ""){
                $user = CommonUtil::Decrypt($user);
                $password = CommonUtil::Decrypt($password);
            }
            
            $this->connect($driver, $dbhost, $dbname, $dbport, $user, $password);
        }
    }
        
    public function getDBCon(){
        return $this->_connection;
    }
    
    public function connect($driver, $dbhost, $dbname, $dbport, $user, $password){
        try{
            $conn=null;
            if("sqlite" == $driver){
                $conn = new PDO($driver . ":" . $dbhost . $dbname);
                //echo "sqlite";
            }
            else if("mysql" == $driver) {
                $conn = new PDO("mysql:dbname=$dbname;host=$dbhost;port=$dbport", $user, $password);
                //echo "mysql";
            }
            
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            
            $this->_connection = $conn;
        } catch ( PDOException $e ){
            $this->_sErrorCde = $e->getCode();
            $this->_sErrorMsg = __METHOD__ . " failed: " . $e->getMessage();
            $this->_connection = null;
        }
    }

    public function execute($sql){
        if($this->_connection == null){
            $this->_sErrorCde = err_code::NULL_CONNECTION;
            $this->_sErrorMsg = __METHOD__ . " failed: " . "Connection object is null.";
            return -1;
        }
        
        try {
            
            $withacid = $this->_acidenabld;
            if(!$withacid){
                $this->beginTrans();
            }
            
            //execute the query
            $affected = $this->_connection->exec($sql);
            
            if(!$withacid){
                $this->commitTrans();
            }
            
            return $affected;
        }
        catch (PDOException $e ){
            if(!$withacid){
                $this->rollbackTrans();
            }
            
            $this->_sErrorCde = $e->getCode();
            $this->_sErrorMsg = __METHOD__ . " failed: " . $e->getMessage();
            return -1;
        }
    }
    
    
    public function query($sql){
        if($this->_connection == null){
            $this->_sErrorCde = err_code::NULL_CONNECTION;
            $this->_sErrorMsg = __METHOD__ . " failed: " . "Connection object is null.";
            return null;
        }
        
        try {
            $stmt = $this->_connection->query($sql);
            return $stmt;
        }
        catch (PDOException $e ){
            $this->_sErrorCde = $e->getCode();
            $this->_sErrorMsg = __METHOD__ . " failed: " . $e->getMessage();
            return null;
        }
    }
    
    //+++++++++++++++++++++++++++++
    //public function fetch($sql)
    //+++++++++++++++++++++++++++++
    //if function returns null. Be sure to check the Error Message.
    public function fetch($sql){
        if($this->_connection == null){
            $this->_sErrorCde = err_code::NULL_CONNECTION;
            $this->_sErrorMsg = __METHOD__ . " failed: " . "Connection object is null.";
            return null;
        }
        
        try {
            $stmt = $this->_connection->query($sql);
            return $stmt->fetchAll();
        }
        catch (PDOException $e ){
            $this->_sErrorCde = $e->getCode();
            $this->_sErrorMsg = __METHOD__ . " failed: " . $e->getMessage();
            return null;
        }
    }
    
    public function prepare($sql){
        if($this->_connection == null){
            $this->_sErrorCde = err_code::NULL_CONNECTION;
            $this->_sErrorMsg = __METHOD__ . " failed: " . "Connection object is null.";
            return null;
        }
        
        try {
            $stmt = $this->_connection->prepare($sql);
            return $stmt;
        }
        catch (PDOException $e ){
            $this->_sErrorCde = $e->getCode();
            $this->_sErrorMsg = __METHOD__ . " failed: " . $e->getMessage();
            return null;
        }
    }
    
    public function quote($value){
        if($this->_connection == null){
            $this->_sErrorCde = err_code::NULL_CONNECTION;
            $this->_sErrorMsg = __METHOD__ . " failed: " . "Connection object is null.";
            return "";
        }
        
        try {
            $quoted = $this->_connection->quote($value);
            return $quoted;
        }
        catch (PDOException $e ){
            $this->_sErrorCde = $e->getCode();
            $this->_sErrorMsg = __METHOD__ . " failed: " . $e->getMessage();
            return "";
        }
    }
    
    //+++++++++++++++++++++++++++++
    //public function begin()
    //+++++++++++++++++++++++++++++
    //returns false if begin encounter error
    public function beginTrans(){
        if($this->_connection == null){
            $this->_sErrorCde = err_code::NULL_CONNECTION;
            $this->_sErrorMsg = __METHOD__ . " failed: " . "Connection object is null.";
            return false;
        }
        
        if($this->_acidenabld){
            $this->_sErrorMsg = __METHOD__ . " failed: " . "beginTrans already executed...";
            return false;
        }
        
        try {
            $this->_connection->beginTransaction();
            $this->_acidenabld = true;
        }
        catch (PDOException $e ){
            $this->_sErrorCde = $e->getCode();
            $this->_sErrorMsg = __METHOD__ . " failed: " . $e->getMessage();
            return false;
        }
    }
    
    //+++++++++++++++++++++++++++++
    //public function commit()
    //+++++++++++++++++++++++++++++
    //returns false if commit encounter error
    public function commitTrans(){
        if($this->_connection == null){
            $this->_sErrorCde = err_code::NULL_CONNECTION;
            $this->_sErrorMsg = __METHOD__ . " failed: " . "Connection object is null.";
            return false;
        }
        
        if(!$this->_acidenabld){
            $this->_sErrorMsg = __METHOD__ . " failed: " . "beginTrans was not executed...";
            return false;
        }
        
        try {
            $this->_connection->commit();
            $this->_acidenabld = false;
        }
        catch (PDOException $e ){
            $this->_sErrorCde = $e->getCode();
            $this->_sErrorMsg = __METHOD__ . " failed: " . $e->getMessage();
            return false;
        }
    }
    
    //+++++++++++++++++++++++++++++
    //public function rollback()
    //+++++++++++++++++++++++++++++
    //returns false if rollback encounter error
    public function rollbackTrans(){
        if($this->_connection == null){
            $this->_sErrorCde = err_code::NULL_CONNECTION;
            $this->_sErrorMsg = __METHOD__ . " failed: " . "Connection object is null.";
            return false;
        }
        
        if(!$this->_acidenabld){
            $this->_sErrorMsg = __METHOD__ . " failed: " . "beginTrans was not executed...";
            return false;
        }
        
        try {
            $this->_connection->rollback();
            $this->_acidenabld = false;
        }
        catch (PDOException $e ){
            $this->_sErrorCde = $e->getCode();
            $this->_sErrorMsg = __METHOD__ . " failed: " . $e->getMessage();
            return false;
        }
    }
    
    public function getErrorCode(){
        return $this->_sErrorCde;
    }
    public function getMessage(){
        return $this->_sErrorMsg;
    }
    
    
}

?>
