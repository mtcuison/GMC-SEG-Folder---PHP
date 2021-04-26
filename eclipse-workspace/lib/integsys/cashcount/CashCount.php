<?php
/*******************************************************************************
 *  Cash Count Object 
 *  ----------------------------------------------------------------------------   
 *  RMJ Solutions © 2019
 *  ----------------------------------------------------------------------------
 *  iMac [2019-01-05]
 *      Started creating this object
*******************************************************************************/
    class CashCount{
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

	function __construct($app, $parent=null, $stat, $branch = ""){
            $this->mode = EditMode::Unknown;
            $this->app = $app;
            $this->parent = $parent;
            $this->stat = $stat;
            $this->branch = $branch;
            $this->master = array();
            $this->oldmaster = null;
            
            $this->master_table = "Cash_Count_Master";
            $this->primary_field = "sTransNox";
            $this->excluded_field = "";
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
                case "ncn0001cx":
                case "ncn0005cx":
                case "ncn0025cx":
                case "ncn0001px":
                case "ncn0005px":
                case "ncn0010px":
                case "nnte0020p":
                case "nnte0050p":
                case "nnte0100p":
                case "nnte0200p":
                case "nnte0500p":
                case "nnte1000p":
                    if (!is_numeric($value)){
                        $this->master[$field] = 0;
                    } else {$this->master[$field] = $value;}
                    break;
                case "sornoxxxx":
                case "ssinoxxxx":
                case "sprnoxxxx":
                case "scrnoxxxx":
                case "dentrydte":
                case "sreqstdby":    
                case "dtransact":
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
            //check if entry is correct...
            //  if(!$this->isEntryOk()){return false;}
            //initialize the datetime object
            $date = new DateTime('now');
            //if called by a parent object, initialize acid...
            if($this->parent == null || $this->parent == "")
                $this->app->beginTrans();

            //extract the SQL from the our array...
            if($this->mode == EditMode::AddNew){
                //set received date
                $this->master["dReceived"] = $date->format(CommonUtil::format_timestamp);
                
                $this->master[$this->primary_field] = CommonUtil::GetNextCode($this->master_table, $this->primary_field, true, $this->app->getConnection(), $this->branch);
                $sql = DBUtil::array2sql($this->mmeta, $this->master_table, $this->master, null, "", $this->app->env("sUserIDxx"), $date->format(CommonUtil::format_timestamp),$this->excluded_field);
            } else {
                $sql = DBUtil::array2sql($this->mmeta, $this->master_table, $this->master, $this->oldmaster, $this->primary_field . " = " . CommonUtil::toSQL($this->master[$this->primary_field]) , $this->app->env("sUserIDxx"), $date->format(CommonUtil::format_timestamp), $this->excluded_field);
            }
            
            //check if there are updates detected
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

            //if called by a parent object, commit the update...
            if($this->parent == null || $this->parent == "")
                $this->app->commitTrans();
            return true;
	}//end SaveRecord
        
        function LoadTransaction($value){
            $sql = CommonUtil::addcondition($this->getSQLMaster(), "a." . $this->primary_field . " = '$value'");

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
            Nautilus::loadmeta($stmt, $this->mmeta, $this->master);

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
        
        function getSQLMaster(){            
            return "SELECT" . 
                        "  sTransNox" .
                        ", dTransact" .
                        ", nCn0001cx" .
                        ", nCn0005cx" .
                        ", nCn0025cx" .
                        ", nCn0001px" .
                        ", nCn0005px" .
                        ", nCn0010px" .
                        ", nNte0020p" .
                        ", nNte0050p" .
                        ", nNte0100p" .
                        ", nNte0200p" .
                        ", nNte0500p" .
                        ", nNte1000p" .
                        ", sORNoxxxx" .
                        ", sSINoxxxx" .
                        ", sPRNoxxxx" .
                        ", sCRNoxxxx" .
                        ", dEntryDte" .
                        ", dReceived" .
                        ", sReqstdBy" .
                        ", sModified" .
                        ", dModified" .
                    " FROM " . $this->master_table;
        }
    }
?>

