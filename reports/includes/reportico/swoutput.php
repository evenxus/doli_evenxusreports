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
 * Base class for all report output formats.
 * Defines base functionality for handling report 
 * page headers, footers, group headers, group trailers
 * data lines
 *
 * @link http://www.reportico.org/
 * @copyright 2010-2011 Peter Deed
 * @author Peter Deed <info@reportico.org>
 * @package Reportico
 * @version $Id: swoutput.php,v 1.6 2011-09-22 21:57:30 peter Exp $
 */

class reportico_report extends reportico_object
{
	var	$query_set = array();
	var	$document;
	var	$report_file = "";
	var	$page_width;
	var	$page_height;
	var	$page_length = 65;
	var	$page_count = 0;
	var	$page_line_count = 0;
	var	$line_count = 0;
	var	$page_number;
	var	$columns;
	var	$last_line = false;
	var	$query;
	var	$reporttitle;
	var	$body_display = "show";
	var	$graph_display = "show";
	var	$text = "";

	var $attributes = array (
		"TopMargin" => "4%",
		"BottomMargin" => "2%",
		"RightMargin" => "5%",
		"LeftMargin" => "5%",
		"BodyStart" => "10%",
		"BodyEnd" => "10%",
		"ReportTitle" => "Set Report Title"
		);


	function reportico_report()
	{
		reportico_object::reportico_object();

		$this->formats = array(
		"body_style" => "blankline",
		"after_header" => "blankline",
		"before_trailer" => "blankline",
		"after_trailer" => "blankline"
			);
	}

	function reportico_string_to_php($in_string)
	{
		// first change '(colval)' parameters
		$out_string = $in_string;

		if ( preg_match_all( "/{([^}]*)/", $out_string, $matches ) )
		{
			foreach ( $matches[1] as $match )
			{
				$first = substr($match, 0, 1);
				if ( $first == "=" )
				{
					$crit = substr ( $match, 1 );
					$out_string = preg_replace("/\{$match\}/", 
							$this->query->lookup_queries[$crit]->
										get_criteria_clause(false,false,true),
										$out_string);
				}
			}
			
		}


		if ( preg_match("/date\((.*)\)/", $out_string, $match) )	
		{
			$dt = preg_replace("/[\"']/", "", date($match[1]));
			$out_string = preg_replace("/date\(.*\)/i", "$dt", $out_string);
		}

		$out_string = preg_replace('/date("\(.*\)")/', "$this->page_count", 
			$out_string);

		$out_string = preg_replace('/pageno\(\)/', "$this->page_count", 
			$out_string);

		$out_string = preg_replace('/page\(\)/', "$this->page_count", 
			$out_string);

		$out_string = preg_replace('/{page}/', "$this->page_count", 
			$out_string);

		$out_string = preg_replace('/{#page}/', "$this->page_count", 
			$out_string);

		$out_string = preg_replace('/{title}/', $this->reporttitle, 
			$out_string);

		return($out_string);
	}

	function set_query (&$query)
	{
		$this->query =& $query;
		$this->columns =& $query->columns;
	}

	function set_columns (&$columns)
	{
		$this->columns =& $columns;
	}

	function start ()
	{
		$this->body_display = $this->query->derive_attribute( "bodyDisplay",  "show" );
		$this->graph_display = $this->query->derive_attribute( "graphDisplay",  "show" );
		$this->page_line_count = 0;
		$this->line_count = 0;
		$this->page_count = 0;
		$this->debug("Base Start **");
		$this->reporttitle = $this->query->derive_attribute("ReportTitle", 
					"Set Report Title");
		$pos = 5;
	}


	function finish ()
	{
		$this->last_line = true;
		$this->debug("Base finish");
		$this->after_group_trailers();
		if ( $this->page_count > 0 )
			$this->finish_page();


	}

	function begin_page()
	{
		$this->debug("Base New Page");
		$this->page_count ++;
		$this->page_line_count = 0;

	}

	function before_format_criteria_selection()
	{
	}

	function format_criteria_selection_set()
	{
		if ( get_request_item("target_show_criteria") )
		{
			$this->before_format_criteria_selection();
			foreach ( $this->query->lookup_queries as $name => $crit)
			{
				$label = "";
				$value = "";
				if ( get_request_item($name."_FROMDATE_DAY", "" ) )
				{
					$label = $crit->derive_attribute("column_title", $crit->query_name);
					$label = sw_translate($label);
					$mth = get_request_item($name."_FROMDATE_MONTH","") + 1;
					$value = get_request_item($name."_FROMDATE_DAY","")."/".
					$mth."/".
					get_request_item($name."_FROMDATE_YEAR","");
					if ( get_request_item($name."_TODATE_DAY", "" ) )
					{
						$mth = get_request_item($name."_TODATE_MONTH","") + 1;
						$value .= "-";
						$value .= get_request_item($name."_TODATE_DAY","")."/".
						$mth."/".
						get_request_item($name."_TODATE_YEAR","");
					}
				}
				else if ( get_request_item("HIDDEN_".$name."_FROMDATE", "" ) )
				{
					$label = $crit->derive_attribute("column_title", $crit->query_name);
					$label = sw_translate($label);
					$value = get_request_item("HIDDEN_".$name."_FROMDATE","");
					if ( get_request_item("HIDDEN_".$name."_TODATE", "" ) )
					{
						$value .= "-";
						$value .= get_request_item("HIDDEN_".$name."_TODATE");
					}
		
				}
				else if ( get_request_item("MANUAL_".$name, "" ) )
				{
					$label = $crit->derive_attribute("column_title", $crit->query_name);
					$label = sw_translate($label);
					$value = get_request_item("MANUAL_".$name."_FROMDATE","");
					$value .= get_request_item("MANUAL_".$name, "");
		
				}
				if ( $label || $value )
					$this->format_criteria_selection($label, $value);
			}
			$this->after_format_criteria_selection();
		}
	}

	function after_format_criteria_selection()
	{
	}

	function page_headers()
	{
		$this->format_page_header_start();
		foreach($this->query->page_headers as $ph)
		{
				$this->format_page_header($ph);
		}
		$this->format_page_header_end();
	}

	function page_footers()
	{
		$this->format_page_footer_start();
		foreach($this->query->page_footers as $ph)
		{
				$this->format_page_footer($ph);
		}
		$this->format_page_footer_end();
	}

	function finish_page()
	{
		$this->debug("Base Finish Page");
	}

	function new_line()
	{
		$this->debug(" Base New Page");
	}

	function format_format($column_item)
	{
		return;
	}

	function format_page_header(&$header)
	{
		return;
	}

	function format_page_footer(&$header)
	{
		return;
	}

	function format_page_header_start()
	{
		return;
	}

	function format_page_header_end()
	{
		return;
	}

	function format_page_footer_start()
	{
		return;
	}

	function format_page_footer_end()
	{
		return;
	}


	function format_column(& $column_item)
	{
		$this->debug(" Base Format Column");
	}

	function new_column_header()
	{
		$this->debug("Base New Page");
	}

	function new_column()
	{
		$this->debug("New Column");
	}

	function show_column_header(& $column_item)
	{
		$this->debug("Show Column Header");

		if ( !is_object($column_item) )
			return(false);

		$disp = $column_item->derive_attribute(
			"column_display",  "show" );

		if ( $disp == "hide" )
			return false;

		return true;
	}


	function publish()
	{
		$this->debug("Base Publish");
	}

	function begin_line()
	{
		return;
	}

	function end_line()
	{
		return;
	}

	function format_column_trailer(&$trailer_col, &$value_col, $trailer_first = false)
	{
	}
	
	function format_column_trailer_before_line()
	{
	}

	function check_graphic_fit()
	{
		return true;
	}

	function each_line($val)
	{

		$this->debug("Base Each Line");
		if ( $this->page_count == 0 )
		{
			$this->begin_page();

			// Print Criteria Items at top of report
			$this->format_criteria_selection_set();
			//$this->page_headers();
		}


		$this->after_group_trailers();
		$this->before_group_headers();

		$this->page_line_count++;
		$this->line_count++;

		// Add relevant values to any graphs
		foreach ( $this->query->graphs as $k => $v )
		{
			$gr =& $this->query->graphs[$k];
			if ( !$gr ) continue;
			foreach ( $gr->plots as $k1 => $v1 )
			{
				$pl =& $gr->plots[$k1];
				$col = get_query_column($pl["name"], $this->query->columns ) ;
    			$gr->add_plot_value($pl["name"], 
						$col->column_value);
			}
			if ( $gr->xlabel_column )
			{
				$col1 = get_query_column($gr->xlabel_column, $this->query->columns ) ;
    			$gr->add_xlabel( $col1->column_value);
			}
		}


		$this->debug("Line: ".$this->page_line_count."/".$this->line_count);
	}

