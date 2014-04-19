<?php
/*
 Reportico - PHP Reporting Tool
 Copyright (C) 2010-2011 Peter Deed

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.
 
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

 * File:        reportico.php
 *
 * Contains utility functions required during Reportico operation
 *
 * @link http://www.reportico.org/
 * @copyright 2010-2011 Peter Deed
 * @author Peter Deed <info@reportico.org>
 * @package Reportico
 * @version $Id: swutil.php,v 1.11 2011-09-22 21:57:30 peter Exp $
 */

global $g_error_status;

// System Error Handling and Debug Tracking Variables
$g_system_errors = array();
$g_code_area = "";
$g_system_debug = array();
$g_debug_mode = false;
$g_error_status = false;

// Debug Levels
define('SW_DEBUG_NONE', 0);
define('SW_DEBUG_LOW', 1);
define('SW_DEBUG_MEDIUM', 2);
define('SW_DEBUG_HIGH', 3);
define('SW_DEFAULT_IND', '.');

// Ensure that sessions from different browser windows on same devide
// target separate SESSION_ID
function set_up_session()
{
	
	global $sessionname, $session_name;

	$session_name = $sessionname;

	// Check for Posted Session Name
	if (isset($_REQUEST['session_name'])) 
    		$session_name = $_REQUEST['session_name'];
	
	if ( !$session_name )
	{
		session_start();
    	session_regenerate_id(false);
		$session_name = session_id();
		//$_SESSION = array();
	}
	else
	{
		session_id($session_name);
		@session_start();
	}
}

function convertYMDtoLocal($in_time, $from_format, $to_format)
{
	$from_format = get_locale_date_format ( $from_format );
	$to_format = get_locale_date_format ( $to_format );

	$datetime = DateTime::createFromFormat($from_format, $in_time);
	$retval =$datetime->format ( $to_format );
	return $retval;
	
	$outstr="";

	$yr = substr($in_time, 0, 4);
	$mn = substr($in_time, 5, 2);
	$dy = substr($in_time, 8, 2);
    	if ( $from_format == "d-m-Y" )
    	{
        	$yr = substr($in_time, 6, 4);
        	$mn = substr($in_time, 3, 2);
        	$dy = substr($in_time, 0, 2);
    	}
	$retval = sprintf("%02d/%02d/%04d", $dy, $mn, $yr);
	
	$retval = false;
	if ( $mn )
	{
		$retval = strftime ($to_format, mktime (0,0,0,$mn,$dy,$yr));
	}
	return $retval;
}

function parse_date($in_keyword, $in_time = false, $in_mask = "%d/%m/%Y" )
{

	$in_mask = get_locale_date_format ( $in_mask );
	if ( !$in_time )
	{
		$in_time = time();
	}
	$now = localtime($in_time, true);

	// Begin calculating the required data/time value
	switch ( $in_keyword )
	{

		case "FIRSTOFLASTMONTH":
			$now["tm_mday"] = 1;
			$now["tm_mon"]--;
			if ( $now["tm_mon"] < 0 )
			{
				$now["tm_year"]--;
				$now["tm_mon"]=11;
			}
			break;

		case "FIRSTOFYEAR":
			$now["tm_mday"] = 1;
			$now["tm_mon"] = 0;
			break;

		case "FIRSTOFLASTYEAR":
			$now["tm_mday"] = 1;
			$now["tm_mon"] = 0;
			$now["tm_year"]--;
			break;

		case "LASTOFYEAR":
			$now["tm_mday"] = 1;
			$now["tm_mon"] = 0;
			break;

		case "LASTOFLASTYEAR":
			$now["tm_mday"] = 31;
			$now["tm_mon"] = 11;
			$now["tm_year"]--;
			break;

		case "LASTOFLASTMONTH":
		case "FIRSTOFMONTH":
			$now["tm_mday"] = 1;
			break;

		case "LASTOFMONTH":
			$now["tm_mday"]= 1;
			$now["tm_mon"]++;
			if ( $now["tm_mon"] == 12 )
			{
				$now["tm_year"]++;
				$now["tm_mon"]=0;
			}
			break;

		case "YESTERDAY":
		case "TOMORROW":
		case "TODAY":
			break;

		default:
			return $in_keyword;
	}

	if ( $now["tm_year"] < 1000 )
		$now["tm_year"] += 1900;

	// Convert the modified date time values back to to UNIX time
	$new_time = mktime($now["tm_hour"], $now["tm_min"],
					   $now["tm_sec"], $now["tm_mon"] + 1,
					   $now["tm_mday"], $now["tm_year"]);
					   //$now["tm_isdst"] );



	// Apply any element transformations to get the reuqired UNIX date
	switch ( $in_keyword )
	{
		case "YESTERDAY":
			$new_time -= 60 * 60 * 24;
			break;

		case "TOMORROW":
			$new_time += 60 * 60 * 24;
			break;

		case "LASTOFLASTMONTH":
		case "LASTOFMONTH":
			$new_time -= 60 * 60 * 24;
			break;

		case "FIRSTOFMONTH":
		default:
			break;

	}

	// Format the date into the require string format suitable for return
	$datetime = new DateTime();
	$datetime->setTimestamp ( $new_time );
	$ret =$datetime->format ( $in_mask );

	//$ret = strftime($in_mask, $new_time);
	return($ret);

	
}

