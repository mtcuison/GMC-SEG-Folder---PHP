<?php
require_once 'WSHeaderValidatorClient.php';
require_once 'WSHeaderValidatorCompany.php';

class WSHeaderValidatorFactory{
    protected $_validator;
    
    public function make($validator){
        $this->_validator = null;
        if($validator == "GuanzonApp"){
            $this->_validator = new WSHeaderValidatorClient();
        }
        elseif ($validator == "Telecom" || $validator == "IntegSys"){
            $this->_validator = new WSHeaderValidatorCompany();
        }
        
        return $this->_validator;
    }
}

?>