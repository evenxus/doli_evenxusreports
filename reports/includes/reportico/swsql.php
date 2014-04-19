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
 * Contains functionality for parsing SQL statements and
 * converting them to queries that can be used by the
 * Reportico engine
 *
 * @link http://www.reportico.org/
 * @copyright 2010-2011 Peter Deed
 * @author Peter Deed <info@reportico.org>
 * @package Reportico
 * @version $Id: swsql.php,v 1.4 2011-09-22 21:57:30 peter Exp $
 */


/**
 * Class reportico_sql_parser
 *
 * Parses SQL statements entered by user during
 * report design mode and imports them into
 * the Reportico engine
 */
class reportico_sql_parser
{

	var $sql;
	var $columns = array();
	var $tables = array();
	var $table_text;
	var $where = "";
	var $group = "";
	var $orders = array();
	var $status_message = "";
	var $unique = false;

	function reportico_sql_parser( $in_sql )
	{
		$this->sql = $in_sql;
	}

	function import_into_query( &$in_query )
	{

		//When importing into query, we need to ensure that we remove
		// any columns already existing which do not appear in the
		// new query
		$delete_columns = array();
		foreach ( $in_query->columns as $k => $v )
		{
			if ( $v->in_select )
			{
				$delete_columns[$v->query_name] = true;
			}
		}

		foreach ( $this->columns as $col )
		{
			$qn = $col["name"];
			if ( $col["alias"] )
				$qn = $col["alias"];
		        
			$in_query->create_criteria_column(
					$qn, $col["table"], $col["name"], "char", 30, "####", true);

			if ( array_key_exists($qn, $delete_columns ) )
			{
				$delete_columns[$qn] = false;
			}
		}


		$ct = 0;
		$tabtext = "";
		foreach ( $this->tables as $col )
		{
				if ( $ct++ > 0 )
					$tabtext .= ",";

				switch ( $col["jointype"] )
				{
					case "outer":
						$tabtext .= "outer ";
						break;

					case "inner":
					case "default":
				}

				$tabtext .= $col["name"];
				if ( $col["alias"] )
					$tabtext .= " ".$col["alias"];
		}

		$in_query->table_text = $tabtext;
		$in_query->table_text = $this->table_text;

		$in_query->where_text = "AND ".$this->where;
		if ( substr($in_query->where_text, 0, 9) == "AND 1 = 1" ) 
		{
			$in_query->where_text = substr($in_query->where_text, 9);
		}

		if ( $this->group )
			$in_query->group_text = "GROUP BY ".$this->group;
		else
			$in_query->group_text = "";

		// Delete existing order columns
		$in_query->order_set = array();
		foreach ( $this->orders as $col )
		{
				if ( ($qc = get_query_column($col["name"], $in_query->columns)) )
					$in_query->create_order_column( $col["name"], $col["type"] );
		}

		// Now remove from the parent query any columns which were not in the
		// imported SQL
		foreach ( $delete_columns as $k => $v )
		{
			if ( $v )
			{
				$in_query->remove_column($k);
			}
		}

		// Now order the query columns in the reportico query to reflect the order specified in 
		// the select statement
		$pos = 0;
		$xx = false;
		foreach ( $this->columns as $col )
		{
			$pos2 = 0;
			$cut = false;
			foreach ( $in_query->columns as $k => $v )
			{
				if ( $v->query_name == $col["alias"] )
				{
					$cut = array_splice($in_query->columns, $pos2, 1 );
					break;
				}
				$pos2++;
				
			}

			if ( $cut )
			{
				array_splice($in_query->columns, $pos, 0,
								$cut );
			}

			$pos++;

		}

		$in_query->rowselection = "all";		
		if ( $this->unique )
			$in_query->rowselection = "unique";		


	}

