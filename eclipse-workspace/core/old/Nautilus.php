<?php
require_once 'Crypto.php';

class Nautilus{
    //Make sure to set the timezon at php.ini to Asia/Manila
    public const format_date = "Y-m-d";
    public const format_timestamp = "Y-m-d H:i:s";
    public const format_longdate = "F j, Y";
    public const format_longtimestamp = "F j, Y H:i:s";
    public const vat_rate = 12;
    
    private const _signature = "08220326";
    
    private $_envirment=array();
    private $_branchcfg=array();
    private $_prodctcfg=array();
    
    //private $_apppath = dirname($_SERVER['DOCUMENT_ROOT']);
    private $_apppath;
    
    private $_bConfigLd;		//LoadEnv was executed
    private $_bUserLogx;		//LogIn was executed
    
    private $_dbdrvr;
    private $_dbhost;
    private $_dbname;
    private $_dbuser;
    private $_dbpswd;
    private $_dbport;
    
    private $_connection;
    private $_acidenabld;
    
    //Reason of why function returns false...
    private $_sErrorMsg="";
    
    public function __construct($doc_path){
        $this->_apppath = $doc_path;
        date_default_timezone_set('Asia/Singapore');
    }
    
    //Rebuild Ghostrider class...
    public function Reload($clientid, $pcname, $product, $userid){
        if(!$this->LoadEnv($product)){
            $this->_sErrorMsg = __METHOD__ . " failed...\n" . $this->_sErrorMsg;
            return false;
        }
        
        if(!$this->loaduser($product, $userid)){
            return false;
        }
        
        return true;
    }
    
    //+++++++++++++++++++++++++++++
    //public function LoadEnv($clientid, $pcname, $product="gRider")
    //+++++++++++++++++++++++++++++
    public function LoadEnv($product="gRider"){
        //Reset value of
        $this->_bConfigLd = false;
        $this->_envirment = array();
        
        if($product == ""){
            $this->_sErrorMsg = __METHOD__ . " failed: Product was empty...";
            return false;
        }
        
        //read config file
        if(!$this->loadConfig($product))
            return false;
            
            //Connect
            if(!$this->connect())
                return false;
                
                //Indicate success of loading
                return true;
    }
    
    public function getclient($product, $pcname){
        $sql = "SELECT DISTINCT" .
            "  a.sClientID" .
            " FROM xxxSysWorkStation a" .
            " WHERE a.sProdctID = " . self::toSQL($product) .
            " AND a.sComptrNm = " . self::toSQL($pcname);
        
        $rows = $this->fetch($sql);
        
        if($rows == null){
            $this->_sErrorMsg = __METHOD__ . " failed.\n" . $this->_sErrorMsg;
            return "";
        }
        
        return $rows[0]["sClientID"];
        
    }
    
    //-----------------------------
    //public function validproduct($clientid, $pcname, $product)
    //-----------------------------
    public function validproduct($clientid, $pcname, $product){
        //echo "INSIDE validproduct...\n";
        $sql = "SELECT DISTINCT" .
            "  a.sClientID" .
            ", a.sClientNm" .
            ", a.sAddressx" .
            ", a.sTownName" .
            ", a.sZippCode" .
            ", a.sProvName" .
            ", a.sTelNoxxx" .
            ", a.sFaxNoxxx" .
            ", a.sApproved" .
            ", a.sBranchCd" .
            ", b.sProdctID" .
            ", b.sProdctNm" .
            ", b.sApplName" .
            ", c.sSysAdmin" .
            ", c.sNetWarex" .
            ", c.sMachinex" .
            ", c.dSysDatex" .
            ", c.dLicencex" .
            ", c.nNetError" .
            ", c.sSkinCode" .
            ", d.sComptrNm" .
            ", e.sBranchCD" .
            ", e.sBranchNm" .
            ", e.cWareHous" .
            ", e.cMainOffc" .
            " FROM xxxSysClient a" .
            ", xxxSysObject b" .
            ", xxxSysApplication c" .
            " LEFT JOIN xxxSysWorkStation d" .
            " ON c.sClientID = d.sClientID AND c.sProdctID = d.sProdctID" .
            ", Branch e" .
            " WHERE c.sClientID = a.sClientID" .
            " AND c.sProdctID = b.sProdctID" .
            " AND a.sBranchCd = e.sBranchCd" .
            " AND d.sClientID = " . self::toSQL($clientid) .
            " AND d.sProdctID = " . self::toSQL($product) .
            " AND d.sComptrNm = " . self::toSQL($pcname) ;
        
        $rows = $this->fetch($sql);
        //echo $sql;
        if($rows == null){
            $this->_sErrorMsg = __METHOD__ . " failed.\n" . $this->_sErrorMsg;
            return false;
        }
        
        if($rows[0]["sComptrNm"] == null){
            $this->_sErrorMsg = __METHOD__ . " failed.\n" . "Computer is Not Registered to Use The Selected System";
            return false;
        }
        
        if($rows[0]["nNetError"] >= 200){
            $this->_sErrorMsg = __METHOD__ . " failed.\n" . "Maximum Error Limit has been Reached!";
            return false;
        }
        
        if(!$this->isSignatureOk($rows[0]["sMachinex"], $rows[0]["sNetWarex"], $rows[0]["sSysAdmin"])){
            $this->_sErrorMsg = __METHOD__ . " failed.\n" . $this->_sErrorMsg;
            return false;
        }
        
        $this->_envirment = array();
        $this->_envirment["sClientID"] = $rows[0]["sClientID"];
        $this->_envirment["sClientNm"] = $rows[0]["sClientNm"];
        $this->_envirment["sAddressx"] = $rows[0]["sAddressx"];
        $this->_envirment["sTownName"] = $rows[0]["sTownName"];
        $this->_envirment["sZippCode"] = $rows[0]["sZippCode"];
        $this->_envirment["sProvName"] = $rows[0]["sProvName"];
        $this->_envirment["sTelNoxxx"] = $rows[0]["sTelNoxxx"];
        $this->_envirment["sFaxNoxxx"] = $rows[0]["sFaxNoxxx"];
        $this->_envirment["sApproved"] = $rows[0]["sApproved"];
        $this->_envirment["sBranchCd"] = $rows[0]["sBranchCd"];
        $this->_envirment["sProdctID"] = $rows[0]["sProdctID"];
        $this->_envirment["sProdctNm"] = $rows[0]["sProdctNm"];
        $this->_envirment["sApplName"] = $rows[0]["sApplName"];
        $this->_envirment["sSysAdmin"] = self::Decrypt($rows[0]["sSysAdmin"]);
        //$this->_envirment["sNetWarex"] = self::Decrypt($rows[0]["sNetWarex"]);
        //$this->_envirment["sMachinex"] = self::Decrypt($rows[0]["sMachinex"]);
        $this->_envirment["dSysDatex"] = $rows[0]["dSysDatex"];
        $this->_envirment["dLicencex"] = $rows[0]["dLicencex"];
        $this->_envirment["nNetError"] = $rows[0]["nNetError"];
        $this->_envirment["sSkinCode"] = $rows[0]["sSkinCode"];
        $this->_envirment["sComptrNm"] = $rows[0]["sComptrNm"];
        $this->_envirment["sBranchCD"] = $rows[0]["sBranchCD"];
        $this->_envirment["sBranchNm"] = $rows[0]["sBranchNm"];
        $this->_envirment["cWareHous"] = $rows[0]["cWareHous"];
        $this->_envirment["cMainOffc"] = $rows[0]["cMainOffc"];
        return true;
    }
    
