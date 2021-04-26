<?php
/*******************************************************************************
 *  Sign Up API for Guanzon App
 *  ----------------------------------------------------------------------------
 *  RMJ Business Solutions
 *  ----------------------------------------------------------------------------
 *  iMac [2019-06-12]
 *      Started creating API.
 *******************************************************************************/
    class SignUp{
        public $master_table;
        public $primary_field;
        public $excluded_field;
        
        public $mode;
        public $master;
        public $oldmaster;
        public $mmeta;
        
        public $app;
        public $parent;
        public $branch;
        
        public $sWarngMsg="";
        
        //constructor
        function __construct($app, $parent=null, $branch = ""){
            $this->mode = EditMode::Unknown;
            $this->app = $app;
            $this->parent = $parent;
            $this->branch = $branch;
            $this->master = array();
            $this->oldmaster = null;
            
            $this->master_table = "G_Card_App_Master";
            $this->primary_field = "sTransNox";
            $this->excluded_field = "";
        }
        //end of constructor
        
        //destructor
        function __destruct() {
            $this->app = null;
            $this->master = null;
            $this->oldmaster = null;
        }
        //end of destructor
        
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
                    case "navlpoint":
                        if (!is_numeric($value)){
                            $this->master[$field] = 0;
                        } else {$this->master[$field] = $value;}
                        break;
                    case "stransnox":
                    case "sclientnm":
                    case "scardnmbr":
                    case "smobileno":
                    case "semailadd":
                    case "spassword":
                    case "sanswer01":
                    case "sanswer02":
                    case "sanswer03":
                        $this->master[$field] = $value;
                }
            }
        }//end of setMaster()
        
        function NewTransaction(){
            $sql = CommonUtil::addcondition($this->getSQLMaster(), "0=1");
            $stmt = $this->app->query($sql);
            
            $this->master = array();
            $this->oldmaster = null;
            
            $this->mmeta = array();
            self::loadmeta($stmt);
                       
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
        
        private function isEntryOK(){
            //check if email address was already used.
            $sql = CommonUtil::addcondition($this->getSQLMaster(), "sEmailAdd = " . CommonUtil::toSQL($this->master["sEmailAdd"]));
            $stmt = $this->app->query($sql);
            
            if ($stmt->rowCount() > 0){
                $this->sWarngMsg = "EMAIL ADDRESS was already registered.";
                return false;
            }
            
            return true;
        }
        
        function SaveTransaction(){
            //check if entry is correct...
            if (!$this->isEntryOK()) return false;
            
            //initialize the datetime object
            $date = new DateTime('now');
            //if called by a parent object, initialize acid...
            if($this->parent == null || $this->parent == "")
                $this->app->beginTrans();
                
                //extract the SQL from the our array...
                if($this->mode == EditMode::AddNew){
                    $this->master[$this->primary_field] = CommonUtil::GetNextCode($this->master_table, $this->primary_field, true, $this->app->getConnection(), $this->branch);
                    
                    $sql = DBUtil::array2sql($this->mmeta, $this->master_table, $this->master, null, "", "", $date->format(CommonUtil::format_timestamp),$this->excluded_field);
                } else {
                    $sql = DBUtil::array2sql($this->mmeta, $this->master_table, $this->master, $this->oldmaster, $this->primary_field . " = " . CommonUtil::toSQL($this->master[$this->primary_field]) , "", $date->format(CommonUtil::format_timestamp), $this->excluded_field);
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
            $sql = CommonUtil::addcondition($this->getSQLMaster(), $this->primary_field . " = '$value'");
            
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
            
            //indicate that the data was load successfully
            $this->mode = EditMode::Ready;
            
            return true;
        }//end LoadTransaction
        
        function getSQLMaster(){
            return "SELECT" .
                        "  sTransNox" .
                        ", sClientNm" .
                        ", sCardNmbr" .
                        ", sMobileNo" .
                        ", sEmailAdd" .
                        ", nAvlPoint" .
                        ", sPassword" .
                        ", sAnswer01" .
                        ", sAnswer02" .
                        ", sAnswer03" .
                        ", dModified" .
                    " FROM " . $this->master_table;
        }
    }//end of class SignUp
?>