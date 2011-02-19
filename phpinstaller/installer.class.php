<?php

/**
 * Project: Php Installer, Compress contents in a single autoinstall php
 *  
 * File: installer.class.php
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @link http://www.raincms.com
 * @author Federico Ulfo <info@rainelemental.net>
 * @version 0.5
 * @copyright 2006 - 2010 Federico Ulfo | www.federicoulfo.it
 * @package php installer
 */


	ob_start();
	define( "INSTALL_DEFAULT", "install.php" );
	
	class PhpInstaller{

		var $directory;
		var $ignore_list = array( "create_install.php", "phpinstaller/" );
		var $version = "0.5";		// installer version
		var $size = 0;
		var $compressed_size = 0;
		
		/**
		 * Init
		 */
		function phpInstaller( $title, $description = null, $author = null, $execute = null ){
			$this->title = $title;
			$this->description = $description;
			$this->author = $author;
			$this->execute = $execute;
		}
		

		
		/**
		 * Add dir
		 */
		function addDir( $dir ){

			if( $dir == "/" )
				$dir = "";
			
			//------------------------------------------
			// Read the directory tree
			//------------------------------------------
			if( $files = glob( $dir . "*" ) )
				foreach( $files as $filename ){
					if( is_dir( $filename ) && !in_array( getcwd() . "/" . $filename, $this->ignore_list ) )
						$this->addDir( $filename . "/" );
					elseif( !in_array( getcwd() . "/" . $filename, $this->ignore_list ) )
						$this->addFile( $filename );
				}

			if( is_dir( $dir ) && !in_array( getcwd() . "/" . $filename, $this->ignore_list ) )
				$this->dir[$dir] = true;
		}

		
		
		/**
		 * Add file
		 */
		function addFile( $file ){
			$this->file[$file] = true;
		}
		

		/**
		 * Add mysql database
		 */
		function addDb( $hostname, $username, $password, $database ){
			$this->db_hostname = $hostname;
			$this->db_username = $username;
			$this->db_password = $password;
			$this->db_database = $database;
		}


	//------------------------------------------
	// Private methods
	//------------------------------------------

	
		/**
		 * Compress a file
		 */
		function compressFile( $file ){

			$contents = file_get_contents( $file );	// read file
			$contents = gzcompress( $contents );	// compress contents
			$contents = base64_encode( $contents );	// convert contents in base64
			
			$filename 			= basename( $file );
			$dir 				= dirname( $file );
			$ext 				= end( (explode('.', $file)) );
			$name 				= basename( $file, ".".$ext );
			$size 				= filesize( $file );
			$compressed_size 	= strlen( $contents );

			$this->size += $size;
			$this->compressed_size += $compressed_size;
					
			return array( $filename, $dir, $name, $ext, $size, $contents, $compressed_size );
			
		}
		
		
		
		/**
		 * Read and compress mysql database
		 */
		function compressDb( ){
			
			//------------------------------------------
			// Connect to database
			//------------------------------------------

			mysql_connect( $this->db_hostname, $this->db_username, $this->db_password ) or die( "database error" );
			mysql_select_db( $this->db_database ) or die( "database not found" );

			//------------------------------------------
			// Dump database
			//------------------------------------------
			$tables = mysql_query( "SHOW TABLES FROM {$this->db_database}" );

			while ( list($table) = mysql_fetch_array($tables)){				

				echo "dump $table ";
				myFlush();

				$insert_sql = "";
				if ( $r = mysql_query("SHOW CREATE TABLE `$table`") ){
					$d = mysql_fetch_array($r);
					$d[1] .= ";";
					$TABLE[] = str_replace("\n", "", $d[1]);
					$table_query = mysql_query("SELECT * FROM `$table`");
					$num_fields = mysql_num_fields($table_query);
					
					$table_list[$table] = $num_fields;
					
					while ($fetch_row = mysql_fetch_array($table_query)){
						$insert_sql .= "INSERT INTO $table VALUES(";
						for ($n=1;$n<=$num_fields;$n++){
							$m = $n - 1;
							$insert_sql .= "'".mysql_real_escape_string($fetch_row[$m])."', ";
						}
						$insert_sql = substr($insert_sql,0,-2);
						$insert_sql .= ");\n";
					}
	
					if ($insert_sql!= "")
						$SQL[] = $insert_sql;
				}
				
				echo " OK<br>";
				myFlush();
			}
			mysql_close();


			//------------------------------------------
			// Compress and return values
			//------------------------------------------
			$tables = implode("\r", $TABLE);
			$insert = implode("\r", $SQL);

			$tables = gzcompress( $tables );
			$tables = base64_encode( $tables );

			$insert = gzcompress( $insert );
			$insert = base64_encode( $insert );

			return array( $table_list, $tables, $insert );

		}
		
		
		/**
		 * create installer file
		 */
		function createInstaller( $install = INSTALL_DEFAULT, $download = false ){
			

			//------------------------------------------
			// Open file
			//------------------------------------------
			$header = "<?php" . "\n";
			$header .= "//Compressed with Php Installer {$this->version}";

			$fp = fopen( $install, "w" );
			fwrite( $fp, $header, strlen( $header ) );



			//------------------------------------------
			// Write directory list
			//------------------------------------------
			if( isset( $this->dir ) ){
				
				$directory = "\n\n" . "//Directory list" . "\n";
				$directory .= "\$directory = array();";
				
				ksort( $this->dir );
				foreach( $this->dir as $dir => $compress ){
					// if directory can be compressed
					if( $compress ){
						$perms = fileperms( $dir );
						$directory .= "\n" . "\$directory['{$dir}'] = array( 'perms' => {$perms} );";
					}
				}
				
				fwrite( $fp, $directory, strlen( $directory ) );
				echo "<hr>DIRECTORY: OK<br><br>"; 

			}
			
			

			

			//------------------------------------------
			// Compress and write file
			//------------------------------------------
			if( isset( $this->file ) ){
				
				echo "<hr>FILES:<br>";
				myFlush();
				
				$compressed_file =  "\n\n" . "//File list" . "\n";
				$compressed_file .= "\$file= array();";
				fwrite( $fp, $compressed_file, strlen( $compressed_file ) );
				
				foreach( $this->file as $file => $compress ){
					
					echo $file . " : ";
					list( $basename, $dir, $name, $ext, $size, $contents, $compressed_size ) = $this->compressFile( $file );
					$perms = fileperms( $file );
					$compressed_file = "\n" . "\$file['$file'] = array( 'basename' => '$basename','dir' =>'$dir','name' =>'$name','ext' =>'$ext','size' =>$size, 'perms' => $perms, 'contents' =>'$contents' ); ";
					fwrite( $fp, $compressed_file, strlen( $compressed_file ) );				
					
					echo " OK<br>";
					myFlush();
				}
			}
			
			
			//------------------------------------------
			// Compress and write database dump
			//------------------------------------------
			if( isset( $this->db_hostname ) ){

				echo "<hr>DATABASE:<br>";
				myFlush();

				
				list( $table_list, $db_structure, $db_contents ) = $this->compressDb();
				$tables = var_export( $table_list, true );
				
				$compressed_db = "\n\n" . "//Database";
				$compressed_db .= "\n\n" . "\$db = array( 	'table_list' => $tables, " . "\n";
				$compressed_db .= "						'structure' =>'{$db_structure}', " . "\n";
				$compressed_db .= "						'contents' =>'{$db_contents}', " . "\n";
				$compressed_db .= "						'database' =>'{$this->db_database}', " . "\n";
				$compressed_db .= "					 ); " . "\n\n";

				fwrite( $fp, $compressed_db, strlen( $compressed_db ) );				
			}
			
			
			//------------------------------------------
			// Settings
			//------------------------------------------
			$settings_file =  "\n\n" . "//Settings" . "\n";
			$settings_file .= "\$settings=array();";
			$settings_file .= "\n" . "\$settings['execute'] = '{$this->execute}';" . "\n";
			fwrite( $fp, $settings_file, strlen( $settings_file ) );



			//------------------------------------------
			// Create the User Interface of installation
			//------------------------------------------
			$search = array( '{$title}','{$description}','{$author}','{$size}','{$compressed_size}','{$nfile}','{$ndir}');
			$replace = array( $this->title, $this->description, $this->author, byte($this->size), byte($this->compressed_size), count($this->file), count($this->dir) );
			$installation_ui = str_replace( $search, $replace, file_get_contents( "phpinstaller/tpl/installation_ui.html") );			
				
			
			
			//------------------------------------------
			// Close file and write installer and footer
			//------------------------------------------			
			$style = "\n" . "?>" . "\n" . "<style>" . "\n" . file_get_contents( "phpinstaller/tpl/style.css" ) . "\n" . "</style>" . "\n";
			
			$installer_code = file_get_contents( "phpinstaller/installer.php" );
			$installer_code = str_replace( "#_INSTALLATION_UI_#", $installation_ui, $installer_code );

			$footer = $style . $installer_code;

			fwrite( $fp, $footer, strlen( $footer ) );
			fclose( $fp );
			
			echo " <br><hr><h1>Installation file ready: $install</h1>Copy and exec the file to install the package.";
			myFlush();

			
			return $install;

		}

	}
	
?>