	function  display()
	{
		echo "Columns<br>\n=======<br>\n";
		foreach ( $this->columns as $col )
		{
				echo $col["table"].".".$col["name"];
				echo " (".$col["alias"].")";
				echo " => ",$col["expression"];
				echo "<br>\n";
		}
		echo "<br>\nTables<br>\n======<br>\n";
		foreach ( $this->tables as $col )
		{
				echo $col["name"];
				echo " (".$col["alias"].")";
				echo " - ".$col["jointype"];
				echo "<br>\n";
		}

		echo "<br>\nWhere<br>\n=====<br>\n";
		echo $this->where;
		echo "<br>\n";

		echo "<br>\nOrder<br>\n=====<br>\n";
		foreach ( $this->orders as $col )
		{
				echo $col["name"]." ";
				echo $col["type"];
				echo "<br>\n";
		}

	}

	function  parse()
	{
		$err = false;
	
		$sel_match = "/^\s*SELECT\s*(.*)/is";
		$seldup_match = "/^\s*SELECT\s*DISTINCT\s*(.*)/is";
		$seldup1_match = "/^\s*SELECT\s*UNIQUE\s*(.*)/is";
		$upd_match = "/^\s*UPDATE\s*(.*)/is";
		$del_match = "/^\s*DELETE\s*(.*)/is";
		$ord = "";

		$sql =& $this->sql;

		$this->unique = false;
		if ( preg_match($sel_match, $sql, $cpt ) )
		{
			$sel_type = "SELECT";

			if ( preg_match($seldup_match, $sql, $cpt ) )
			{
				$this->unique = true;
			}
			else if ( preg_match($seldup1_match, $sql, $cpt ) )
			{
				$this->unique = true;
			}
			else
			{
				preg_match($sel_match, $sql, $cpt );
			}

			// Knock out distinct records ...

			$col_match = "/(.*)\s+FROM\s+(.*?WHERE.*)/is";
			$tab_match = "/(.*?)\s+WHERE\s+(.*)/is";
			$whr_match = "/(.*?)\s+ORDER\s+BY\s+(.*)/is";
			$grp_match = "/(.*?)\s+GROUP\s+BY\s+(.*)/is";

			$rest = $cpt[1];
			if ( !preg_match($col_match, $rest, $cpt ) )
			{
				$err = "Failure in FROM Decode";
				trigger_error("No FROM clause specified.", E_USER_ERROR);
			}
			else
			{
				$col = $cpt[1];
				$rest = $cpt[2];
			}

			if ( !$err && !preg_match($tab_match, $rest, $cpt ) )
			{
				$err = "Failure in TABLE Decode";
				trigger_error("No WHERE clause specified. If no clause is required use WHERE 1 = 1", E_USER_ERROR);
			}
			else
			{
				$tables = $cpt[1];
				$rest = $cpt[2];
			}

			if ( !$err )
			{
				if ( !preg_match($whr_match, $rest, $cpt ) )
				{
					$this->where = $rest;
					$rest = "";
				}
				else
				{
					$this->where = $cpt[1];
					$rest = $cpt[2];
				}
			}


			if ( !$err && $rest )
			{
				$ord = $rest;
			}

			if ( !$err && $this->where )
			{
				if ( preg_match($grp_match, $this->where, $cpt ) )
				{
					$this->where = $cpt[1];
					$this->group = $cpt[2];
				}
			}


			if ( $err )
				return false;


			if ( $col )
			{
				$this->parse_column_list($col);
			}

			if ( $tables )
			{
				$this->table_text = $tables;
				$this->parse_table ( $tables );
			}

			if ( $this->where )
			{
				//echo "Where: $this->where\n";
			}

			if ( $this->group )
			{
				//echo "Group: $this->group\n";
			}

			if ( $ord )
			{
				$this->parse_order ( $ord );
			}

		}
		else
			trigger_error("no SELECT clause specified. Query must begin with 'SELECT'", E_USER_ERROR);

		if ( preg_match($upd_match, $sql, $cpt ) )
		{
			print_r($cpt);
			$sel_type = "UPDATE";
		}

		if ( preg_match($del_match, $sql, $cpt ) )
		{
			print_r($cpt);
			$sel_type = "DELETE";
		}

	}