    public function validLog($logno, $prodctid, $clientid, $userid, $pcname){
        $sql = "SELECT b.sClientID, a.*" .
            " FROM xxxSysUserLog a" .
            " LEFT JOIN xxxSysWorkStation b ON a.sProdctID = b.sProdctID AND a.sComptrNm = b.sComptrNm" .
            " WHERE a.sLogNoxxx = " . self::toSQL($logno) .
            " AND b.sClientID = " . self::toSQL($clientid);
        
        $rows = $this->fetch($sql);
        //echo $sql . "\n";
        
        if($rows == null){
            $this->_sErrorMsg = "Invalid AUTH LOG detected";
            return false;
        }
        
        //if($rows[0]["sClientID"] != $clientid){
        //	$this->_sErrorMsg = "Invalid AUTH CLIENT detected";
        //	return false;
        //}
        
        if($rows[0]["sUserIDxx"] != $userid){
            $this->_sErrorMsg = "Invalid AUTH USER detected";
            return false;
        }
        
        if($rows[0]["sProdctID"] != $prodctid){
            $this->_sErrorMsg = "Invalid AUTH DEVICE detected";
            return false;
        }
        
        if($rows[0]["sComptrNm"] != $pcname){
            $this->_sErrorMsg = "Invalid AUTH DEVICE detected";
            return false;
        }
        
        //Check if logno has logout
        if($rows[0]["dLogOutxx"] != null){
            $this->_sErrorMsg = "Invalid AUTH NO detected";
            return false;
        }
        
        //get logintime of this logno
        $logdate = new DateTime($rows[0]["dLogInxxx"]);
        //get the current datetime
        $logused = new DateTime();
        
        //compare the dates
        if($logused < $logdate){
            $this->_sErrorMsg = "Invalid AUTH DATE detected";
            return false;
        }
        
        
        //echo $logdate->format('Y-m-d H:i:s');
        //echo $logused->format('Y-m-d H:i:s');
        //echo "Diff in days: " . $interval->d;
        if($logdate->format('Y-m-d') != $logused->format('Y-m-d')){
            $this->_sErrorMsg = "Expired AUTH DATE detected";
            return false;
        }
        
        return true;
    }
    
    
    //+++++++++++++++++++++++++++++
    //public function Login($username, $password, $product)
    //+++++++++++++++++++++++++++++
    public function Login($username, $password, $product, $pcname){
        $user = self::Encrypt($username);
        $pswd = self::Encrypt($password);
        
        //echo $user;
        $user = self::HexToString($user);
        $pswd = self::HexToString($pswd);
        //echo $user;
        $sql = "SELECT *" .
            " FROM xxxSysUser" .
            " WHERE sLogNamex = " . self::toSQL($user) .
            " AND sPassword = " . self::toSQL($pswd) .
            " AND cUserStat = '1'" .
            " ORDER BY nUserLevl ASC";
        $rows = $this->fetch($sql);
        //echo $sql . "\n";
        
        if($rows == null){
            $this->_sErrorMsg = __METHOD__ . " failed.\n" . $this->_sErrorMsg;
            return false;
        }
        
        //$logno = self::GetNextCode("xxxSysUserLog", "sLogNoxxx", true, $this->_connection, $this->_envirment["sBranchCD"]);
        $logno = self::GetNextCode("xxxSysUserLog", "sLogNoxxx", true, $this->_connection, "MX01");
        $userid = $rows[0]["sUserIDxx"];
        $date = new DateTime('now');
        $login = $date->format(self::format_timestamp);
        
        $sql = "INSERT INTO xxxSysUserLog (sLogNoxxx, sUserIDxx, dLogInxxx, sProdctID, sComptrNm) VALUES(:logno, :userid, :login, :product, :pcname)";
        $stmt = $this->prepare($sql);
        if($stmt == null){
            $this->_sErrorMsg = "Login failed...\n" . $this->_sErrorMsg;
            return false;
        }
        $stmt->bindValue(':logno', $logno);
        $stmt->bindValue(':userid', $userid);
        $stmt->bindValue(':login', $login);
        $stmt->bindValue(':product', $product);
        $stmt->bindValue(':pcname', $pcname);
        
        $stmt->execute();
        
        $this->_envirment["sUserIDxx"] = $rows[0]["sUserIDxx"];
        $this->_envirment["sLogNoxxx"] = $logno;
        //TODO:
        //$this->_envirment["sUserName"] = self::Decrypt($rows[0]["sUserName"]);
        
        
        if($rows[0]["sEmployNo"] != ""){
            $this->_envirment["sUserName"] = $this::getEmployeeInfo($rows[0]["sEmployNo"]);
            //echo "Department " . $this->_envirment["sDeptIDxx"];
        }
        else{
            $this->_envirment["sUserName"] = self::Decrypt($rows[0]["sLogNamex"]);
        }
        
        $this->_envirment["sEmployNo"] = $rows[0]["sEmployNo"];
        $this->_envirment["nUserLevl"] = $rows[0]["nUserLevl"];
        
        return true;
    }
    
