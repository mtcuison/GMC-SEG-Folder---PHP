<?php
    interface FBPromoValidatorInterface{
        public function IsDocumentValid();
        public function SaveMaster();
        public function SaveDetail($pagelink, $cstatusxx);
        
        public function setMaster($index, $value);
        public function getMessage();
        public function getErrorCode();
        public function getDivision();
    }
?>