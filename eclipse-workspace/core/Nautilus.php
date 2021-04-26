<?php
require_once 'Crypto.php';
require_once 'CommonUtil.php';

class Nautilus{
    //Make sure to set the timezon at php.ini to Asia/Manila
    public const vat_rate = 12;
    
    private const _signature = "08220326";
    private const _branchcd = "GAP0";
    
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
    private $_sErrorCde=0;
    
    public function __construct($doc_path){
        $this->_apppath = $doc_path;
        date_default_timezone_set('Asia/Singapore');
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
        if(!$this->loadConfig($product)){
            return false;
        }
            
        //Connect
        if(!$this->connect()){
            return false;
        }
        
        //initialize the environment variable here...
        $this->_envirment = array();
        $this->_envirment["sProdctID"] = $product;
        
        //Indicate success of loading
        return true;
    }

    //kalyptus - 2019.06.28 05:12pm
    //Added $mobile and $model as parameter(for GuanzonApp)
    public function Login($email, $password, $product, $pcname, $token, $client="", $mobile="", $model=""){
        if($product == "GuanzonApp"){
            return $this->Login_App_User_Client($email, $password, $product, $pcname, $token, $mobile, $model);
        }
        elseif ($product == "Telecom" || $product == "IntegSys"){
            return $this->Login_App_User_Company($email, $password, $product, $pcname, $token, $client, $mobile, $model);
        }
    }
    
    //+++++++++++++++++++++++++++++
    //public function Login($username, $password, $product)
    //+++++++++++++++++++++++++++++
    private function Login_App_User_Client($email, $password, $product, $pcname, $token, $mobile, $model){
        IF(empty($token)){
            $this->_sErrorCde = AppErrorCode::INVALID_AUTH_TOKEN;
            $this->_sErrorMsg = "Invalid AUTH TOKEN detected.";
            return false;            
        }
        
        $sql = "SELECT e.sProdctNm, a.*" .
            " FROM App_User_Master a" .
            " LEFT JOIN xxxSysObject e ON a.sProdctID = e.sProdctID" .
            " WHERE a.sEmailAdd = '$email'" .
            " AND (a.sProdctID = '$product' || (a.sProdctID != '$product' && a.cGloblAct = '1'))" .
            " ORDER BY a.cGloblAct DESC";
        $rows = $this->fetch($sql);
        //echo $sql . "\n";
        
        //verify email address
        if($rows == null){
            if($this->_sErrorMsg == ""){
                $this->_sErrorCde = AppErrorCode::INVALID_ACCOUNT;
                $this->_sErrorMsg = "Invalid email detected.";
            }
            else{
                $this->_sErrorMsg = "Login failed.\n" . $this->_sErrorMsg;
            }
            return false;
        }
        
        //check if the account is an employee
        if($rows[0]["sProdctNm"] == null){
            $this->_sErrorCde = AppErrorCode::INVALID_APPLICATION;
            $this->_sErrorMsg = "Invalid application detected.";
            return false;
        }
        
        if($rows[0]["cActivatd"] !== '1'){
            $this->_sErrorCde = AppErrorCode::UNACTIVATED_ACCOUNT;
            $this->_sErrorMsg = "Account was not yet activated.";
            return false;
        }
        
        //validate the password given by the user
        $xpassword = CommonUtil::app_decrypt($rows[0]["sPassword"], $rows[0]["sItIsASIN"]);
        if($password !== $xpassword){
            $this->_sErrorCde = AppErrorCode::INVALID_PASSWORD;
            $this->_sErrorMsg = "Invalid password detected.";
            return false;
        }
        
        $this->beginTrans();
        
        $userid = $rows[0]["sUserIDxx"];
        $date = new DateTime('now');
        $login = $date->format(CommonUtil::format_timestamp);
        
        //save the information to the our environment variables...
        $this->_envirment["sUserIDxx"] = $rows[0]["sUserIDxx"];
        $this->_envirment["sProdctID"] = $rows[0]["sProdctID"];
        $this->_envirment["sUserName"] = $rows[0]["sUserName"];
        $this->_envirment["sEmailAdd"] = $rows[0]["sEmailAdd"];
        $this->_envirment["sMobileNo"] = $rows[0]["sMobileNo"];
        $this->_envirment["sEmployNo"] = $rows[0]["sEmployNo"];
        $this->_envirment["nUserLevl"] = $rows[0]["nUserLevl"];
        $this->_envirment["cActivatd"] = $rows[0]["cActivatd"];
        $this->_envirment["dCreatedx"] = $rows[0]["dCreatedx"];
        
        //verify if usage of this device was recorded
        $sql = "SELECT * " .
            " FROM App_User_Device" .
            " WHERE sUserIDxx = '$userid'" .
            " AND sProdctID = '$product'" .
            " AND sIMEINoxx = '$pcname'";
        $rows = $this->fetch($sql);
        //echo $sql . "\n";
        
        //verify email address
        $sql = "";
        if($rows == null){
            $sql = "INSERT INTO App_User_Device" .
                " SET sUserIDxx = '$userid'" .
                ", sProdctID = '$product'" .
                ", sIMEINoxx = '$pcname'" .
                ", sMobileNo = '$mobile'" .
                ", sModelCde = '$model'" .
                ", sTokenIDx = '$token'" .
                ", dLastLogx = '$login'" .
                ", sLogNoxxx = 'YES'" .
                ", dLastVrfy = '$login'";
        }
        else{
            $sql = "UPDATE App_User_Device" .
                " SET sTokenIDx = '$token'" .
                ", sMobileNo = '$mobile'" .
                ", sModelCde = '$model'" .
                ", dLastLogx = '$login'" .
                ", sLogNoxxx = 'YES'" .
                ", dLastVrfy = '$login'" .
                " WHERE sUserIDxx = '$userid'" .
                " AND sProdctID = '$product'" .
                " AND sIMEINoxx = '$pcname'";
        }
        
        $result = $this->execute($sql);
        if($result <= 0){
            $this->_sErrorMsg = "Login failed...\n" . $this->_sErrorMsg;
            $this->rollbackTrans();
            return false;
        }
        
        $this->commitTrans();
        
        return true;
    }
    
