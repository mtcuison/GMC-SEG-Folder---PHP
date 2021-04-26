<?php 
namespace xapi\core\v100;

require_once  'x-api/v1.0/Crypto.php';

class CommonUtil{
    public const format_date = "Y-m-d";
    public const format_timestamp = "Y-m-d H:i:s";
    public const format_longdate = "F j, Y";
    public const format_longtimestamp = "F j, Y H:i:s";
    
    private const _signature = "08220326";
    
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
        $max = str_repeat("0", $len);
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
    
    public static function get_ip_address() {
        
        // Check for shared Internet/ISP IP
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        
        // Check for IP addresses passing through proxies
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            
            // Check if multiple IP addresses exist in var
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false) {
                $iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($iplist as $ip) {
                    if (validate_ip($ip)){
                        return $ip;
                    }
                }
            }
            else {
                if (validate_ip($_SERVER['HTTP_X_FORWARDED_FOR'])){
                    return $_SERVER['HTTP_X_FORWARDED_FOR'];
                }
            }
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED']) && validate_ip($_SERVER['HTTP_X_FORWARDED'])){
            return $_SERVER['HTTP_X_FORWARDED'];
        }
        if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && validate_ip($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])){
            return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && validate_ip($_SERVER['HTTP_FORWARDED_FOR'])){
            return $_SERVER['HTTP_FORWARDED_FOR'];
        }
        if (!empty($_SERVER['HTTP_FORWARDED']) && validate_ip($_SERVER['HTTP_FORWARDED'])){
            return $_SERVER['HTTP_FORWARDED'];
        }
        
        // Return unreliable IP address since all else failed
        return $_SERVER['REMOTE_ADDR'];
    }
    
    /**
     * Ensures an IP address is both a valid IP address and does not fall within
     * a private network range.
     */
    function validate_ip($ip) {
        if (strtolower($ip) === 'unknown'){
            return false;
        }
        
        // Generate IPv4 network address
        $ip = ip2long($ip);
        
        // If the IP address is set and not equivalent to 255.255.255.255
        if ($ip !== false && $ip !== -1) {
            // Make sure to get unsigned long representation of IP address
            // due to discrepancies between 32 and 64 bit OSes and
            // signed numbers (ints default to signed in PHP)
            $ip = sprintf('%u', $ip);
            
            // Do private network range checking
            if ($ip >= 0 && $ip <= 50331647){
                return false;
            }
            if ($ip >= 167772160 && $ip <= 184549375){
                return false;
            }
            if ($ip >= 2130706432 && $ip <= 2147483647){
                return false;
            }
            if ($ip >= 2851995648 && $ip <= 2852061183){
                return false;
            }
            if ($ip >= 2886729728 && $ip <= 2887778303){
                return false;
            }
            if ($ip >= 3221225984 && $ip <= 3221226239){
                return false;
            }
            if ($ip >= 3232235520 && $ip <= 3232301055){
                return false;
            }
            if ($ip >= 4294967040){
                return false;
            }
        }
        return true;
    }
    
    public static function toDate($value)
    {
        if (empty($value)) {
            return null;
        }
        
        try {
            $date = new \DateTime($value);
            //echo $date->format("Y-m-d H:i:s");
            return $date;
        } catch (\Exception $e) {
            //echo $e->getMessage();
            return null;
        }
    }
    
    public static function toDecimal($value){
        return floatval(str_replace(",", "", $value));
    }
}

?>