	function after_group_trailers()
	{
		$trailer_first = true;

		if ( $this->line_count <= 0 )
		{
			// No group trailers as it's the first page
		}
		else
		{
			//Plot After Group Trailers
			if ( count($this->query->groups) == 0 )
				return;

			end($this->query->groups);
			do
			{
				$group = current($this->query->groups);

				if ( $this->query->changed($group->group_name) || $this->last_line) 
				{
					$lev = 0;
					$tolev = 0;
					while ( $lev <= $tolev )
					{
						if ( $lev == 0 )
							$this->apply_format($group, "before_trailer");

						$this->format_group_trailer_start($trailer_first);
						$this->format_column_trailer_before_line();

						$junk = 0;
						$wc = count($this->columns);
						foreach ( $this->query->display_order_set["column"] as $w )
						{
							if ( !$this->show_column_header($w) )
									continue;

							if ( array_key_exists($w->query_name, $group->trailers) )
							{
								if ( count($group->trailers[$w->query_name]) >= $lev + 1 )
								{
									$colgrp =& $group->trailers[$w->query_name][$lev];
									$this->format_column_trailer($w, $colgrp,$trailer_first);
								}
								else
									$this->format_column_trailer($w, $junk,$trailer_first);	
								
								if (  $group->max_level > $tolev )
								{
									$tolev =  $group->max_level;
								}

							}
							else
							{
								$this->format_column_trailer($w, $junk, $trailer_first);	
							}
						} // foreach
						if ( $trailer_first )
							$trailer_first = false;
						$lev++;
						$this->end_line();
					} // while

				}
			}
			while( prev($this->query->groups) );

			// Plot After Group Graphs
			end($this->query->groups);
			do
			{
				$group = current($this->query->groups);

				if ( $this->query->changed($group->group_name) || $this->last_line) 
				{
					if ( !function_exists( "imagecreatefromstring" ) )
						trigger_error("Function imagecreatefromstring does not exist - ensure PHP is installed with GD option" );
					if ( function_exists( "imagecreatefromstring" ) &&
				       			$this->graph_display && 
							get_checkbox_value("target_show_graph"))
					if ( $graph =& $this->query->get_graph_by_name($group->group_name) )
					{
						if ( $url_string = $graph->generate_url_params() )
						{
								$this->plot_graph($graph);
						}
					}
				}
			}
			while( prev($this->query->groups) );
		}
	}

	function plot_graph(&$graph)
	{
	}

	function apply_format($item, $format)
	{
		$formatval = $item->get_format($format);
		$this->format_format($formatval);
	}

	function format_group_trailer_start($first = false)
	{
			return;
	}

	function format_group_trailer_end()
	{
			return;
	}

	function format_group_header_start()
	{
			
			return;
	}

	function format_group_header_end()
	{
			return;
	}

	function before_group_headers()
	{
		$changect = 0;
		reset($this->query->groups);
		foreach ( $this->query->groups as $name => $group) 
		{
			if ( (  $group->group_name == "REPORT_BODY" && $this->line_count == 0 ) || $this->query->changed($group->group_name) ) 
			{
				if ( $changect == 0 && $this->page_line_count > 0)
				{
					$changect++;
					$this->apply_format($group, "before_header");
					$this->format_group_header_start();
				}
				else if ( $changect == 0 || 1)
				{
					//echo "wow<br>";
					$this->format_group_header_start();
				}


				for ($i = 0; $i < count($group->headers); $i++ )
				{
					$col =& $group->headers[$i];
					//echo "heder  $i<br>";
					$this->format_group_header($col);
				}
				
				if ( $graph =& $this->query->get_graph_by_name($group->group_name) )
				{
					$graph->clear_data();
				}

				$this->format_group_header_end();
				$this->apply_format($group, "after_header");
			}
			//echo "done ($group->group_name<br>";
		}
		
		//echo "change ".$changect."Line count ".$this->page_line_count."<BR>";
		if ( $changect > 0 || $this->page_line_count == 0 )
		{	
			$this->format_headers();
		}
	}

	function format_group_header(&$col)
	{
		return;
	}

	function format_headers()
	{
			return;
	}



}

/**
 * Class reportico_report_array
 *
 * Allows a reportico data query to send its output to an
 * array. generally used internally for storing data
 * from user criteria selection lists.
 */
class reportico_report_array extends reportico_report
{
	var	$abs_top_margin;
	var	$abs_bottom_margin;
	var	$abs_left_margin;
	var	$abs_right_margin;
	var	$record_template;
	var	$results = array();
	
	function reportico_report_array ()
	{
		$this->page_width = 595;
		$this->page_height = 842;
		$this->column_spacing = "2%";
	}

	function start ()
	{

		reportico_report::start();

		$results=array();

		$ct=0;
	}

	function finish ()
	{
		reportico_report::finish();

	}

	function format_column(& $column_item)
	{
		if ( !$this->show_column_header($column_item) )
				return;

		$k =& $column_item->column_value;
		$padstring = str_pad($k,20);
	}

	function each_line($val)
	{
		reportico_report::each_line($val);

		// Set the values for the fields in the record
		$record = array();

		foreach ( $this->query->display_order_set["column"] as $col )
	  	{
			$qn = get_query_column($col->query_name, $this->columns ) ;
			$this->results[$qn->query_name][] = $qn->column_value;
			$ct = count($this->results[$qn->query_name]);
       	}
		
	}

}

/**
 * Class reportico_report_array
 *
 * Allows a reportico data query to send its output to an
 * array. generally used internally for storing data
 * from user criteria selection lists.
 */
class reportico_report_table extends reportico_report
{
	var	$abs_top_margin;
	var	$abs_bottom_margin;
	var	$abs_left_margin;
	var	$abs_right_margin;
	var	$record_template;
	var	$target_table = "unknown";
	
	function reportico_report_table ($in_table="unknown")
	{
		$this->target_table = "unknown";
		$this->page_width = 595;
		$this->page_height = 842;
		$this->column_spacing = "2%";
	}

	function start ()
	{

		reportico_report::start();

		// Create the target table
		$ds =& $this->query->datasource->ado_connection;

		$dict = NewDataDictionary($ds);
		if (!$dict) 
			die;

		if (!$dict) return;

		$flds = "";
		$ct=0;
		foreach ( $this->columns as $col )
	  	{
			if ( $ct++ > 0 )
				$flds = $flds.",";

			$colname = preg_replace('/ /', '_', $col->query_name); 
			$flds = $flds.$colname." ".$col->column_type;
			if ( $col->column_length > 0 )
				$flds = $flds."(".$col->column_length.")";
       	}
		
		$opts = array('REPLACE','mysql' => 'TYPE=ISAM', 'oci8' => 'TABLESPACE USERS');
		$sqli = ($dict->CreateTableSQL($this->target_table,$flds, $opts));
			
		for ($i = 0; $i < count($sqli); $i++)
		{
			$sql = $sqli[$i];
			$sql = preg_replace("/CREATE TABLE/", "CREATE TEMP TABLE",$sql);
			//print("Executing ..\n$sql");
			//echo "<br>";
			//$result = mysql_query($sql) ;
			$result = $ds->Execute($sql) ;
			//printf("SQL returned  $result");
			//printf("SQL returned  $ds".$ds->ErrorMsg());
			echo "<br>";
		}

		//$ds = $this->query->datasource->ado_connection;
		$sql = "SELECT * FROM ".$this->target_table." WHERE 0 = 1";
		$this->record_template = $ds->Execute($sql);
	}

	function finish ()
	{
		reportico_report::finish();
	}

	function format_column(& $column_item)
	{
		if ( !$this->show_column_header($column_item) )
				return;

		$k =& $column_item->column_value;
		$padstring = str_pad($k,20);
	}

	function each_line($val)
	{
		reportico_report::each_line($val);


		// Get the record template
		$ds =& $this->query->datasource->ado_connection;
		$rs = $this->record_template;

		// Set the values for the fields in the record
		$record = array();

		foreach ( $this->columns as $k => $col )
	  	{
			$qn = $this->columns[$k];
			$colname = preg_replace('/ /', '_', $qn->query_name); 
			$record[$colname] = $qn->column_value;
       	}
		
		// Pass the empty recordset and the array containing the data to insert
		// into the GetInsertSQL function. The function will process the data and return
		// a fully formatted insert sql statement.
		$insertSQL = $ds->GetInsertSQL($rs, $record);

		//echo "$insertSQL<br>";

		// Insert the record into the database
		$ds->Execute($insertSQL);

	}

}