function get_query_column_value( $name, &$arr )
{
	$ret = "NONE";
	foreach($arr as $val)
	{
		if ( $val->query_name == $name )
		{	
			return $val->column_value;
		}
	}
	
	//foreach($arr as $val)
	//{
		//return $val->column_value;
	//}
	//return $name;
}
	
function get_query_column( $name, &$arr )
{
	foreach($arr as $k => $val)
	{
		if ( $val->query_name == $name )
			return $arr[$k];
	}
	return false;
}

function get_group_column( $name, &$arr )
{
	foreach($arr as $k => $val)
	{
		if ( $val->group_name == $name )
			return $arr[$k];
	}
	return false;
}
	
function &get_db_image_string(
	$in_driver, 
	$in_dbname, 
	$in_hostname,
	$in_sql,
	$in_conn = false
	)
{

	$rs = false;
	if ( !$in_conn )
	{
  		$hostname = $in_hostname;
  		$dbname = $in_dbname;
  		$driver = $in_driver;

		$ado_connection = NewADOConnection($driver);
		$ado_connection->SetFetchMode(ADODB_FETCH_ASSOC);
		$ado_connection->PConnect($hostname,'','',$dbname);

		$rs = $ado_connection->Execute($in_sql) 
			or die("Query failed : " . $ado_connection->ErrorMsg());
	}
	else
	{
		$rs = $in_conn->Execute($in_sql) 
			or die("Query failed : " . $in_conn->ErrorMsg());
	}

	$line = $rs->FetchRow();

	if ( $line )
   		foreach ( $line as $col )
   		{
   			$data = $col;
   			break;
   		}
	else
		$data = false;

	return $data;
	$rs->Close();
}

function key_value_in_array($in_arr, $in_key)
{
	if ( array_key_exists($in_key, $in_arr) )
		$ret =  $in_arr[$in_key];
	else
		$ret =  false;

	return ( $ret );
}

function get_request_item($in_val, $in_default = false, $in_default_condition = true)
{
	if ( array_key_exists($in_val, $_REQUEST) )
		$ret =  $_REQUEST[$in_val];
	else
		$ret =  false;

	if ( $in_default && $in_default_condition && !$ret )
		$ret = $in_default;

	return ( $ret );
}

function session_request_item($in_item, $in_default = false, $in_default_condition = true)
{
	$ret = false;
	if ( array_key_exists($in_item, $_SESSION) )
		$ret = $_SESSION[$in_item];

	if ( array_key_exists($in_item, $_REQUEST) )
		$ret = $_REQUEST[$in_item];

	if ( !$ret )
		$ret = false;
	
	if ( $in_default && $in_default_condition && !$ret )
		$ret = $in_default;

	$_SESSION[$in_item] = $ret;

	return ( $ret );
}

function get_checkbox_value($in_tag)
{
	if ( array_key_exists($in_tag, $_REQUEST) )
		return true;
	else
		return false;
}

function hhmmss_to_seconds($in_hhmmss)
{
	$ar = explode(":", $in_hhmmss);

	if ( count($ar) != 3 )
		return(0);

	if ( preg_match( "/ /", $in_hhmmss ) )
			return(0);


	$secs = (int)$ar[0] * 3600;
	$secs += (int)$ar[1] * 60;
	$secs += (int)$ar[2];
	$first = substr($in_hhmmss, 0, 1);
	if ( $first == "-" )
		$secs = -$secs;

	return($secs);
}

