<?php
class Tokenize{
    public static function EncryptAuthToken($employid, $mobileno, $authlevl, $autheqlx){
        if ($employid == "") return "";
        if ($mobileno == "") return "";
        if ($authlevl == "" || !is_numeric($authlevl)) return "";
        if ($autheqlx == "" || !is_numeric($autheqlx)) return "";
        
        $value = $employid . ":" . $mobileno . ":" . $authlevl . ":" . $autheqlx;
        
        $aes = new MySQLAES($employid);
        
        $value = strtoupper($aes->encrypt($value));
        $value = CommonUtil::StringToHex($value);
        
        return $value;
    }
    
    public static function EncryptApprovalToken($transnox, $apprtype, $rqsttype, $employid){
        if ($transnox == "") return "";
        if ($apprtype == "" || !is_numeric($apprtype)) return "";
        if ($rqsttype == "") return "";
        if ($employid == "") return "";
        
        $value = $transnox . ":" . $apprtype . ":" . $rqsttype;
        
        $aes = new MySQLAES($employid);
        
        $value = strtoupper($aes->encrypt($value));
        $value = CommonUtil::StringToHex($value);
        
        return $value;
    }
    
    public static function DecryptToken($value, $employid){
        if ($value == "") return "";
        if ($employid == "") return "";
        
        $value = CommonUtil::HexToString($value);
        
        $aes = new MySQLAES($employid);
        
        $value = $aes->decrypt($value);
        
        return $value;
    }
}
?>