// -----------------------------------------------------------------------------
// Class reportico_report_pdf
// -----------------------------------------------------------------------------
class reportico_report_pdf extends reportico_report
{
	var	$abs_top_margin;
	var	$abs_bottom_margin;
	var	$abs_left_margin;
	var	$abs_right_margin;
	var	$orientation;
	var	$page_type;
	var	$column_order;
	var	$fontName;
	var	$fontSize;
	var	$vsize;
	var	$justifys = array (
		"right" => "R",
		"centre" => "C",
		"center" => "C",
		"left" => "L"
		);
	var	$orientations = array (
		"Portrait" => "P",
		"Landscape" => "L"
		);
	var	$page_types = array (
		"B5" => array ("height" => 709, "width" => 501 ),
		"A6" => array ("height" => 421, "width" => 297 ),
		"A5" => array ("height" => 595, "width" => 421 ),
		"A4" => array ("height" => 842, "width" => 595 ),
		"A3" => array ("height" => 1190, "width" => 842 ),
		"A2" => array ("height" => 1684, "width" => 1190 ),
		"A1" => array ("height" => 2380, "width" => 1684 ),
		"A0" => array ("height" => 3368, "width" => 2380 ),
		"US-Letter" => array ("height" => 792, "width" => 612 ),
		"US-Legal" => array ("height" => 1008, "width" => 612 ),
		"US-Ledger" => array ("height" => 792, "width" => 1224 ),
		);
	var	$yjump = 0;
	var	$vspace = 0;

	
	function reportico_report_pdf ()
	{
		$this->column_spacing = 5;
	}

	function start ()
	{
		reportico_report::start();
		$this->debug("PDF Start **");

		$this->page_line_count = 0;
		$this->fontName = $this->query->get_attribute("pdfFont");
		$this->fontSize = $this->query->get_attribute("pdfFontSize");
		$this->vsize = $this->fontSize + $this->vspace;
		$this->orientation = $this->query->get_attribute("PageOrientation");
		$this->page_type = $this->query->get_attribute("PageSize");
		if ( $this->orientation == "Portrait" )
		{
			$this->abs_page_width = $this->page_types[$this->page_type]["width"];
			$this->abs_page_height = $this->page_types[$this->page_type]["height"];
		}
		else
		{
			$this->abs_page_width = $this->page_types[$this->page_type]["height"];
			$this->abs_page_height = $this->page_types[$this->page_type]["width"];
		}
		$this->abs_top_margin = $this->abs_paging_height($this->query->get_attribute("TopMargin"));
		$this->abs_bottom_margin = $this->abs_page_height - 
						$this->abs_paging_height($this->query->get_attribute("BottomMargin"));
		$this->abs_right_margin = $this->abs_page_width - 
						$this->abs_paging_width($this->query->get_attribute("RightMargin"));
		$this->abs_left_margin = $this->abs_paging_width($this->query->get_attribute("LeftMargin"));

		//require_once("fpdf/fpdf.php");
		dol_include_once('/reports/includes/reportico/fpdf/fpdf.php');

		$this->document = new FPDF($this->orientations[$this->orientation],'pt',$this->page_type);
		//if ( $this->report_file )
			//pdf_open_file($this->document, $this->report_file.".pdf");
		//else
			//pdf_open_file($this->document);
		$this->document->SetAutoPageBreak(false);
		$this->document->SetMargins(0,0,0);
		$this->document->SetCreator('Reportico');
		$this->document->SetAuthor('Reportico');
		$this->document->SetTitle($this->reporttitle);

		// Calculate column print and width poistions based on the column start attributes
		$looping = true;

		foreach ( $this->query->display_order_set["column"] as $k => $w )
		{
			$col = get_query_column($w->query_name, $this->query->columns ) ;
			$startcol =  $col->attributes["ColumnStartPDF"];
			$colwidth =  $col->attributes["ColumnWidthPDF"];
			if ( $startcol )
				$col->abs_column_start = $this->abs_paging_width($startcol);
			else
				$col->abs_column_start = 0;
			if ( $colwidth )
				$col->abs_column_width = $this->abs_paging_width($colwidth);
			else
				$col->abs_column_width = 0;
			//echo "Init ".$col->query_name." - ".$col->abs_column_start."<BR>";
			//echo "Init ".$col->query_name." - ".$col->abs_column_width."<BR>";
		}

		while ( $looping )
		{
			$fromkey = 0;
			$nextkey = 0;
			$frompos = 0;
			$nextpos = 0;
			$topos = 0;
			$lastwidth = 0;
			$looping = false;
			$gapct = 0;
			$k = 0;
			$colct = count($this->query->display_order_set["column"]);
			$coltaken = 0;
			$colstocalc = 0;
			$colswithwidth = 0;

			//echo "<BR>NEW !!<BR>";
			foreach ( $this->query->display_order_set["column"] as $k => $w )
			{
				if ( $w->attributes["column_display"] != "show")
					continue;

				{
						$col = get_query_column($w->query_name, $this->query->columns ) ;
						$startcol =  $col->abs_column_start;
						$colwidth =  $col->abs_column_width;
						//echo "With ".$w->query_name." $startcol $colwidth $coltaken<BR>";
						if ( $startcol )
						{
							//echo "From $fromkey Pos $frompos Gap $gapct<br>";
							if ( /*$fromkey &&*/ $frompos && $gapct )
							{
								//echo "at end<br>";
								//$tokey = $k;
								$topos = $col->abs_column_start;
								break;
							}
							else
							{
								//echo "Settingf to $k<br>";
								$fromkey = $k;
								$tokey = $k;
								$frompos = $col->abs_column_start;
								if ( $colwidth )
								{
									$coltaken += $colwidth;
									//echo " reset type 1 ".$col->query_name."<BR>";
									$coltaken = 0;
									$colswithwidth=1;
									$colstocalc=1;
								}
								else
								{
									$colstocalc++;
									$gapct++;
								}
							}
							$lastct = 0;
						}
						else
						{
							//echo "All blank<br>";
							if ( /*!$fromkey &*/ !$frompos )
							{
								$col->abs_column_start = $this->abs_left_margin;
								$frompos = $col->abs_column_start;
								$fromkey = $k;
							}
							if ( $colwidth )
							{
								$coltaken += $colwidth;
									//echo "type 2 ".$col->query_name."<BR>";
								$colswithwidth++;
							}
							$colstocalc++;
							$tokey =$k;
							$gapct++;
							$looping = true;
						}
				}

			}

			//echo "Here gap $gapct - from $fromkey/$frompos to $tokey/$topos<br>";
			if ( !$gapct )
				break;

			// We have two known positions find total free space between
			$calctoend = false;
			if ( !$topos )
			{
				//echo "Calc to end<BR>";
				$calctoend = true;
				$topos =  $this->abs_right_margin;
				//echo "until ".$topos." of ".$this->abs_page_width."<BR>";
				//echo "gone to ".$this->abs_page_width." - ".$this->abs_right_margin."<BR>";
			}

			//echo "From pos $frompos to $topos marg = $this->abs_left_margin<BR>";
			$totwidth = $topos - $frompos;
			if ( $coltaken > $totwidth )
				$coltaken = $totwidth;

			//echo "Taken $coltaken/$totwidth<br>";
			$colno = 0;
			$calccolwidth = ( $totwidth - $coltaken ) / (( $colstocalc - $colswithwidth ) );
			//echo "$calccolwidth = ( $totwidth - $coltaken ) / (( $colstocalc - $colswithwidth ) )<BR>";
			//echo "Total cols to calc $colstocalc - $colswithwidth = $calccolwidth<br>";
			$lastpos = $this->abs_left_margin;
			for ( $ct = $fromkey; $ct <= $tokey; $ct++ )
			{
				$col1 =& $this->query->display_order_set["column"][$ct];
				if ( $col1->attributes["column_display"] == "show")
				{
					$abspos = $col1->abs_column_start;

					if ( !$abspos )
					{
						$col1->abs_column_start = $lastpos;
						$colwidth =  $col1->attributes["ColumnWidthPDF"];
						//echo "$ct. we have ".$colwidth."(".$col1->attributes["ColumnWidthPDF"].")<br>";
						if ( $colwidth )
						{
							$col1->abs_column_width = $this->abs_paging_width($colwidth);
							$lastpos = $col1->abs_column_start + $col1->abs_column_width;
						}
						else
						{
							$col1->abs_column_width = $calccolwidth;
							$lastpos = $col1->abs_column_start + $calccolwidth;
						}
						//echo "$ct. 1 set ".$col->abs_column_start."/".$col->abs_column_width."<BR>";
					}
					else
					{
						$colwidth =  $col1->attributes["ColumnWidthPDF"];
						if ( $colwidth )
						{
							$col1->abs_column_width = $this->abs_paging_width($colwidth);
							$lastpos = $col1->abs_column_start + $col1->abs_column_width;
						}
						else
						{
							$col1->abs_column_width = $calccolwidth;
							$lastpos = $col1->abs_column_start + $calccolwidth;
						}
						//echo "$ct. 2 set ".$col1->abs_column_start."/".$col1->abs_column_width."<BR>";
					}
				}
			}

		}

/*
		$looping = true;
		while ( $looping )
		{
			$looping = false;
			$lastpos = false;
			$lastkey = false;
			$lastqn = false;
			$temppos = false;
			foreach ( $this->query->display_order_set["column"] as $k => $w )
			{
				$col =& $this->query->display_order_set["column"][$k];
//echo "$k ->".$col->query_name."/".$col->abs_column_start."<BR>";
				if ( $col->attributes["column_display"] == "show")
				{
					if ( $lastpos )
						if ( $col->abs_column_start < $lastpos->abs_column_start )
						{
							$tempos =& $col;
							$this->query->display_order_set["column"][$k] =& 
							    get_query_column($lastqn, $this->query->columns ) ;
							$this->query->display_order_set["column"][$lastkey] 
								= get_query_column($col->query_name, $this->query->columns);
	
						}
					$lastpos =& $col;
					$lastkey = $k;
					$lastqn = $col->query_name;
				}
						
			}
		}
*/

	}