	// -----------------------------------------------------------------------------
	// Function : parse_order
	// -----------------------
	// Will take an order list from a select statement and generate the
	// order items
	// ----------------------------------------------------------------------------
	function parse_order( $in_string )
	{
		$err = false;

		$ordname = "";

		if ( ! $collist = preg_split("/\s*,\s*/", $in_string) )
		{
			$err = "Decode Table List Failure";
			return false;
		}

		foreach ( $collist as $colitem )
		{
			if ( ! $colset = preg_split("/\s+/", $colitem) )
			{
				$err = "Decode Column Set List Failure";
				return false;
			}

			// Split out type
			$coltype = 'ASC';
			if ( preg_match("/\s+/", $colitem, $out_match) )
			{
				$colitem = $colset[0];
				$coltype = $colset[1];
			}

			// Orderby can be column name, table.column or number
			// find the appropriate column from the list and pass
			// it to the order list
			$tabname = false;
			$colname = $colset[0];
			if ( preg_match("/\./", $colitem, $out_match) )
			{
				$colset = preg_split("/\./", $colitem);
				$tabname = $colset[0];
				$colname = $colset[1];
			}

			// Handle numeric column
			if ( preg_match("/^[0-9]*$/", $colname ) )
			{
				$i = (int)$colname;
				if ( count($this->columns) < $i )
					trigger_error("Order By Number ".$i." To High for Select", E_USER_ERROR);
				else
					$colname = $this->columns[$i-1]["alias"];
					if ( !$colname )
						$colname = $this->columns[$i-1]["name"];
			}
			else
			{

				$matchno=0;
				foreach ( $this->columns as $col )
				{
					if ( $colname == $col["name"] )
					{
						if ( $tabname && $col["table"] )
						{
							if ( $tabname == $col["table"] )
							{
								$colname = $col["alias"];
								if ( !$colname )
									$colname = $col["name"];
								$matchno++;
							}
						}
						else
						{
							$colname = $col["alias"];
							if ( !$colname )
								$colname = $col["name"];
							$matchno++;
						}
					}
				}

				if ( $matchno == 0 )
					$this->status_message = "Order $colset[0] cannot be matched in column list";

				if ( $matchno > 1 )
					trigger_error("Order $colset[0] ambiguous in column list", E_USER_ERROR);

			}
		       		
			$this->orders[] = array
					(
					"name" => $colname,
					"type" => $coltype
					);
		}

	}

	// -----------------------------------------------------------------------------
	// Function : parse_table
	// -----------------------
	// Will take a column item from an SQL statement and parse it to identify
	// any alias, table identifieror expression
	// ----------------------------------------------------------------------------
	function parse_table( $in_string )
	{
		$err = false;

		
		$tabname = "";
		$tabalias = "";

		if ( ! $collist = preg_split("/\s*,\s*/", $in_string) )
		{
			$err = "Decode Table List Failure";
			return false;
		}

		foreach ( $collist as $colitem )
		{
			if ( ! $colset = preg_split("/\s+/", $colitem) )
			{
				$err = "Decode Column Set List Failure";
				return false;
			}

			// Fetch the table list, fetching details of aliases and outer joins
			$ptr = 0;

			$tabname = $colset[$ptr];
			$jointype = "inner";

			if ( $tabname == "outer" )
			{
				$jointype = "outer";
				$ptr++;
			}

			$tabname = $colset[$ptr++];

			if ( count($colset) > $ptr )
				$tabalias = $colset[$ptr];
			else
				$tabalias = "";

			$this->tables[] = array
					(
					"name" => $tabname,
					"alias" => $tabalias,
					"jointype" => $jointype
					);
		}

	}

