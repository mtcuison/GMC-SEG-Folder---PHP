<?php 
  //Module: upload.md5_hash.php

  //Author: kalyptus
  //Date History:
  		//2014.11.08 01:19pm
  		//	started creating this object. 

	include '../lib/gcons.php';
	$json = NULL;
	
	//make sure that a filename was passed
	if(isset($_GET["file"])){ 
	
		echo md5_file(HOST_DIR . "upload/zipped/" . substr($_GET["file"], 0, 4) . "/" . $_GET["file"] . ".json.tar.gz");
	
	} //if(isset($_GET["file"])){
	
?>