	function finish ()
	{
		reportico_report::finish();
		$this->debug("Finish");

		if ( $this->line_count < 2 )
		{
			$this->debug ("No Records Found" );
			$this->document->Write(5, "No Records Found");
		}


		$this->document->SetDisplayMode("real");
		//$this->document->pdf_close($this->document);

		if ( $this->report_file )
		{
			$this->debug("Saved to $this->report_file");
		}
		else
		{
			$this->debug("No pdf file specified !!!");
			//$buf = $this->document->pdf_get_buffer($this->document);
			$buf = $this->document->Output("", "S");
			$len = strlen($buf);

			//ob_clean();	
			/*header("Content-Type: application/pdf");
			header("Content-Length: $len");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			if ( get_request_item("target_attachment", "" ) )
				header('Content-Disposition: attachment; filename=reportico.pdf');
			else
				header('Content-Disposition: inline; filename=reportico.pdf');
			print($buf);
			die;*/
				
			//$file_temp = tempnam(sys_get_temp_dir(), "DolRep");
			$file_temp = dol_buildpath("/reports/includes/reportico/templates_c/".dol_now().".pdf", 0);

			$gestor = fopen($file_temp, "w");
			fwrite($gestor, $buf);
			fclose($gestor);
			$url=dol_buildpath("/reports/download.php", 2)."?file=".$file_temp."&cvs=0";
			ini_set('display_errors','Off');
			print "<meta http-equiv='refresh' content='0;url=".$url."'>"; 
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				
			} else {
				die;
			}
		
		}
	}

	function abs_paging_height($height_string)
	{
		//if ( preg_match("/(\d*\.*(\d+)(\D*)/", $height_string, $match) )
		if ( preg_match("/(\d+)(\D*)/", $height_string, $match) )
		{
			$height = $match[1];
			if ( isset( $match[2] ) )
			{
				switch ( $match[2] )
				{
					case "pt":
						$height = $height;
						break;

					case "%":
						$height = ( $height * $this->abs_page_height ) / 100;
						break;

					case "mm":
						$height = $height / 0.35277777778;
						break;

					case "cm":
						$height = $height / 0.035277777778;
						break;

					default:
						//handle_error("Unknown Page Sizing Option ".$match[2]);
						break;

				}
			}
		}
		else
		{
			$height = $height_string;
			//handle_error("Unknown Page Sizing Option $height_string");
		}

		return $height;
	}

	function abs_paging_width($width_string)
	{
		if ( preg_match("/(\d+)(\D*)/", $width_string, $match) )
		{
			$width = $match[1];
			if ( isset( $match[2] ) )
			{
				switch ( $match[2] )
				{
					case "pt":
						$width = $width;
						break;

					case "%":
						$width = ( $width * $this->abs_page_width ) / 100;
						break;

					case "mm":
						$width = $width / 0.35277777778;
						break;

					case "cm":
						$width = $width / 0.035277777778;
						break;

					default:
						handle_error("Unknown Page Sizing Option $width_string");
						break;

				}
			}
		}
		else
		{
			$width = $width_string;
			//handle_error("Unknown Page Sizing Option $width_string");
		}

		return $width;
	}

	function format_column_trailer(&$trailer_col, &$value_col, $trailer_first = false)
	{
		if ( $this->body_display != "show" ||
							!get_checkbox_value("target_show_body"))
			return;

		if ( $value_col )
		{

			$y = $this->document->GetY();

			// Fetch Group Header Label
			$group_label = $value_col->get_attribute("group_header_label" );
			if ( !$group_label )
				$group_label = $value_col->get_attribute("column_title" );

			if ( !$group_label )
			{
				$group_label = $value_col->query_name;
				$group_label = str_replace("_", " ", $group_label);
				$group_label = ucwords(strtolower($group_label));
			}

			$group_label = sw_translate($group_label);

			// Fetch Group Header Label End Column + display
			$group_xpos = $trailer_col->abs_column_start;

			$wd = $trailer_col->abs_column_width;
			if ( $wd - $this->column_spacing > 0 )
				$wd = $wd - $this->column_spacing;

			$this->document->SetXY($group_xpos, $y);
			$padstring = $value_col->old_column_value;
			$just = $this->justifys[$trailer_col->derive_attribute( "justify",  "left")];
			$group_label = $value_col->get_attribute("group_trailer_label" );
			if ( !$group_label )
				$group_label = $value_col->get_attribute("column_title" );
			if ( $group_label && $group_label != "BLANK" )
				$padstring = sw_translate($group_label).":".$padstring;

			$this->document->CellTrunc($wd,$this->vsize + 2,"$padstring","BT", 0, $just);

			// Fetch Group Header Label Start Column + display
			$group_xpos = $value_col->get_attribute("group_header_label_xpos" );
			if ( !$group_xpos )
				$group_xpos = 0;
			$group_xpos = $this->abs_paging_width($group_xpos);
			$group_xpos = $value_col->abs_column_start;

			$this->document->SetXY($group_xpos, $y);
			$padstring = $group_label;
			$just = $this->justifys[$trailer_col->derive_attribute( "justify",  "left")];
			//$this->document->CellTrunc($wd,$this->vsize,"$padstring","T",0,$just);
			//$this->document->Ln();
		}

	}

	function end_line()
	{
		$this->document->Ln();
	}


	function format_page_header_start()
	{
		return;
	}

	function format_page_header_end()
	{
		$this->document->Ln();
		$this->document->Ln();
	}

	function before_format_criteria_selection()
	{
	}

	function format_criteria_selection($label, $value)
	{
		$y = $this->document->GetY();

		$this->yjump = 0;
		// Fetch Group Header Label Start Column + display
		$group_xpos = false;
		if ( !$group_xpos )
			$group_xpos = $this->abs_left_margin;
		$group_xpos = $this->abs_paging_width($group_xpos);

		$this->document->SetXY($group_xpos, $y);
		$padstring = $label;
		$this->document->CellTrunc( 400, $this->vsize, "$padstring");

		// Fetch Group Header Label End Column + display
		$group_xpos = false;
		if ( !$group_xpos )
			$group_xpos = $this->abs_paging_width($group_xpos) + 250;
		$group_xpos = $this->abs_paging_width($group_xpos);

		$this->document->SetXY($group_xpos, $y);
		$qn = get_query_column($col->query_name, $this->query->columns ) ;
		$padstring = $value;
		$this->document->CellTrunc(100, $this->vsize, "$padstring");
		$this->document->Ln();
		$y = $this->document->GetY();
		if ( $this->yjump )
			$this->document->SetY($y + $this->yjump);

		$label = "";
		$value = "";
	}

	function after_format_criteria_selection()
	{
	}

	function format_group_header_start()
	{
		$y = $this->document->GetY();
		$this->document->Ln();
		$y = $this->document->GetY();

		// Throw new page if current position + number headers + line + headers > than bottom margin
		$y = $this->document->GetY();

		$ln = 0;
		foreach ( $this->query->groups as $val )
			$ln += count($val->headers);
		$ln += 5;
		if ( ($y + ($ln * $this->vsize))> $this->abs_bottom_margin )
		{
			$this->finish_page();
			$this->begin_page();
			$x = $this->document->GetX();
			$y = $this->document->GetY();
		}
	}

	function format_group_trailer_start($first=false)
	{
		return;
	}

	function format_group_header_end()
	{
		$this->document->Ln();
	}

	function format_group_trailer_end()
	{
		return;
	}

	function format_group_header(&$col)
	{
		$y = $this->document->GetY();
		$group_label = $col->get_attribute("group_header_label" );
		if ( !$group_label )
			$group_label = $col->get_attribute("column_title" );
		if ( !$group_label )
		{
			$group_label = $col->query_name;
			$group_label = str_replace("_", " ", $group_label);
			$group_label = ucwords(strtolower($group_label));
		}
		$group_label = sw_translate($group_label);


		$this->yjump = 0;
		// Fetch Group Header Label Start Column + display
		$group_xpos = $col->get_attribute("group_header_label_xpos" );
		if ( !$group_xpos )
			$group_xpos = $this->abs_left_margin;
		$group_xpos = $this->abs_paging_width($group_xpos);

		$this->document->SetXY($group_xpos, $y);
		$padstring = $group_label;
		$this->document->CellTrunc( 400, $this->vsize, "$padstring");

		// Fetch Group Header Label End Column + display
		$group_xpos = $col->get_attribute("group_header_data_xpos" );
		if ( !$group_xpos )
			$group_xpos = $this->abs_paging_width($group_xpos) + 250;
		$group_xpos = $this->abs_paging_width($group_xpos);

		$contenttype = $col->derive_attribute( "content_type",  $col->query_name);
		if ( $contenttype == "graphic" )
		{
			$qn = get_query_column($col->query_name, $this->query->columns ) ;
			$sql = @preg_replace("/.*imagesql=/", "", $qn->column_value);
			$sql = @preg_replace("/'>$/", "", $sql);
			$str = 
			&get_db_image_string(
				$this->query->datasource->driver, 
				$this->query->datasource->database, 
				$this->query->datasource->host_name, 
				$sql,
				$this->query->datasource->ado_connection
			);

			if ( $str )
			{
				$tmpnam = tempnam(SW_TMP_DIR, "dbi");
				$width = $qn->abs_column_width;
				$height = 20;
				$im = imagecreatefromstring($str);

				if ( imagepng($im, $tmpnam.".png" ) )
				{
					$x = $qn->abs_column_start;
					$y = $this->document->GetY();
					$this->document->SetX($group_xpos);
					$h = $this->document->Image($tmpnam.".png", $group_xpos, $y, $width );
					$this->yjump =$h;
					unlink($tmpnam.".png");
				}
			}
		}
		else
		{
			$this->document->SetXY($group_xpos, $y);
			$qn = get_query_column($col->query_name, $this->query->columns ) ;
			$padstring = $qn->column_value;
			$this->document->CellTrunc(100, $this->vsize, "$padstring");
		}
		$this->document->Ln();
		$y = $this->document->GetY();
		if ( $this->yjump )
			$this->document->SetY($y + $this->yjump);
	}


	function format_column_header(& $column_item)
	{
		if ( $this->body_display != "show" ||
							!get_checkbox_value("target_show_body"))
			return;

		if ( !$this->show_column_header($column_item) )
				return;

		$k =& $column_item->query_name;
		$padstring = $column_item->derive_attribute( "column_title",  $column_item->query_name);
		$padstring = str_replace("_", " ", $padstring);
		$padstring = ucwords(strtolower($padstring));
		$padstring = sw_translate($padstring);

		$just = $this->justifys[$column_item->derive_attribute( "justify",  "left")];

		$contenttype = $column_item->derive_attribute(
			"content_type",  $column_item->query_name);

		$tw = $column_item->abs_column_start;
		$x = $this->document->GetX();
		$y = $this->document->GetY();
		$this->document->SetXY($tw, $y);

		$wd = $column_item->abs_column_width;
		if ( $wd - $this->column_spacing > 0 )
			$wd = $wd - $this->column_spacing;
		if ( !$wd )
		{
			$this->document->Write( "$padstring");
		}
		else
		{
			$this->document->SetX($tw);
			$this->document->CellTrunc($wd, $this->vsize, $padstring,"B",0,$just);
		}
	}

	function plot_graph(&$graph)
	{
		$this->document->Ln();
		$graph->width_actual = check_for_default("GraphWidthPDF", $graph->width_pdf);
		$graph->height_actual = check_for_default("GraphHeightPDF", $graph->height_pdf);

		$graph->title_actual = reportico_assignment::reportico_meta_sql_criteria($this->query, $graph->title, true);
		$graph->xtitle_actual = reportico_assignment::reportico_meta_sql_criteria($this->query, $graph->xtitle, true);
		$graph->ytitle_actual = reportico_assignment::reportico_meta_sql_criteria($this->query, $graph->ytitle, true);
		
		$handle = $graph->generate_graph_image();

		$tmpnam = tempnam(SW_TMP_DIR, "gph");
		unlink($tmpnam);

		if ( imagepng($handle, $tmpnam.".png" ) )
		{
			$x = $this->document->GetX();
			$y = $this->document->GetY();
			$this->document->SetX( $this->abs_left_margin);

			$width = $graph->width_actual;
			$height = $graph->height_actual;

			if ( $width > ($this->abs_right_margin - $this->abs_left_margin) )
			{
				$height = $height * (  ($this->abs_right_margin - $this->abs_left_margin) / $width );
				$width = ($this->abs_right_margin - $this->abs_left_margin);
			}
			$xaddon = ( $this->abs_right_margin - $this->abs_left_margin - $width ) / 2 ;
	
			if ( $y + $height >= $this->abs_bottom_margin )
			{
				$this->finish_page();
				$this->begin_page();
				$x = $this->document->GetX();
				$y = $this->document->GetY();
			}
			$this->document->Image($tmpnam.".png", $this->abs_left_margin + $xaddon, $y, $width, $height );
			$y = $this->document->SetY($y + $height);
			$this->document->Ln();
			unlink($tmpnam.".png");
		}
	}

	function format_headers()
	{
		foreach ( $this->columns as $w )
			$this->format_column_header($w);
		$this->document->Ln();
		$this->document->Ln();
	}


	function format_column(& $column_item)
	{
		if ( !$this->show_column_header($column_item) )
				return;

		$k =& $column_item->column_value;
		$tw = $column_item->abs_column_start;
		$wd = $column_item->abs_column_width;

		if ( $wd - $this->column_spacing > 0 )
			$wd = $wd - $this->column_spacing;
		$just = $this->justifys[$column_item->derive_attribute( "justify",  "left")];
		$contenttype = $column_item->derive_attribute(
			"content_type",  $column_item->query_name);
		if ( $contenttype == "graphic" )
		{
			$sql = @preg_replace("/.*imagesql=/", "", $column_item->column_value);
			$sql = @preg_replace("/'>$/", "", $sql);
			$str = 
			&get_db_image_string(
				$this->query->datasource->driver, 
				$this->query->datasource->database, 
				$this->query->datasource->host_name, 
				$sql,
				$this->query->datasource->ado_connection
			);

			if ( $str )
			{
				$tmpnam = tempnam(SW_TMP_DIR, "dbi");
				$width = $column_item->abs_column_width;
				//echo "Widht is ".$column_item->abs_column_width."<BR>";
				$height = 20;
				$im = imagecreatefromstring($str);

				if ( imagepng($im, $tmpnam.".png" ) )
				{
					$x = $column_item->abs_column_start;
					$y = $this->document->GetY();
					$this->document->SetX($x);
					$h = $this->document->Image($tmpnam.".png", $x, $y, $width );
					if ( $h > $this->yjump )
						$this->yjump =$h;
					unlink($tmpnam.".png");
				}
			}
		}
		else
		{
			if ( !$wd )
				$this->document->Write( "$padstring");
			else
			{
				$this->document->SetX($tw);
				$this->document->CellTrunc($wd, $this->vsize, $k,0,0,$just);
				$tw = $this->abs_page_width - $this->abs_right_margin;
			}
		}
	}

	function each_line($val)
	{
		reportico_report::each_line($val);
		/*if(isset($val['idLine']) && $val['idLine']%2==0)
		{
			$this->document->SetTextColor(0,0,0);
		}
		else
		{
			$this->document->SetTextColor(150,150,150);
		}
*/
		$y = $this->document->GetY();
		$this->document->SetXY(50, $y);
		 $this->document->SetY($y+10);

		if ( $y + $this->vsize > $this->abs_bottom_margin )
		{
		//	$this->document->SetTextColor(0,0,0);
			$this->finish_page();
			$this->begin_page();
		}

		$this->check_graphic_fit();
		
		$this->yjump = 0;
		if ( $this->body_display == "show" &&
							get_checkbox_value("target_show_body"))
		{
			foreach ( $this->columns as $col )
			{
				$this->format_column($col);
			}
			$this->document->Ln();
		}
		$y = $this->document->GetY();
		if ( $this->yjump )
			$this->document->SetY($y + $this->yjump);

		if ( $y + $this->vsize > $this->abs_bottom_margin )
		{
		//	$this->document->SetTextColor(0,0,0);
			$this->finish_page();
			$this->begin_page();
		}


	}

	function check_graphic_fit()
	{
		$will_fit = true;
		$max_height = $this->vsize;
		foreach ( $this->columns as $col )
		{
			$contenttype = $col->derive_attribute( "content_type",  $col->query_name);
			if ( $contenttype == "graphic" )
			{
				$qn = get_query_column($col->query_name, $this->query->columns ) ;
				$sql = @preg_replace("/.*imagesql=/", "", $qn->column_value);
				$sql = @preg_replace("/'>$/", "", $sql);
				$str = 
					&get_db_image_string(
					$this->query->datasource->driver, 
					$this->query->datasource->database, 
					$this->query->datasource->host_name, 
					$sql,
					$this->query->datasource->ado_connection
				);

				if ( $str )
				{
					//$im = convert_image_string_to_image($str, "png");
					$tmpnam = tempnam(SW_TMP_DIR, "dbi");
					$width = $qn->abs_column_width;
					$height = 20;
					$im = imagecreatefromstring($str);

					if ( imagepng($im, $tmpnam.".png" ) )
					{
						$h = $this->document->ImageHeight($tmpnam.".png", $group_xpos, $y, $width );
						unlink($tmpnam.".png");
						if ( $max_height < $h )
							$max_height = $h;
					}
				}
				//echo "height $h $max_height<br>";
			}
		}

		$y = $this->document->GetY();

		if ( $y + $max_height /*+ 10*/ > $this->abs_bottom_margin )
		{
			//echo "if ( $y + $max_height > $this->abs_bottom_margin )<br>";
			//echo "thorwing<br>";
			$this->finish_page();
			$this->begin_page();
			$this->before_group_headers();
			$this->page_line_count++;
		}

	}

	function page_template()
	{
		$this->debug("Page Template");
	}

	function begin_page()
	{
		reportico_report::begin_page();

		$this->debug("PDF Begin Page\n");

		$this->document->AddPage($this->orientations[$this->orientation]);
		$font = $this->document->SetFont($this->fontName);
		$font = $this->document->SetFontSize($this->vsize);
		$this->document->SetXY($this->abs_left_margin, $this->abs_top_margin);
		reportico_report::page_headers();
	}

	function finish_page()
	{
		$this->debug("Finish Page");
		$this->page_footers();
		//$this->document->pdf_end_page($this->document);
	}

	function publish()
	{
		reportico_report::publish();
		$this->debug("Publish PDF");
	}

	function format_page_header(&$header)
	{
		$startcol = $header->get_attribute("ColumnStartPDF");
		$tw = $this->abs_paging_width($startcol);
		if ( !$tw )
			$tw = $this->abs_right_margin;

		$wd = $header->get_attribute("ColumnWidthPDF");
		if ( !$wd )
			if ( $this->abs_right_margin > $tw )
				$wd = $this->abs_right_margin - $tw;
			else
				$wd = "100%";
		$wd = $this->abs_paging_width($wd);

		$just = $this->justifys[$header->derive_attribute( "justify",  "left")];

		$y = $this->abs_top_margin + ( $this->vsize * ( $header->line - 1 ) );
		$this->document->SetXY($tw,$y);
		
		$tx = $this->reportico_string_to_php(reportico_assignment::reportico_meta_sql_criteria($this->query, sw_translate($header->text)));
		$this->document->CellTrunc($wd, $this->vsize, $tx, 0, 0, $just );
		$this->document->Ln();

		return;
	}

	function format_page_footer(&$footer)
	{
		$startcol = $footer->get_attribute("ColumnStartPDF");
		$tw = $this->abs_paging_width($startcol);
		if ( !$tw )
			$tw = $this->abs_right_margin;

		$wd = $footer->get_attribute("ColumnWidthPDF");
		if ( !$wd )
			if ( $this->abs_right_margin > $tw )
				$wd = $this->abs_right_margin - $tw;
			else
				$wd = "100%";
		$wd = $this->abs_paging_width($wd);

		$just = $this->justifys[$footer->derive_attribute( "justify",  "left")];

		$y = $this->abs_bottom_margin + ( $this->vsize * $footer->line );
		$this->document->SetXY($tw, $y);
		//$tx = $this->reportico_string_to_php($footer->text);
		$tx = $this->reportico_string_to_php(reportico_assignment::reportico_meta_sql_criteria($this->query, sw_translate($footer->text)));
		$this->document->CellTrunc($wd, $this->vsize, $tx, 0, 0, $just);
		$this->document->Ln();

		return;
	}

	function format_format($in_value)
	{
		switch($in_value)
		{
			case "blankline" :
				$this->document->Ln();
				break;

			case "solidline" :
				$y = $this->document->GetY();
				$this->document->Line($this->abs_left_margin, $y, $this->abs_page_width - $this->abs_right_margin, $y);
				//$this->document->pdf_stroke($this->document);
				//$font = $this->document->pdf_findfont($this->document, 'Courier', 'host', 0);
				//$this->document->pdf_setfont($this->document, $font, 8.0);
				$this->document->SetXY($this->abs_right_margin, $y);
				$this->document->Ln();
				break;

			case "newpage" :
				$this->finish_page();
				$this->begin_page();
				break;


			default :
				$this->document->Ln();
				break;
				
		}	
	}



}

