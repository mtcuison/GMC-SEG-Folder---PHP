<?php 

class CommonUtil{
    public const format_date = "Y-m-d";
    public const format_timestamp = "Y-m-d H:i:s";
    public const format_longdate = "F j, Y";
    public const format_longtimestamp = "F j, Y H:i:s";
    
    //::::::::::::::::::::::::::::::
    //public static function HexToString($value)
    //::::::::::::::::::::::::::::::
    public static function HexToString($value){
        if (self::is_hex($value)){
            return pack("H*", $value);
        }
        else{
            return $value;
        }
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
    
    public static function GenerateOTP($len){
        $max = str_repeat("9", $len);
        $otp = mt_rand(0, $max);
        return str_pad($otp, $len, "0", STR_PAD_LEFT);
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
    
    public static function SerializeNumber($value){
        return strtoupper(base_convert($value, 10, 36));
    }
    
    public static function DeSerializeNumber($value){
        return base_convert($value, 36, 10);
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
    //public static function isValidEmail($email)
    //::::::::::::::::::::::::::::::
    public static function isValidEmail($email){
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    //::::::::::::::::::::::::::::::
    //public static function is_hex($hex_code)
    //::::::::::::::::::::::::::::::
    public static function is_hex($hex_code) {
        return @preg_match("/^[a-f0-9]{2,}$/i", $hex_code) && !(strlen($hex_code) & 1);
    }
    
    //::::::::::::::::::::::::::::::
    //public static function is_hex($hex_code)
    //::::::::::::::::::::::::::::::
    public static function Hex2String($hex){
        if (self::is_hex($hex)){
            return pack("H*", $hex);
        }
        else{
            return $hex;
        }
    }
    
    //::::::::::::::::::::::::::::::
    //public static function isValidMobile($mobile)
    //::::::::::::::::::::::::::::::
    public static function isValidMobile($mobile){
    		$mobile = str_replace("+63", "0", $mobile);	
        return preg_match('/^[0-9]{11}+$/', $mobile);
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
    
    //mac 2019.10.10
    // fix the mobile number to Philippine format
    public static function fixMobile($sMobileNo){
        if (strlen($sMobileNo) == 11){
            if (substr($sMobileNo, 0, 1) == "0")
                return "+63" . substr($sMobileNo, 1, 10);
                else return "";
        } else if (strlen($sMobileNo) == 10){
            if (substr($sMobileNo, 0, 1) == "9")
                return "+63" . $sMobileNo;
                else return "";
        } else if (strlen($sMobileNo) < 10){
            return "";
        } else if (strlen($sMobileNo) == 12){
            if (substr($sMobileNo, 0, 2) == "63")
                return "+" . $sMobileNo;
                else return "";
        } else if (strlen($sMobileNo) == 13){
            if (substr($sMobileNo, 0, 3) == "+63")
                return $sMobileNo;
                else return "";
        } else {
            return "";
        }
        
    }
    
    //mac 2019.10.10
    // get the network provier of a mobile number
    //mac 2020.06.13
    //  updated the list
    public static function getMobileNetwork($sMobileNo){
        $sMobileNo = str_replace("+63", "0", $sMobileNo);
        
        //compare 5 digit prefix first
        $prefix = substr($sMobileNo, 0, 5); //XXXXX
        
        $globe = "09173»09178»09256»09175»09253»09257»09176»09255»09258"; //globe postpaid prefixes
        
        if (strpos($globe, $prefix) !== false) return 0; //globe postpaid 
        
        $prefix = substr($sMobileNo, 0, 4); //XXXX
        
        //globe and touch mobile prefix
        $globe = "0817»0905»0906»0915»0916»0917»0926»0927»0935»0936»0937»0945»0955»0956»0965»0966»0967»0973»0975»0976»0977»0978»0979»0994»0995»0996»0997";
        
        //smart and talk n text prefix
        $smart = "0813»0907»0908»0909»0910»0911»0912»0913»0914»0918»0919»0920»0921»0928»0929»0930»0938»0939»0940»0946»0947»0948»0949»0950»0951»0970»0981»0989»0992»0998»0999";
        
        //sun cellular prefix
        $sun = "0922»0923»0924»0925»0931»0932»0933»0934»0941»0942»0943»0944";
        
        if (strpos($globe, $prefix) !== false){
            return "0";
        } else if (strpos($smart, $prefix) !== false){
            return "1";
        } else if (strpos($sun, $prefix) !== false){
            return "2";
        } else {
            return ""; //unknown
        }
    }
    
    //mac 2021.04.14
    public static function dateDiff($interval, $start, $end){
        switch ($interval){
            case "Y":
                $date1 = $start;
                $date2 = $end;
                
                $ts1 = strtotime($date1);
                $ts2 = strtotime($date2);
                
                $year1 = date('Y', $ts1);
                $year2 = date('Y', $ts2);
                                
                return $year2 - $year1;
            case "m":
                $date1 = $start;
                $date2 = $end;
                
                $ts1 = strtotime($date1);
                $ts2 = strtotime($date2);
                
                $year1 = date('Y', $ts1);
                $year2 = date('Y', $ts2);
                
                $month1 = date('m', $ts1);
                $month2 = date('m', $ts2);
                                
                return (($year2 - $year1) * 12) + ($month2 - $month1);
            default:
                $start_date = strtotime($start);
                $end_date = strtotime($end);
                $result = $end_date - $start_date;
                return $result/60/60/24;
        }
    }
}
?>