    //+++++++++++++++++++++++++++++
    //private function Login_App_User_Company($username, $password, $product)
    //+++++++++++++++++++++++++++++
    private function Login_App_User_Company($email, $password, $product, $pcname, $token, $clientid, $mobile, $model){
        $sql = "";
        if($clientid == ""){
            $sql = "SELECT d.sClientID, c.sBranchCD, c.sBranchNm, b.sDeptIDxx, b.sPositnID, b.sEmpLevID, e.sProdctNm, b.sBranchCd xBranchCD,  a.*" .
                " FROM App_User_Master a" .
                " LEFT JOIN Employee_Master001 b ON a.sEmployNo = b.sEmployID" .
                " LEFT JOIN Branch c ON b.sBranchCD = c.sBranchCD" .
                " LEFT JOIN xxxSysClient d ON c.sBranchCD = d.sBranchCD" .
                " LEFT JOIN xxxSysObject e ON a.sProdctID = e.sProdctID" .
                " WHERE a.sEmailAdd = '$email'" .
                " AND (a.sProdctID = '$product' || (a.sProdctID != '$product' && a.cGloblAct = '1'))" .
                " ORDER BY a.cGloblAct DESC";
        }
        else{
            $sql = "SELECT d.sClientID, c.sBranchCD, c.sBranchNm, b.sDeptIDxx, b.sPositnID, b.sEmpLevID, e.sProdctNm, b.sBranchCd xBranchCD, a.*" .
                " FROM xxxSysApplication f" .
                " LEFT JOIN xxxSysClient d ON f.sClientID = d.sClientID" .
                " LEFT JOIN xxxSysObject e ON f.sProdctID = e.sProdctID" .
                " LEFT JOIN Branch c ON d.sBranchCD = c.sBranchCD" .
                ", App_User_Master a" .
                " LEFT JOIN Employee_Master001 b ON a.sEmployNo = b.sEmployID" .
                " WHERE ((f.sProdctID = a.sProdctID AND a.sProdctID = '$product') OR (a.sProdctID != '$product' AND a.cGloblAct = '1'))" .
                " AND a.sEmailAdd = '$email'" .
                " AND f.sProdctID = '$product'" .
                " AND d.sClientID = '$clientid'" .
                " ORDER BY a.cGloblAct DESC";
        }
        
        $rows = $this->fetch($sql);
        //echo $sql . "\n";

        //$clientid == "" ? "" : " AND d.sClientID = '$clientid'" .
        
        //verify email address
        if($rows == null){
            if($this->_sErrorMsg == ""){
                $this->_sErrorCde = AppErrorCode::INVALID_ACCOUNT;
                $this->_sErrorMsg = "Invalid email detected.";
            }
            else{
                $this->_sErrorMsg = "Login failed.\n" . $this->_sErrorMsg;
            }
            return false;
        }
        
        //if parameter has a client id then perform some validation
        if($clientid != ""){
            //015->SALES;036=>Mobile Phone;038=>Mobile Phone
            if($rows[0]["sDeptIDxx"] == "015" || $rows[0]["sDeptIDxx"] == "036" || $rows[0]["sDeptIDxx"] == "038"){
                if($rows[0]["sBranchCD"] != $rows[0]["xBranchCD"]){
                    $this->_sErrorCde = AppErrorCode::INVALID_SYS_CLIENT;
                    $this->_sErrorMsg = "Invalid SYSCLIENT detected.";
                    return false;
                }
            }
        }
        
        //check if the account is an employee
        if($rows[0]["sProdctNm"] == null){
            $this->_sErrorCde = AppErrorCode::INVALID_APPLICATION;
            $this->_sErrorMsg = "Invalid application detected.";
            return false;
        }
        
        if($rows[0]["cActivatd"] !== '1'){
            $this->_sErrorCde = AppErrorCode::UNACTIVATED_ACCOUNT;
            $this->_sErrorMsg = "Account was not yet activated.";
            return false;
        }
        
        //check if the account is an employee
        if($rows[0]["sBranchNm"] == null){
            $this->_sErrorCde = AppErrorCode::UNAUTHORIZED_USER;
            $this->_sErrorMsg = "Employees need to ask for authorization to use the APP.";
            return false;
        }
        
        //validate the password given by the user
        $xpassword = CommonUtil::app_decrypt($rows[0]["sPassword"], $rows[0]["sItIsASIN"]);
        if($password !== $xpassword){
            $this->_sErrorCde = AppErrorCode::INVALID_PASSWORD;
            $this->_sErrorMsg = "Invalid password detected.";
            return false;
        }
        
        $this->beginTrans();
        
        //log the user...
        $logno = CommonUtil::GetNextCode("xxxSysUserLog", "sLogNoxxx", true, $this->_connection, $this::_branchcd);
        $userid = $rows[0]["sUserIDxx"];
        $date = new DateTime('now');
        $login = $date->format(CommonUtil::format_timestamp);
        
        $sql = "INSERT INTO xxxSysUserLog (sLogNoxxx, sUserIDxx, dLogInxxx, sProdctID, sComptrNm) VALUES(:logno, :userid, :login, :product, :pcname)";
        $stmt = $this->prepare($sql);
        if($stmt == null){
            $this->_sErrorMsg = "Login failed...\n" . $this->_sErrorMsg;
            $this->rollbackTrans();
            return false;
        }
        
        $stmt->bindValue(':logno', $logno);
        $stmt->bindValue(':userid', $userid);
        $stmt->bindValue(':login', $login);
        $stmt->bindValue(':product', $product);
        $stmt->bindValue(':pcname', $pcname);
        
        $result = $stmt->execute();
        if($result <= 0){
            $this->_sErrorMsg = "Login failed...\n" . $this->_sErrorMsg;
            $this->rollbackTrans();
            return false;
        }
        
        //save the information to the our environment variables...
        $this->_envirment["sLogNoxxx"] = $logno;
        $this->_envirment["sClientID"] = $rows[0]["sClientID"];
        $this->_envirment["sBranchCD"] = $rows[0]["sBranchCD"];
        $this->_envirment["sBranchNm"] = $rows[0]["sBranchNm"];
        $this->_envirment["sDeptIDxx"] = $rows[0]["sDeptIDxx"];
        $this->_envirment["sPositnID"] = $rows[0]["sPositnID"];
        $this->_envirment["sEmpLevID"] = $rows[0]["sEmpLevID"];
        $this->_envirment["sProdctNm"] = $rows[0]["sProdctNm"];
        $this->_envirment["sEmpLevID"] = $rows[0]["sEmpLevID"];

        $this->_envirment["sUserIDxx"] = $rows[0]["sUserIDxx"];
        $this->_envirment["sProdctID"] = $rows[0]["sProdctID"];
        $this->_envirment["sUserName"] = $rows[0]["sUserName"];
        $this->_envirment["sEmailAdd"] = $rows[0]["sEmailAdd"];
        $this->_envirment["sMobileNo"] = $rows[0]["sMobileNo"];
        $this->_envirment["sEmployNo"] = $rows[0]["sEmployNo"];
        $this->_envirment["nUserLevl"] = $rows[0]["nUserLevl"];
        $this->_envirment["cActivatd"] = $rows[0]["cActivatd"];
        $this->_envirment["dCreatedx"] = $rows[0]["dCreatedx"];
        
        //verify if usage of this device was recorded
        $sql = "SELECT * " .
              " FROM App_User_Device" . 
              " WHERE sUserIDxx = '$userid'" .
                " AND sProdctID = '$product'" . 
                " AND sIMEINoxx = '$pcname'";
        $rows = $this->fetch($sql);
        //echo $sql . "\n";
        
        //verify email address
        $sql = "";
        if($rows == null){
            $sql = "INSERT INTO App_User_Device" .
                  " SET sUserIDxx = '$userid'" .
                  ", sProdctID = '$product'" .
                  ", sIMEINoxx = '$pcname'" .
                  ", sMobileNo = '$mobile'" .
                  ", sModelCde = '$model'" .
                  ", sTokenIDx = '$token'" . 
                  ", dLastLogx = '$login'" . 
                  ", sLogNoxxx = '$logno'" . 
                  ", dLastVrfy = '$login'";
        }
        else{
            $sql = "UPDATE App_User_Device" .
                  " SET sTokenIDx = '$token'" .
                      ", sMobileNo = '$mobile'" .
                      ", sModelCde = '$model'" .
                      ", dLastLogx = '$login'" .
                      ", sLogNoxxx = '$logno'" .
                      ", dLastVrfy = '$login'" .
                " WHERE sUserIDxx = '$userid'" .
                " AND sProdctID = '$product'" .
                " AND sIMEINoxx = '$pcname'";
        }
        
        $result = $this->execute($sql);
        if($result <= 0){
            $this->_sErrorMsg = "Login failed...\n" . $this->_sErrorMsg;
            $this->rollbackTrans();
            return false;
        }
        
        $this->commitTrans();
        
        return true;
    }
    
    
    //+++++++++++++++++++++++++++++
    //private function validLog($logno, $prodctid, $clientid, $userid, $pcname)
    //+++++++++++++++++++++++++++++
    public function validLog($prodctid, $userid, $pcname, $logno="", $clientid="", $relog=false){
        if($prodctid == "GuanzonApp"){
            return $this->validLog_App_User_Client($prodctid, $userid, $pcname);
        }
        elseif ($prodctid == "Telecom" || $prodctid == "IntegSys"){
            return $this->validLog_App_User_Company($prodctid, $userid, $pcname, $logno, $clientid, $relog);
        }
    }
    
