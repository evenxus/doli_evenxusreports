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
 * Contains interface to data retrieval functionality
 * that is responsible for fetching data from databases
 * during report execution
 * 
 * Your database must be supported by the ADODB database
 * abstraction classes provided along with Reportico. Currently
 * the only databases to be tested are MySQL and Informix
 * 
 *
 * @link http://www.reportico.org/
 * @copyright 2010-2011 Peter Deed
 * @author Peter Deed <info@reportico.org>
 * @package Reportico
 * @version $Id: swdb.php,v 1.4 2011-09-22 21:57:29 peter Exp $
 */


// Include the ADODB Database Abstraction Classes
include_once('adodb/adodb.inc.php');

/**
 * Class reportico_datasource
 *
 * Core interface for database retrieval
 */
class reportico_datasource extends reportico_object
{

	var	$driver = "mysql";
	var	$host_name;
	var	$service_name;
	var	$user_name = false;
	var	$password = "";
	var	$database;
	var	$server;
	var	$protocol;
	var	$connection;
	var	$connected = false;
	var	$ado_connection;

	var $_conn_host_name = SW_DB_HOST;
	var $_conn_user_name = SW_DB_USER;
	var $_conn_password = SW_DB_PASSWORD;
	var $_conn_driver = SW_DB_DRIVER;
	var $_conn_database = SW_DB_DATABASE;
	var $_conn_server = SW_DB_SERVER;
	var $_conn_protocol = SW_DB_PROTOCOL;
	
	function reportico_datasource($driver = "mysql", $host_name = "localhost", 
						$service_name = "?Unknown?", $server = false, $protocol = false )
	{
		reportico_object::reportico_object();

		$this->driver = $driver;
		$this->host_name = $host_name;
		$this->service_name = $service_name;
		$this->protocol = $protocol;
		$this->server = $server;
	}

	function set_details($driver = "mysql", $host_name = "localhost", 
						$service_name = "?Unknown?",
						$server = false, $protocol = false )
	{
		$this->driver = $driver;
		$this->host_name = $host_name;
		$this->service_name = $service_name;
		$this->protocol = $protocol;
		$this->server = $server;
	}

	function set_database($database)
	{
		$this->database = $database;
	}

	function map_column_type($driver, $type)
	{
		$ret = $type;
		switch ( $driver )
		{
			case "informix":
				switch ( (int)$type )
				{
					case 2:
					case 258:
					case 262:
						$ret = "integer";
						break;

					case 1:
						$ret = "interval hour to second";
						break;

					case 10:
						$ret = "datetime year to second";
						break;

					case 14:
						$ret = "interval hour to second";
						break;

					case 5:
						$ret = "decimal(16)";
						break;

					case 256:
					case 0:
						$ret = "char";
						break;

					case 1:
					case 257:
						$ret = "smallint";
						break;

					default:
						break;
				}
				break;

			default:
				$retype = $type;
				break;
		}
		return $ret;
	}

