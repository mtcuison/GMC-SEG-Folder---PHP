<?php

    //+++++++++++++++++++++++++++++++
    //Interface for all classes that will validate the headers received in our APIs.
    //+++++++++++++++++++++++++++++++
    interface iWSHeaderValidator{
        public function isHeaderOk($myheader);
        public function getMessage();
        public function getErrorCode();
    }
?>