    //+++++++++++++++++++++++++++++
    //private function validLog_App_User_Client($prodctid, $userid, $pcname
    //+++++++++++++++++++++++++++++
    private function validLog_App_User_Client($prodctid, $userid, $pcname){
        $sql = "SELECT * " .
            " FROM App_User_Device" .
            " WHERE sUserIDxx = '$userid'" .
            " AND sProdctID = '$prodctid'" .
            " AND sIMEINoxx = '$pcname'";
        
        $rows = $this->fetch($sql);
        //echo $sql . "\n";
        
        if($rows === null){
            $this->_sErrorMsg = "Error checking log..." . $this->getErrorMessage();
        }
        elseif(empty($rows)){
            $this->_sErrorCde = AppErrorCode::INVALID_AUTH_RECORD;
            $this->_sErrorMsg = "Invalid AUTH RECORD detected";
            return false;
        }
        
        if($rows[0]["sUserIDxx"] != $userid){
            $this->_sErrorCde = AppErrorCode::INVALID_AUTH_USER;
            $this->_sErrorMsg = "Invalid AUTH USER detected";
            return false;
        }
        
        if($rows[0]["sProdctID"] != $prodctid){
            $this->_sErrorCde = AppErrorCode::INVALID_AUTH_PRODUCT;
            $this->_sErrorMsg = "Invalid AUTH PRODUCT detected";
            return false;
        }
        
        if($rows[0]["sIMEINoxx"] != $pcname){
            $this->_sErrorCde = AppErrorCode::INVALID_AUTH_DEVICE;
            $this->_sErrorMsg = "Invalid AUTH DEVICE detected";
            return false;
        }
        
        //Check if logno has logout
        if($rows[0]["sLogNoxxx"] != "YES"){
            $this->_sErrorCde = AppErrorCode::INVALID_AUTH_LOG;
            $this->_sErrorMsg = "Invalid AUTH LOG detected";
            return false;
        }
        
        return true;
    }
    
        
    //+++++++++++++++++++++++++++++
    //private function Login_App_User_Company($prodctid, $userid, $pcname, $logno, $clientid, $relog)
    //+++++++++++++++++++++++++++++
    private function validLog_App_User_Company($prodctid, $userid, $pcname, $logno, $clientid, $relog){
        $sql = "SELECT a.*" .
            " FROM xxxSysUserLog a" .
            " WHERE a.sLogNoxxx = " . CommonUtil::toSQL($logno) .
              " AND a.sUserIDxx = " . CommonUtil::toSQL($userid);
        
        $rows = $this->fetch($sql);

        if($rows === null){
            $this->_sErrorMsg = "Error checking log..." . $this->getErrorMessage();
        }
        elseif(empty($rows)){
            $this->_sErrorCde = AppErrorCode::INVALID_AUTH_LOG;
            $this->_sErrorMsg = "Invalid AUTH LOG detected" . $sql;
            return false;
        }
        
        if($rows[0]["sProdctID"] != $prodctid){
            $this->_sErrorCde = AppErrorCode::INVALID_AUTH_PRODUCT;
            $this->_sErrorMsg = "Invalid AUTH PRODUCT detected";
            return false;
        }
        
        if($rows[0]["sComptrNm"] != $pcname){
            $this->_sErrorCde = AppErrorCode::INVALID_AUTH_DEVICE;
            $this->_sErrorMsg = "Invalid AUTH DEVICE detected";
            return false;
        }
        
        //Check if logno has logout
        if($rows[0]["dLogOutxx"] != null){
            $this->_sErrorCde = AppErrorCode::INVALID_AUTH_DATE;
            $this->_sErrorMsg = "Invalid AUTH DATE detected";
            return false;
        }
        
        //get logintime of this logno
        $logdate = new DateTime($rows[0]["dLogInxxx"]);
        //get the current datetime
        $logused = new DateTime();
        
        //compare the dates
        if($logused < $logdate){
            $this->_sErrorCde = AppErrorCode::INVALID_AUTH_DATE;
            $this->_sErrorMsg = "Invalid AUTH DATE detected";
            return false;
        }
        
        
        //echo $logdate->format('Y-m-d H:i:s');
        //echo $logused->format('Y-m-d H:i:s');
        //echo "Diff in days: " . $interval->d;
        if($logdate->format('Y-m-d') != $logused->format('Y-m-d')){
            $this->_sErrorCde = AppErrorCode::INVALID_AUTH_DATE;
            $this->_sErrorMsg = "Expired AUTH DATE detected";
            return false;
        }
        
        if($relog){
            $this->relogUser($prodctid, $userid, $clientid, $logno);
        }
        
        return true;
    }
    