    //+++++++++++++++++++++++++++++
    //public function Logout($logno)
    //+++++++++++++++++++++++++++++
    public function Logout($logno){
        $sql = "UPDATE xxxSysUserLog SET dLogOutxx = :logout WHERE sLogNoxxx = :logno";
        $stmt = $this->prepare($sql);
        if($stmt == null){
            $this->_sErrorMsg = __METHOD__ . " failed...\n" . $this->_sErrorMsg;
            return false;
        }
        
        $date = new DateTime('now');
        $logout = $date->format(self::format_timestamp);
        
        $stmt->bindValue(':logout', $logout);
        $stmt->bindValue(':logno', $logno);
        
        $stmt->execute();
        
        return true;
    }
    
    //+++++++++++++++++++++++++++++
    //public function Connect
    //+++++++++++++++++++++++++++++
    public function connect(){
        //echo "inside connect";
        
        if(!$this->_bConfigLd)
            return false;
            
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
                $this->_sErrorMsg = __METHOD__ . " failed: " . $e->getMessage();
                return false;
            }
    }
    
    //+++++++++++++++++++++++++++++
    //public function execute($sql)
    //+++++++++++++++++++++++++++++
    //returns -1 if execute encounter error
    public function execute($sql, $source="", $refer="", $stat=0){
        if($this->_connection == null){
            $this->_sErrorMsg = __METHOD__ . " failed: " . "Connection object is null.";
            return -1;
        }
        
        try {
            //make sure to indicate sourceno and sourcecd for parent transaction entries/updates
            if(!($source == "" && $refer == "")){
                $transno = $this->GetNextCode("xxxAuditTrail", "sTransNox", true, $this->_connection, "MX01");
                //$transno = $this->GetNextCode("xxxAuditTrail", "sTransNox", true, $this->_connection, $this->_envirment["sBranchCd"]);
                $user = $this->_envirment["sUserIDxx"];
                $date = new DateTime('now');
                $stamp = $date->format(self::format_timestamp);
                
                $sqlx = "INSERT INTO xxxAuditTrail(sTransNox, sObjectCd, sReferNox, cStatusxx, cTranStat, sModified, dModified)" .
                    "VALUES('$transno', '$source', '$refer', '$stat', '0', '$user', '$stamp')";
                $this->_connection->exec($sqlx);
            }
            
            $affected = $this->_connection->exec($sql);
            return $affected;
        }
        catch (PDOException $e ){
            $this->_sErrorMsg = __METHOD__ . " failed: " . $e->getMessage();
            return -1;
        }
    }
    
    //+++++++++++++++++++++++++++++
    //public function query($sql)
    //+++++++++++++++++++++++++++++
    //if function returns null. Be sure to check the Error Message.
    public function query($sql){
        if($this->_connection == null){
            $this->_sErrorMsg = __METHOD__ . " failed: " . "Connection object is null.";
            return null;
        }
        
        try {
            $stmt = $this->_connection->query($sql);
            return $stmt;
        }
        catch (PDOException $e ){
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
            $this->_sErrorMsg = __METHOD__ . " failed: " . "Connection object is null.";
            return null;
        }
        
        try {
            $stmt = $this->_connection->query($sql);
            return $stmt->fetchAll();
        }
        catch (PDOException $e ){
            $this->_sErrorMsg = __METHOD__ . " failed: " . $e->getMessage();
            return null;
        }
    }
    
    public function prepare($sql){
        if($this->_connection == null){
            $this->_sErrorMsg = __METHOD__ . " failed: " . "Connection object is null.";
            return null;
        }
        
        try {
            $stmt = $this->_connection->prepare($sql);
            return $stmt;
        }
        catch (PDOException $e ){
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
            $this->_sErrorMsg = __METHOD__ . " failed: " . $e->getMessage();
            return false;
        }
    }
    
    //+++++++++++++++++++++++++++++
    //public function getConnection()
    //+++++++++++++++++++++++++++++
    public function getConnection(){
        return $this->_connection;
    }
    
    //+++++++++++++++++++++++++++++
    //public function Env()
    //+++++++++++++++++++++++++++++
    public function Env($index){
        //if LoadEnv was not yet executed then return an empty string...
        if(!$this->_bConfigLd)
            return "";
            
            if (array_key_exists($index, $this->_envirment))
                return $this->_envirment[$index];
                else
                    return "";
    }
    
    //+++++++++++++++++++++++++++++
    //public function showEnv()
    //+++++++++++++++++++++++++++++
    public function showEnv(){
        print_r($this->_envirment);
    }
    
    public function branch($field, $load=false){
        if(!$this->_bConfigLd)
            return "";
            
            /*
             if (array_key_exists($field, $this->_branchcfg) && !$load)
             return $this->_branchcfg[$field];
             */
             
             $sql = "SELECT $field FROM Branch_Others WHERE sBranchCD = " . self::toSQL($this->_envirment["sBranchCD"]);
             
             if($rows = $this->fetch($sql))
                 return $rows[0][$field];
                 
                 $sql = "SELECT $field" .
                 " FROM xxxOtherInfo a" .
                 " LEFT JOIN xxxSysClient b" .
                 " ON a.sClientID = b.sClientID" .
                 " WHERE sBranchCD = " . self::toSQL($this->_envirment["sBranchCD"]);
                 
                 if($rows = $this->fetch($sql))
                     return $rows[0][$field];
                     else
                         return "";
                         
    }
    
    public function product($field){
        if(!$this->_bConfigLd)
            return "";
            
            /*
             if (array_key_exists($field, $this->_prodctcfg) && !$load)
             return $this->_prodctcfg[$field];
             */
             
             $sql = "SELECT sValuexxx" .
                 " FROM xxxOtherConfig" .
                 " WHERE sProdctID = " . self::toSQL($this->_envirment["sProdctID"]) .
                 " AND sConfigID = " . self::toSQL($field);
             
             
             if($rows = $this->fetch($sql))
                 return $rows[0]["sValuexxx"];
                 else
                     return "";
                     
    }
    
    //+++++++++++++++++++++++++++++
    //+++++++++++++++++++++++++++++
    //public function getErrorMessage()
    public function getErrorMessage(){
        return $this->_sErrorMsg;
    }
    
    //public function getMessage(){
    //	return $this->_sWarningMsg;
    //}
    
    //-----------------------------
    //private function loadConfig($product)
    //-----------------------------
    private function loadConfig($product){
        //echo "INSIDE loadConfig...\n";
        
        if(!file_exists($this->_apppath . "/config/gRider.ini")){
            $this->_sErrorMsg = "Config file not found!";
            return false;
        }
        
        $main_config = parse_ini_file($this->_apppath . "/config/gRider.ini", true);
        $this->_dbhost = $main_config[$product]["ServerName"];
        $this->_dbname = $main_config[$product]["Database"];
        
        if (array_key_exists("DBDriver", $main_config[$product])) {
            $this->_dbdrvr = $main_config[$product]["DBDriver"];
        }
        else
            $this->_dbdrvr = "mysql";
            
            
            if (array_key_exists("UserName", $main_config[$product])) {
                $this->_dbuser = self::Decrypt($main_config[$product]["UserName"]);
            }
            else
                $this->_dbuser = "";
                
                if (array_key_exists("Password", $main_config[$product])){
                    $this->_dbpswd = self::Decrypt($main_config[$product]["Password"]);
                }
                else
                    $this->_dbpswd = "";
                    
                    if (array_key_exists("Port", $main_config[$product]))
                        $this->_dbport = $main_config[$product]["Port"];
                        else
                            $this->_dbport = "3306";
                            
                            //echo "Loadconfig done...";
                            
                            //echo $this->_dbuser . "-" . $this->_dbpswd . "\n";
                            //echo $main_config[$product]["UserName"] . "-" . $main_config[$product]["Password"] . "\n";
                            
                            $this->_bConfigLd = true;
                            return true;
    }
    
    //-----------------------------
    //private function isSignatureOk($machine, $netware)
    //-----------------------------
    private function isSignatureOk($machine, $netware, $sysadmn){
        //decrypt the value of $machine and $netware
        //kalyptus - 2018.12.08 09:43am
        //TODO: Please remove self::StringToHex later on
        $machinex = self::Decrypt(self::StringToHex($machine));
        $netwarex = self::Decrypt(self::StringToHex($netware));
        //echo "Machine " . self::Encrypt("marlon") . " Netware: " . self::Decrypt(self::Encrypt("marlon"));
        //echo "Machine " . $machinex . " Netware: " . netwarex;
        //create the prepared statement
        $sql = "SELECT sUserIDxx, sLogNamex" .
            " FROM xxxSysUser" .
            " WHERE sUserIDxx IN (:machine, :netware)";
        $stmt = $this->prepare($sql);
        if($stmt == null){
            $this->_sErrorMsg = __METHOD__ . " failed: Error loading of product...\n" . $this->_sErrorMsg;
            return false;
        }
        $stmt->bindValue(':machine', $machinex);
        $stmt->bindValue(':netware', $netwarex);
        
        $stmt->execute();
        $rows = $stmt->fetchAll();
        
        //echo $machinex . "-" . $netwarex . "-" . sizeof($rows) . "\n";
        
        if(sizeof($rows) <> 2){
            $this->_sErrorMsg = __METHOD__ . " failed: Error verifying product..." . sizeof($rows);
            return false;
        }
        
        for($ctr=0;$ctr<sizeof($rows);$ctr++){
            if($rows[$ctr]["sUserIDxx"]==$netwarex){
                //echo $rows[$ctr]["sLogNamex"] . "-" . $sysadmn;
                if($rows[$ctr]["sLogNamex"] <> $sysadmn){
                    $this->_sErrorMsg = __METHOD__ . " failed: Unregistered copy of product detected...";
                    return false;
                }
            }
        }
        
        return true;
    }
    
    //-----------------------------
    //private function loaduser($userid)
    //-----------------------------
    private function loaduser($product, $userid){
        $sql = "SELECT *" .
            " FROM xxxSysUser" .
            " WHERE sUserIDxx = " . self::toSQL($userid);
        $rows = $this->fetch($sql);
        //echo $sql . "\n";
        
        if($rows == null){
            $this->_sErrorMsg = "loaduser failed.\n" . $this->_sErrorMsg;
            return false;
        }
        
        $this->_envirment["sUserIDxx"] = $rows[0]["sUserIDxx"];
        //TODO
        //$this->_envirment["sUserName"] = self::Decrypt($rows[0]["sUserName"]);
        
        if($rows[0]["sEmployNo"] != ""){
            $this->_envirment["sUserName"] = $this::getEmployeeInfo($rows[0]["sEmployNo"]);
            //echo "Department " . $this->_envirment["sDeptIDxx"];
        }
        else{
            $this->_envirment["sUserName"] = self::Decrypt($rows[0]["sLogNamex"]);
        }
        
        $this->_envirment["sEmployNo"] = $rows[0]["sEmployNo"];
        $this->_envirment["nUserLevl"] = $rows[0]["nUserLevl"];
        
        return true;
    }
    
    //::::::::::::::::::::::::::::::
    //public static function HexToString($value)
    //::::::::::::::::::::::::::::::
    public static function HexToString($value){
        if(trim($value) == "")
            return $value;
            return pack("H*", $value);
    }
    
    //::::::::::::::::::::::::::::::
    //public static function StringToHex($value)
    //::::::::::::::::::::::::::::::
    public static function StringToHex($value){
        if($value == "")
            return $value;
            return bin2hex($value);
    }
    
    //::::::::::::::::::::::::::::::
    //public static function Encrypt($value, $salt)
    //::::::::::::::::::::::::::::::
    public static function Encrypt($value, $salt=""){
        if($salt=="")
            $salt = self::_signature;
            
            $result = Crypto::Encrypt($value, $salt);
            
            return self::StringToHex($result);
    }
    
    //::::::::::::::::::::::::::::::
    //public static function Decrypt($value, $salt)
    //::::::::::::::::::::::::::::::
    public static function Decrypt($value, $salt=""){
        $result = self::HexToString($value);
        
        if($salt=="")
            $salt = self::_signature;
            
            return Crypto::Decrypt($result, $salt);
    }
    
    //::::::::::::::::::::::::::::::
    //public static function toSQL($value)
    //::::::::::::::::::::::::::::::
    //use in comparing the passwords
    public static function toSQL($value){
        switch(gettype($value)){
            case "integer":
                return $value;
            case "double":
                return $value;
            case "NULL":
                return "NULL";
            case "boolean":
                return $value;
            default:
                return "'" . $value . "'";
        }
    }
    
    //public static function DBEmpty($value)
    //::::::::::::::::::::::::::::::
    //use is setting the initial value of fields base on $type extracted from the database;
    public static function DBEmpty($type){
        switch($type){
            case "DATETIME":
            case "DATE":
                return null;
            case "TINY":
            CASE "LONG":
            case "SHORT":
                return 0;
            case "DECIMAL":
            case "NEWDECIMAL":
                return 0.00;
            default:
                return "";
        }
    }
    
    //::::::::::::::::::::::::::::::
    //public static function GetNextCode($table, $field, $year, $conn, $branch="")
    //::::::::::::::::::::::::::::::
    //where $table=string(tablename)
    //      $field=string(fieldname)
    //		  $year=boolean(do we need to use year)
    //		  $conn=connection object
    //      $branch=string(branch code)
    public static function GetNextCode($table, $field, $year, $conn, $branch=""){
        $value="";
        
        if(trim($branch)<>"")
            $value .= $branch;
            
            //use the date function of php since the app/web server and dbf server
            //is installed in one pc...
            if($year)
                $value .= substr(date("Y"), -2);
                
                //extract last row from the table
                $sql = "SELECT $field FROM $table" .
                " WHERE $field LIKE " . self::toSQL($value . "%") .
                " ORDER BY $field DESC LIMIT 1";
                $stmt = $conn->query($sql);
                
                //get meta info of the column to get the size of field
                $meta = $stmt->getColumnMeta(0); //$meta["len"]
                
                //get sizes for next transact no computation
                $ctr = 1;
                $remlen = $meta["len"] - strlen($value);
                
                if($row=$stmt->fetch()){
                    $ctr = substr($row[$field], -1 * $remlen) + 1;
                }
                $value = $value . str_pad($ctr, $remlen, "0", STR_PAD_LEFT);
                
                return $value;
    }
    
    //::::::::::::::::::::::::::::::
    //public static function GetNextReference($table, $field, $order, $colfilter, $colvalue, $conn)
    //::::::::::::::::::::::::::::::
    //where $table=string(tablename)
    //      $field=string(fieldname)
    //		  $order=string(fieldname use in ordering the result)
    //		  $colfilter=string(fieldname use in filtering the data to be use in getting the next reference)
    //		  $colvalue=string(the value of the column to be used in filtering)
    //		  $conn=connection object
    public static function GetNextReference($table, $field, $order, $colfilter, $colvalue, $conn){
        $sql = "SELECT $field FROM $table" .
        " WHERE $colfilter LIKE " . self::toSQL($colvalue . "%") .
        " ORDER BY $order DESC, $field DESC" .
        " LIMIT 1";
        $stmt = $conn->query($sql);
        $meta = $stmt->getColumnMeta(0);
        
        $value = "0";
        if($row = $stmt->fetch()){
            $value = $row[$field];
            $size = strlen($row[$field]);
        }
        else
            $size = $meta["len"];
            
            $value += 1;
            
            return str_pad($value, $size, "0", STR_PAD_LEFT);
    }
    
    //::::::::::::::::::::::::::::::
    //public static function isCS_Equal()
    //::::::::::::::::::::::::::::::
    //check if contents of session and cookies are equal
    //kalyptus - 2017.11.07 02:25pm
    public function isHeaderOk($myheader){
        //Product ID
        if(!isset($myheader['g-api-id'])){
            $this->_sErrorMsg = "Unset API ID detected";
            return false;
        }
        
        //Computer Name/IEMI of Device
        if(!isset($myheader['g-api-imei'])){
            $this->_sErrorMsg = "Unset DEVICE ID detected";
            return false;
        }
        
        //Current Time
        if(!isset($myheader['g-api-key'])){
            $this->_sErrorMsg = "Unset TAG ID detected";
            return false;
        }
        
        //SysClient ID
        if(!isset($myheader['g-api-client'])){
            $this->_sErrorMsg = "Unset TAG ID detected";
            return false;
        }
        
        //SysLog No
        if(!isset($myheader['g-api-log'])){
            $this->_sErrorMsg = "Unset LOG ID detected";
            return false;
        }
        
        //USER ID
        if(!isset($myheader['g-api-user'])){
            $this->_sErrorMsg = "Unset USER ID detected";
            return false;
        }
        
        //$myheader['g-api-imei'] + $myheader['g-api-key'] HASH
        if(!isset($myheader['g-api-hash'])){
            $this->_sErrorMsg = "Unset HASH SECURITY KEY detected";
            return false;
        }
        
        //echo md5($myheader['g-api-imei'] . $myheader['g-api-key']) . '---'  . $myheader['g-api-hash'];
        
        //Check validity of $_SERVER['g-api-imei'], $_SERVER['g-api-key'], and $_SERVER['g-api-hash']
        if(md5($myheader['g-api-imei'] . $myheader['g-api-key']) != $myheader['g-api-hash']){
            $this->_sErrorMsg = "Invalid AUTH KEY detected";
            return false;
        }
        
        $date = date('Ymd');
        //echo $date;
        //echo substr($myheader['g-api-key'], 0, 8);
        
        if(substr($myheader['g-api-key'], 0, 8) != $date){
            $this->_sErrorMsg = "Invalid HASH SECURITY KEY detected";
            return false;
        }
        
        return true;
        
    }
    
    //::::::::::::::::::::::::::::::
    //public static function validDate($date, $format = 'Y-m-d H:i:s')
    //::::::::::::::::::::::::::::::
    //kalyptus - 2017.11.07 02:25pm
    //extracted from http://php.net/manual/en/function.checkdate.php
    public static function validDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
    
    //::::::::::::::::::::::::::::::
    //public static function array2sql($meta, $table, $new, $old=null, $filter="", $smodified="", $dmodified="", $sexcluded="")
    //::::::::::::::::::::::::::::::
    //converts a recordset($new) to sql (equivalent to ado2SQL)
    public static function array2sql($meta, $table, $new, $old=null, $filter="", $smodified="", $dmodified="", $sexcluded=""){
        if($smodified <> "")
            $sexcluded .= "sModified";
            
            if($dmodified <> "")
                $sexcluded .= "dModified";
                
                if($old == null && $filter == ""){
                    $fields = "";
                    $values = "";
                    $ctr=0;
                    while(array_key_exists($ctr, $meta)){
                        $field = $meta[$ctr]["name"];
                        $value = $new[$field];
                        if($sexcluded==""){
                            $fields .= ", $field";
                            $values .= ", " . self::toSQL($value);
                        }
                        else{
                            if(stripos($sexcluded, $field) === false){
                                $fields .= ", $field";
                                $values .= ", " . self::toSQL($value);
                            }
                        }
                        $ctr++;
                    }
                    
                    if($smodified <> ""){
                        $fields .= ", sModified";
                        $values .= ", " . self::toSQL($smodified);
                    }
                    
                    if($dmodified <> ""){
                        $fields .= ", dModified";
                        $values .= ", " . self::toSQL($dmodified);
                    }
                    
                    $fields = substr($fields, 2);
                    $values = substr($values, 2);
                    return "INSERT INTO $table($fields) VALUES($values)";
                }
                else{
                    $ctr=0;
                    $sql="";
                    while(array_key_exists($ctr, $meta)){
                        $field = $meta[$ctr]["name"];
                        if($new[$field] <> $old[$field]){
                            $value = $new[$field];
                            if($sexcluded=="")
                                $sql .= ", $field = " . self::toSQL($value);
                                else{
                                    if(stripos($sexcluded, $field) === false){
                                        $sql .= ", $field = " . self::toSQL($value);
                                    }
                                }
                        }
                        $ctr++;
                    }
                    
                    if($sql == "")
                        return "";
                        
                        if($smodified <> ""){
                            $sql .= ", sModified = " . self::toSQL($smodified);
                        }
                        
                        if($dmodified <> ""){
                            $sql .= ", dModified = " . self::toSQL($dmodified);
                        }
                        
                        $sql = substr($sql, 2);
                        return "UPDATE $table SET $sql WHERE $filter";
                }
    }
    
    //::::::::::::::::::::::::::::::
    //public static function isValidEmail($email)
    //::::::::::::::::::::::::::::::
    public static function isValidEmail($email){
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    //::::::::::::::::::::::::::::::
    //public static function isValidMobile($mobile)
    //::::::::::::::::::::::::::::::
    public static function isValidMobile($mobile){
        return preg_match('/^[0-9]{10}+$/', $mobile);
    }
    
    //::::::::::::::::::::::::::::::
    //public static function isValidTelephone($telno)
    //::::::::::::::::::::::::::::::
    public static function isValidTelephone($telno){
        return preg_match('/^[0-9]{9}+$/', $telno);
    }
    
    //::::::::::::::::::::::::::::::
    //public static function addcondition($sql, $filter)
    //::::::::::::::::::::::::::::::
    public static function addcondition($sql, $filter){
        $subs = self::gsplit(" UNION ", $sql);
        $newsql = "";
        $size = sizeof($subs);
        
        if($size == 0){
            return $sql;
        }
        elseif($size == 1) {
            return self::insertcondition($subs[0], $filter);
        }
        else{
            $newsql = self::insertcondition($subs[0], $filter);
            for($ctr=1;$ctr<$size;$ctr++){
                $newsql .= " UNION " . self::insertcondition($subs[$ctr], $filter);
            }
            return $newsql;
        }
    }
    
    //::::::::::::::::::::::::::::::
    //public static function gsplit($pattern, $string)
    //::::::::::::::::::::::::::::::
    public static function gsplit($pattern, $string){
        return preg_split("/$pattern/i", $string);
    }
    
    //::::::::::::::::::::::::::::::
    //public static function str_insert($str, $ins, $pos)
    //::::::::::::::::::::::::::::::
    public static function str_insert($str, $ins, $pos){
        //$len = strlen($str);
        return substr($str, 0, $pos) . $ins . substr($str, $pos);
    }
    
    //::::::::::::::::::::::::::::::
    //public static function app_encrypt($value, $salt)
    //::::::::::::::::::::::::::::::
    public static function app_encrypt($value, $salt){
        $method = "AES-256-CBC";
        $iv = "0a6575be41b8e2b8";
        $result = openssl_encrypt($value, $method, $salt, 0, $iv);
        $result = base64_encode($result);
        return $result;
    }
    
    //::::::::::::::::::::::::::::::
    //public static function app_decrypt($value, $salt)
    //::::::::::::::::::::::::::::::
    public static function app_decrypt($value, $salt){
        $method = "AES-256-CBC";
        $iv = "0a6575be41b8e2b8";
        $value = base64_decode($value);
        $result = openssl_decrypt($value, $method, $salt, 0, $iv);
        return $result;
    }
    
    public static function loadmeta($stmt, &$xmeta, &$data){
        $xmeta = array();
        for($ctr=0;$ctr<$stmt->columnCount();$ctr++){
            $meta = $stmt->getColumnMeta($ctr);
            
            $name = strtolower($meta["name"]);
            $xmeta[$name] = array();
            $xmeta[$name]["name"] = $meta["name"];
            $xmeta[$name]["pos"] = $ctr;
            $xmeta[$name]["len"] = $meta["len"];
            $xmeta[$name]["type"] = $meta["native_type"];
            
            $xmeta[$ctr] = array();
            $xmeta[$ctr]["name"] = $meta["name"];
            $xmeta[$ctr]["len"] = $meta["len"];
            $xmeta[$ctr]["type"] = $meta["native_type"];
            
            $data[$meta["name"]] = self::DBEmpty($meta["native_type"]);
        }
    }
    
    public static function loadRecord($app, $sql, &$new, &$old, &$meta, $bdetail){
        $stmt = $app->query($sql);
        
        //check result of initializing detail table
        if($stmt == null){
            return false;
        }
        
        if($bdetail){
            //extract the metadata info from the statement object
            Nautilus::loadmeta($stmt, $meta, $new[0]);
        }
        else{
            Nautilus::loadmeta($stmt, $meta, $new);
        }
        
        //kalyptus - 2017.11.25 02:56pm
        //store fetch row/data to the _master and _oldmaster array object
        if(is_array($old)){
            if($bdetail){
                $ctrx = 0;
                while($row = $stmt->fetch()){
                    for($ctr=0;$ctr<$stmt->columnCount();$ctr++){
                        $new[$ctrx][$meta[$ctr]["name"]] = $row[$meta[$ctr]["name"]];
                        $old[$ctrx][$meta[$ctr]["name"]] = $row[$meta[$ctr]["name"]];
                    }
                    $ctrx++;
                }
            }
            else{
                //since this is a master record then just issue a single fetch
                $row = $stmt->fetch();
                
                //check if there are loaded record/transaction
                if(!$row){
                    return false;
                }
                
                //transfer loaded record to our recordset
                for($ctr=0;$ctr<$stmt->columnCount();$ctr++){
                    $new[$meta[$ctr]["name"]] = $row[$meta[$ctr]["name"]];
                    $old[$meta[$ctr]["name"]] = $row[$meta[$ctr]["name"]];
                }
            }
        }
        
        return true;
    }
    
    public static function savemaster($app, $new, $old, $meta, $mode, $table, $pkey, $pvalue, $exempt, &$warning, $parent, $code){
        //initialize the datetime object
        $date = new DateTime('now');
        
        //if called by a parent object, initialize acid...
        $source = "";
        $refer = "";
        if($parent == null || $parent == ""){
            $source = $code;
            $refer = $pvalue;
        } //if($this->parent == null || $this->parent == ""){
        
        //extract the SQL from our array...
        if($mode == EditMode::AddNew){
            $sql = Nautilus::array2sql($meta, $table, $new, null, "", "", $date->format(Nautilus::format_timestamp), $exempt);
        }
        else{
            $sql = Nautilus::array2sql($meta, $table, $new, $old, "$pkey = " . Nautilus::toSQL($pvalue) , "", $date->format(Nautilus::format_timestamp), $exempt);
        } //if($mode == EditMode::AddNew) ... else{
        
        //check if there are updates detected
        if($sql != ""){
            //check if the update was successfuly
            $affected = $app->execute($sql, $source, $refer, $mode == EditMode::AddNew?TrailType::New:TrailType::Update);
            
            if($affected <= 0){
                //create a warning message to be check by the calling html page...
                if($affected == 0){
                    $warning = __METHOD__ . " failed: No record was affected.";
                }
                else{
                    $warning = __METHOD__ . " failed: " . $app->getErrorMessage();
                } //if($affected == 0) ... else{
                
                return false;
            } //if($affected <= 0){
        } // if($sql != ""){
        
        return true;
    }
    
    public static function savedetail($app, $new, $old, $meta, $mode, $table, $pkey, $pvalue, $exempt, &$warning){
        //initialize the datetime object
        $date = new DateTime('now');
        
        //since the detail table's primary key is sTransNox + nEntryNox
        //we used the sTransNox + nEntryNox when we refer to the row
        //either updating the row or deleting the row...
        $oldrow = 1;
        for($ctr=0;$ctr<sizeof($new);$ctr++){
            $row = $new[$ctr];
            $row["nEntryNox"] = $ctr+1;
            if($mode == EditMode::AddNew){
                $row[$pkey] = $pvalue;
                $sql = Nautilus::array2sql($meta, $table, $row, null, "", "", $date->format(Nautilus::format_timestamp), $exempt);
            } //if($mode == EditMode::AddNew)
            else{
                //assume that previous primary key value was save with x as prefix
                //e.g. sTransNox => xTransNox
                $xpkey = "x" . substr($pkey, 1);
                if(!isset($row[$xpkey]) || $row[$xpkey] == ""){
                    $row[$pkey] = $pvalue;
                    $sql = Nautilus::array2sql($meta, $table, $row, null, "", "", $date->format(Nautilus::format_timestamp), $exempt);
                }
                else{
                    $sql = Nautilus::array2sql($meta, $table, $row, $old[$oldrow-1], "$pkey = '$pvalue' AND nEntryNox = $oldrow", "", $date->format(Nautilus::format_timestamp), $exempt);
                    $oldrow++;
                } //if(!isset($row["xTransNox"]) || $row["xTransNox"] == ""){
            } //if($mode == EditMode::AddNew) ... else{
            
            //check if there are updates detected
            if($sql != ""){
                //check if the update was successfuly
                $affected = $app->execute($sql);
                
                if($affected <= 0){
                    //create a warning message to be check by the calling html page...
                    if($affected == 0){
                        $warning = __METHOD__ . " failed: No record was affected.";
                    }
                    else{
                        $warning = __METHOD__ . " failed: " . $app->getErrorMessage();
                    } //if($affected == 0) ... else{
                    
                    return false;
                } //if($affected <= 0){
            } //if($sql != ""){
        } //for($ctr=0;$ctr<sizeof($new);$ctr++){
        
        //Note: How are we going to indicate that this update will result
        //to deletion of some details.
        if($mode <> EditMode::AddNew){
            if(count($old) > count($new)){
                $oldrow = count($new);
                $sql = "DELETE FROM " . $table .
                " WHERE $pkey = " . Nautilus::toSQL($pvalue) .
                " AND nEntryNox >= " . ($oldrow + 1);
                
                //check if the deletion was successfuly
                $affected = $app->execute($sql);
                
                if($affected <= 0){
                    //create a warning message to be check by the calling html page...
                    if($affected == 0){
                        $warning = __METHOD__ . " failed: No record was affected.";
                    }
                    else{
                        $warning = __METHOD__ . " failed: " . $app->getErrorMessage();
                    } //if($affected == 0) ... else{
                    
                    return false;
                }//if($affected <= 0){
                
            }//if(count($old) > count($new)){
        } //if($this->mode <> EditMode::AddNew){
        
        return true;
    }
    
    public static function initrecord($xmeta){
        //echo __METHOD__ . count($xmeta);
        $data = array();
        for($ctr=0;isset($xmeta[$ctr]["name"]);$ctr++){
            $name = $xmeta[$ctr]["name"];
            $data[$name] = self::DBEmpty($xmeta[$ctr]["type"]);
        }
        //print_r($data);
        return $data;
    }
    
    public static function reset_value($meta, &$data, $except){
        for($ctr=0;isset($meta[$ctr]["name"]);$ctr++){
            $pos = strpos($except, $meta[$ctr]["name"]);
            if(!$pos){
                $data[$meta[$ctr]["name"]] = self::DBEmpty($meta[$ctr]["native_type"]);
            }
        }
    }
    
    private static function insertcondition($sql, $filter){
        $subs = self::gsplit(" WHERE ", $sql);
        $newsql = "";
        $size = sizeof($subs);
        
        if($size == 0){
            return $sql;
        }
        elseif($size == 1) {
            return self::insertwhere($subs[0], $filter);
        }
        else{
            $newsql = $subs[0];
            for($ctr=1;$ctr<$size;$ctr++){
                $newsql .= " WHERE " . self::addwhere($subs[$ctr], $filter);
            }
            return $newsql;
        }
    }
    
    private static function addwhere($sql, $filter){
        if($pos = stripos($sql, " GROUP BY ")){
            return self::str_insert($sql, " AND ($filter)", $pos);
        }
        elseif($pos = stripos($sql, " HAVING ")){
            return self::str_insert($sql, " AND ($filter)", $pos);
        }
        elseif($pos = stripos($sql, " ORDER BY ")){
            return self::str_insert($sql, " AND ($filter)", $pos);
        }
        elseif($pos = stripos($sql, " LIMIT ")){
            return self::str_insert($sql, " AND ($filter)", $pos);
        }
        else{
            return $sql . " AND ($filter)";
        }
    }
    
    private static function insertwhere($sql, $filter){
        if($pos = stripos($sql, " GROUP BY ")){
            return self::str_insert($sql, " WHERE ($filter)", $pos);
        }
        elseif($pos = stripos($sql, " HAVING ")){
            return self::str_insert($sql, " WHERE ($filter)", $pos);
        }
        elseif($pos = stripos($sql, " ORDER BY ")){
            return self::str_insert($sql, " WHERE ($filter)", $pos);
        }
        elseif($pos = stripos($sql, " LIMIT ")){
            return self::str_insert($sql, " WHERE ($filter)", $pos);
        }
        else{
            return $sql . " WHERE ($filter)";
        }
    }
    
    private function getEmployeeInfo($employid){
        $sql = "SELECT a.sDeptIDxx, sCompnyNm sClientNm" .
            " FROM Employee_Master001 a " .
            " LEFT JOIN Client_Master b ON a.sEmployID = b.sClientID" .
            " WHERE sClientID = '$employid'";
        $row = $this->fetch($sql);
        //echo $sql;
        if($row){
            $this->_envirment["sDeptIDxx"] = $row[0]["sDeptIDxx"];
            //echo $this->_envirment["sDeptIDxx"];
            return $row[0]["sClientNm"];
        }
        else
            return "";
    }
}

