<?php

	ini_set( "display_errors", true );
	error_reporting( E_ALL );

	

	//------------------------------------------
	// Include functions
	//------------------------------------------
	include "phpinstaller/installer.class.php";
	include "phpinstaller/functions.php";

	

	//------------------------------------------
	// Settings
	//------------------------------------------
	$lang_id = get('lang_id') ? get('lang_id') : "en";
	$title = get('title');
	$description = get('description');
	$author = get('author');
	$filename = get('filename') ? get('filename') : INSTALL_DEFAULT;
	$execute = get('execute');
	$dir = get('dir');

	// database info
	$db 		 = get('db');					// if db add database to installer
	$db_hostname = get('db_hostname');
	$db_username = get('db_username');
	$db_password = get('db_password');
	$db_database = get('db_database');
	
	$download = get('download');				// true if you want download the file
	
	
	
	
	//------------------------------------------
	// Installer
	//------------------------------------------

	if( get('install') ){
		//------------------------------------------
		// Create Installer
		//------------------------------------------
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<title>PHP Compressor</title>
			<link href="phpinstaller/tpl/style.css" type="text/css" rel="stylesheet" />
		</head>
		<body>
		<?php
		
		
		$php = new PhpInstaller( $title, $description, $author, $execute );
		$php->addDir( $dir );

		if( $db )
			$php->addDb( $db_hostname, $db_username, $db_password, $db_database );
			
		$php->createInstaller( $filename, $download );	// create installer
		
	?>
			</body>
		</html>
	<?php
	}
	else{
		
		//------------------------------------------
		// Draw UI
		//------------------------------------------
		include "phpinstaller/tpl/create_installer_ui.html";

	}



?>