    private function relogUser($product, $userid, $clientid, $logno){
        $sql = "SELECT d.sClientID, c.sBranchCD, c.sBranchNm, b.sDeptIDxx, b.sPositnID, b.sEmpLevID, e.sProdctNm, b.sBranchCd xBranchCD, a.*" .
            " FROM xxxSysApplication f" .
            " LEFT JOIN xxxSysClient d ON f.sClientID = d.sClientID" .
            " LEFT JOIN xxxSysObject e ON f.sProdctID = e.sProdctID" .
            " LEFT JOIN Branch c ON d.sBranchCD = c.sBranchCD" .
            ", App_User_Master a" .
            " LEFT JOIN Employee_Master001 b ON a.sEmployNo = b.sEmployID" .
            " WHERE ((f.sProdctID = a.sProdctID AND a.sProdctID = '$product') OR (a.sProdctID != '$product' AND a.cGloblAct = '1'))" .
            " AND a.sUserIDxx = '$userid'" .
            " AND f.sProdctID = '$product'" .
            " AND d.sClientID = '$clientid'" .
            " ORDER BY a.cGloblAct DESC";
        $rows = $this->fetch($sql);
        
        //save the information to the our environment variables...
        $this->_envirment["sLogNoxxx"] = $logno;
        $this->_envirment["sClientID"] = $rows[0]["sClientID"];
        $this->_envirment["sBranchCD"] = $rows[0]["sBranchCD"];
        $this->_envirment["sBranchNm"] = $rows[0]["sBranchNm"];
        $this->_envirment["sDeptIDxx"] = $rows[0]["sDeptIDxx"];
        $this->_envirment["sPositnID"] = $rows[0]["sPositnID"];
        $this->_envirment["sEmpLevID"] = $rows[0]["sEmpLevID"];
        $this->_envirment["sProdctNm"] = $rows[0]["sProdctNm"];
        $this->_envirment["sEmpLevID"] = $rows[0]["sEmpLevID"];
        
        $this->_envirment["sUserIDxx"] = $rows[0]["sUserIDxx"];
        $this->_envirment["sProdctID"] = $rows[0]["sProdctID"];
        $this->_envirment["sUserName"] = $rows[0]["sUserName"];
        $this->_envirment["sEmailAdd"] = $rows[0]["sEmailAdd"];
        $this->_envirment["sMobileNo"] = $rows[0]["sMobileNo"];
        $this->_envirment["sEmployNo"] = $rows[0]["sEmployNo"];
        $this->_envirment["nUserLevl"] = $rows[0]["nUserLevl"];
        $this->_envirment["cActivatd"] = $rows[0]["cActivatd"];
        $this->_envirment["dCreatedx"] = $rows[0]["dCreatedx"];
    }
    