// -----------------------------------------------------------------------------
// Class reportico_report_html_template
// -----------------------------------------------------------------------------
class reportico_report_soap_template extends reportico_report
{

	var $soapdata = array();
	var $soapline = array();
	var $soapresult = false;

	function start ()
	{

		// Include NuSoap Web Service PlugIn
		//require_once("nusoap.php");

		reportico_report::start();

		$this->reporttitle = $this->query->derive_attribute("ReportTitle", "Set Report Title");
		$this->debug("SOAP Start **");
	}

	function finish ()
	{
		reportico_report::finish();
		$this->debug("HTML End **");

		if ( $this->line_count < 1 )
		{
			$this->soapresult = new soap_fault('Server',100,"No Data Returned","No Data Returned");
		}
		else
		{
			$this->soapdata = array(
				"ReportTitle" => $this->reporttitle,
				"ReportTime" => date("Y-m-d H:I:s T"),
				$this->soapdata
				);
				
			$this->soapresult = 
				new soapval('reportReturn',
         				'ReportDeliveryType',
         				$this->soapdata,
         				'http://reportico.org/xsd');
					//$x = $this->soapresult->serialize();
		//var_dump($this->soapresult);
		//var_dump($x);
		}

	}

	function format_column(& $column_item)
	{
		if ( $this->body_display != "show" || !get_checkbox_value("target_show_body"))
			return;

		if ( !$this->show_column_header($column_item) )
				return;

		$this->soapline[$column_item->query_name] = $column_item->column_value;
	}

