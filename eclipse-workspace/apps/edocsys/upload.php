<?php
    //https://www.codejava.net/java-ee/servlet/upload-file-to-servlet-without-using-html-form

require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'WebClient.php';
require_once 'WSHeaderValidatorFactory.php';
require_once 'MySQLAES.php';

$myheader = apache_request_headers();

if(!isset($myheader['g-api-id'])){
    echo "anggapoy nagawaan ti labat awa!";
    return;
}

if(stripos(APPSYSX, $myheader["g-api-id"]) === false){
    echo "anto la ya... sika lamet!";
    return;
}

$validator = (new WSHeaderValidatorFactory())->make($myheader['g-api-id']);
//var_dump($myheader);
$json = array();
if(!$validator->isHeaderOk($myheader)){
    $json["result"] = "error";
    $json["error"]["code"] = $validator->getErrorCode();
    $json["error"]["message"] = $validator->getMessage();
    echo json_encode($json);
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
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

header('Content-Type: application/json');
$json = array();

//check module code (client/scandoc/backup/replication)
//please see https://php.net/manual/en/features.file-upload.post-method.php

/*
 * g-edoc-modl  -> edocsys module 
 * g-edoc-year  -> year
 * g-edoc-dept  -> department
 * g-edoc-brcd  -> branch code
 * g-edoc-empl  -> employee id
 * g-edoc-hash  -> file hash 
 * 
 * Path: /edocsys/year/module/department
 *       /edocsys/year/module/branch code
 *       /edocsys/year/module/employee id
 */

if(empty($_POST['modulecd'])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Empty module detected.";
    echo json_encode($json);
    return;
}

if(empty($_POST['year'])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Empty year detected.";
    echo json_encode($json);
    return;
}

if(empty($_POST['hash'])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Empty hash info detected.";
    echo json_encode($json);
    return;
}

if(empty($_FILES['uploaded_file'])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Empty image file detected.";
    echo json_encode($json);
    return;
}

//check if file has no problem during upload
$hash = md5_file($_FILES['uploaded_file']['tmp_name']);
if($hash !== $_POST['hash']){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Invalid hash info detected.";
    echo json_encode($json);
    return;
}

// get details of the uploaded file
$fileTmpPath = $_FILES['uploaded_file']['tmp_name'];
$fileName = $_FILES['uploaded_file']['name'];
//$fileSize = $_FILES['uploaded_file']['size'];
//$fileType = $_FILES['uploaded_file']['type'];
//$fileNameCmps = explode(".", $fileName);
//$fileExtension = strtolower(end($fileNameCmps));

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

//echo $uploadFileDir;
if(!is_dir($path)){
    mkdir($path, 0777, true);
    chmod($path, 0777);
}

$dest_path = $path . "/" . $fileName;
if(!move_uploaded_file($fileTmpPath, $dest_path))
{
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "There was some error moving the file to upload directory. Please make sure the upload directory is writable by web server.";
    echo json_encode($json);
    return;
}

$json["result"] = "success";
$json["module"] = $_POST['modulecd'] . "==" . $_POST['year'] . "==" . $_POST['hash'];
echo json_encode($json);

/*
Nansulat kami ta piyan ipatanir so plano na kongregasyon mi(bayan tan pasibi) ya pangi-install na 
water system diad KH.

Walay dwaran water station diad barangay ya kulaan na KH pero samay sakey et aliwdiwa so serbisyo
to. Samay sakey et agaylay karawi na pan-konektaan. Katon siad sayan bekta, say wala labat ya water 
system mi ed samay water pump. Walay kairapan so pan-asol na saray punduan na danum no panaoy mamauran
tan no tiagew et mangupot na panaon so pan-mentina na landscape. Katon nantungtungan na dwaran kongregasyon
so pangi-install na water system ta pian napansyansya mi so kalinisan na CR na KH tan say dakep na landscape.

Inlaktip mi ed sayan sulat so plano na sayan proyekto kaiba la so projected budget ya iexpect mi ya
gastusen na saray kongregasyon. 

Ilaloan mi so nayarin ni-suggest yo ta pian natumbok mi so kakaukulan nipaakar ed saray proyekto ya walay iyarum
ed kingdom hall, ontan to met ed say resulta na sayan request mi.

Saray agagi yo,


Marlon A. Sayson
KH Operating Committee

*/

?>