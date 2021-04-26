<?php
/*******************************************************************************
 *  KwikSearch PHP Object 
 *  ----------------------------------------------------------------------------   
 *  RMJ Solutions © 2019
 *  ----------------------------------------------------------------------------
 *  iMac [2019-01-05]
 *      Started creating this object
*******************************************************************************/
    class KwikSearch{
        public $app;
        
        public $sWarngMsg = "";
        
        function __construct($app){
            $this->app = $app;
	}

	function __destruct() {
            $this->app = null;
	}
        
        function getMessage(){
	    return $this->sWarngMsg;
	}
        
        function Find($sql, $fields = ""){
            if ($sql == ""){
                $this->sWarngMsg = "Empty SQL STATEMENT detected.";
                return null;
            }

            $rows = $this->app->fetch($sql);             
            
            if ($rows == null){
                $this->sWarngMsg = "Empty RESULTSET.";
                return null;
            }
            
            $master = array();
            if ($fields != ""){
                $fldarray = explode("»", $fields);
                for ($ctr = 0; $ctr < sizeof($rows); $ctr++){
                    $master[$ctr] = array();

                    for ($x = 0; $x < sizeof($fldarray); $x++){
                        $master[$ctr][$fldarray[$x]] = $rows[$ctr][$fldarray[$x]];
                    }
                }
                return $master;
            } else {
                $stmt = $this->app->query($sql);
                if($row = $stmt->fetch()){
                    for ($ctr = 0; $ctr < sizeof($rows); $ctr++){
                        $master[$ctr] = array();
                        
                        for($x=0; $x < $stmt->columnCount(); $x++){
                            $meta = $stmt->getColumnMeta($x);
                            $master[$ctr][$meta["name"]] = $rows[$ctr][$meta["name"]];
                        }
                    }
                    return $master;
                }
            }
        }
    }
?>