	function each_line($val)
	{
		reportico_report::each_line($val);

		if ( $this->page_line_count == 1 )
		{
			//$this->text .="<tr class='swPrpCritLine'>";
			//foreach ( $this->columns as $col )
				//$this->format_column_header($col);
			//$this->text .="</tr>";
		}

		$this->soapline = array();
		foreach ( $this->query->display_order_set["column"] as $col )
				$this->format_column($col);
		$this->soapdata[] = new soapval('ReportLine', 'ReportLineType', $this->soapline);
	}

	function page_template()
	{
		$this->debug("Page Template");
	}

}


// -----------------------------------------------------------------------------
// Class reportico_report_html_template
// -----------------------------------------------------------------------------
class reportico_report_html_template extends reportico_report
{
	var	$abs_top_margin;
	var	$abs_bottom_margin;
	var	$abs_left_margin;
	var	$abs_right_margin;
	var	$graph_session_placeholder = 0;
	
	function reportico_report_html_template ()
	{
		return;
	}

	function start ()
	{
		reportico_report::start();

		$this->debug("HTML Start **");

		//pdf_set_info($this->document,'Creator', 'God');
		//pdf_set_info($this->document,'Author', 'Peter');
		//pdf_set_info($this->document,'Title', 'The Title');

		$this->page_line_count = 0;
		$this->abs_top_margin = $this->abs_paging_height($this->get_attribute("TopMargin"));
		$this->abs_bottom_margin = $this->abs_paging_height($this->get_attribute("BottomMargin"));
		$this->abs_right_margin = $this->abs_paging_height($this->get_attribute("RightMargin"));
		$this->abs_left_margin = $this->abs_paging_height($this->get_attribute("LeftMargin"));
	}

	function finish ()
	{
		reportico_report::finish();
		$this->debug("HTML End **");

		if ( $this->line_count < 1 )
		{
			$title = $this->query->derive_attribute("ReportTitle", "Unknown");
			$this->text .= '<H1 class="swRepTitle">'.sw_translate($title).'</H1>';
			$forward = session_request_item('forward_url_get_parameters', '');
			if ( $forward )
				$forward .= "&";
			//$this->text .= '<div class="swRepBackBox"><a class="swLinkMenu" href="'.session_request_item('linkbaseurl', SW_USESELF).'?'.$forward.'execute_mode=PREPARE&session_name='.session_id().'">'.sw_translate(SW_MESSAGE_BACK).'</a></div>';
			$this->text .= '<div class="swRepNoRows">'.sw_translate("No Data Matched Your Criteria").'</div>';
		}

		if ( $this->report_file )
		{
			$this->debug("Saved to $this->report_file");
		}
		else
		{
			$this->debug("No html file specified !!!");
			$buf = "";
			$len = strlen($buf) + 1;
	
			print($buf);
		}

		$this->text .= "</TABLE>";
		$this->text .= "</BODY>";
		//$this->text .= "</HTML>";
	}

	function abs_paging_height($height_string)
	{
		$height = (int)$height_string;
		if ( strstr($height_string, "%" ) )
		{
			$height = (int)
				( $this->page_height * $height_string ) / 100;
		}

		return $height;
	}

	function abs_paging_width($width_string)
	{
		$width = (int)$width_string;
		if ( strstr($width_string, "%" ) )
		{
			$width = (int)
				( $this->page_width * $width_string ) / 100;
		}

		return $width;
	}

