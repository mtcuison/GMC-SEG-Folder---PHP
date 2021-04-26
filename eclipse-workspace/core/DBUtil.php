<?php 

require_once 'CommonUtil.php';

class DBUtil{
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
            
            $data[$meta["name"]] = CommonUtil::DBEmpty($meta["native_type"]);
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
            self::loadmeta($stmt, $meta, $new[0]);
        }
        else{
            self::loadmeta($stmt, $meta, $new);
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
            $sql = self::array2sql($meta, $table, $new, null, "", "", $date->format(CommonUtil::format_timestamp), $exempt);
        }
        else{
            $sql = self::array2sql($meta, $table, $new, $old, "$pkey = " . CommonUtil::toSQL($pvalue) , "", $date->format(CommonUtil::format_timestamp), $exempt);
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
                $sql = self::array2sql($meta, $table, $row, null, "", "", $date->format(CommonUtil::format_timestamp), $exempt);
            } //if($mode == EditMode::AddNew)
            else{
                //assume that previous primary key value was save with x as prefix
                //e.g. sTransNox => xTransNox
                $xpkey = "x" . substr($pkey, 1);
                if(!isset($row[$xpkey]) || $row[$xpkey] == ""){
                    $row[$pkey] = $pvalue;
                    $sql = self::array2sql($meta, $table, $row, null, "", "", $date->format(CommonUtil::format_timestamp), $exempt);
                }
                else{
                    $sql = self::array2sql($meta, $table, $row, $old[$oldrow-1], "$pkey = '$pvalue' AND nEntryNox = $oldrow", "", $date->format(CommonUtil::format_timestamp), $exempt);
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
                " WHERE $pkey = " . CommonUtil::toSQL($pvalue) .
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
            $data[$name] = CommonUtil::DBEmpty($xmeta[$ctr]["type"]);
        }
        //print_r($data);
        return $data;
    }
    
    public static function reset_value($meta, &$data, $except){
        for($ctr=0;isset($meta[$ctr]["name"]);$ctr++){
            $pos = strpos($except, $meta[$ctr]["name"]);
            if(!$pos){
                $data[$meta[$ctr]["name"]] = CommonUtil::DBEmpty($meta[$ctr]["native_type"]);
            }
        }
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
                            $values .= ", " . CommonUtil::toSQL($value);
                        }
                        else{
                            if(stripos($sexcluded, $field) === false){
                                $fields .= ", $field";
                                $values .= ", " . CommonUtil::toSQL($value);
                            }
                        }
                        $ctr++;
                    }
                    
                    if($smodified <> ""){
                        $fields .= ", sModified";
                        $values .= ", " . CommonUtil::toSQL($smodified);
                    }
                    
                    if($dmodified <> ""){
                        $fields .= ", dModified";
                        $values .= ", " . CommonUtil::toSQL($dmodified);
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
                                $sql .= ", $field = " . CommonUtil::toSQL($value);
                                else{
                                    if(stripos($sexcluded, $field) === false){
                                        $sql .= ", $field = " . CommonUtil::toSQL($value);
                                    }
                                }
                        }
                        $ctr++;
                    }
                    
                    if($sql == "")
                        return "";
                        
                        if($smodified <> ""){
                            $sql .= ", sModified = " . CommonUtil::toSQL($smodified);
                        }
                        
                        if($dmodified <> ""){
                            $sql .= ", dModified = " . CommonUtil::toSQL($dmodified);
                        }
                        
                        $sql = substr($sql, 2);
                        return "UPDATE $table SET $sql WHERE $filter";
                }
    }
}
?>