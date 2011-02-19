<?php

		$GLOBALS['HTTP_VARS'] = $_GET + $_POST;
		function get($key=null){
			if( !$key )
				return $GLOBALS['HTTP_VARS'];
			if( isset( $GLOBALS['HTTP_VARS'][$key] ) )
				return $GLOBALS['HTTP_VARS'][$key];
		}
		function fileExt( $file, $toLower = true ){
			if( $dot_position = strrpos($file,".") )
				return ( ( $ext = substr( $file, $dot_position + 1, ( strlen( $file ) - $dot_position ) ) ) && $toLower ) ? strtolower($ext) : $ext;
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
		    ob_end_flush();
		    if( ob_get_flush() )
		    	ob_flush();
		    flush();
		    ob_start(); 
		}

	
	
?>