	function format_column_header(& $column_item)
	{

		if ( $this->body_display != "show" ||
							!get_checkbox_value("target_show_body"))
			return;

		if ( !$this->show_column_header($column_item) )
				return;

		$padstring = $column_item->derive_attribute( "column_title",  $column_item->query_name);
		$padstring = str_replace("_", " ", $padstring);
		$padstring = ucwords(strtolower($padstring));
		$padstring = sw_translate($padstring);

		$cw = $column_item->derive_attribute(
			"ColumnWidthHTML",  "");

		$just = $column_item->derive_attribute( "justify",  "left");

		if ( $cw )
			$this->tag_embed($padstring, "TD", "swRepColHdr", 'align="'.$just.'" width="'.$cw.'"');
		else
			$this->tag_embed($padstring, "TD", "swRepColHdr", 'align="'.$just.'"');
	}

	function tag_embed($output, $tag_type, $class="", $extra="")
	{
			$str = '<'.$tag_type;
			if ( $class ) $str .= ' class="'.$class.'"';
			if ( $extra ) $str .= ' '.$extra.'">';
			$this->text .= $str;
			$this->text .= $output;
			$this->text .= '</'.$tag_type.'>';
	}

	function format_column(& $column_item)
	{
		if ( $this->body_display != "show" ||
							!get_checkbox_value("target_show_body"))
			return;

		if ( !$this->show_column_header($column_item) )
				return;

		$padstring =& $column_item->column_value;

		$just = $column_item->derive_attribute(
			"justify",  "left");

		$wd = $column_item->get_attribute("ColumnWidthHTML");
		if ( !$wd )
		{
			//$this->text .= '<TD>';
			$this->text .= '<TD align="'.$just.'">';
			$this->text .= utf8_encode($padstring);
			$this->text .= "</TD>";
		}
		else
		{
			//$wd = $this->abs_paging_width($wd);
			$this->text .= '<TD align="'.$just.'" width="'.$wd.'">';
			$this->text .= utf8_encode($padstring);
			$this->text .= "</TD>";
		}
	}

	function format_format($in_value)
	{
		switch($in_value)
		{
			case "blankline" :
				//$this->text .= "<TR><TD><br></TD></TR>";
				break;

			case "solidline" :
				$this->text .= '<TR><TD colspan="10"><hr width="100%" size="2"/></TD>';
				break;

			case "newpage" :
				$this->text .= '</TABLE><br><TABLE width="100%" class="noborder">';
				break;

			default :
				$this->text .= "<TR><TD>Unknown Format $in_value</TD></TR>";
				break;
				
		}	
	}

	function format_headers()
	{
		if ( $this->body_display != "show" ||
							!get_checkbox_value("target_show_body"))
			return;
		$this->text .="<tr class='liste_titre'>";
		foreach ( $this->query->display_order_set["column"] as $w )
			$this->format_column_header($w);
		$this->text .="</tr>";
	}

	function format_group_header_start()
	{
		$this->text .= "<TR class=swRepDatRow>";
		$this->text .= "<TD class=swRepDatVal colspan='20'>";
		$this->text .= '<TABLE class="swRepGrpHdrBox" cellspacing="0">';
	}

	function format_group_header(&$col)
	{
		$this->text .= '<TR class="swRepGrpHdrRow">';
		$this->text .= '<TD class="swRepGrpHdrLbl">';
		$qn = get_query_column($col->query_name, $this->query->columns ) ;
		$padstring = $qn->column_value;
		$tempstring = str_replace("_", " ", $col->query_name);
		$tempstring = ucwords(strtolower($tempstring));
		$this->text .= sw_translate($col->derive_attribute("column_title",  $tempstring));
		$this->text .= "</TD>";
		$this->text .= '<TD class="swRepGrpHdrDat">';
		$this->text .= "$padstring";
		$this->text .= "</TD>";
		$this->text .= "</TR>";
	}

	function format_group_header_end()
	{
		$this->text .= "</TABLE>";
		$this->text .= "</TD>";
		$this->text .= "</TR>";
	}

	function begin_line()
	{
		if ( $this->body_display != "show" ||
							!get_checkbox_value("target_show_body"))
			return;
		$this->text .= '<TR class="swRepResultLine">';
	}

	function plot_graph(&$graph)
	{
		$this->graph_session_placeholder++;
		$graph->width_actual = check_for_default("GraphWidth", $graph->width);
		$graph->height_actual = check_for_default("GraphHeight", $graph->height);
		$graph->title_actual = reportico_assignment::reportico_meta_sql_criteria($this->query, $graph->title, true);
		$graph->xtitle_actual = reportico_assignment::reportico_meta_sql_criteria($this->query, $graph->xtitle, true);
		$graph->ytitle_actual = reportico_assignment::reportico_meta_sql_criteria($this->query, $graph->ytitle, true);
		$url_string = $graph->generate_url_params($this->graph_session_placeholder);
		$this->text .= '<TR>';
		$this->text .= '<TD align="center" class="swRepResultGraph" width = "80%" colspan="60">';
		if ( $url_string )
		{
			$this->text .= $url_string;
		}
		$this->text .= '</TD>';
		$this->text .= '</TR>';
	}
	function format_column_trailer(&$trailer_col, &$value_col, $trailer_first=false)
	{
		if ( $this->body_display != "show" ||
							!get_checkbox_value("target_show_body"))
			return;

		if ( $value_col ) $alineacion = $value_col->get_attribute("justify");
		else $alineacion= "right";
		if ( $trailer_first )
			$this->text .= '<TD class="swRepGrpTlrDat1st" align="'.$alineacion.'">';
		else
			$this->text .= '<TD class="swRepGrpTlrDat" align="'.$alineacion.'">';
		if ( $value_col )
		{
			$group_label = $value_col->get_attribute("group_trailer_label" );
			if ( !$group_label )
				$group_label = $value_col->get_attribute("column_title" );
			if ( !$group_label )
			{
				$group_label = $value_col->query_name;
				$group_label = str_replace("_", " ", $group_label);
				$group_label = ucwords(strtolower($group_label));
			}
			$group_label = sw_translate($group_label);
			$padstring = $value_col->old_column_value;
			if ( $group_label == "BLANK" )
				$this->text .= $padstring;
			else
				$this->text .= $group_label.":".$padstring;
		}
		else
			$this->text .= "&nbsp";
		$this->text .= "</TD>";
	}

	function format_group_trailer_start($first=false)
	{
		if ( $first )
			$this->text .= '<TR class="swRepGrpTlrRow1st">';
		else
			$this->text .= '<TR class="swRepGrpTlrRow">';
	}

	function format_group_trailer_end()
	{
		//$this->text .= "</TABLE>";
		//$this->text .= "</TD>";
		$this->text .= "</TR>";
	}


	function end_line()
	{
		if ( $this->body_display != "show" ||
							!get_checkbox_value("target_show_body"))
			return;
		$this->text .= "</TR>";
	}

	function each_line($val)
	{
		reportico_report::each_line($val);

		if ( $this->page_line_count == 1 )
		{
			//$this->text .="<tr class='swPrpCritLine'>";
			//foreach ( $this->columns as $col )
				//$this->format_column_header($col);
			//$this->text .="</tr>";
		}

		$this->begin_line();
		//foreach ( $this->columns as $col )
		if ( $this->body_display == "show" &&
							get_checkbox_value("target_show_body"))
			foreach ( $this->query->display_order_set["column"] as $col )
				$this->format_column($col);
		$this->end_line();

		//if ( $y < $this->abs_bottom_margin )
		//{
			//$this->finish_page();
			//$this->begin_page();
		//}


	}

	function page_template()
	{
		$this->debug("Page Template");
	}

	function begin_page()
	{
		reportico_report::begin_page();

		$this->debug("HTML Begin Page\n");


		$title = $this->query->derive_attribute("ReportTitle", "Unknown");
		$this->text .= '<H1 class="titre">'.sw_translate($title).'</H1>';
		$forward = session_request_item('forward_url_get_parameters', '');
		if ( $forward )
			$forward .= "&";
		$this->text .= '<div align="right"><a class="swLinkMenu" href="'.session_request_item('linkbaseurl', SW_USESELF).'?'.$forward.'execute_mode=PREPARE&session_name='.session_id().'">'.sw_translate(SW_MESSAGE_BACK).'</a></div>';
		
		// Alternate lines
		$this->text .= '<script type="text/javascript">
		$(function() {
		$("table tr:nth-child(even)").addClass("impair");
		});
		</script>';
		
		$this->text .= '<TABLE width="100%" class="noborder">';
		//$this->text .= '<COLGROUP>';
		//foreach ( $this->columns as $col )
			//$this->text .= '<COL width="0*">';
		//$this->text .= '</COLGROUP>';
		//$this->text .= '<TBODY>';

		//$this->text .= "<FONT name='Arial'>";
	}