    //+++++++++++++++++++++++++++++
    //public function Logout($logno)
    //+++++++++++++++++++++++++++++
    public function Logout($prodctid, $userid, $pcname, $logno = ""){
        if($prodctid == "GuanzonApp"){
            return $this->Logout_App_User_Client($prodctid, $userid, $pcname);
        }
        elseif ($prodctid == "Telecom" || $prodctid == "IntegSys"){
            return $this->Logout_App_User_Company($prodctid, $userid, $pcname, $logno);
        }
    }
    
    private function Logout_App_User_Client($prodctid, $userid, $pcname){
        $sql = "UPDATE App_User_Device SET sLogNoxxx = ''" .
            " WHERE sUserIDxx = '$userid'" .
            " AND sProdctID = '$prodctid'" .
            " AND sIMEINoxx = '$pcname'";
        $this->execute($sql);
        return true;
    }
    
    private function Logout_App_User_Company($prodctid, $userid, $pcname, $logno){

        $this->beginTrans();
        
        $sql = "UPDATE xxxSysUserLog SET dLogOutxx = :logout WHERE sLogNoxxx = :logno";
        $stmt = $this->prepare($sql);
        if($stmt == null){
            $this->_sErrorMsg = __METHOD__ . " failed...\n" . $this->_sErrorMsg;
            return false;
        }
        
        $date = new DateTime('now');
        $logout = $date->format(CommonUtil::format_timestamp);
        
        $stmt->bindValue(':logout', $logout);
        $stmt->bindValue(':logno', $logno);
        
        $stmt->execute();
        
        $sql = "UPDATE App_User_Device SET sLogNoxxx = ''" .
            " WHERE sUserIDxx = '$userid'" .
            " AND sProdctID = '$prodctid'" .
            " AND sIMEINoxx = '$pcname'";
        $this->execute($sql);
        
        $this->commitTrans();
        return true;
    }
    //+++++++++++++++++++++++++++++
    //public function Connect
    //+++++++++++++++++++++++++++++
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
    //public function execute($sql)
    //+++++++++++++++++++++++++++++
    //returns -1 if execute encounter error
    public function execute($sql, $table="", $branch="", $destinat=""){
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
            
            //check if the value of $table is not empty since it indicates logging of audit trail
            if($table != ""){
                if($branch == ""){
                    if(array_key_exists("sBranchCD", $this->_envirment)){
                        $branch = $this->_envirment["sBranchCD"];
                    }
                    else{
                        $branch = "MX01";
                    }
                }

                $transnox = CommonUtil::GetNextCode("xxxReplicationLog", "sTransNox", true, $this->_connection, "MX01");
                if(array_key_exists("sUserIDxx", $this->_envirment)){
                    $user = $this->_envirment["sUserIDxx"];
                }
                else{
                    $user = "haha";
                }
                $date = new DateTime('now');
                $stamp = $date->format(CommonUtil::format_timestamp);
            
                $qry = "INSERT INTO xxxReplicationLog(sTransNox, sBranchCd, sStatemnt, sTableNme, sDestinat, sModified, dEntryDte, dModified)" . 
                      " VALUES(:transno, :branch, :sql, :table, :dest, :smodify, :dentry, :dmodify)";
                $stmt = $this->prepare($qry);
                
                if($stmt == null){
                    $this->_sErrorMsg = "Entry to Replication Log failed...\n" . $this->_sErrorMsg;
                    $this->rollbackTrans();
                    return false;
                }
                
                $stmt->bindValue(':transno', $transnox);
                $stmt->bindValue(':branch', $branch);
                $stmt->bindValue(':sql', $sql);
                $stmt->bindValue(':table', $table);
                $stmt->bindValue(':dest', $destinat);
                $stmt->bindValue(':smodify', $user);
                $stmt->bindValue(':dentry', $stamp);
                $stmt->bindValue(':dmodify', $stamp);
                
                $result = $stmt->execute();
                if($result <= 0){
                    $this->_sErrorMsg = "Login failed...\n" . $this->_sErrorMsg;
                    $this->rollbackTrans();
                    return false;
                }
            }
            
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
        if(!$this->_bConfigLd){
            return "";
        }
            
        if (array_key_exists($index, $this->_envirment)){
            return $this->_envirment[$index];
        }
        else{
            return "";
        }
    }
    
