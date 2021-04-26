<?php
    //https://www.electrictoolbox.com/image-headers-php/
    //https://www.tutorialrepublic.com/php-tutorial/php-file-download.php
    //https://www.media-division.com/the-right-way-to-handle-file-downloads-in-php/
    //https://www.baeldung.com/java-download-file
    //mime_content_type(string $filename)
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'WebClient.php';
require_once 'WSHeaderValidatorFactory.php';
require_once 'MySQLAES.php';

$myheader = apache_request_headers();

if(!isset($myheader['g-api-id'])){
    header('Request-Result: error');
    header('Error-Code: ' . "0000");
    header('Error-Message: ' . "anggapoy nagawaan ti labat awa");
    
    echo "anggapoy nagawaan ti labat awa!";
    return;
}

if(stripos(APPSYSX, $myheader["g-api-id"]) === false){
    header('Request-Result: error');
    header('Error-Code: ' . "0000");
    header('Error-Message: ' . "anto la ya... sika lamet!");
    
    echo "anto la ya... sika lamet!";
    return;
}

$validator = (new WSHeaderValidatorFactory())->make($myheader['g-api-id']);
//var_dump($myheader);
//$json = array();
if(!$validator->isHeaderOk($myheader)){
    header('Request-Result: error');
    header('Error-Code: ' . $validator->getErrorCode());
    header('Error-Message: ' . $validator->getErrorMessage());
    return;
}

//GET HEADERS HERE
//Product ID
$prodctid = $myheader['g-api-id'];
//Computer Name / IEMI No
//$pcname 	= $myheader['g-api-imei'];
//SysClient ID
//$clientid = $myheader['g-api-client'];
//Log No
//$logno 		= $myheader['g-api-log'];
//User ID
//$userid		= $myheader['g-api-user'];

$app = new Nautilus(APPPATH);
if(!$app->LoadEnv($prodctid)){
    header('Request-Result: error');
    header('Error-Code: ' . $app->getErrorCode());
    header('Error-Message: ' . $app->getErrorMessage());
    echo base64_encode($app->getErrorMessage());
    return;
}

//$data = file_get_contents('php://input');

//parse into json the PARAMETERS
//$parjson = json_decode($data, true);

if(empty($_POST['modulecd'])){
    header('Request-Result: error');
    header('Error-Code: ' . AppErrorCode::INVALID_PARAMETER);
    header('Error-Message: ' . "Empty module detected");
    echo base64_encode('Empty module detected');
    return;
}

if(empty($_POST['year'])){
    header('Request-Result: error');
    header('Error-Code: ' . AppErrorCode::INVALID_PARAMETER);
    header('Error-Message: ' . "Empty year detected");
    echo base64_encode('Empty year detected');
    return;
}

if(empty($_POST['file'])){
    header('Request-Result: error');
    header('Error-Code: ' . AppErrorCode::INVALID_PARAMETER);
    header('Error-Message: ' . "Empty file name");
    echo base64_encode('Empty file name');
    return;
}

if(!empty($_POST['owner'])){
    header('Request-Result: error');
    header('Error-Code: ' . AppErrorCode::INVALID_PARAMETER);
    header('Error-Message: ' . "Empty branch/department/employee detected");
    echo base64_encode('Empty file name');
    return;
}

$path = EDOCSYS_BASE
      . DIRECTORY_SEPARATOR . $_POST['modulecd']
      . DIRECTORY_SEPARATOR . $_POST['year']
      . DIRECTORY_SEPARATOR . $_POST['owner'];

$file = $_POST['file'];

if(!file_exists($path . DIRECTORY_SEPARATOR . $file)){
    header('Request-Result: error');
    header('Error-Code: ' . AppErrorCode::INVALID_PARAMETER);
    header('Error-Message: ' . $file . ' does not exist...');
    echo base64_encode($file . ' does not exist...');
    return;
}
else{
    header('Request-Result: success');
    header('File-Path: ' . $path . DIRECTORY_SEPARATOR . $file);
}

header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

//create the base64 version of the file
$imageData = base64_encode(file_get_contents($path . DIRECTORY_SEPARATOR . $file));
//save the data to a temporary directory
file_put_contents(EDOCSYS_TEMP . DIRECTORY_SEPARATOR . $file, $imageData);

//since it is a base64 encode file, just set is as an octet-stream...
header('Content-Type: application/octet-stream');
//header('Content-Length: ' . filesize($path . DIRECTORY_SEPARATOR . $file));
header('Content-Length: ' . filesize(EDOCSYS_TEMP . DIRECTORY_SEPARATOR . $file));
header('File-Hash: ' . md5_file(EDOCSYS_TEMP . DIRECTORY_SEPARATOR . $file));

//send the data to the client
set_time_limit(0);
$xfile = @fopen(EDOCSYS_TEMP . DIRECTORY_SEPARATOR . $file,"rb");
while(!feof($xfile))
{
    print(@fread($xfile, 1024*8));
    ob_flush();
    flush();
}

@fclose($xfile);

//remove the file
unlink(EDOCSYS_TEMP . DIRECTORY_SEPARATOR . $file);

?>

