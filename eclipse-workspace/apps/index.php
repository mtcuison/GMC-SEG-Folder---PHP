<?php
require_once 'config.php';
require_once APPPATH.'/core/Nautilus.php';
require_once APPPATH.'/core/MySQLAES.php';
require_once APPPATH.'/core/WSHeaderValidatorFactory.php';
require_once APPPATH.'/core/Tokenize.php';

echo CommonUtil::dateDiff("m", "2019-09-05", "2021-04-15") + 1;
//$emailadd = htmlspecialchars("adrabanal.85@gmail.com");
//$username = htmlspecialchars("Rabanal, Aerwyn D");
//$password = htmlspecialchars("N1c0Q0U2VVQ3N2V0YmZzaVkvWk9TZz09");
//$salt = htmlspecialchars("c7cd189031271d97eeba5688b379a459b5d158c11e5f29ba50249158b07ca926");
//$password = CommonUtil::app_decrypt($password, $salt);
//echo $password;

//$value = Tokenize::EncryptAuthToken("M00111005387", "09260375777", "3", "2");
//$value = Tokenize::EncryptApprovalToken("GK0120000001", "1", "EP", "M00111005387");

//$value = "30364234374542393234353445314244373239333233413033463131363736423931353639463445374132323535434338443535444539394442393430364241";
//echo Tokenize::DecryptToken($value, "M00111005387");

//echo CommonUtil::isValidMobile("+639260375777");

?>