    //+++++++++++++++++++++++++++++
    //public function showEnv()
    //+++++++++++++++++++++++++++++
    public function showEnv(){
        print_r($this->_envirment);
    }
    
    public function branch($field, $load=false){
        if(!$this->_bConfigLd){
            return "";
        }
            
        /*
         if (array_key_exists($field, $this->_branchcfg) && !$load)
         return $this->_branchcfg[$field];
         */
             
         $sql = "SELECT $field FROM Branch_Others WHERE sBranchCD = " . CommonUtil::toSQL($this->_envirment["sBranchCD"]);
             
         if($rows = $this->fetch($sql)){
             return $rows[0][$field];
         }
                 
         $sql = "SELECT $field" .
         " FROM xxxOtherInfo a" .
         " LEFT JOIN xxxSysClient b" .
         " ON a.sClientID = b.sClientID" .
         " WHERE sBranchCD = " . CommonUtil::toSQL($this->_envirment["sBranchCD"]);
                 
         if($rows = $this->fetch($sql)){
             return $rows[0][$field];
         }
         else{
             return "";
         }
    }
    
    public function product($field){
        if(!$this->_bConfigLd){
            return "";
        }
            
        /*
         if (array_key_exists($field, $this->_prodctcfg) && !$load)
         return $this->_prodctcfg[$field];
         */
         
         $sql = "SELECT sValuexxx" .
             " FROM xxxOtherConfig" .
             " WHERE sProdctID = " . self::toSQL($this->_envirment["sProdctID"]) .
             " AND sConfigID = " . self::toSQL($field);
         
         
         if($rows = $this->fetch($sql)){
             return $rows[0]["sValuexxx"];
         }
         else{
             return "";
         }
    }
    
    //+++++++++++++++++++++++++++++
    //public function getErrorMessage()
    public function getErrorMessage(){
        return $this->_sErrorMsg;
    }

