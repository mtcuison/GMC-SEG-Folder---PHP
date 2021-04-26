<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'x-api/v1.0/api-config.php';
require_once 'x-api/v1.0/api-const.php';

require_once 'x-api/v1.0/GAuth.php';
require_once 'x-api/v1.0/GDBConfig.php';
require_once 'x-api/v1.0/GConn.php';
require_once 'x-api/v1.0/TokenValidator.php';
require_once 'x-api/v1.0/GAnalytics.php';
require_once 'x-api/v1.0/MySQLAES.php';
require_once 'x-api/v1.0/CommonUtil.php';

//Load Composer's autoloader
require APPPATH .'/apps/vendor/autoload.php';


use xapi\config\v100\APIErrCode;
use xapi\core\v100\GDBConfig;
use xapi\core\v100\GConn;
use xapi\core\v100\TokenValidator;
use xapi\core\v100\GAnalytics;
use xapi\core\v100\CommonUtil;
use xapi\core\v100\MYSQLAES;

$myheader = apache_request_headers();

//echo 'hello';

//perform the initial checking of header
$json = array();
if(!isset($myheader['g-access-token'])){
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::UNAUTHORIZED_ACCESS;
    $json["error"]["message"] = "Invalid authorization key detected.";
    echo json_encode($json);
    return;
}

$jwt = $myheader['g-access-token'];
$validator = new TokenValidator(null);

if(!$validator->isValidAccessKey($jwt)){
    $json["result"] = "error";
    $json["error"]["code"] = $validator->getErrorCode();
    $json["error"]["message"] = $validator->getMessage();
    echo json_encode($json);
    return;
}

//check if parameters are valid
$param = file_get_contents('php://input');
//echo $param;

$parjson = mb_convert_encoding(json_decode($param, true), 'ISO-8859-1', 'UTF-8');

if(!isset($parjson['to'])){
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::INVALID_PARAMETERS;
    $json["error"]["message"] = "Invalid parameter detected.(to)";
    echo json_encode($json);
    return;
}

if(!isset($parjson['subject'])){
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::INVALID_PARAMETERS;
    $json["error"]["message"] = "Invalid parameter detected.(subject)";
    echo json_encode($json);
    return;
}

if(!isset($parjson['body'])){
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::INVALID_PARAMETERS;
    $json["error"]["message"] = "Invalid parameter detected.(body)";
    echo json_encode($json);
    return;
}

$mail = new PHPMailer(true);                              // Passing `true` enables exceptions

$aes = new MySQLAES(APPKEYX);
$sndrpaswd = $aes->decrypt(USERPAS);

try {
    $mail->SMTPDebug = 0;                                 // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = '192.168.10.220';                       // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = USERACT;       // SMTP username
    $mail->Password = $sndrpaswd;                      // SMTP password
    $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = 587;                                    // TCP port to connect to
    //$mail->SMTPAutoTLS = true;
    $mail->setFrom('noreply@guanzongroup.com.ph', 'Guanzon App');
    $mail->addAddress($parjson['to']);
    $mail->Subject = $parjson['subject'];
    $mail->Body    = $parjson['body'];
    
    //create the temporary file for the attachment here...
    $ctr = 1;
    while(isset($parjson["data" . $ctr])){
        if(!isset($parjson["filename" . $ctr])){
            $json["result"] = "error";
            $json["error"]["code"] = APIErrCode::INVALID_PARAMETERS;
            $json["error"]["message"] = "Invalid parameter detected.(attachment)";
            echo json_encode($json);
            return;
        }
	//echo $parjson["filename" . $ctr];
	//return;
        $bin = base64_decode($parjson["data" . $ctr]);
	    //echo $parjson["data"];
        //echo EDOCSYS_TEMP . DIRECTORY_SEPARATOR .  $parjson["filename"];
        file_put_contents(EDOCSYS_TEMP . DIRECTORY_SEPARATOR .  $parjson["filename" . $ctr], $bin);
        $mail->AddEmbeddedImage(EDOCSYS_TEMP . DIRECTORY_SEPARATOR .  $parjson["filename" . $ctr], $parjson["filename" . $ctr]);
	$ctr++;
    }
    
    //$mail->addEmbeddedImage($path, $cid);
    //example: $mail->AddEmbeddedImage('img/2u_cs_mini.jpg', 'logo_2u');
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    $mail->send();
    
    //delete attachement here
    //if(isset($parjson["data"])){
    //    unlink(EDOCSYS_TEMP . DIRECTORY_SEPARATOR .  $parjson["file"]);
    //}

    $ctr = 1;
    while(isset($parjson["data" . $ctr])){
        unlink(EDOCSYS_TEMP . DIRECTORY_SEPARATOR .  $parjson["filename" . $ctr]);
	$ctr++;
    }
    
} catch (Exception $e) {
    $json["result"] = "error";
    $json["error"]["code"] = APIErrCode::INVALID_PARAMETERS;
    $json["error"]["message"] = 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
    echo json_encode($json);
    return;
}

$json["result"] = "success";
echo json_encode($json);
return;


?>
