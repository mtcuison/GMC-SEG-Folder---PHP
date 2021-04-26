<?php
    interface iCodeApproval{
        function getResult();
        function setBranch($branch);
        function setDateRequested($date);
        function setIssuedBy($codeby);
        function setSystem($systemcd);
        function setMiscInfo($misc);
        function Encode();
        function isEqual($fsCode1, $fsCode2);
        function isEqualx($fsCode1, $fsCode2);
    }