	function before_format_criteria_selection()
	{
		$this->text .= "<TR class=swRepDatRow>";
		$this->text .= "<TD class=swRepDatVal colspan='20'>";
		$this->text .= '<TABLE class="swRepGrpHdrBox" cellspacing="0">';
	}

	function format_criteria_selection($label, $value)
	{
		$this->text .= '<TR class="swRepGrpHdrRow">';
		$this->text .= '<TD class="swRepGrpHdrLbl">';
		$this->text .= $label;
		$this->text .= "</TD>";
		$this->text .= '<TD class="swRepGrpHdrDat">';
		$this->text .= $value;
		$this->text .= "</TD>";
		$this->text .= "</TR>";

	}

	function after_format_criteria_selection()
	{
		$this->text .= "</TABLE>";
		$this->text .= "</TD>";
		$this->text .= "</TR>";
	}


	function finish_page()
	{
		$this->debug("HTML Finish Page");
		//pdf_end_page($this->document);
	}

	function publish()
	{
		reportico_report::publish();
		$this->debug("Publish HTML");
	}

	function format_page_header(&$header)
	{
		$just = strtolower($header->get_attribute("justify"));

		$this->text .= "<TR>";
		$this->text .= '<TD colspan="10" justify="'.$just.'">';
		$this->text .=($header->text);
		$this->text .= "</TD>";
		$this->text .= "</TR>";

		return;
	}

	function format_page_footer(&$header)
	{
		$just = strtolower($header->get_attribute("justify"));

		$this->text .= "<TR>";
		$this->text .= '<TD colspan="10" justify="'.$just.'">';
		$this->text .=($header->text);
		$this->text .= "</TD>";
		$this->text .= "</TR>";

		return;
	}


}

// -----------------------------------------------------------------------------
// Class reportico_report_csv
// -----------------------------------------------------------------------------
class reportico_report_csv extends reportico_report
{
	var	$abs_top_margin;
	var	$abs_bottom_margin;
	var	$abs_left_margin;
	var	$abs_right_margin;
	var $cvs_file;
	
	function reportico_report_csv ()
	{
	}

	function start ()
	{
		reportico_report::start();

		$this->debug("Excel Start **");

		$this->page_line_count = 0;

		// Start the web page
	}

	function finish ()
	{
		reportico_report::finish();
		$this->debug("Excel End **");


		//if ( $this->line_count <= 1 )
			//echo("No Records found<br>");

		if ( $this->report_file )
		{
			$this->debug("Saved to $this->report_file");
		}
		else
		{
			$this->debug("No csv file specified !!!");
			$buf = "";
			$len = strlen($buf) + 1;
	
			print($buf);
		}

	}

	function format_column_header(& $column_item)
	{

		if ( !$this->show_column_header($column_item) )
				return;

		$padstring = $column_item->derive_attribute( "column_title",  $column_item->query_name);
		$padstring = str_replace("_", " ", $padstring);
		$padstring = ucwords(strtolower($padstring));
		$padstring = sw_translate($padstring);

		$this->cvs_file .='"'.$padstring.'"'.",";
		//echo '"'.$padstring.'"'.",";
	}

	function format_column(& $column_item)
	{
		if ( !$this->show_column_header($column_item) )
				return;

		$padstring =& $column_item->column_value;
		// Dont allow HTML values in CSV output
		if ( preg_match ( "/^<.*>/", $padstring ) )
			$padstring = "";
		$this->cvs_file .='"'.$padstring.'"'.",";
		//echo '"'.$padstring.'"'.",";
	}

	function each_line($val)
	{
		reportico_report::each_line($val);

		// Excel requires group headers are printed as the first columns in the spreadsheet against
		// the detail. 
                foreach ( $this->query->groups as $name => $group)
		{
			if ( count($group->headers) > 0  )
			foreach ($group->headers as $gphk => $col )
			{
				$qn = get_query_column($col->query_name, $this->query->columns ) ;
				$padstring = $qn->column_value;
				$this->cvs_file .="\"".$padstring."\"";
				$this->cvs_file .=",";
				//echo "\"".$padstring."\"";
				//echo ",";
			}
		}
				

		//foreach ( $this->columns as $col )
		foreach ( $this->query->display_order_set["column"] as $col )
	  	{
			$this->format_column($col);
       		}
       	$this->cvs_file .="\n";
		//echo "\n";

	}

	function page_template()
	{
		$this->debug("Page Template");
	}

	function begin_page()
	{
		reportico_report::begin_page();

		//ob_clean();	
		/*header("Content-type: application/octet-stream");

		if ( get_request_item("target_attachment", "" ) )
		{
			header("Content-Disposition: attachment; filename=csvfile.csv");
		}
		else
		{
			header("Content-Disposition: inline; filename=csvfile.csv");
		}
		header("Pragma: no-cache");
		header("Expires: 0");*/

		$this->debug("Excel Begin Page\n");
		$this->cvs_file='"'."$this->reporttitle".'"';
		$this->cvs_file .="\n";
		//echo '"'."$this->reporttitle".'"';
		//echo "\n";

	}

	function format_criteria_selection($label, $value)
	{
		$this->cvs_file .="\"".$label."\"";
		$this->cvs_file .=",";
		$this->cvs_file .="\"".$value."\"";
		$this->cvs_file .="\n";
		/*echo "\"".$label."\"";
		echo ",";
		echo "\"".$value."\"";
		echo "\n";*/
	}

	function after_format_criteria_selection()
	{
		$this->cvs_file .="\n";
		//echo "\n";
	}

	function finish_page()
	{
		$this->debug("Excel Finish Page");
		//pdf_end_page($this->document);
				
			$file_temp = tempnam("/tmp", "DolRep");

			$gestor = fopen($file_temp, "w");
			fwrite($gestor, $this->cvs_file);
			fclose($gestor);
			$url=dol_buildpath("/reports/download.php", 1)."?file=".$file_temp."&cvs=1";
			print "<meta http-equiv='refresh' content='0;url=".$url."'>"; 
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			
			} else {
				die;
			}
	}

	function format_headers()
	{
		// Excel requires group headers are printed as the first columns in the spreadsheet against
		// the detail. 
                foreach ( $this->query->groups as $name => $group)
		{
			for ($i = 0; $i < count($group->headers); $i++ )
			{
				$col =& $group->headers[$i];
				$qn = get_query_column($col->query_name, $this->query->columns ) ;
				$tempstring = str_replace("_", " ", $col->query_name);
				$tempstring = ucwords(strtolower($tempstring));
				$this->cvs_file .="\"".sw_translate($col->derive_attribute("column_title",  $tempstring))."\"";
				$this->cvs_file .=",";
				/*echo "\"".sw_translate($col->derive_attribute("column_title",  $tempstring))."\"";
				echo ",";*/
			}
		}
				
		foreach ( $this->query->display_order_set["column"] as $w )
			$this->format_column_header($w);
			$this->cvs_file .="\n";
		//echo "\n";
	}

	function format_group_header(&$col)
	{
		
		// Excel requires group headers are printed as the first columns in the spreadsheet against
		// the detail. 
		return;

		$qn = get_query_column($col->query_name, $this->query->columns ) ;
		$padstring = $qn->column_value;
		$tempstring = str_replace("_", " ", $col->query_name);
		$tempstring = ucwords(strtolower($tempstring));
		
		$this->cvs_file .=sw_translate($col->derive_attribute("column_title",  $tempstring));
		$this->cvs_file .=": ";
		$this->cvs_file .= "$padstring";
		$this->cvs_file .= "\n";
		
		/*echo sw_translate($col->derive_attribute("column_title",  $tempstring));
		echo ": ";
		echo "$padstring";
		echo "\n";*/
	}


	function begin_line()
	{
		return;
	}

	function format_column_trailer_before_line()
	{
		// Excel requires group headers are printed as the first columns in the spreadsheet against
		// the detail. 
                foreach ( $this->query->groups as $name => $group)
		{
			for ($i = 0; $i < count($group->headers); $i++ )
			{
				$this->cvs_file .=",";
				//echo ",";
			}
		}
	}

	function format_column_trailer(&$trailer_col, &$value_col, $trailer_first = false)
	{
		if ( $value_col )
		{
			$group_label = $value_col->get_attribute("group_trailer_label" );
			if ( !$group_label )
				$group_label = $value_col->get_attribute("column_title" );
			if ( !$group_label )
			{
				$group_label = $value_col->query_name;
				$group_label = str_replace("_", " ", $group_label);
				$group_label = ucwords(strtolower($group_label));
			}
			$group_label = sw_translate($group_label);
			$padstring = $value_col->old_column_value;
			$this->cvs_file .=$group_label.":".$padstring;
			//echo $group_label.":".$padstring;
		}
		$this->cvs_file .=",";
		//echo ",";
	}

	function end_line()
	{
		$this->cvs_file .= "\n";
		//echo "\n";
	}

	function publish()
	{
		reportico_report::publish();
		$this->debug("Publish Excel");
	}
}
?>