class UserRights{
    public const Encoder = 1;
    public const Supervisor = 2;
    public const Manager = 4;
    public const Audit = 8;
    public const SysAdmin = 16;
    public const SysOwner = 32;
    public const Engineer = 64;
    public const SysMaster = 128;
}

class RecordStatus{
    public const Inactive = 0;
    public const Active = 1;
    public const Unknown = 2;
}

class UserStatus{
    public const Suspended = 0;
    public const Active = 1;
}

class TransactionStatus{
    public const Open = 0;
    public const Closed = 1;
    public const Posted = 2;
    public const Cancelled = 3;
    public const Void = 4;
    public const Unknown = 4;
}

class MCLocation{
    public const Warehouse = 0;
    public const Branch = 1;
    public const Supplier = 2;
    public const Customer = 3;
    public const Unknown = 4;
    public const ServiceCenter = 5;
}

class EditMode{
    public const Unknown = -1;
    public const Ready = 0;
    public const AddNew = 1;
    public const Update = 2;
    public const Delete = 3;
}

class Logical{
    public const No = '0';
    public const Yes = "1";
}

class TransMethod{
    public const NEWTRANSACTION = 0;
    public const SAVETRANSACTION = 1;
    public const OPENTRANSACTION = 2;
    public const CLOSETRANSACTION = 3;
    public const POSTTRANSACTION = 4;
    public const CANCELTRANSACTION = 5;
    public const VOIDTRANSACTION = 6;
}

