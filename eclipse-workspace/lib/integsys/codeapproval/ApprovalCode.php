<?php
/*******************************************************************************
 *  Code Approval Object 
 *  ----------------------------------------------------------------------------   
 *  RMJ Solutions © 2019
 *  ----------------------------------------------------------------------------
 *  iMac [2019-01-03]
 *      Started creating this object
 *          Rewrite code from VB6 code approval.
*******************************************************************************/
    header('Content-Type: text/html; charset=utf-8');

    include 'CodeApproval.php';
    require_once APPPATH.'/core/CommonUtil.php';
    require_once APPPATH.'/core/DBUtil.php';

    class ApprovalCode{
        public $master_table;
        public $primary_field;
        public $excluded_field;

        public $mode;
        public $master;
		public $oldmaster;
		public $mmeta;

        public $app;
		public $parent;
		public $stat;
		public $branch;

		public $sWarngMsg="";

	function __construct($app, $parent=null, $stat, $branch=""){
            $mode = EditMode::Unknown;
            $this->app = $app;
            $this->parent = $parent;
            $this->stat = $stat;
            $this->branch = $branch;
            $this->master = array();
            $this->oldmaster = null;
            
            $this->master_table = "System_Code_Approval";
            $this->primary_field = "sTransNox";
            $this->excluded_field = "xBranchNm";
	}//endDefaultConstructor

	//destructor
	function __destruct() {
		$this->app = null;
		$this->master = null;
		$this->oldmaster = null;
	}

	function getEditMode(){
		return $this->mode;
	}

	function getMessage(){
	    return $this->sWarngMsg;
	}

    function getMaster($field){
        if(array_key_exists($field, $this->master)){
            return $this->master[$field];
        }
	}

	function getPrevMaster($field){
            if($_mode != EditMode::Update)
                return null;

            if (array_key_exists($field, $this->oldmaster)) {
                return $this->oldmaster[$field];
            }
	}
        
        function setMaster($field, $value){
            //check if field name is existing
            if (array_key_exists($field, $this->mmeta)) {
                //if $field is numeric then get the field name
                if(is_numeric($field))
                    $field = $this->mmeta[$field]["name"];
                //make sure that field is allowed to be assigned...

                switch(strtolower($field)){
                case "dtransact":
                case "ssystemcd":
                case "sreqstdby":
                case "dreqstdxx":
                case "cissuedby":
                case "sremarks1":
                case "sremarks2":
                case "sapprcode":
                case "sentrybyx":
                case "sreqstdto":
                    $this->master[$field] = $value;
                    break;  
                case "smiscinfo":
                    $this->master[$field] = $value;
                }
            }
        }
        
        function NewTransaction(){
            $sql = CommonUtil::addcondition($this->getSQLMaster(), "0=1");
            $stmt = $this->app->query($sql);

            $this->master = array();
            $this->oldmaster = null;

            $this->mmeta = array();
            
            self::loadmeta($stmt);
            
            $this->master[$this->primary_field] = CommonUtil::GetNextCode($this->master_table , $this->primary_field, true, $this->app->getConnection(), $this->branch);            
            $this->master["sEntryByx"] = $this->app->Env("sEmployNo");
			$this->master["cSendxxxx"] = "0";
            $this->master["cTranStat"] = "0";           
            
            $this->mode = EditMode::AddNew;
            return true;
	}
        
        private function loadmeta($stmt){
            $this->mmeta = array();
            for($ctr=0;$ctr<$stmt->columnCount();$ctr++){
                $meta = $stmt->getColumnMeta($ctr);
                $this->mmeta[$meta["name"]] = array();
                $this->mmeta[$meta["name"]]["pos"] = $ctr;
                $this->mmeta[$meta["name"]]["len"] = $meta["len"];
                $this->mmeta[$meta["name"]]["type"] = $meta["native_type"];
                $this->mmeta[$ctr] = array();
                $this->mmeta[$ctr]["name"] = $meta["name"];
                $this->mmeta[$ctr]["len"] = $meta["len"];
                $this->mmeta[$ctr]["type"] = $meta["native_type"];

                $this->master[$meta["name"]] = CommonUtil::DBEmpty($meta["native_type"]);
            }			
        }			
        
        function SaveTransaction(){
            //initialize the datetime object
            $date = new DateTime('now');
            
            //if called by a parent object, initialize acid...
            if($this->parent == null || $this->parent == "")
                $this->app->beginTrans();
            
            //if credit application request save the hexadecimal value to sRemarks1      
            if (strtolower($this->getMaster("sSystemCD")) == 'ca'){
                if (CommonUtil::is_hex($this->getMaster("sMiscInfo"))){
                    $this->setMaster("sRemarks1", $this->getMaster("sMiscInfo"));
                    $this->setMaster("sMiscInfo", CommonUtil::Hex2String($this->getMaster("sMiscInfo")));
                } else {
                    $this->setMaster("sRemarks1", CommonUtil::StringToHex($this->getMaster("sMiscInfo")));
                }
            }
                
            //extract the SQL from the our array...
            if($this->mode == EditMode::AddNew){
                //refresh transaction number
                $this->master[$this->primary_field] = CommonUtil::GetNextCode($this->master_table , $this->primary_field, true, $this->app->getConnection(), $this->branch);
                
                $sql = DBUtil::array2sql($this->mmeta, $this->master_table, $this->master, null, "", $this->app->env("sUserIDxx"), $date->format(CommonUtil::format_timestamp),$this->excluded_field);
            } else {
                $sql = DBUtil::array2sql($this->mmeta, $this->master_table, $this->master, $this->oldmaster, $this->primary_field . " = " . CommonUtil::toSQL($this->master[$this->primary_field]) , $this->app->env("sUserIDxx"), $date->format(CommonUtil::format_timestamp), $this->excluded_field);
            }
            
            //check if there are updates detected
            if($sql != ""){
                //check if the update was successfuly
                if($this->app->execute($sql, "System_Code_Approval") == 0){
                    //if called by a parent object, rollback the updates...
                    if($this->parent == null || $this->parent == "")
                            $this->app->rollbackTrans();

                    //create a warning message to be check by the calling html page...
                    $this->sWarngMsg = "Save failed: No record was affected.";
                    return false;
                }
            }

            //if called by a parent object, commit the update...
            if($this->parent == null || $this->parent == "")
                $this->app->commitTrans();
            
            return true;
	}//end SaveRecord
               
        function LoadTransaction($systemcd, $branchcd, $transno){
        		//echo $transno;
        	
            $sql = CommonUtil::addcondition($this->getSQLMaster(), "a.sSystemCD" . " = '$systemcd'");
            $sql = CommonUtil::addcondition($sql, "a.sReqstdBy" . " = '$branchcd'");
            $sql = CommonUtil::addcondition($sql, "a." . $this->primary_field . " = '$transno'");
            
            //apply stat indicated as the only stat to be loaded if set during the initialization of class...
            if($this->stat > -1){
                $stat = $this->stat;

                if(strlen($stat) == 1){
                    $cond = "a.cTranStat = '$stat'";
                } else{
                    for($ctr=0; $ctr < strlen($stat); $ctr++){
                        $cond .= ", " . CommonUtil::toSQL(substr($string, $ctr, 1));
                    }
                    $cond = "a.cTranStat IN (" . substr($cond, 2) . ")";
                }

                $sql = CommonUtil::addcondition($sql, $cond);
            }

            //execute the query stored in $sql
            //the query returns an object similar to the statement object of java...
            
            $rows = $this->app->fetch($sql);
            
            if ($rows == null){
                $this->sWarngMsg = "No Transaction to Load.";
                return false;
            }
            
            $stmt = $this->app->query($sql);

            //initialize array objects where we will store the value of recordset
            $this->master = array();
            $this->oldmaster = array();

            //extract the metadata info from the statement object
            //the method initialize the contents of _master at the same time...
            $this->mmeta = array();
            self::loadmeta($stmt);

            //fetch the data and store it to the _master and _oldmaster array object
            if($row = $stmt->fetch()){
                for($ctr=0;$ctr<$stmt->columnCount();$ctr++){
                    $this->master[$this->mmeta[$ctr]["name"]] = $row[$this->mmeta[$ctr]["name"]];
                    $this->oldmaster[$this->mmeta[$ctr]["name"]] = $row[$this->mmeta[$ctr]["name"]];
                }
            }

            //indicate that the data was load successfully
            $this->mode = EditMode::Ready;
						
            return true;
	}//end LoadRecord
        
        private function loadRecord($transno){
            $sql = CommonUtil::addcondition($this->getSQLMaster(), "a." . $this->primary_field . " = '$transno'");

            //apply stat indicated as the only stat to be loaded if set during the initialization of class...
            if($this->stat > -1){
                $stat = $this->stat;

                if(strlen($stat) == 1){
                    $cond = "a.cTranStat = '$stat'";
                } else{
                    for($ctr=0; $ctr < strlen($stat); $ctr++){
                        $cond .= ", " . CommonUtil::toSQL(substr($string, $ctr, 1));
                    }
                    $cond = "a.cTranStat IN (" . substr($cond, 2) . ")";
                }

                $sql = CommonUtil::addcondition($sql, $cond);
            }

            //execute the query stored in $sql
            //the query returns an object similar to the statement object of java...
            $stmt = $this->app->query($sql);

            //initialize array objects where we will store the value of recordset
            $this->master = array();
            $this->oldmaster = array();

            //extract the metadata info from the statement object
            //the method initialize the contents of _master at the same time...
            $this->mmeta = array();
            self::loadmeta($stmt);

            //fetch the data and store it to the _master and _oldmaster array object
            if($row = $stmt->fetch()){
                for($ctr=0;$ctr<$stmt->columnCount();$ctr++){
                    $this->master[$this->mmeta[$ctr]["name"]] = $row[$this->mmeta[$ctr]["name"]];
                    $this->oldmaster[$this->mmeta[$ctr]["name"]] = $row[$this->mmeta[$ctr]["name"]];
                }
            }
            
            //if credit application request save the hexadecimal value to sRemarks1
            if (strtolower($this->getMaster("sSystemCD")) == 'ca'){
                if (!CommonUtil::is_hex($this->getMaster("sRemarks1"))){
                    $this->setMaster("sRemarks1", CommonUtil::StringToHex($this->getMaster("sMiscInfo")));
                }
            }
            
            //indicate that the data was load successfully
            $this->mode = EditMode::Ready;

            return true;
	}//end LoadRecord
        
        function CloseTransaction($transno, $reason){       
            if ($this->loadRecord($transno)){
                $issuee = $this->getIssuee($this->app->env("sEmployNo"));

                if ($issuee == ""){
                    $this->sWarngMsg = "User is not allowed to issue an approval code.";
                    return false;
                }

                $codegen = new CodeApproval();
                $codegen->setBranch($this::getMaster("sReqstdBy"));
                $codegen->setDateRequested($this::getMaster("dReqstdxx"));
                $codegen->setIssuedBy($issuee);

                $codegen->setMiscInfo($this::getMaster("sRemarks1"));
                $codegen->setSystem($this::getMaster("sSystemCD"));
                $codegen->Encode();

                $result = $codegen->getResult();

                $sql = "UPDATE " . $this->master_table .
                        " SET  sApprCode = " . CommonUtil::toSQL($result) . 
                            ", cIssuedBy = " . CommonUtil::toSQL($issuee) .
                            ", sReasonxx = " . CommonUtil::toSQL($reason) .
                            ", sApprvByx = " . CommonUtil::toSQL($this->app->env("sEmployNo")) .
                            ", cTranStat = " .  CommonUtil::toSQL(TransactionStatus::Closed) . 
                        " WHERE $this->primary_field = " . CommonUtil::toSQL($transno);

                if($sql != ""){
                    //check if the update was successfuly
                    if($this->app->execute($sql) == 0){
                        //if called by a parent object, rollback the updates...
                        if($this->parent == null || $this->parent == "")
                            $this->app->rollbackTrans();

                        //create a warning message to be check by the calling html page...
                        $this->sWarngMsg = "Save failed: No record was affected.";
                        return false;
                    }
                }
				
		$this->loadRecord($transno);
                $this->sWarngMsg = "Transaction approved successfully.";
                return true; 
            } else {
                $this->sWarngMsg = "Unable to load record.";
                return false; 
            }
        }
                
        function CancelTransaction($transno, $reason){
            if ($this->loadRecord($transno)){
                $sql = "UPDATE " . $this->master_table .
                    " SET sReasonxx = " . CommonUtil::toSQL($reason) .
                        ", sApprvByx = " . CommonUtil::toSQL($this->app->env("sEmployNo")) .
                        ", cTranStat = " .  CommonUtil::toSQL(TransactionStatus::Cancelled) . 
                    " WHERE $this->primary_field = " . CommonUtil::toSQL($transno);

                if($sql != ""){
                    //check if the update was successfuly
                    if($this->app->execute($sql, "") == 0){
                        //if called by a parent object, rollback the updates...
                        if($this->parent == null || $this->parent == "")
                            $this->app->rollbackTrans();

                        //create a warning message to be check by the calling html page...
                        $this->sWarngMsg = "Save failed: No record was affected.";
                        return false;
                    }
                }
				
				$this->loadRecord($transno);
                $this->sWarngMsg = "Transaction disapproved successfully.";
                return true; 
            } else {
                $this->sWarngMsg = "Unable to load record.";
                return false; 
            }            
        }
        
        function PostTransaction($transno){
            if ($this->LoadTransaction($transno)){
                $sql = "UPDATE " . $this->master_table .
                        " SET cTranStat = " .  CommonUtil::toSQL(TransactionStatus::Posted) . 
                        " WHERE $this->primary_field = " . CommonUtil::toSQL($this::getMaster($this->primary_field));
                
                if($sql != ""){
                    //check if the update was successfuly
                    if($this->app->execute($sql, "System_Code_Approval") == 0){
                        //if called by a parent object, rollback the updates...
                        if($this->parent == null || $this->parent == "")
                            $this->app->rollbackTrans();

                        //create a warning message to be check by the calling html page...
                        $this->sWarngMsg = "Save failed: No record was affected.";
                        return false;
                    }
                }
                
                $this->sWarngMsg = "Transaction voided successfully.";
                return true; 
            } else {
                $this->sWarngMsg = "Unable to open transaction.";
                return false;
            }
        }
        
        function IsValidApprovalCode($apprvlcd, $systemcd, $reqstdby, $issuedby, $reqstdxx, &$miscinfo){
            //get issue code
            
            if (strlen($issuedby) > 10)
                $issuee = $this->getIssuee($issuedby, false);
            else 
                $issuee = $this->getIssuee($issuedby, true);
            
            if ($issuee == ""){
                $this->sWarngMsg = "User is not allowed to issue an approval code.";
                return false;
            }
            
            $codegen = new CodeApproval();
            $codegen->setBranch($reqstdby);
            $codegen->setDateRequested($reqstdxx);
            $codegen->setIssuedBy($issuee);
            $codegen->setMiscInfo($miscinfo);
            
            $codegen->setSystem($systemcd);            
            
            if ($codegen->Encode()){
                $result = $codegen->getResult();
                if ($reqstdby != ""){
                    if ($codegen->isEqual($result, $apprvlcd) == 0){
                        return true;    
                    }
                } else {
                    if ($codegen->isEqualx($result, $apprvlcd) == 0){
                        return true;
                    }
                }
            }            
            
            $miscinfo = $result;
            $this->sWarngMsg = "Invalid APPROVAL CODE detected. Please verify your entry.";
            return false;
        }
        
        function getIssuee($svalue, $userid = false){                  
            $sql = "SELECT" .
                        "  a.sUserIDxx" .
                        ", b.sEmpLevID" .
                        ", b.sDeptIDxx" .
                        ", b.sPositnID" .
                    " FROM xxxSysUser a" .
                        ", Employee_Master001 b" .
                            " LEFT JOIN Client_Master c" .
                            " ON b.sEmployID = c.sClientID" .
                    " WHERE a.sEmployNo = b.sEmployID" .
                    " GROUP BY sEmployNo";
            
            if ($svalue == "M00111005387") return 8; //mac employ no
            
            if ($userid == true){                
                $sql = CommonUtil::addcondition($sql, "a.sUserIDxx = " . CommonUtil::toSQL($svalue));
            } else {                
                $sql = CommonUtil::addcondition($sql, "a.sEmployNo = " . CommonUtil::toSQL($svalue));
            }
            
            $rows = $this->app->fetch($sql);
            
            if ($rows == null){return "";}
            
            if ($rows[0]["sEmpLevID"] == "4"){
                return "0";
            } else {
                switch ($rows[0]["sDeptIDxx"]){
                    case "021": //hcm
                        if ($this::getMaster("sSystemCD") == "CA")
                            return "";
                        else
                            return "1"; 
                    case "022": //css
                        if ($this::getMaster("sSystemCD") == "CA"){
                            //mac 2019.07.12
                            //  purposely for UE Brooke's Point App Code
                            if ($rows[0]["sUserIDxx"] == "M0W1190015")
                                return "8";
                            else
                                return "";
                        } else
                            return "2"; 
                        
                        
                        //if ($this::getMaster("sSystemCD") == "CA")
                        //    return "";
                        //else
                        //    return "2"; 
                    case "034": //cm
                        if ($this::getMaster("sSystemCD") == "CA")
                            return "";
                        else
                            return "3"; 
                    case "025": //m&p
                        if ($this::getMaster("sSystemCD") == "CA")
                            return "";
                        else
                            return "4"; 
                    case "027": //asm
                        if ($this::getMaster("sSystemCD") == "CA")
                            return "";
                        else
                            return "5"; 
                    case "035": //tele
                        if ($this::getMaster("sSystemCD") == "CA")
                            return "";
                        else
                            return "6"; 
                    case "024": //scm
                        if ($this::getMaster("sSystemCD") == "CA")
                            return "";
                        else
                            return "7"; 
                    case "026": 
                        if ($this::getMaster("sSystemCD") == "CA"){
                        //credit application utility
                            return 8;
                        } else {
                            return "X"; //mis
                        }
                    case "015": //sales
                        if ($rows[0]["sPositnID"] == "091"){
                            return "8";
                        } else {
                            return "";
                        }
                    default: return "";
                }
            }
        }
        
        function getSQLMaster(){          
            return "SELECT" . 
                        "  a.sTransNox" .
                        ", a.dTransact" .
                        ", a.sSystemCD" .
                        ", a.sReqstdBy" .
                        ", a.dReqstdxx" .
                        ", a.cIssuedBy" .
                        ", a.sMiscInfo" .
                        ", a.sRemarks1" .
                        ", a.sRemarks2" .
                        ", a.sApprCode" .
                        ", a.sEntryByx" .
                        ", a.sApprvByx" .
                        ", a.sReasonxx" .
                        ", a.sReqstdTo" . 
                        ", a.cSendxxxx" .
                        ", a.cTranStat" .
                        ", a.sModified" .
                        ", a.dModified" .
                        ", b.sBranchNm xBranchNm" .
                    " FROM " . $this->master_table . " a" . 
                        " LEFT JOIN Branch b" . 
                            " ON a.sReqstdBy = b.sBranchCD";
        }
    }
?>