// Debug Message Handler
function handle_debug($dbgstr, $in_level)
{
  	global $g_system_debug;
  	global $g_code_area;
  	global $g_debug_mode;

	//if ( $g_debug_mode )
	//{
		if ( $g_debug_mode >= $in_level )
		{
  			$g_system_debug[] = array (
			"dbgstr" => $dbgstr,
			"dbgarea" => $g_code_area
			);
		}
	//}

}
  
// User Error Handler
function handle_error($errstr, $type = E_USER_ERROR)
{
  	global $g_system_debug;
  	global $g_code_area;
  	global $g_errors;

	$g_errors = true;

	trigger_error($errstr, $type);
}
  
  
// error handler function
function ErrorHandler($errno, $errstr, $errfile, $errline)
{
global $g_system_errors;
  	global $g_error_status;
  	global $g_code_area;
  	global $g_code_source;

	switch ( $errno )
	{
		case E_ERROR:
			$errtype = sw_translate("Error");
			break;
		case E_NOTICE:
			$errtype = sw_translate("Notice");
			break;
		case E_USER_ERROR:
			$errtype = sw_translate("Error");
			break;
		case E_USER_WARNING:
			$errtype = sw_translate("");
			break;
		case E_USER_NOTICE:
			$errtype = sw_translate("");
			break;
		case E_WARNING:
			$errtype = sw_translate("");
			break;

		default :
			$errtype = sw_translate("Fatal Error");

	}

	// Avoid adding duplicate errors 
	if ( !$g_system_errors )
		$g_system_errors = array();
	foreach ( $g_system_errors as $k => $val )
	{
		if ( $val["errstr"] == $errstr )
		{
			$g_system_errors[$k]["errct"]++;
			return;
		}
	}

	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
	
	} else {
		$g_system_errors[] = array (
		"errno" => $errno,
		"errstr" => $errstr,
		"errfile" => $errfile,
		"errline" => $errline,
		"errtype" => $errtype,
		"errarea" => $g_code_area,
		"errsource" => $g_code_source,
		"errct" => 1
		);
	}

    $g_error_status = 1;

}
  
// error handler function
function has_default($in_code)
{
	if ( substr($in_code, 0, 1) == SW_DEFAULT_IND )
	{
		return true;
	}
	return false;
}

function get_default($in_code)
{
	$out_val = false;
	if ( defined("SW_DEFAULT_".$in_code) )
	{
		$out_val = constant("SW_DEFAULT_".$in_code);
	}
	return $out_val;
}

// error handler function
function check_for_default($in_code, $in_val)
{
	$out_val = $in_val;

	if ( !$in_val )
	{
		$out_val = $in_val;
		if ( defined("SW_DEFAULT_".$in_code) )
		{
			$out_val = constant("SW_DEFAULT_".$in_code);
		}
	}
	else
	if ( substr($in_val, 0, 1) == SW_DEFAULT_IND )
	{
		$out_val = substr($in_val, 1);
		if ( defined("SW_DEFAULT_".$in_code) )
		{
			$out_val = constant("SW_DEFAULT_".$in_code);
		}
	}
	return $out_val;
}

// Look for a file in the include path, or the path of the current source file
function find_file_to_include($file_path, &$new_file_path, &$rel_to_include = "")
{
	static $_path_array = null;
	if(!isset($_path_array)) 
	{
		$_ini_include_path = get_include_path();

		if ( defined ( "__DIR __" ) )
			$selfdir = __DIR__;
		else
			$selfdir = dirname(__FILE__);

		if(strstr($_ini_include_path,';')) 
		{
			$_ini_include_path = $selfdir.";".$_ini_include_path;
			$_path_array = explode(';',$_ini_include_path);
		} 
		else 
		{
			$_ini_include_path = $selfdir.":".$_ini_include_path;
			$_path_array = explode(':',$_ini_include_path);
		}
	}
        foreach ($_path_array as $_include_path) {
            if (file_exists($_include_path . "/" . $file_path)) 
	    {
               	$new_file_path = $_include_path . "/" . $file_path;
				return true;
            }
        }
	$new_file_path = $file_path;
	return false;
}

// Translate string into another language using the g_translations global array
function &sw_translate($in_string)
{
	global $g_language;
	global $g_translations;

	$out_string =& $in_string;

	if ( $g_translations )
		if ( array_key_exists( $g_language, $g_translations ) )
		{
			$langset =& $g_translations[$g_language];
			if ( isset ( $langset[$in_string] ) )
				$out_string =& $langset[$in_string];
		}
	return  $out_string;
}