//Mac 2017-11-18
class ParamMethod{
    public const NEWRECORD = 0;
    public const SAVERECORD = 1;
    public const LOADRECORD = 2;
    public const ACTIVATE = 3;
    public const DEACTIVATE = 4;
    public const UPDATERECORD = 5;
}

class AlertType{
    public const SUCCESS = 0;
    public const INFO = 1;
    public const WARNING =  2;
    public const DANGER = 3;
}

//kalyptus - 2017.11.27 11:05am
class TrailType{
    public const New = 0;
    public const Close = 1;
    public const Post = 2;
    public const Cancel = 3;
    public const Void = 4;
    public const Delete = 5;
    public const Update = 6;
}

//kalyptus - 2017.12.01 01:05pm
class CheckStat{
    public const Open = '0';
    public const Deposited = '1';
    public const Cleared = '2';
    public const Cancelled = '3';
    public const Hold = '4';
    public const Withdraw = '5';
}

//kalyptus - 2017.12.01 01:18pm
class PaymentForm{
    public const Cash = "0";
    public const Check = "1";
    public const Card = "2";       //Credit Card
    public const Cert = "3";       //Gift Certificate
    public const Point = "4";      //GCard Points?
    public const Other = "5";      //Other Type of Payment Form
}

//kalyptus - 2017.12.01 01:25pm
class InventoryStat{
    public const Inactive = "0";
    public const Active = "1";
    public const Limited = "2"; //Limited Inventory
    public const Push = "3";    //Push Inventory
    public const Stop = "4";    //Stop Production
}

?>
