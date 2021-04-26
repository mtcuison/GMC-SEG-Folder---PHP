<?php
    require_once 'iWSHeaderValidator.php';
        
    class WSHeaderValidatorClient implements iWSHeaderValidator{
        protected $_sErrorMsg;
        protected $_app;
        protected $_sErrorCde;
        
        //@implementation
        //*************************
        public function isHeaderOk($myheader){
            //Product ID
            if(!isset($myheader['g-api-id'])){
                $this->_sErrorCde = AppErrorCode::UNSET_API_ID;
                $this->_sErrorMsg = "Unset API ID detected";
                return false;
            }
            
            //USER ID
            if(!isset($myheader['g-api-user'])){
                $this->_sErrorCde = AppErrorCode::UNSET_USER_ID;
                $this->_sErrorMsg = "Unset USER ID detected";
                return false;
            }
            
            //Computer Name/IEMI of Device
            if(!isset($myheader['g-api-imei'])){
                $this->_sErrorCde = AppErrorCode::UNSET_DEVICE_ID;
                $this->_sErrorMsg = "Unset DEVICE ID detected";
                return false;
            }
            
            //Current Time
            if(!isset($myheader['g-api-key'])){
                $this->_sErrorCde = AppErrorCode::UNSET_TAG_ID;
                $this->_sErrorMsg = "Unset TAG ID detected";
                return false;
            }
            
            //$myheader['g-api-imei'] + $myheader['g-api-key'] HASH
            if(!isset($myheader['g-api-hash'])){
                $this->_sErrorCde = AppErrorCode::UNSET_HASH_SEC_KEY;
                $this->_sErrorMsg = "Unset HASH SECURITY KEY detected";
                return false;
            }
            
            //Check validity of $_SERVER['g-api-imei'], $_SERVER['g-api-key'], and $_SERVER['g-api-hash']
            if(md5($myheader['g-api-imei'] . $myheader['g-api-key']) != $myheader['g-api-hash']){
                $this->_sErrorCde = AppErrorCode::INVALID_AUTH_KEY;
                $this->_sErrorMsg = "Invalid AUTH KEY detected";
                return false;
            }
            
            $date = date('Ymd');
            //echo $date;
            //echo substr($myheader['g-api-key'], 0, 8);
            if(substr($myheader['g-api-key'], 0, 8) != $date){
                $this->_sErrorCde = AppErrorCode::INVALID_HASH_SEC_KEY;
                $this->_sErrorMsg = "Invalid HASH SECURITY KEY detected";
                return false;
            }
            
            //$myheader['g-api-imei'] + $myheader['g-api-key'] HASH
            if(!isset($myheader['g-api-token'])){
                $this->_sErrorCde = AppErrorCode::UNSET_API_TOKEN;
                $this->_sErrorMsg = "Unset API TOKEN detected";
                return false;
            }
            return true;
        }
        
        //@implementation
        //*************************
        public function getMessage(){
            return $this->_sErrorMsg;
        }
        //@implementation
        //*************************
        public function getErrorCode(){
            return $this->_sErrorCde;
        }
    }
?>
