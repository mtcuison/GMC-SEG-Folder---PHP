<?php 
/**
 * localhost/security/web_connect.php
 */
require_once 'config.php';
require_once 'Nautilus.php';

$app = new web_connect(APPPATH);

if (!$app->LoadEnv()){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
    
}

$sql = "SELECT" .
            "  a.post_date `dPostedxx`" .
            ", a.post_content `sEventDtl`" .
            ", CONCAT('https://www.guanzongroup.com.ph/event/', a.post_name) `sEventURL`" .
            ", c.guid `sImageURL`" .
        " FROM wp_posts a" .
            " LEFT JOIN wp_postmeta b ON a.ID = b.post_id AND b.meta_key = '_thumbnail_id'" .
            " LEFT JOIN wp_posts c ON b.meta_value = c.ID" .
        " WHERE a.post_type = 'mec-events'" .
            " AND a.post_status = 'publish'";

$rows = $app->fetch($sql);

//validate client id if valid
if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Error found during loading..." . $app->getErrorMessage();
    echo json_encode($json);
    return;
} elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
    $json["error"]["message"] = "No CLIENT found based on the given ID.";
    echo json_encode($json);
    return;
}

$rows_found = sizeof($rows);

for($ctr=0;$ctr<$rows_found;$ctr++){
    $value = $rows[$ctr]["sEventDtl"];
    
    $pos = strpos($value, "*M");
    
    if ($pos == true){
        echo strip_tags($value);
        echo "<br>";
    }
    
    
}

return;

class web_connect{
    private $_bConfigLd;		//LoadEnv was executed
    
    private $_dbdrvr;
    private $_dbhost;
    private $_dbname;
    private $_dbuser;
    private $_dbpswd;
    private $_dbport;
    
    private $_connection;
    private $_acidenabld;
    
    private $_sErrorCde;
    private $_sErrorMsg;
    
    public function __construct($doc_path){
        $this->_apppath = $doc_path;
        date_default_timezone_set('Asia/Singapore');
    }
    
    public function LoadEnv($product="Website"){
        //Reset value of
        $this->_bConfigLd = false;
        
        if($product == ""){
            $this->_sErrorMsg = __METHOD__ . " failed: Product was empty...";
            return false;
        }
        
        //read config file
        if(!$this->loadConfig($product)){
            return false;
        }
        
        //Connect
        if(!$this->connect()){
            return false;
        }
        
        return true;
    }
    
    private function loadConfig($product){
        if(!file_exists($this->_apppath . "/config/gRider.ini")){
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
            $this->_dbuser =  Nautilus::Decrypt($main_config[$product]["UserName"]);
        }
        else{
            $this->_dbuser = "";
        }
        
        if (array_key_exists("Password", $main_config[$product])){
            $this->_dbpswd =  Nautilus::Decrypt($main_config[$product]["Password"]);
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
        
        $this->_bConfigLd = true;
        return true;
    }
    
    public function connect(){
        //echo "inside connect";
        
        if(!$this->_bConfigLd){
            return false;
        }
        
        $driver=$this->_dbdrvr;
        $db=$this->_dbname;
        $host=$this->_dbhost;
        $port=$this->_dbport;        
        
        try{
            if("sqlite" == $driver){
                $conn = new PDO($driver . ":" . $host . $db);
                //echo "sqlite";
            }
            else if("mysql" == $driver) {
                $conn = new PDO("mysql:dbname=$db;host=$host;port=$port", $this->_dbuser, $this->_dbpswd);
                //echo "mysql";
            }
            
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            
            $this->_connection = $conn;
            
            return true;
        } catch ( PDOException $e ){
            $this->_sErrorCde = $e->getCode();
            $this->_sErrorMsg = __METHOD__ . " failed: " . $e->getMessage();
            return false;
        }
    }
    
    //+++++++++++++++++++++++++++++
    //public function query($sql)
    //+++++++++++++++++++++++++++++
    //if function returns null. Be sure to check the Error Message.
    public function query($sql){
        if($this->_connection == null){
            $this->_sErrorCde = AppErrorCode::NULL_CONNECTION;
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
            $this->_sErrorCde = AppErrorCode::NULL_CONNECTION;
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
            $this->_sErrorCde = AppErrorCode::NULL_CONNECTION;
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
            $this->_sErrorCde = AppErrorCode::NULL_CONNECTION;
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
            $this->_sErrorCde = AppErrorCode::NULL_CONNECTION;
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
            $this->_sErrorCde = AppErrorCode::NULL_CONNECTION;
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
    
    //+++++++++++++++++++++++++++++
    //public function execute($sql)
    //+++++++++++++++++++++++++++++
    //returns -1 if execute encounter error
    public function execute($sql){
        if($this->_connection == null){
            $this->_sErrorCde = AppErrorCode::NULL_CONNECTION;
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
    
    //+++++++++++++++++++++++++++++
    //public function getConnection()
    //+++++++++++++++++++++++++++++
    public function getConnection(){
        return $this->_connection;
    }
    
    public function getErrorMessage(){
        return $this->_sErrorMsg;
    }
    
    public function getErrorCode(){
        return $this->_sErrorCde;
    }
}
?>