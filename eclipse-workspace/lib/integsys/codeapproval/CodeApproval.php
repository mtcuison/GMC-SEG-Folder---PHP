<?php
    header('Content-Type: text/html; charset=utf-8');

    require_once 'iCodeApproval.php'; 
    require_once APPPATH.'/core/CommonUtil.php';
    
    class CodeApproval implements iCodeApproval{
        protected $poRaw;
        protected $poResult;
        
        protected $sErrorMsg = '';
        
        function __construct(){
            $this->poRaw = new xObject();
            $this->poResult = new xObject();
        }
        
        function __destruct() {
            $this->poRaw = null;
            $this->poResult = null;
	   }
        
        public function Encode() {   
            //verify system approval requested
            if ($this->poRaw->getSystem() == ''){
                $this->sErrorMsg = 'Invalid System Approval Requested detected!';
                return false;
            }
            
            if ($this->poRaw->getDate() == ''){
                $this->sErrorMsg = 'Invalid Date Requested detected!';
                return false;
            }
            
            
            if ($this->poRaw->getIssuedBy() == ''){
                $this->sErrorMsg = 'Invalid Issuing Department/Person detected!';
                return false;
            }
            
            switch ($this->poRaw->getSystem()){
                case CodeGenConst::ManualLog:
                    //Misc should be the binary equivalent of the periods approved...
                    if (is_numeric($this->poRaw->getMisc() == false)){
                        $this->sErrorMsg = 'Invalid Reference Number detected!';
                        return false;
                    }
                    
                    $this->poResult->setMisc(random_int(0, 9) . str_pad(dechex($this->poRaw->getMisc()), 2, "0", STR_PAD_LEFT));
                    break;
                case CodeGenConst::Day2Day:
                    //Misc should be the time the request was issued...                    
                    $this->poResult->setMisc(chr(random_int(65, 90)) . str_pad(dechex(strval($this->poRaw->getMisc()) + 70), 2, "0", STR_PAD_LEFT));
                case CodeGenConst::OfficeRebate:
                case CodeGenConst::FieldRebate:
                case CodeGenConst::MCDiscount:
                case CodeGenConst::PartsDiscount:
                case CodeGenConst::SPPurcDelivery:
                case CodeGenConst::IssueORNotPR:
                case CodeGenConst::IssueORNotSI:
                case CodeGenConst::MCIssuance:
                case CodeGenConst::MPDiscount:
                case CodeGenConst::JobOrderWOGCard:
                case CodeGenConst::MCTransfer:
                case CodeGenConst::MCDownPayment:
                    if ($this->poRaw->getSystem() != CodeGenConst::JobOrderWOGCard){
                        //Misc should be the reference number of the transaction approved...
                        if (is_numeric($this->poRaw->getMisc()) == false){
                            $this->sErrorMsg = 'Invalid Reference Number detected!';
                            return false;
                        }
                    } else {
                        $this->poRaw->setMisc(str_replace("-", "", $this->poRaw->getMisc()));
                    }
                    
                    $this->poResult->setMisc(str_pad(dechex($this->TotalStr($this->poRaw->getMisc())), 3, "0", STR_PAD_LEFT));
                    break;
                case CodeGenConst::Forgot2Log:
                case CodeGenConst::BusinessTrip:
                case CodeGenConst::BusinessTripLog:
                case CodeGenConst::Leave:
                case CodeGenConst::Overtime:
                case CodeGenConst::Shift:
                case CodeGenConst::DayOff:
                case CodeGenConst::Tardiness:
                case CodeGenConst::Undertime:
                case CodeGenConst::CreditInv:
                case CodeGenConst::CreditApp:
                case CodeGenConst::CashBalance:
                case CodeGenConst::MCClusteringDelivery:
                case CodeGenConst::FSEPActivation:
                case CodeGenConst::FSEXActivation:                    
                case CodeGenConst::Additional:
                case CodeGenConst::ByahengFiesta:
                case CodeGenConst::TeleMarketing:
                case CodeGenConst::PreApproved:
                case CodeGenConst::HotModel:
                    $this->poResult->setMisc(str_pad(dechex($this->TotalStr(strtolower(substr($this->poRaw->getMisc(), 0, 29)))), 3, "0", STR_PAD_LEFT));
                    break;
                default:
                    $this->sErrorMsg = 'Invalid System Approval Request detected!';
                    return false;
            }
            
            if ($this->poRaw->getBranch() != ''){
                $this->poResult->setBranch(str_pad(dechex($this->TotalStr(substr($this->poRaw->getBranch(), 1))), 2, "0", STR_PAD_LEFT));
                $this->poResult->setDate(str_pad(dechex(idate('m', strtotime($this->poRaw->getDate())) + 
                                                    idate('d', strtotime($this->poRaw->getDate())) + 
                                                    idate('y', strtotime($this->poRaw->getDate()))), 2, "0", STR_PAD_LEFT));
            } else {
                $this->poResult->setDate(dechex(str_pad(date('d', strtotime($this->poRaw->getDate())), 2, "", STR_PAD_LEFT) .
                                            str_pad(date('m', strtotime($this->poRaw->getDate())), 2, "", STR_PAD_LEFT) . 
                                            date('y', strtotime($this->poRaw->getDate()))));
            }
            
            $this->poResult->setSystem(dechex($this->TotalStr($this->poRaw->getSystem())));
            
            $this->poResult->setIssuedBy($this->poRaw->getIssuedBy());

            return true;
        }

        public function getResult() {
            if ($this->poRaw->getBranch() != ''){                   
                return strtoupper($this->poResult->getMisc() . 
                                    $this->poResult->getIssuedBy() . 
                                    $this->poResult->getBranch() . 
                                    $this->poResult->getSystem() . 
                                    $this->poResult->getDate());
            } else {
                return strtoupper($this->poResult->getMisc() . 
                                    $this->poResult->getIssuedBy() .
                                    $this->poResult->getSystem() . 
                                    $this->poResult->getDate());
            }
        }

        public function isEqual($fsCode1, $fsCode2) {
            if (strlen($fsCode1) != 10){return -100;}
            if (strlen($fsCode2) != 10){return -100;}
            
            $fsCode1 = strtoupper($fsCode1);
            $fsCode2 = strtoupper($fsCode2);
            
            //Requesting branch is different from the given code                   
            if (substr($fsCode1, 4, 2) != substr($fsCode2, 4, 2)){return -100;}
            
            //System approval request is different from the given code            
            if (substr($fsCode1, 6, 2) != substr($fsCode2, 6, 2)){return -100;}
            
            //Date requested is different from the given code
            if (substr($fsCode1, 8, 2) != substr($fsCode2, 8, 2)){return -100;}
            
            //Issuing Department/Person is different from the given code            
            if (substr($fsCode1, 3, 1) != substr($fsCode2, 3, 1)){return -100;}
            
            switch (substr($fsCode1, 6, 2)){
                case str_pad(dechex($this->TotalStr(CodeGenConst::Day2Day)), 2, "0"):
                    //'Misc Info is different from the given code
                    //New Issued - Old Issued => If <=0 Then INVALID SINCE we need a new code
                    return strval(substr($fsCode2, 1, 2)) - strval(substr($fsCode1, 1, 2));
                case str_pad(dechex($this->TotalStr(CodeGenConst::ManualLog)), 2, "0"):
                    //Misc Info is different from the given code
                    if (substr($fsCode1, 1, 2) != substr($fsCode2, 1, 2)){return -100;}
                    break;
                default:
                    //Misc Info is different from the given code
                    if (substr($fsCode1, 0, 3) != substr($fsCode2, 0, 3)){return -100;}
            }
            
            return 0;
        }
        
        public function isEqualx($fsCode1, $fsCode2) {               
            if (strlen($fsCode1) != strlen($fsCode2)){return -100;}
            //if (strlen($fsCode2) != 10){return -100;}
            
            $fsCode1 = strtoupper($fsCode1);
            $fsCode2 = strtoupper($fsCode2);
            
            //Issuing Department/Person is different from the given code
            if (substr($fsCode1, 3, 1) != substr($fsCode2, 3, 1)){return -100;}
            
            //System approval request is different from the given code
            if (substr($fsCode1, 4, 2) != substr($fsCode2, 4, 2)){return -100;}
            
            //Misc Information/Name
            if (substr($fsCode1, 0, 3) != substr($fsCode2, 0, 3)){return -100;}
            
            //extract DATE2
            $lsDatex= str_pad(hexdec(substr($fsCode1, 6)), "6", "0", STR_PAD_LEFT);
            $lsDate1 = substr($lsDatex, 2, 2) . "-" . substr($lsDatex, 0, 2) . "-" . substr($lsDatex, 4, 2);
            $ldDate1 = date_create_from_format('m-d-y', $lsDate1);
            $ldDate1 = date_format($ldDate1, 'Y-m-d');
            
            //extract DATE1
            $lsDatex= str_pad(hexdec(substr($fsCode1, 6)), "6", "0", STR_PAD_LEFT);
            $lsDate2 = substr($lsDatex, 2, 2) . "-" . substr($lsDatex, 0, 2) . "-" . substr($lsDatex, 4, 2);
            $ldDate2 = date_create_from_format('m-d-y', $lsDate2);
            $ldDate2 = date_format($ldDate2, 'Y-m-d');
            
            $ldDate1 = new DateTime($ldDate1);
            $ldDate2 = new DateTime($ldDate2);
            
            switch (strtolower(substr($fsCode1, 4, 2))){
                case str_pad(dechex($this->TotalStr(CodeGenConst::TeleMarketing)), 2, "0"):
                case str_pad(dechex($this->TotalStr(CodeGenConst::PreApproved)), 2, "0"):
                    $ldDate2x = new DateTime(date_format(date_add($ldDate2,date_interval_create_from_date_string("60 days")), "Y-m-d"));
                   
                    if ($ldDate1->diff($ldDate2)->format('%a') >= 0 && $ldDate1->diff($ldDate2x)->format('%a') <= 60){
                        return 0;
                    }
                case str_pad(dechex($this->TotalStr(CodeGenConst::ByahengFiesta)), 2, "0"):
                    $ldDate2x = new DateTime(date_format(date_add($ldDate2,date_interval_create_from_date_string("3 days")), "Y-m-d"));
                    if ($ldDate1->diff($ldDate2)->format('%a') >= 0 && $ldDate1->diff($ldDate2x)->format('%a') <= 3){
                        return 0;
                    }
                case str_pad(dechex($this->TotalStr(CodeGenConst::Additional)), 2, "0"):
                    $ldDate2x = new DateTime(date_format(date_add($ldDate2,date_interval_create_from_date_string("2 days")), "Y-m-d"));
                    if ($ldDate1->diff($ldDate2)->format('%a') >= 0 && $ldDate1->diff($ldDate2x)->format('%a') <= 2){
                        return 0;
                    }
                default:
                    return -100;
            }
        }

        public function setBranch($branch) {
            $this->poRaw->setBranch($branch);
        }

        public function setDateRequested($date) {
            $this->poRaw->setDate($date);
        }

        public function setIssuedBy($codeby) {
            $this->poRaw->setIssuedBy($codeby);
        }

        public function setMiscInfo($misc) {
            $this->poRaw->setMisc(CommonUtil::Hex2String($misc));
        }

        public function setSystem($systemcd) {
            $this->poRaw->setSystem($systemcd);
        }
        
        private function TotalStr($fsStr){
            $fsStr = str_replace(' ', '', $fsStr);
            $fsStr = str_replace(',', '', $fsStr);
            
            $lnTotal = 0;
            for ($x = 0; $x <= strlen($fsStr); $x++){
                $lnTotal = $lnTotal + ord(substr($fsStr, $x, 1));
            }
            return $lnTotal;
        }
}   
    
    class CodeGenConst{
        public const Day2Day = 'DT';
        public const ManualLog = 'ML';
        public const Forgot2Log = 'FL';
        public const BusinessTrip = 'OB';
        public const BusinessTripLog = 'OL';
        public const Leave = 'LV';
        public const Overtime = 'OT';
        public const Shift = 'SH';
        public const DayOff = 'DO';
        public const Tardiness = 'TD';
        public const Undertime = 'UD';
        public const CreditInv = 'CI';
        public const CreditApp = 'CA';
        public const WholeSaleDisc = 'WD';
        public const CashBalance = 'CB';
        public const OfficeRebate = 'R1';
        public const FieldRebate = 'R2';
        public const PartsDiscount = 'SI';
        public const MCDiscount = 'DR';
        public const SPPurcDelivery = 'PD';
        public const IssueORNotPR = 'OR';
        public const IssueORNotSI = 'OX';
        public const Additional = 'RS';
        public const ByahengFiesta = 'BF';
        public const TeleMarketing = 'TM';
        public const MCIssuance = 'MI';
        public const MCClusteringDelivery = 'CD';
        public const FSEPActivation = 'FA';
        public const FSEXActivation = 'FX';
        public const MPDiscount = 'MD';
        public const PreApproved = 'PA';
        public const JobOrderWOGCard = 'JG';
        public const MCDownPayment = 'DP';
        public const MCTransfer = 'MT';
        public const HotModel = 'HM';
    }
    
    class xObject{
        private $system = "";
        private $branch = "";
        private $issuedby= "";
        private $date = "";
        private $misc = "";
        
        function getSystem(){
            return $this->system;
	}
        
        function getBranch(){
            return $this->branch;
	}
        
        function getIssuedBy(){
            return $this->issuedby;
	}
        
        function getDate(){
            return $this->date;
	}
        
        function getMisc(){
            return $this->misc;
	}
        
        function setSystem($system){
            $this->system = $system;
        }
        
        function setBranch($branch){
            $this->branch = $branch;
        }
        
        function setIssuedBy($issuedby){
            $this->issuedby = $issuedby;
        }
        
        function setDate($date){
            $this->date = $date;
        }
        
        function setMisc($misc){
            $this->misc = $misc;
        }
    }
?>
