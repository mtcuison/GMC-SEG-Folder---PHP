<?php


	class getNextCode{	
		
		public static function GetTransNox($table, $field, $year, $conn, $branch=""){
			$value="";
			
			if(trim($branch)<>"")
				
				$value .= $branch;				
			
			//use the date function of php since the app/web server and dbf server
			//is installed in one pc...			
			if($year)
				$value .= substr(date("Y"), -2);		
			
			//extract last row from the table 			
			$sql = "SELECT $field FROM $table" . 
					  " WHERE $field LIKE " . getNextCode::toSQL($value . "%") . 
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
	}
?>