// Translate string into another language using the g_translations global array
function &sw_translate_report_desc($in_report)
{
	global $g_language;
	global $g_report_desc;

	$out_string = false;
	if ( $g_report_desc )
		if ( array_key_exists( $g_language, $g_report_desc ) )
		{
			$langset =& $g_report_desc[$g_language];
			if ( isset ( $langset[$in_report] ) )
				$out_string =& $langset[$in_report];
		}
	return  $out_string;
}

// Is path executable and writeable?
function sw_path_executable($in_path)
{
	global $g_language;
	global $g_report_desc;

	$perms = fileperms($in_path);
	
	if ( !is_dir ( $in_path ) )
		return false;

	if (!strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && is_executable ( $in_path ) )
		return false;

	if ( !is_writeable ( $in_path ) )
		return false;

	return  true;
}

// Search currentl directory and include for best absolute poistion 
// of a file path
function find_best_location_in_include_path( $path ) 
{
	$newpath = $path;
	$reltoinclude;
	if ( !is_file ( $newpath ) )
	{
		find_file_to_include($newpath, $newpath, $reltoinclude);
		$newpath = get_relative_path(str_replace ("/", "\\", realpath($newpath)), dirname($_SERVER["SCRIPT_FILENAME"]));
	}
	return $newpath;
}

// Builds the base URL elements to HTML links produced in HTML 
// created from :-
//    1 - the http://.....
//    2 - extra GET parameters
function build_forward_url_get_params($path, $forward_url_get_params, $remainder)
{
	$urlpath = find_best_location_in_include_path($path);

	if ( $forward_url_get_params || $remainder )
		$urlpath .= "?";

		
	if ( $forward_url_get_params )
		$urlpath .= $forward_url_get_params;
		if ( $remainder )
			$urlpath .= "&";
	if ( $remainder ) 
		$urlpath .= $remainder;

	return $urlpath;
}

// For backward compatibility ensures that date formats anything expressed in 
// formats sutiable for the date function ( e.g. Y-m-d ) are converted to 
// locale formats ( e.g. %Y-%m-%d )
function get_locale_date_format( $in_format ) {

	$out_format = $in_format;
	if ( $in_format  == "%d/%m/%Y" ) $out_format = "d-m-Y";
	if ( $in_format  == "%Y/%m/%d" ) $out_format = "Y-m-d";
	if ( $in_format  == "%m/%Y/%d" ) $out_format = "m-Y-d";
	if ( $in_format  == "%d-%m-%Y" ) $out_format = "d-m-Y";
	if ( $in_format  == "%Y-%m-%d" ) $out_format = "Y-m-d";
	if ( $in_format  == "%m-%Y-%d" ) $out_format = "m-Y-d";
	if ( !$in_format )
		$in_format = "d-m-Y";
	return ( $out_format );
}


// Converts absolute path to relative path
function get_relative_path( $path, $compareTo ) {

        // Convert Windows paths with "\" delimiters to forward delimiters
        $path = preg_replace ("+\\\+", "/", $path );

        // clean arguments by removing trailing and prefixing slashes
        if ( substr( $path, -1 ) == '/' ) {
            $path = substr( $path, 0, -1 );
        }
        if ( substr( $path, 0, 1 ) == '/' ) {
            $path = substr( $path, 1 );
        }

        if ( substr( $compareTo, -1 ) == '/' ) {
            $compareTo = substr( $compareTo, 0, -1 );
        }
        if ( substr( $compareTo, 0, 1 ) == '/' ) {
            $compareTo = substr( $compareTo, 1 );
        }

        // simple case: $compareTo is in $path
        if ( strpos( $path, $compareTo ) === 0 ) {
            $offset = strlen( $compareTo ) + 1;
            return substr( $path, $offset );
        }

        $relative  = array(  );
        $pathParts = explode( '/', $path );
        $compareToParts = explode( '/', $compareTo );

        foreach( $compareToParts as $index => $part ) {
            if ( isset( $pathParts[$index] ) && $pathParts[$index] == $part ) {
                continue;
            }

            $relative[] = '..';
        }

        foreach( $pathParts as $index => $part ) {
            if ( isset( $compareToParts[$index] ) && $compareToParts[$index] == $part ) {
                continue;
            }

            $relative[] = $part;
        }

        return implode( '/', $relative );
}
