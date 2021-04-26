<?php
    interface FBPromoValidatorInterface{
        public function IsDocumentValid();
        public function SaveTransaction();
        public function getMessage();
        public function getErrorCode();
        public function getDivision();
    }
?>