    //+++++++++++++++++++++++++++++
    public function getErrorCode(){
        return $this->_sErrorCde;
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
        
        //var_dump($main_config[$product]);
        
        if (array_key_exists("DBDriver", $main_config[$product])) {
            $this->_dbdrvr = $main_config[$product]["DBDriver"];
        }
        else{
            $this->_dbdrvr = "mysql";
        }
            
        if (array_key_exists("UserName", $main_config[$product])) {
            $this->_dbuser = self::Decrypt($main_config[$product]["UserName"]);
        }
        else{
            $this->_dbuser = "";
        }
            
        if (array_key_exists("Password", $main_config[$product])){
            $this->_dbpswd = self::Decrypt($main_config[$product]["Password"]);
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
        //echo 'after port';
        
        //echo "Loadconfig done...";
        
        //echo $this->_dbuser . "-" . $this->_dbpswd . "\n";
        //echo $main_config[$product]["UserName"] . "-" . $main_config[$product]["Password"] . "\n";
        
        $this->_bConfigLd = true;
        return true;
    }
    
    public function loaduserClient($product, $userid, $pcname, $token, $mobile=""){
        $sql = "SELECT e.sProdctNm, a.*" .
            " FROM App_User_Master a" .
            " LEFT JOIN xxxSysObject e ON a.sProdctID = e.sProdctID" .
            " WHERE a.sUserIDxx = '$userid'" .
            " AND (a.sProdctID = '$product' || (a.sProdctID != '$product' && a.cGloblAct = '1'))" .
            " ORDER BY a.cGloblAct DESC";
        $rows = $this->fetch($sql);
        //echo $sql . "\n";
        
        //verify email address
        if($rows == null){
            if($this->_sErrorMsg == ""){
                $this->_sErrorCde = AppErrorCode::INVALID_ACCOUNT;
                $this->_sErrorMsg = "Invalid user detected.";
            }
            else{
                $this->_sErrorMsg = "Client log reload failed.\n" . $this->_sErrorMsg;
            }
            return false;
        }
        
        //check if the account is an employee
        if($rows[0]["sProdctNm"] == null){
            $this->_sErrorCde = AppErrorCode::INVALID_APPLICATION;
            $this->_sErrorMsg = "Invalid application detected.";
            return false;
        }
        
        if($rows[0]["cActivatd"] !== '1'){
            $this->_sErrorCde = AppErrorCode::UNACTIVATED_ACCOUNT;
            $this->_sErrorMsg = "Account was not yet activated.";
            return false;
        }
        
        $this->beginTrans();
        
        $userid = $rows[0]["sUserIDxx"];
        $date = new DateTime('now');
        $login = $date->format(CommonUtil::format_timestamp);
        
        //save the information to the our environment variables...
        $this->_envirment["sUserIDxx"] = $rows[0]["sUserIDxx"];
        $this->_envirment["sProdctID"] = $rows[0]["sProdctID"];
        $this->_envirment["sUserName"] = $rows[0]["sUserName"];
        $this->_envirment["sEmailAdd"] = $rows[0]["sEmailAdd"];
        $this->_envirment["sMobileNo"] = $rows[0]["sMobileNo"];
        $this->_envirment["sEmployNo"] = $rows[0]["sEmployNo"];
        $this->_envirment["nUserLevl"] = $rows[0]["nUserLevl"];
        $this->_envirment["cActivatd"] = $rows[0]["cActivatd"];
        $this->_envirment["dCreatedx"] = $rows[0]["dCreatedx"];
        
        //verify if usage of this device was recorded
        $sql = "SELECT * " .
            " FROM App_User_Device" .
            " WHERE sUserIDxx = '$userid'" .
            " AND sProdctID = '$product'" .
            " AND sIMEINoxx = '$pcname'";
        $rows = $this->fetch($sql);
        //echo $sql . "\n";
        
        //verify email address
        $sql = "";
        if($rows == null){
            $sql = "INSERT INTO App_User_Device" .
                " SET sUserIDxx = '$userid'" .
                ", sProdctID = '$product'" .
                ", sIMEINoxx = '$pcname'" .
                ", sMobileNo = '$mobile'" .
                ", sTokenIDx = '$token'" .
                ", dLastLogx = '$login'" .
                ", sLogNoxxx = 'YES'" .
                ", dLastVrfy = '$login'";
        }
        else{
            $sql = "UPDATE App_User_Device" .
                " SET sTokenIDx = '$token'" .
                ", sMobileNo = '$mobile'" .
                ", dLastLogx = '$login'" .
                ", sLogNoxxx = 'YES'" .
                ", dLastVrfy = '$login'" .
                " WHERE sUserIDxx = '$userid'" .
                " AND sProdctID = '$product'" .
                " AND sIMEINoxx = '$pcname'";
        }
        
        $result = $this->execute($sql);
        if($result <= 0){
            $this->_sErrorMsg = "Reload failed...\n" . $this->_sErrorMsg;
            $this->rollbackTrans();
            return false;
        }
        
        $this->commitTrans();
        
        return true;
    }
    
    public function loaduser($product, $userid){
        $sql = "SELECT *" .
            " FROM xxxSysUser" .
            " WHERE sUserIDxx = " . CommonUtil::toSQL($userid);
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
            //$this->_envirment["sUserName"] = CommonUtil::Decrypt($rows[0]["sLogNamex"]);
        }
        
        $this->_envirment["sEmployNo"] = $rows[0]["sEmployNo"];
        $this->_envirment["nUserLevl"] = $rows[0]["nUserLevl"];
        
        return true;
    }
    
