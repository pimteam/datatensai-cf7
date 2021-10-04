<?php
// functions for CSV exports 
class DataTensaiCSV {
	public static function define_newline() {
		// credit to http://yoast.com/wordpress/users-to-csv/
		$unewline = "\r\n";
		if (strstr(strtolower($_SERVER["HTTP_USER_AGENT"]), 'win')) {
		   $unewline = "\r\n";
		} else if (strstr(strtolower($_SERVER["HTTP_USER_AGENT"]), 'mac')) {
		   $unewline = "\r";
		} else {
		   $unewline = "\n";
		}
		return $unewline;
	} // end define_newline

	public static function get_mime_type()  {
		// credit to http://yoast.com/wordpress/users-to-csv/
		$USER_BROWSER_AGENT="";
	
				if (preg_match('/OPERA(\/| )([0-9].[0-9]{1,2})/', strtoupper($_SERVER["HTTP_USER_AGENT"]), $log_version)) {
					$USER_BROWSER_AGENT='OPERA';
				} else if (preg_match('/MSIE ([0-9].[0-9]{1,2})/',strtoupper($_SERVER["HTTP_USER_AGENT"]), $log_version)) {
					$USER_BROWSER_AGENT='IE';
				} else if (preg_match('/OMNIWEB\/([0-9].[0-9]{1,2})/', strtoupper($_SERVER["HTTP_USER_AGENT"]), $log_version)) {
					$USER_BROWSER_AGENT='OMNIWEB';
				} else if (preg_match('/MOZILLA\/([0-9].[0-9]{1,2})/', strtoupper($_SERVER["HTTP_USER_AGENT"]), $log_version)) {
					$USER_BROWSER_AGENT='MOZILLA';
				} else if (preg_match('/KONQUEROR\/([0-9].[0-9]{1,2})/', strtoupper($_SERVER["HTTP_USER_AGENT"]), $log_version)) {
			    	$USER_BROWSER_AGENT='KONQUEROR';
				} else {
			    	$USER_BROWSER_AGENT='OTHER';
				}
	
		$mime_type = ($USER_BROWSER_AGENT == 'IE' || $USER_BROWSER_AGENT == 'OPERA')
					? 'application/octetstream'
					: 'application/octet-stream';
		return $mime_type;
	} // end get_mime_type
}