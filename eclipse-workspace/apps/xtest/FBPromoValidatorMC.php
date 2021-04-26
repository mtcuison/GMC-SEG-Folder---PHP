<?php 
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'FBPromoValidatorInterface.php';

class FBPromoValidatorMC implements FBPromoValidatorInterface{
    protected $_app;
    protected $_division;
    protected $_branchcd;
    protected $_doctype;
    protected $_docnmbr;
    protected $_mobileno;
    
    protected $_sErrorMsg;
    protected $_sErrorCde;
    
    const DOCUMENT_TYPE = "OR»PR»CR»AR»SI"; 
    
    public function __construct($app, $branchcd, $doctype, $docnmbr, $mobileno){
        $this->_app = $app;
        $this->_division = "1"; //MC
        $this->_branchcd = $branchcd;
        $this->_doctype = strtoupper($doctype);
        $this->_docnmbr = $docnmbr;
        $this->_mobileno = $mobileno;
    }
    
    public function IsDocumentValid(){
        if (strpos("OR»PR»CR»AR»SI", $this->_doctype, 0)){
            $this->_sErrorCde = "100";
            $this->_sErrorMsg = "Document used was invalid.";
            return false;
        }
        
        return true;
    }
    
    public function SaveTransaction(){
        
    }

    public function getErrorCode(){return $this->_sErrorCde;}
    public function getMessage(){return $this->_sErrorMsg;}
    public function getDivision(){return $this->_division;}
}
?>