	function connect($ignore_config = false)
	{
		$connected = false;

		if ( $this->connected ) 
		{
			$this->disconnect();
		}

		if ( $ignore_config )
		{
			$this->_conn_driver = $this->driver;
			$this->_conn_user_name = $this->user_name;
			$this->_conn_password = $this->password;
			$this->_conn_host_name = $this->host_name;
			$this->_conn_database = $this->database;
			$this->_conn_server = $this->server;
			$this->_conn_protocol = $this->protocol;
		}
		else if ( SW_DB_CONNECT_FROM_CONFIG )
		{
			$this->_conn_driver = SW_DB_DRIVER;
			if ( !$this->_conn_user_name ) 
				$this->_conn_user_name = $this->user_name;
			$this->_conn_password = SW_DB_PASSWORD;
			if ( !$this->_conn_password ) 
				$this->_conn_password = $this->password;
			$this->_conn_host_name = SW_DB_HOST;
			$this->_conn_database = SW_DB_DATABASE;
			$this->_conn_server = SW_DB_SERVER;
			$this->_conn_protocol = SW_DB_PROTOCOL;
		}
		else
		{
			$this->_conn_driver = $this->driver;
			$this->_conn_driver = SW_DB_DRIVER;
			$this->_conn_user_name = $this->user_name;
			$this->_conn_password = $this->password;
			$this->_conn_host_name = $this->host_name;
			$this->_conn_database = SW_DB_DATABASE;
			$this->_conn_server = SW_DB_SERVER;
			$this->_conn_protocol = SW_DB_PROTOCOL;
		}

		if ( $this->_conn_driver == "none" )
		{
			$connected = true;
		}

		switch ( $this->_conn_driver )
		{
			case "none":
				$connected = true;
				break;

			case "array":
				$this->ado_connection = new reportico_db_array();
				$this->ado_connection->Connect($this->_conn_database);
				$connected = true;
				break;

			case "mysql":
				$this->ado_connection = NewADOConnection($this->_conn_driver);
				$this->ado_connection->SetFetchMode(ADODB_FETCH_ASSOC);
				$connected = $this->ado_connection->Connect($this->_conn_host_name,
					$this->_conn_user_name,$this->_conn_password,$this->_conn_database);
				break;

			case "informix":
				$this->ado_connection = NewADOConnection($this->_conn_driver);
				$this->ado_connection->SetFetchMode(ADODB_FETCH_ASSOC);
				if ( function_exists("ifx_connect") )
					$connected = $this->ado_connection->Connect($this->_conn_host_name,
						$this->_conn_user_name,$this->_conn_password,$this->_conn_database);
				else
					handle_error( "Attempt to connect to Informix Database Failed. Informix PHP Driver is not Available");
				break;

			case "pdo_mssql":
				if ( class_exists('PDO') )
				{
					$this->ado_connection = NewADOConnection("pdo");
					$cnstr =
						"dblib:".
						"host=".$this->_conn_host_name."; ".
						"username=".$this->_conn_user_name."; ".
						"password=".$this->_conn_password."; ".
						"dbname=".$this->_conn_database;
					$connected = $this->ado_connection->Connect($cnstr,$this->_conn_user_name,$this->_conn_password);
				}
				else
					handle_error( "Attempt to connect to SQL Server Database Failed. PDO Driver is not Available");
				break;

			case "pdo_mysql":
				if ( class_exists('PDO') )
				{
					$this->ado_connection = NewADOConnection("pdo");
					$cnstr =
						"mysql:".
						"host=".$this->_conn_host_name."; ".
						"username=".$this->_conn_user_name."; ".
						"password=".$this->_conn_password."; ".
						"dbname=".$this->_conn_database;
					$connected = $this->ado_connection->Connect($cnstr,$this->_conn_user_name,$this->_conn_password);
				}
				else
					handle_error( "Attempt to connect to Informix Database Failed. PDO Driver is not Available");
				break;

			case "pdo_sqlite3":
				if ( class_exists('PDO') )
				{
					$this->ado_connection = NewADOConnection("pdo");
					$cnstr = "sqlite:" . $this->_conn_database;
					$connected = $this->ado_connection->Connect($cnstr,'','');
				}
				else
					handle_error( "Attempt to connect to SQLite-3 Database Failed. PDO Driver is not Available");
				break;

			case "sqlite":
				$driver =   'sqlite' ;
				$database = $this->_conn_host_name . $this->_conn_database;
				$query =    'select * from Chave' ;
				$db = ADONewConnection($driver);
				if ($db && $db->PConnect($database, "", "", ""))
				{
				}
				else
				{
					die( "* CONNECT TO SQLite-2 FAILED" ) ;
				}
				break;

			case "pdo_informix":
				if ( class_exists('PDO') )
				{
					$this->ado_connection = NewADOConnection("pdo");
					$cnstr =
						"informix:".
						"host=".$this->_conn_host_name."; ".
						"server=".$this->_conn_server."; ".
						"protocol=".$this->_conn_protocol."; ".
						"username=".$this->_conn_user_name."; ".
						"password=".$this->_conn_password."; ".
						"database=".$this->_conn_database;
					$connected = $this->ado_connection->Connect($cnstr,$this->_conn_user_name,$this->_conn_password);
				}
				else
					handle_error( "Attempt to connect to Informix Database Failed. PDO Driver is not Available");
				break;

			case "odbc":
				$this->ado_connection = NewADOConnection($this->_conn_driver);
				$this->ado_connection->SetFetchMode(ADODB_FETCH_ASSOC);
				$connected = $this->ado_connection->Connect($this->_conn_host_name,
						$this->_conn_user_name, $this->_conn_password);
				break;

			case "unknown":
				handle_error( "Database driver of unknown specified - please configure your project database connectivity");
				break;
			default:
				$this->ado_connection = NewADOConnection($this->_conn_driver);
				$this->ado_connection->SetFetchMode(ADODB_FETCH_ASSOC);
				$connected = $this->ado_connection->Connect($this->_conn_host_name,
					$this->_conn_user_name,$this->_conn_password,$this->_conn_database);
		}

		// Note force connected for SQLite3
		if ( $this->_conn_driver == "sqlite" )
		{
			$connected = true ;
		}
		else
		{
			if ( !$connected && $this->_conn_driver != "unknown" )
				handle_error( "Error in Connection: ".$this->ado_connection->ErrorMsg());
		}

		$this->connected = $connected;
		return $this->connected;
	}

	function disconnect()
	{
		if ( $this->connected && $this->_conn_driver != "none" ) 
			$this->ado_connection->Close();
		$this->connected = false;
	}
}

/**
 * Class reportico_db_array
 *
 * Allows an array of data to appear like a database table by
 * implementing the necessary functions for connecting, disconnecting
 * and fetching. This means the Reportico engine will not care if data comes
 * from a database or an array
 */
class reportico_db_array
{
	var $array_set;
	var $EOF = false;
	var $ct = 0;
	var $numrows = 0;

	function reportico_db_array()
	{
	}

	function Connect(&$in_array)
	{
		$this->array_set =& $in_array;
		reset($this->array_set);
		$k = key($this->array_set);
		$this->numrows = count($this->array_set[$k]);
	}

	function FetchRow()
	{
		$rs = array();

		reset($this->array_set);
		while ( $d =& key($this->array_set) )
		{
			$rs[$d] = $this->array_set[$d][$this->ct];
			next($this->array_set);
		}
		$this->ct++;

		if ( $this->ct == $this->numrows )
		{
			$this->EOF = true;
		}

		return($rs);
	}

	function & ErrorMsg()
	{
		return "Array dummy Message";
	}

	function Close()
	{
		return ;
	}

	function & Execute($in_query)
	{
		return($this);
	}


}

?>