	function tokenise_columns( $in_string )
	{

		$escaped = false;
		$level_stack = array();
		$in_dquote = false;
		$in_squote = false;
		$rbracket_level = 0;
		$sbracket_level = 0;
		$collist = array();
		$cur = false;

		for ( $ct = 0; $ct < strlen($in_string); $ct++ )
		{
			if ( $ct == 0 )
			{
				$collist[] = "";
				end($collist);
				$ky = key($collist);
				$cur =& $collist[$ky];
			}

			$ch = substr($in_string,$ct,1);
			$ok_to_add = true;
			
			switch ( $ch )
			{
				case ",":
					if ( !($in_dquote || $in_squote || $rbracket_level > 0 || $sbracket_level > 0) )
					{
						$collist[] = "";
						end($collist);
						$ky = key($collist);
						$cur =& $collist[$ky];
						$ok_to_add = false;
					}
					break;

				case "\"":
					if ( $in_dquote )
						$in_dquote = false;
					else
						if ( !$in_squote )
							$in_dquote = true;
					break;

				case "'":
					if ( $in_squote )
						$in_squote = false;
					else
						if ( !$in_dquote )
							$in_squote = true;
					break;

				case "(":
					if ( !$in_squote && !$in_dquote )
						$rbracket_level++;
					break;

				case ")":
					if ( !$in_squote && !$in_dquote )
						$rbracket_level--;
					break;
			
				case "[":
					if ( !$in_squote && !$in_dquote )
						$sbracket_level++;
					break;

				case "]":
					if ( !$in_squote && !$in_dquote )
						$sbracket_level--;
					break;
			}

			if ($ok_to_add )
				$cur .= $ch;

		}

		return $collist;
	} 

	function parse_column_list( $in_string )
	{

		$collist = $this->tokenise_columns($in_string);

		foreach ( $collist as $k => $colitem )
		{
				if ( !$this->parse_column($k + 1, trim($colitem)) )
					return false;
		}
		return true;
	} 

	// -----------------------------------------------------------------------------
	// Function : parse_column
	// -----------------------
	// Will take a column item from an SQL statement and parse it to identify
	// any alias, table identifieror expression
	// ----------------------------------------------------------------------------
	function parse_column( $in_colno, $in_string )
	{
		$err = false;

		$colalias = "";
		$colname = "";
		$coltable = "";
		$colexp = "";

		// Check for an alias ( any final word which is preceded by any non
		// numeric or expression character

		// Split out the last two elements
		if ( preg_match("/(.+)\s+([^S]*)\s*\$/s", $in_string, $out_match) )
		{
			if ( preg_match ( "/^\w+$/s", $out_match[2] ) )
			{
				$colalias = $out_match[2];
				$colname = $out_match[1];
				$colexp = $colname;
			}
			else
			{
				if ( preg_match("/[^0-9A-Za-z_\r\n\t .]/", $in_string ) )
				{
					$colalias = "column".$in_colno;
					trigger_error("Expression value unnamed. Column Name $colalias allocated to expression ($in_string).", E_USER_WARNING);
				}
				$colname = $in_string;
				$colexp = $in_string;
			}
		}
		else
		{
			// Single column value only so assume no alias
			if ( preg_match("/[^0-9A-Za-z_\r\n\t .]/", $in_string ) )
			{
				$colalias = "column".$in_colno;
				trigger_error("Expression value unnamed. Column Name $colalias allocated to expression ($in_string).", E_USER_WARNING);
			}
			$colname = $in_string;
			$colexp = $in_string;
		}

		// Now with what's left of the column  try to ascertain a table name
		// and column part
		if ( preg_match("/^(\w+)\.(\w+)$/", $colname, $out_match) )
		{
			$coltable = $out_match[1];
			$colname = $out_match[2];
		}

		$this->columns[] = array(
				"name" =>  $colname,
				"table" =>  $coltable,
				"alias" =>  $colalias,
				"expression" =>  $colexp
				)
				;
		return true;

	}
}

