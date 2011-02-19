<?php

	set_time_limit(300); // 5 minutes
	ob_start();

	$GLOBALS['HTTP_VARS'] = $_GET + $_POST;
	function get($key){
		if( isset( $GLOBALS['HTTP_VARS'][$key] ) )
			return $GLOBALS['HTTP_VARS'][$key];
	}
	function byte( $size ) {
	    $unim = array("B","KB","MB","GB","TB","PB");
	    $i = 0;
	    while ($size>=1024) {
	        $i++;
	        $size = $size/1024;
	    }
	    return number_format($size,($i ? 2 : 0),",",".")." ".$unim[$i];
	}
	function myFlush(){
	    //ob_end_flush();
	    //ob_flush();
	    flush();
	    ob_start(); 
	}
	
	if( get('dump') && isset( $db ) ){
		$sql = base64_decode( $db['contents'] );
		$sql = gzuncompress( $sql );
		echo "Dump database <b>{$db['database']}</b><br/></br><textarea style=\"width:100%;padding:10px;\" rows=\"30\">$sql</textarea>";
		exit;
	}

	$dir = get('dir');
	$execute = isset( $settings['execute'] ) ? $dir . "/" . $settings['execute'] : null;
	$overwrite = get('overwrite');
	$install = get('install');
	
	//se install
	if( $install ){

		if( $dir != "" && substr( $dir, strlen($dir)-1,1 ) != "/" )
			$dir .= "/";
			
		if( $dir != '' && !file_exists( $dir ) )
			mkdir( $dir );


		if( get('db') ){

			$db_hostname=get('db_hostname');
			$db_username=get('db_username');
			$db_password=get('db_password');
			$db_database=get('db_database');

			if( mysql_connect( $db_hostname, $db_username, $db_password ) ){
				if( mysql_select_db( $db_database ) ){

					$db_installed = false;
					$db_error = false;

					$sql = base64_decode( $db['structure'] );
					$sql = gzuncompress( $sql );
					$query = explode( ";\r", $sql );
					echo "<br/><br/><h3>DATABASE: CREATE STRUCTURE</h3>";
					for( $i=0; $i<count($query);$i++){
						list($table,$rows) = each( $db['table_list'] );
						echo "<div>$table ($rows rows)";
						if( !mysql_query( $query[$i] ) ){
							$error = true;
							echo ' - <font color="red">ERROR</font>';
						}
						else
							echo " - OK";
						echo "</div>";
						myFlush();
					}
					if( $error )
						echo "<div>Ci sono stati errori</div>";
					$error = false;

					$sql = base64_decode( $db['contents'] );
					$sql = gzuncompress( $sql );
					$query = explode( ";\n", $sql );
					
					echo "<br/><br/><hr><h3>DATABASE: INSERT CONTENT</h3>";
					for( $i=0; $i<count($query);$i++){
						echo "<div>Query $i";
						if( !mysql_query( $query[$i] ) ){
							$error = true;
							echo ' - <font color="red">ERROR</font> ' . mysql_error() . " <div class=\"error\">".$query[$i]."</div>";
						}
						else
							echo " - OK";
						echo "</div>";
						myFlush();
					}
					if( $error )
						echo "<div>Ci sono stati errori</div>";

					mysql_close();
					
					
					$db_installed = true;
				}
				else
					echo "<div>Database $db_database not found!</div>";
			}
			
			if( $db_installed )
				echo "Database Installed";
		}



		echo "<br/><br/><hr><h3>CREATE DIRECTORY</h3>";
		foreach( $directory as $dirname => $dir_param )
			if( !file_exists( $dir . $dirname ) )
				mkdir( $dir . $dirname, $dir_param['perms'] );
		echo " - OK";
		myFlush();
				
		echo "<br/><br/><hr><h3>DEFLATING FILES</h3>";
		foreach( $file as $filename => $file_param ){
			
			if( !file_exists( $dir . $filename ) or $overwrite ){
				
				echo $dir . $filename . " ... ";
				myFlush();
				
				$fp = fopen( $dir . $filename, "w" );
				$contents = base64_decode( $file_param['contents'] );
				$contents = gzuncompress( $contents );

				fwrite( $fp, $contents, strlen( $contents ) );
				fclose( $fp );
				chmod( $dir . $filename, $file_param['perms'] );

				echo  " file write<br>";
				myFlush();
			}
		}
		
	echo " <br><hr><h1>Installation Complete!";
	
	if( $execute )
		echo " <a href=\"$execute\">Click here to go $execute</a>.";
	myFlush();
		
	}else{
		?>
		#_INSTALLATION_UI_#
		<?php
	}

?>