    //-----------------------------
    //private function loaduser($userid)
    //kalyptus - 2019.05.29 02:37pm
    //-----------------------------
    public function loaduserx($product, $userid, $pcname, $clientid){
        $sql = "SELECT d.sClientID, c.sBranchCD, c.sBranchNm, b.sDeptIDxx, b.sPositnID, b.sEmpLevID, e.sProdctNm, b.sBranchCd xBranchCD, a.*" .
            " FROM xxxSysApplication f" .
            " LEFT JOIN xxxSysClient d ON f.sClientID = d.sClientID" .
            " LEFT JOIN xxxSysObject e ON f.sProdctID = e.sProdctID" .
            " LEFT JOIN Branch c ON d.sBranchCD = c.sBranchCD" .
            ", xxxSysUser a" .
            " LEFT JOIN Employee_Master001 b ON a.sEmployNo = b.sEmployID" .
            " WHERE ((f.sProdctID = a.sProdctID AND a.sProdctID = '$product') OR (a.sProdctID != '$product' AND a.cUserType = '1'))" .
            " AND a.sUserIDxx = '$userid'" .
            " AND f.sProdctID = '$product'" .
            " AND d.sClientID = '$clientid'" .
            " ORDER BY a.cUserType DESC";
        
        $rows = $this->fetch($sql);
        //echo $sql . "\n";
        
        if($rows == null){
            $this->_sErrorMsg = "loaduser failed.\n" . $this->_sErrorMsg;
            return false;
        }
        
        //if parameter has a client id then perform some validation
        if($clientid != ""){
            //015->SALES;036=>Mobile Phone;038=>Mobile Phone
            if($rows[0]["sDeptIDxx"] == "015" || $rows[0]["sDeptIDxx"] == "036" || $rows[0]["sDeptIDxx"] == "038"){
                if($rows[0]["sBranchCD"] != $rows[0]["xBranchCD"]){
                    $this->_sErrorCde = AppErrorCode::INVALID_SYS_CLIENT;
                    $this->_sErrorMsg = "Invalid SYSCLIENT detected.";
                    return false;
                }
            }
        }
        
        //check if the account is an employee
        if($rows[0]["sProdctNm"] == null){
            $this->_sErrorCde = AppErrorCode::INVALID_APPLICATION;
            $this->_sErrorMsg = "Invalid application detected.";
            return false;
        }
        
        //check if the account is an employee
        if($rows[0]["sBranchNm"] == null){
            $this->_sErrorCde = AppErrorCode::UNAUTHORIZED_USER;
            $this->_sErrorMsg = "Employees need to ask for authorization to use the APP.";
            return false;
        }
        
        $this->_envirment["sLogNoxxx"] = "";
        $this->_envirment["sClientID"] = $rows[0]["sClientID"];
        $this->_envirment["sBranchCD"] = $rows[0]["sBranchCD"];
        $this->_envirment["sBranchNm"] = $rows[0]["sBranchNm"];
        $this->_envirment["sDeptIDxx"] = $rows[0]["sDeptIDxx"];
        $this->_envirment["sPositnID"] = $rows[0]["sPositnID"];
        $this->_envirment["sEmpLevID"] = $rows[0]["sEmpLevID"];
        $this->_envirment["sProdctNm"] = $rows[0]["sProdctNm"];
        $this->_envirment["sEmpLevID"] = $rows[0]["sEmpLevID"];
        
        $this->_envirment["sUserIDxx"] = $rows[0]["sUserIDxx"];
        $this->_envirment["sProdctID"] = $rows[0]["sProdctID"];
        $this->_envirment["sEmployNo"] = $rows[0]["sEmployNo"];
        $this->_envirment["nUserLevl"] = $rows[0]["nUserLevl"];
        
        if($rows[0]["sEmployNo"] != ""){
            $this->_envirment["sUserName"] = $this::getEmployeeInfo($rows[0]["sEmployNo"]);
        }
        else{
            $this->_envirment["sUserName"] = self::Decrypt($rows[0]["sLogNamex"]);
            //$this->_envirment["sUserName"] = CommonUtil::Decrypt($rows[0]["sLogNamex"]);
        }
        
        $this->_envirment["sEmployNo"] = $rows[0]["sEmployNo"];
        
        return true;
    }
    
    //::::::::::::::::::::::::::::::
    //public static function Encrypt($value, $salt)
    //::::::::::::::::::::::::::::::
    public static function Encrypt($value, $salt=""){
        if($salt=="")
            $salt = self::_signature;
            
            $result = Crypto::Encrypt($value, $salt);
            
            return CommonUtil::StringToHex($result);
    }
    
    //::::::::::::::::::::::::::::::
    //public static function Decrypt($value, $salt)
    //::::::::::::::::::::::::::::::
    public static function Decrypt($value, $salt=""){
        $result = CommonUtil::HexToString($value);
        
        if($salt=="")
            $salt = self::_signature;
            
            return Crypto::Decrypt($result, $salt);
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

//kalyptus - 2019.07.16 02:28pm
Class NMM_Status{
    public const OPEN       = 0;
    public const DELIVERED  = 1;
    public const RECEIVED   = 2;
    public const SEEN       = 3;
    public const DONE       = 4;
    public const DELETE     = 5;
}

//kalyptus - 2019.06.28 02:12pm
class AppErrorCode{
    public const DEACTIVATED_ACCOUNT  = 40000;
    public const NOT_ALLOWED_DEVICE   = 40001;
    public const INVALID_APPLICATION  = 40002;
    public const UNACTIVATED_ACCOUNT  = 40003;
    public const EXISTING_ACCOUNT     = 40004;
    public const INVALID_ACCOUNT      = 40005;
    public const INVALID_PASSWORD     = 40006;
    public const UNSET_API_ID         = 40007;
    public const UNSET_USER_ID        = 40008;
    public const UNSET_DEVICE_ID      = 40009;
    public const UNSET_TAG_ID         = 40010;
    public const UNSET_HASH_SEC_KEY   = 40011;
    public const INVALID_AUTH_KEY     = 40012;
    public const INVALID_HASH_SEC_KEY = 40013;
    public const UNSET_API_TOKEN      = 40014;
    public const INVALID_AUTH_LOG     = 40015;
    public const INVALID_AUTH_PRODUCT = 40016;
    public const INVALID_AUTH_DEVICE  = 40017;
    public const INVALID_AUTH_RECORD  = 40018;
    public const INVALID_AUTH_USER    = 40019;
    public const INVALID_AUTH_DATE    = 40020;
    public const INVALID_AUTH_TOKEN   = 40021;
    public const INVALID_AUTH_MOBILE  = 40022;
    public const INVALID_SYS_CLIENT   = 40023;
    public const UNAUTHORIZED_USER    = 40024;
    public const NULL_CONNECTION      = 40025;
    public const RECORD_NOT_FOUND     = 40026;
    public const UNSET_LOG_ID         = 40027;
    public const INVALID_PARAMETER    = 40028;
    public const INVALID_STATUS       = 40029;
}

?>
