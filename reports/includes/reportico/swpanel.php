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
 * This module provides functionality for reading and writing
 * xml reporting. 
 * It also controls browser output through Smarty templating class
 * for the different report modes MENU, PREPARE, DESIGN and
 * EXECUTE
 *
 * @link http://www.reportico.org/
 * @copyright 2010-2011 Peter Deed
 * @author Peter Deed <info@reportico.org>
 * @package Reportico
 * @version $Id: swpanel.php,v 1.13 2011-09-22 21:57:30 peter Exp $
 */


/* $Id $ */

/**
 * Class reportico_panel
 *
 * Class for storing the hierarchy of content that will be
 * displayed through the browser when running Reportico
 * 
 */
class reportico_panel
{
	var $panel_type;
	var $query = NULL;
	var $visible = false;
	var $pre_text = "";
	var $body_text = "";
	var $post_text = "";
	var $full_text = "";
	var $program = "";
	var $panels = array();
	var $smarty = false;

	function reportico_panel(&$in_query, $in_type)
	{
		$this->query = &$in_query;
		$this->panel_type = $in_type;
	}

	function set_smarty(&$in_smarty)
	{
		$this->smarty = &$in_smarty;
	}

	function set_menu_item($in_program, $in_text)
	{
		$this->program = $in_program;
		$this->text = $in_text;

		$cp = new reportico_panel($this->query, "MENUITEM");
		$cp->visible = true;
		$this->panels[] =& $cp;
		$cp->program = $in_program;
		$cp->text = $in_text;
	
	}

	function set_project_item($in_program, $in_text)
	{
		$this->program = $in_program;
		$this->text = $in_text;

		$cp = new reportico_panel($this->query, "PROJECTITEM");
		$cp->visible = true;
		$this->panels[] =& $cp;
		$cp->program = $in_program;
		$cp->text = $in_text;
	
	}

	function set_visibility($in_visibility)
	{
		$this->visible = $in_visibility;
	}

	function add_panel(&$in_panel)
	{
		$in_panel->set_smarty($this->smarty);
		$this->panels[] = &$in_panel;
	}

	function draw_smarty($send_to_browser = false)
	{
		$text = "";
		if ( !$this->visible ) 
			return;

		$this->pre_text = $this->pre_draw_smarty();

		// Now draw any panels owned by this panel
		foreach ( $this->panels as $k => $panel )
		{
			$panelref =& $this->panels[$k];
			$this->body_text .= $panelref->draw_smarty();
		}

		$this->post_text = $this->post_draw_smarty();
		$this->full_text = $this->pre_text.$this->body_text.$this->post_text;
		return  $this->full_text;
	}


	function pre_draw_smarty()
	{
		$text = "";
		switch($this->panel_type)
		{
			case "LOGIN":
				$this->smarty->assign('SHOW_LOGIN', true);
				break;

			case "LOGOUT":
				if ( !SW_DB_CONNECT_FROM_CONFIG )
				{
					$this->smarty->assign('SHOW_LOGOUT', true);
				}
				break;

			case "MAINTAIN":
				$text .= $this->query->xmlin->xml2html($this->query->xmlin->data);
				break;
			
			case "BODY":
				$this->smarty->assign('EMBEDDED_REPORT',  $this->query->embedded_report);
				break;

			case "MAIN":
				break;

			case "TITLE":
				$reporttitle = sw_translate($this->query->derive_attribute("ReportTitle", "Set Report Title"));
				$this->smarty->assign('TITLE', $reporttitle);
	
				$submit_self = session_request_item('linkbaseurl', SW_USESELF);
				$forward = session_request_item('forward_url_get_parameters', '');
				if ( $forward )
					$submit_self .= "?".$forward;
				//$this->smarty->assign('SCRIPT_SELF',  session_request_item('linkbaseurl', SW_USESELF));
				$this->smarty->assign('SCRIPT_SELF',  $submit_self);
				break;

			case "CRITERIA":
				$this->smarty->assign('SHOW_CRITERIA', true);
				break;

			case "CRITERIA_FORM":
				$dispcrit = array();
				$ct = 0;
				// Build Select Column List
				$this->query->expand_col = false;
				foreach ( $this->query->lookup_queries as $k => $col )
				{
					if ( $col->criteria_type )
					{
						if ( array_key_exists("EXPAND_".$col->query_name, $_REQUEST) )
							$this->query->expand_col =& $this->query->lookup_queries[$col->query_name];
		
						if ( array_key_exists("EXPANDCLEAR_".$col->query_name, $_REQUEST) )
							$this->query->expand_col =& $this->query->lookup_queries[$col->query_name];

						if ( array_key_exists("EXPANDSELECTALL_".$col->query_name, $_REQUEST) )
							$this->query->expand_col =& $this->query->lookup_queries[$col->query_name];

						if ( array_key_exists("EXPANDSEARCH_".$col->query_name, $_REQUEST) )
							$this->query->expand_col =& $this->query->lookup_queries[$col->query_name];

						$crititle = "";
						if ( $tooltip = $col->derive_attribute("tooltip", false) )
						{
							$title = $col->derive_attribute("column_title", $col->query_name);
							$crittitle = '<a HREF="" onMouseOver="return overlib(\''.$tooltip.
										'\',STICKY,CAPTION,\''.$title.
										'\',DELAY,400);" onMouseOut="nd();" onclick="return false;">'.
										$title.'</A>';
						}
						else
							$crittitle = $col->derive_attribute("column_title", $col->query_name);

						$critsel = $col->format_form_column();
						$critexp = false;

						if ( $col->expand_display && $col->expand_display != "NOINPUT" )
							$critexp = true;

						$dispcrit[] = array (
									"name" => $col->query_name,
									"title" => sw_translate($crittitle),
									"entry" => $critsel,
									"expand" => $critexp
									);
					}
					$this->smarty->assign("CRITERIA_ITEMS", $dispcrit);
				}
				break;

			case "CRITERIA_EXPAND":
				// Expand Cell Table
				$this->smarty->assign("SHOW_EXPANDED", false);

				if ( $this->query->expand_col )
				{
					$this->smarty->assign("SHOW_EXPANDED", true);
					$this->smarty->assign("EXPANDED_ITEM", $this->query->expand_col->query_name);
					$this->smarty->assign("EXPANDED_SEARCH_VALUE", false);
					$title = $this->query->expand_col->derive_attribute("column_title", $this->query->expand_col->query_name);
					$this->smarty->assign("EXPANDED_TITLE", sw_translate($title));


					// Only use then expand value if Search was press
					$expval="";
					if ( $this->query->expand_col->submitted('MANUAL_'.$this->query->expand_col->query_name) )
					{
							$tmpval=$_REQUEST['MANUAL_'.$this->query->expand_col->query_name];
							if ( strlen($tmpval) > 1 && substr($tmpval, 0, 1) == "?" )
									$expval = substr($tmpval, 1);
							
					}
					if ( $this->query->expand_col->submitted('EXPANDSEARCH_'.$this->query->expand_col->query_name) )
						if ( array_key_exists("expand_value", $_REQUEST) )
						{
							$expval=$_REQUEST["expand_value"];
						}
					$this->smarty->assign("EXPANDED_SEARCH_VALUE", $expval);

					$text .= $this->query->expand_col->expand_template();
				}
				else
				{
					if ( !($desc = sw_translate_report_desc($this->query->xmloutfile)) )
						$desc = $this->query->derive_attribute("ReportDescription", false); 
					$this->smarty->debug = true;
					$this->smarty->assign("REPORT_DESCRIPTION", $desc);
				}
				break;

			case "USERINFO":
				$this->smarty->assign('DB_LOGGEDON', true);
				if ( !SW_DB_CONNECT_FROM_CONFIG )
				{
					$this->smarty->assign('DBUSER', $this->query->datasource->user_name);
				}
				break;

			case "RUNMODE":
				
				if ( $this->query->execute_mode == "MAINTAIN" )
					$this->smarty->assign('SHOW_MODE_MAINTAIN_BOX', true);
				else
					$this->smarty->assign('SHOW_DESIGN_BUTTON', true);

				$create_report_url = $this->query->create_report_url;
				$configure_project_url = $this->query->configure_project_url;
				$forward = session_request_item('forward_url_get_parameters', '');
				if ( $forward )
				{
					$configure_project_url .= "&".$forward;
					$create_report_url .= "&".$forward;
				}
				$this->smarty->assign('CONFIGURE_PROJECT_URL', $configure_project_url);
				$this->smarty->assign('CREATE_REPORT_URL', $create_report_url);

				break;

			case "MENUBUTTON":
				$menu_url = $this->query->menu_url;
				$forward = session_request_item('forward_url_get_parameters', '');
				if ( $forward )
					$menu_url .= "&".$forward;
				$this->smarty->assign('MAIN_MENU_URL', $menu_url);

				$admin_menu_url = $this->query->admin_menu_url;
				$forward = session_request_item('forward_url_get_parameters', '');
				if ( $forward )
					$admin_menu_url .= "&".$forward;
				$this->smarty->assign('ADMIN_MENU_URL', $admin_menu_url);
				break;

			case "MENU":
				break;

			case "PROJECTITEM":
				$dr = dirname(session_request_item('linkbaseurl', SW_USESELF));
				if ( $this->text != ".." && $this->text != "admin" )
				{
					$forward = session_request_item('forward_url_get_parameters', '');
					if ( $forward )
						$forward .= "&";
						
					$this->query->projectitems[] = array (
						"label" => $this->text,
						"url" => session_request_item('linkbaseurl', SW_USESELF)."?".$forward."execute_mode=MENU&project=".$this->program."&amp;session_name=".session_id()
							);
				}
				break;

			case "MENUITEM":
				$dr = dirname(session_request_item('linkbaseurl', SW_USESELF));

				$forward = session_request_item('forward_url_get_parameters', '');
				if ( $forward )
					$forward .= "&";
						
				$this->query->menuitems[] = array (
						"label" => $this->text,
						"url" => session_request_item('linkbaseurl', SW_USESELF)."?".$forward."execute_mode=PREPARE&xmlin=".$this->program."&amp;session_name=".session_id()
							);
				break;

			case "TOPMENU":
				$this->smarty->assign('SHOW_TOPMENU', true);
				break;

			case "DESTINATION":

				$this->smarty->assign('SHOW_OUTPUT', true);

				if ( defined("SW_ALLOW_OUTPUT" ) && !SW_ALLOW_OUTPUT )
					$this->smarty->assign('SHOW_OUTPUT', false);

				$op = session_request_item("target_format", "HTML");

				$output_types = array (
							"HTML" => "",
							"PDF" => "",
							"CSV" => ""
							);
				$output_types[$op] = "checked";
				$noutput_types = array();
				foreach ( $output_types as $val )
					$noutput_types[] = $val;
				$this->smarty->assign('OUTPUT_TYPES', $noutput_types );

				$attach = get_request_item("target_attachment", "1", $this->query->first_criteria_selection );
				if ( $attach )
					$attach = "checked";
				$this->smarty->assign("OUTPUT_ATTACH", $attach );

				$this->smarty->assign("OUTPUT_SHOW_SHOWGRAPH", false );
				if ( ( $this->query->allow_debug && SW_ALLOW_DEBUG ) )
				{
					$this->smarty->assign("OUTPUT_SHOW_DEBUG", true );
					$debug_mode = get_request_item("debug_mode", "0", $this->query->first_criteria_selection );
					$this->smarty->assign("DEBUG_NONE", "" );
					$this->smarty->assign("DEBUG_LOW", "" );
					$this->smarty->assign("DEBUG_MEDIUM", "" );
					$this->smarty->assign("DEBUG_HIGH", "" );
					switch ( $debug_mode )
					{
						case 1:
							$this->smarty->assign("DEBUG_LOW", "selected" );
							break;
						case 2:
							$this->smarty->assign("DEBUG_MEDIUM", "selected" );
							break;
						case 3:
							$this->smarty->assign("DEBUG_HIGH", "selected" );
							break;
						default:
							$this->smarty->assign("DEBUG_NONE", "selected" );
					}
						
					if ( $debug_mode )
						$debug_mode = "checked";
					$this->smarty->assign("OUTPUT_DEBUG", $debug_mode );
				}

				$checked = "";
				if ( $this->query->first_criteria_selection && get_default("SHOWCRITERIA" ) )
					$checked="checked";
				if (  get_request_item("target_show_criteria") )
					$checked="checked";

				$this->smarty->assign("OUTPUT_SHOWCRITERIA", $checked );

				$checked="";
				if (  $this->query->get_attribute("bodyDisplay") )
					$checked="checked";


				if (  !get_request_item("target_show_body") && !$this->query->first_criteria_selection )
					$checked="";

				$this->smarty->assign("OUTPUT_SHOWDET", $checked );

				$this->smarty->assign("OUTPUT_SHOW_SHOWGRAPH", false );
				if ( count($this->query->graphs) > 0 )
				{
					$checked="";
					if (  $this->query->get_attribute("graphDisplay") )
						$checked="checked";
					if (  !get_request_item("target_show_graph") && !$this->query->first_criteria_selection )
						$checked="";

					$this->smarty->assign("OUTPUT_SHOW_SHOWGRAPH", true );
					$this->smarty->assign("OUTPUT_SHOWDET", $checked );
				}
				break;

			case "STATUS":

				$msg = "";

				if ( $this->query->status_message )
					$this->smarty->assign('STATUSMSG', $this->query->status_message );

				global $g_system_debug;
				if ( !$g_system_debug )
					$g_system_debug = array();
				foreach ( $g_system_debug as $val )
				{

					$msg .= "<hr>".$val["dbgarea"]." - ".$val["dbgstr"]."\n";
				}

				if ( $msg )
				{
					$msg = "<B>".sw_translate(SW_MESSAGE_DEBUGLIST)."</B>".$msg;
				}

				$this->smarty->assign('STATUSMSG', $msg );
				break;

			case "ERROR":
				$msg = "";

				global $g_system_errors;
				$lastval = false;
				$duptypect = 0;
				if ( !$g_system_errors )
					$g_system_errors = array();
				foreach ( $g_system_errors as $val )
				{

					if ( $val["errno"] == E_USER_ERROR ||  $val["errno"] == E_USER_WARNING )
					{
						$msg .= "<HR>";
  						if ( $val["errarea"] ) $msg .= $val["errarea"]." - ";
						if ( $val["errtype"] ) $msg .= $val["errtype"].": ";
						$msg .= $val["errstr"];
						
						$msg .= $val["errsource"];
						$msg .= "\n";
					}
					else
					{
						// Dont keep repeating Assignment errors
						$msg .= "<HR>";
						//if ( $val["errct"] > 1 ) $msg .= $val["errct"]." occurrences of ";
						// PPP Change $msg .= $val["errarea"]." - ".$val["errtype"].": ".$val["errstr"].
						//" at line ".$val["errline"]." in ".$val["errfile"].$val["errsource"];
						//"\n";
						if ( $val["errarea"] ) $msg .= $val["errarea"]." - ";
						if ( $val["errtype"] ) $msg .= $val["errtype"].": ";
						$msg .= $val["errstr"];
						//$msg .= " at line ".$val["errline"]." in ".$val["errfile"].$val["errsource"];
						"\n";
						$duptypect = 0;
					}
					$lastval = $val;
				}
				if ( $duptypect > 0 )
					$msg .= "<BR>$duptypect more errors like this<BR>";

				if ( $msg )
				{
					$msg = "<B>".sw_translate(SW_MESSAGE_ERRORLIST).":</B>".$msg;
				}

				$this->smarty->assign('ERRORMSG', $msg );
				$_SESSION['latestRequest'] = "";
				break;
		}
		return $text;
	}

	function post_draw_smarty()
	{
		$text = "";
		switch($this->panel_type)
		{
			case "LOGIN":
			case "LOGOUT":
			case "USERINFO":
			case "DESTINATION":
				break;

			case "BODY":
				break;

			case "CRITERIA":
				break;
				
			case "CRITERIA_FORM":
				break;

			case "CRITERIA_EXPAND":
				break;

			case "MENU":
				$this->smarty->assign('MENU_ITEMS', $this->query->menuitems);
				break;

			case "ADMIN":
				$this->smarty->assign('DOCDIR', find_best_location_in_include_path( "doc" ));
				$this->smarty->assign('PROJECT_ITEMS', $this->query->projectitems);
				break;

			case "MENUBUTTON":
				break;

			case "MENUITEM":
				break;

			case "PROJECTITEM":
				break;

			case "TOPMENU":
				break;

			case "MAIN":
				break;
		}
		return $text;
	}

}

/**
 * Class reportico_xml_reader
 *
 * responsible for loading, parsing and organising
 * Reportico report XML definition files
 */
class reportico_xml_reader
{
	var $query;
	var $parser;
	var $records;
	var $record;
	var $value;
	var $current_field = '';
	var $xmltag_type;
	var $ends_record;
	var $queries = array();
	var $data = array();
	var $element_stack = array();
	var $query_stack = array();
	var $column_stack = array();
	var $action_stack = array();
	var $datasource_stack = array();
	var $current_element;
	var $current_datasource;
	var $current_query;
	var $current_action;
	var $current_column;
	var $current_object;
	var $element_count = 0;
	var $level_ct = 0;
	var $oldid = "";
	var $id = "";
	var $last_element = "";
	var $in_column_section = false;
	var $current_criteria_name = false;
	var $show_level = false;
	var	$show_area = false;
	var	$search_tag = false;
	var	$search_response = false;
	var	$element_counts = array();

  	function reportico_xml_reader (&$query, $filename, $xmlstring = false, $search_tag = false ) 
	{

    	$this->query =& $query;
    	$this->parser = xml_parser_create();
	$this->search_tag = $search_tag;
	$this->current_element =& $this->data; 

    	$this->field_display = array (
					"Expression" => array ( "EditMode" => "SAFE" ),
					"Condition" => array ( "EditMode" => "SAFE" ),
					"GroupHeaderColumn" => array ( "Type" => "QUERYCOLUMNS"),
					"GroupTrailerDisplayColumn" => array ( "Type" => "QUERYCOLUMNS"),
					"GroupTrailerValueColumn" => array ( "Type" => "QUERYCOLUMNS"),
					"ColumnType" => array ( "Type" => "HIDE"),
					"ColumnLength" => array ( "Type" => "HIDE"),
					"ColumnName" => array ( "Type" => "HIDE"),
					"QueryName" => array ( "Type" => "HIDE"),
					"Name" => array ( "Type" => "TEXTFIELD"),
					"QueryTableName" => array ( "Type" => "HIDE", "HelpPage" => "criteria"),
					"QueryColumnName" => array ( "Title" => "Main Query Column", "HelpPage" => "criteria"),
					"TableName" => array ( "Type" => "HIDE"),
					"TableSql" => array ( "Type" => "HIDE"),
					"WhereSql" => array ( "Type" => "HIDE"),
					"GroupSql" => array ( "Type" => "HIDE"),
					"RowSelection" => array ( "Type" => "HIDE"),
					"ReportTitle" => array ( "Title" => "Report Title"),
					"LinkFrom" => array ( "Title" => "Link From", "Type" => "CRITERIA"),
					"LinkTo" => array ( "Title" => "Link To", "Type" => "CRITERIA"),
					"AssignName" => array ( "Title" => "Assign To Existing Column", "Type" => "QUERYCOLUMNS"),
					"AssignNameNew" => array ( "Title" => "Assign To New Column"),
					"AssignAggType" => array ( "Title" => "Aggregate Type", "Type" => "AGGREGATETYPES"),
					"AssignAggCol" => array ( "Title" => "Aggregate Column", "Type" => "QUERYCOLUMNS"),
					"AssignAggGroup" => array ( "Title" => "Grouped By", "Type" => "QUERYCOLUMNSOPTIONAL"),
					"AssignGraphicBlobCol" => array ( "Title" => "Column Containing Graphic"),
					"AssignGraphicBlobTab" => array ( "Title" => "Table Containing Graphic"),
					"AssignGraphicBlobMatch" => array ( "Title" => "Column to Match Report Graphic"),
					"AssignGraphicWidth" => array ( "Title" => "Report Graphic Width"),
					"AssignGraphicReportCol" => array ( "Title" => "Graphic Report Column", "Type" => "QUERYCOLUMNS"),
					"DrilldownReport" => array ( "Title" => "Drilldown Report", "Type" => "REPORTLIST"),
					"DrilldownColumn" => array ( "Title" => "Drilldown To", "Type" => "QUERYCOLUMNSOPTIONAL"),
					"GroupName" => array ( "Title" => "Group On Column", "Type" => "GROUPCOLUMNS"),
					"GroupName" => array ( "Title" => "Group On Column", "Type" => "GROUPCOLUMNS"),
					"GraphColumn" => array ( "Title" => "Group Column", "Type" => "QUERYGROUPS"),
					"GraphHeight" => array ( "Title" => "Graph Height" ),
					"GraphWidth" => array ( "Title" => "Graph Width" ),
					"GraphColor" => array ( "Title" => "Graph Color" ),
					"GraphWidthPDF" => array ( "Title" => "Graph Width (PDF)" ),
					"GraphHeightPDF" => array ( "Title" => "Graph Height (PDF)" ),
					"XTitle" => array ( "Title" => "X Axis Title" ),
					"YTitle" => array ( "Title" => "Y Axis Title" ),
					"GridPosition" => array ( "Title" => "Grid Position" ),
					"PlotStyle" => array ( "Title" => "Plot Style" ),
					"LineColor" => array ( "Title" => "Line Color" ),
					"DataType" => array ( "Title" => "Data Type", "Type" => "HIDE" ),
					"FillColor" => array ( "Title" => "Fill Color" ),
					"XGridColor" => array ( "Title" => "X-Grid Color" ),
					"YGridColor" => array ( "Title" => "Y-Grid Color" ),
					"TitleFontSize" => array ( "Title" => "Title Font Size" ),
					"XTickInterval" => array ( "Title" => "X Tick Interval" ),
					"YTickInterval" => array ( "Title" => "Y Tick Interval" ),
					"XTickLabelInterval" => array ( "Title" => "X Tick Label Interval" ),
					"YTickLabelInterval" => array ( "Title" => "Y Tick Label Interval" ),
					"XTitleFontSize" => array ( "Title" => "X Title Font Size" ),
					"YTitleFontSize" => array ( "Title" => "Y Title Font Size" ),
					"MarginColor" => array ( "Title" => "Margin Color" ),
					"MarginLeft" => array ( "Title" => "Margin Left" ),
					"MarginRight" => array ( "Title" => "Margin Right" ),
					"MarginTop" => array ( "Title" => "Margin Top" ),
					"MarginBottom" => array ( "Title" => "Margin Bottom" ),
					"TitleColor" => array ( "Title" => "Title Color" ),
					"XAxisColor" => array ( "Title" => "X Axis Color" ),
					"YAxisColor" => array ( "Title" => "Y Axis Color" ),
					"XAxisFontColor" => array ( "Title" => "X Axis Font Color" ),
					"YAxisFontColor" => array ( "Title" => "Y Axis Font Color" ),
					"XAxisFontSize" => array ( "Title" => "X Axis Font Size" ),
					"YAxisFontSize" => array ( "Title" => "Y Axis Font Size" ),
					"XTitleColor" => array ( "Title" => "X Title Color" ),
					"YTitleColor" => array ( "Title" => "Y Title Color" ),
					"PlotColumn" => array ( "Title" => "Column To Plot", "Type" => "QUERYCOLUMNS"),
					"XLabelColumn" => array ( "Title" => "Column for X Labels", "Type" => "QUERYCOLUMNS"),
					//"YLabelColumn" => array ( "Title" => "Column for Y Labels", "Type" => "HIDE"),
					"ReturnColumn" => array ( "Title" => "Return Column", "HelpPage" => "criteria", "Type" => "QUERYCOLUMNS"),
					"MatchColumn" => array ( "Title" => "Match Column", "HelpPage" => "criteria", "Type" => "QUERYCOLUMNS"),
					"DisplayColumn" => array ( "Title" => "Display Column", "HelpPage" => "criteria", "Type" => "QUERYCOLUMNS"),
					"OverviewColumn" => array ( "Title" => "Summary Column", "HelpPage" => "criteria", "Type" => "QUERYCOLUMNS"),
					"content_type" => array ( "Title" => "Content Type", "Type" => "DROPDOWN", 
								"Values" => array("plain", "graphic")),
					"PreExecuteCode" => array ( 
						"Title" => "Custom Source Code",
						"Type" => "TEXTBOX",
				       		"EditMode" => "SAFE"),
					"ReportDescription" => array ( 
						"Title" => "Report Description",
						"Type" => "TEXTBOX" ),
					"SQLText" => array ( "Type" => "TEXTBOX", "EditMode" => "SAFE" ),
					"QuerySql" => array ( "Title" => "SQL Query<P>(* notation<br>or wildcards<br>not allowed<br>in column<br>selection)", "Type" => "TEXTBOX" ),
					"Password" => array ( "Type" => "PASSWORD" ),
					"PageSize" => array ( "Title" => "Page Size (PDF)", "Type" => "DROPDOWN", 
									"Values" => array(".DEFAULT","B5", "A6", "A5", "A4", "A3", "A2", "A1", 
											"US-Letter","US-Legal","US-Ledger") ),
					"PageOrientation" => array ( "Title" => "Orientation (PDF)", "Type" => "DROPDOWN", 
									"Values" => array(".DEFAULT","Portrait", "Landscape") ),
					"TopMargin" => array ( "Title" => "Top Margin (PDF)" ),
					"BottomMargin" => array ( "Title" => "Bottom Margin (PDF)" ),
					"RightMargin" => array ( "Title" => "Right Margin (PDF)" ),
					"LeftMargin" => array ( "Title" => "Left Margin (PDF)" ),
					"pdfFont" => array ( "Title" => "Font (PDF)" ),
					"OrderNumber" => array ( "Title" => "Order Number" ),
					"ReportJustify" => array ( "Type" => "HIDE" ),
					"BeforeGroupHeader" => array ( "Title" => "Before Group Header", "Type" => "DROPDOWN", 
												"Values" => array("blankline", "solidline", "newpage") ),
					"AfterGroupHeader" => array ( "Title" => "After Group Header", "Type" => "DROPDOWN", 
												"Values" => array("blankline", "solidline", "newpage") ),
					"BeforeGroupTrailer" => array ( "Title" => "Before Group Trailer", "Type" => "DROPDOWN", 
												"Values" => array("blankline", "solidline", "newpage") ),
					"AfterGroupTrailer" => array ( "Title" => "After Group Trailer", "Type" => "DROPDOWN", 
												"Values" => array("blankline", "solidline", "newpage") ),
					"bodyDisplay" => array ( "Title" => "Display Details", "Type" => "DROPDOWN", 
												"Values" => array("hide", "show") ),
					"graphDisplay" => array ( "Title" => "Display Graph", "Type" => "DROPDOWN", 
												"Values" => array("hide", "show") ),
					"GroupHeaderColumn" => array ( "Title" => "Group Header Column", "Type" => "QUERYCOLUMNS" ),
					"GroupTrailerDisplayColumn" => array ( "Title" => "Group Trailer Display Column", "Type" => "QUERYCOLUMNS" ),
					"GroupTrailerValueColumn" => array ( "Title" => "Group Trailer Value Column", "Type" => "QUERYCOLUMNS" ),
					"ColumnStartPDF" => array ( "Title" => "Column Start (PDF)" ),
					"ColumnWidthPDF" => array ( "Title" => "Column Width (PDF)" ),
					"ColumnWidthHTML" => array ( "Title" => "Column Width (HTML)" ),
					"column_title" => array ( "Title" => "Column Title" ),
					"tooltip" => array ( "Type" => "HIDE", "Title" => "Tool Tip" ),
					"group_header_label" => array ( "Title" => "Group Header Label" ),
					"group_trailer_label" => array ( "Title" => "Group Trailer Label" ),
					"group_header_label_xpos" => array ( "Title" => "Group Header Label Start" ),
					"group_header_data_xpos" => array ( "Title" => "Group Header Value Start" ),
					"ReportJustify" => array ( "Type" => "HIDE" ),
					"pdfFontSize" => array ( "Title" => "Font Size (PDF)" ),
					"GridPosition" => array ( "Title" => "Grid Position", "Type" => "DROPDOWN", 
												"Values" => array(".DEFAULT", "back", "front") ),
					"XGridDisplay" => array ( "Title" => "X-Grid Style", "Type" => "DROPDOWN", 
												"Values" => array(".DEFAULT", "none", "major", "all") ),
					"YGridDisplay" => array ( "Title" => "Y-Grid Style", "Type" => "DROPDOWN", 
												"Values" => array(".DEFAULT", "none", "major", "all") ),
					"PlotType" => array ( "Title" => "Plot Style", "Type" => "DROPDOWN", 
												"Values" => array("BAR", "LINE", "PIE", "PIE3D") ),
					"CriteriaDefaults" => array ( "Title" => "Defaults", "HelpPage" => "criteria" ),
					"CriteriaList" => array ( "Title" => "List Values", "HelpPage" => "criteria" ),
					"CriteriaType" => array ( "Title" => "Criteria Type", "HelpPage" => "criteria", "Type" => "DROPDOWN", 
					"Values" => array("TEXTFIELD", "LOOKUP", "DATE", "DATERANGE", "LIST" ) ),
					"Use" => array ( "Title" => "Use", "HelpPage" => "criteria", "Type" => "DROPDOWN", 
                                        "Values" => array("DATA-FILTER","SHOW/HIDE", "SHOW/HIDE-and-GROUPBY") ),
					"CriteriaDisplay" => array ( "Title" => "Criteria Display", "Type" => "DROPDOWN", "HelpPage" => "criteria", 
												"Values" => array("NOINPUT", "TEXTFIELD", "DROPDOWN", "MULTI", "CHECKBOX", "RADIO", "DMYFIELD", "MDYFIELD", "YMDFIELD" ) ),
					"ExpandDisplay" => array ( "Title" => "Expand Display", "Type" => "DROPDOWN", "HelpPage" => "criteria", 
												"Values" => array("NOINPUT", "TEXTFIELD", "DROPDOWN", "MULTI", "CHECKBOX", "RADIO", "DMYFIELD", "MDYFIELD", "YMDFIELD" ) ),
					"DatabaseType" => array ( "Title" => "Datasource Type", "Type" => "DROPDOWN", 
												"Values" => array("informix", "mysql", "sqlite-2", "sqlite-3", "none" ) ),
					"justify" => array ( "Title" => "Justification", "Type" => "DROPDOWN", 
												"Values" => array("left", "center", "right") ),
					"column_display" => array ( "Title" => "Show or Hide?", "Type" => "DROPDOWN", 
												"Values" => array("show", "hide") ),
					"TitleFont" => array ( "Title" => "Title Font", "Type" => "DROPDOWN", 
								"Values" => array(".DEFAULT", "Font 1", "Font 2", "Font 3", "Arial", "Times", "Verdana", "Courier", "Book", "Comic", "Script" ) ),
					"TitleFontStyle" => array ( "Title" => "Title Font Style", "Type" => "DROPDOWN", 
								"Values" => array(".DEFAULT", "Normal", "Bold", "Italic", "Bold+Italic") ),
					"XTitleFont" => array ( "Title" => "X Title Font", "Type" => "DROPDOWN", 
								"Values" => array(".DEFAULT", "Font 1", "Font 2", "Font 3", "Arial", "Times", "Verdana", "Courier", "Book", "Comic", "Script" ) ),
					"YTitleFont" => array ( "Title" => "Y Title Font", "Type" => "DROPDOWN", 
								"Values" => array(".DEFAULT", "Font 1", "Font 2", "Font 3", "Arial", "Times", "Verdana", "Courier", "Book", "Comic", "Script" ) ),
					"XAxisFont" => array ( "Title" => "X Label Font", "Type" => "DROPDOWN", 
								"Values" => array(".DEFAULT", "Font 1", "Font 2", "Font 3", "Arial", "Times", "Verdana", "Courier", "Book", "Comic", "Script" ) ),
					"YAxisFont" => array ( "Title" => "Y Label Font", "Type" => "DROPDOWN", 
								"Values" => array(".DEFAULT", "Font 1", "Font 2", "Font 3", "Arial", "Times", "Verdana", "Courier", "Book", "Comic", "Script" ) ),
					"XAxisFontStyle" => array ( "Title" => "X Label Style", "Type" => "DROPDOWN", 
								"Values" => array(".DEFAULT", "Normal", "Bold", "Italic", "Bold+Italic") ),
					"YAxisFontStyle" => array ( "Title" => "Y Label Style", "Type" => "DROPDOWN", 
								"Values" => array(".DEFAULT", "Normal", "Bold", "Italic", "Bold+Italic") ),
					"XTitleFontStyle" => array ( "Title" => "X Title Font Style", "Type" => "DROPDOWN", 
								"Values" => array(".DEFAULT", "Normal", "Bold", "Italic", "Bold+Italic") ),
					"YTitleFontStyle" => array ( "Title" => "Y Title Font Style", "Type" => "DROPDOWN", 
								"Values" => array(".DEFAULT", "Normal", "Bold", "Italic", "Bold+Italic") )
					);

    	xml_set_object($this->parser, $this);
    	xml_set_element_handler($this->parser, 'start_element', 'end_element');
    	xml_set_character_data_handler($this->parser, 'cdata');
    	xml_parser_set_option($this->parser,XML_OPTION_CASE_FOLDING, false);

    	// 1 = single field, 2 = array field, 3 = record container
    	$this->xmltag_type = array('Assignment' => 2,
                              'QueryColumn' => 2,
                              'OrderColumn' => 2,
                              'CriteriaItem' => 2,
                              'GroupHeader' => 2,
                              'GroupTrailer' => 2,
                              'PreSQL' => 2,
                              'PageHeader' => 2,
                              'Graph' => 2,
                              'Plot' => 2,
                              'PageFooter' => 2,
                              'DisplayOrder' => 2,
                              'Group' => 2,
                              'CriteriaLink' => 2,
                              'QueryColumns' => 3,
                              'Format' => 3,
                              'Datasource' => 3,
                              'SourceConnection' => 3,
                              'EntryForm' => 3,
                              'CogQuery' => 3,
                              'ReportQuery' => 3,
                              'CogModule' => 3,
                              'Report' => 3,
                              'Query' => 3,
                              'SQL' => 3,
                              'Criteria' => 3,
                              'Assignments' => 3,
                              'GroupHeaders' => 3,
                              'GroupTrailers' => 3,
                              'OrderColumns' => 3,
                              'PageHeaders' => 3,
                              'PageFooters' => 3,
                              'PreSQLS' => 3,
                              'DisplayOrders' => 3,
                              'Output' => 3,
                              'Graphs' => 3,
                              'Plots' => 3,
                              'Groups' => 3,
                              'CriteriaLinks' => 3,
                              'XXXX' => 1);
		$this->ends_record = array('book' => true);

		$x = false;
		if ( $xmlstring )
    		$x =& $xmlstring;
		else
		{
			if ( $filename )
			{
				global $g_project, $langs;
				$readfile = $this->query->reports_path."/".$langs->getDefaultLang()."/". $filename;
				$adminfile = $this->query->admin_path."/".$filename;

				if ( !is_file($readfile) )
				{
					find_file_to_include($readfile, $readfile);
				}
				if ( is_file ( $readfile )  )
					$readfile = $readfile;
				else 
				{
					$readfile = $this->query->reports_path."/". $filename;
					
					if ( !is_file($readfile) )
					{
						find_file_to_include($readfile, $readfile);
					}
					if ( is_file ( $readfile )  )
						$readfile = $readfile;
					else 
					{
					
						if ( !is_file($adminfile) )
						{
							find_file_to_include($adminfile, $adminfile);
							if ( is_file ( $readfile )  )
								$readfile = $readfile;
						}
						else
							$readfile = $adminfile;
					}
					
				}
			}
		}
		
		if ( $readfile )
		{
			$x = join("", file($readfile));
		}
		else
			trigger_error ( "Report Definition File  ". $this->query->reports_path."/".$filename." Not Found", E_USER_ERROR );

		if ( $x )
		{
			xml_parse($this->parser, $x);
			xml_parser_free($this->parser);
		}

		//var_dump($this->data);
  	}

	function start_element ($p, $element, &$attributes) 
	{
		//$element = strtolower($element);

		$this->gotdata = false;
		$this->value = "";

		if ( !array_key_exists($element, $this->xmltag_type ) )
			$tp = 1;
		else
			$tp = $this->xmltag_type[$element];


		switch ( $tp )
		{
			case 1:
				$this->current_element[$element] = "";
				break;

			case 2:
				$ar = array();
				$this->current_element[] =& $ar;
				$this->element_count = array_push($this->element_stack, 
					count($this->current_element)-1);
				$this->current_element =& $ar; 
				break;

			case 3:
				$ar = array();
				$this->current_element[$element] =& $ar;
				$this->element_count = array_push($this->element_stack, 
					$element);
				$this->current_element =& $ar; 
				break;

		}

  	}

	function end_element ($p, $element) 
  	{
    		//$element = strtolower($element);
		if ( !array_key_exists($element, $this->xmltag_type ) )
			$tp = 1;
		else
			$tp = $this->xmltag_type[$element];


		if ( $tp == 1 )
		{
			$this->current_element[$element] = $this->value;

			if ( $element == $this->search_tag )
			{
				$this->search_response = $this->value;
			}
		}
		else
		{
			array_pop($this->element_stack);	
			$this->element_count--;

			$ct = 0;
			$this->current_element =& $this->data;
			foreach ( $this->element_stack as $v )
			{
				$this->current_element =& $this->current_element[$v];
			}
		}


 	}

	function cdata ($p, $text) 
  	{
     		$this->value .= $text;
		$this->gotdata = true;
  	}

	function & get_array_element ( &$in_arr, $element ) 
	{
		$retval = false;
		if ( array_key_exists($element, $in_arr ) )
		{
			return $in_arr[$element];
		}
		else
			return $retval;
	}

	function countArrayElements (&$ar) 
	{
			$ct = 0;
			foreach($ar as $k => $el)
			{
					if ( is_array($el) )
					{
						$ct = $ct + $this->countArrayElements($el);
					}
					else
					{
						$ct++;
					}
			}
			return $ct;
	}

	function &analyse_form_item ($tag) 
	{
			$anal = array();
			$qr = false;
			$cl = false;
			$cr = false;
			$grph = false;
			$gr = false;
			$grn = false;
			$grno = false;
			$nm = false;
			$cn = false;
			$item = false;
			$actar = false;

			$anal["name"] = $tag;

			// To analyse the item, chop the item into 4 character fields
			// and then analyse each item
			$ptr = 0;
			$len = strlen($tag);
			//echo "analyse $tag<br>";
			//echo "Bit  = ";
			$last = false;
			while ( $ptr < $len )
			{
				$bit = substr($tag, $ptr, 4 );
				//echo $bit."/";
				$ptr += 4;

				if ( is_numeric($bit) && (int)$bit  == 0 )
					$bit = "ZERO";

				switch ( $bit )
				{
					case "main":
						break;

					case "outp":
						$item =& $this->query;
						$qr =& $this->query;
						break;

					case "quer":
						$item =& $this->query;
						$qr =& $this->query;
						break;

					case "data":
						$item =& $this->query->ds;
						$action = $bit;
						break;

					case "qury":
						if ( !$cr )
							$qr =& $this->query;
						else
							$qr =& $cr->lookup_query;
						$item =& $qr;
						break;

					case "conn":
						$item =& $this->query->datasource;
						$action = $bit;
						break;

					case "crit":
						$item =& $this->query;
						$action = $bit;
						break;

					case "gtrl":
					case "ghdr":
					case "grps":
					case "pgft":
					case "clnk":
					case "plot":
					case "grph":
					case "pghd":
					case "assg":
					case "sqlt":
					case "form":
					case "dord":
					case "psql":
					case "ords":
					case "qcol":
						$action = $bit;
						break;

					case "ZERO":
						$bit = 0;

					default:
						if ( is_numeric($bit) )
						{
							$bit = (int)$bit;
							if ( $last == "crit" )
							{
								$ct = 0;
								foreach ( $qr->lookup_queries as $k => $v )
								{
									if ( $ct == $bit ) 
									{
										$cr =& $qr->lookup_queries[$k];
										break;
									}
									$ct++;
								}
								$item =& $cr;
							}
							if ( $last == "grph" )
							{
								$ct = 0;
								foreach ( $qr->graphs as $k => $v )
								{
									if ( $ct == $bit ) 
									{
										$grph =& $qr->graphs[$k];
										break;
									}
									$ct++;
								}
								$item =& $cl;
							}
							if ( $last == "qcol" )
							{
								$ct = 0;
								foreach ( $qr->columns as $k => $v )
								{
									if ( $ct == $bit ) 
									{
										$cl =& $qr->columns[$k];
										$cn = $cl->query_name;
										//$cn = $k;
										break;
									}
									$ct++;
								}
								$item =& $cl;
							}
							
							if ( $last == "grps" )
							{
								$ct = 0;
								foreach ( $qr->groups as $k => $v )
								{
									if ( $ct == $bit ) 
									{
										$gr =& $qr->groups[$k];
										$grn = $v->group_name;
										$grno = $k;
										break;
									}
									$ct++;
								}
								$item =& $gr;
							}
							if ( $last == "clnk" )
								$item =& $qr->criteria_links[$bit];
							if ( $last == "ords" )
							{
								//$item =& $qr->order_set["itemno"][$bit];
							}
							if ( $last == "dord" )
								$item =& $qr->display_order_set["itemno"][$bit];
							if ( $last == "assg" )
								$item =& $qr->assignment[$bit];
							if ( $last == "pgft" )
								$item =& $qr->page_footers[$bit];
							if ( $last == "plot" )
							{
								//echo $qr->graphs;
								//var_dump ($qr->graphs);
								//echo $qr->graphs->plots;
								//$item =& $qr->graphs->plots[$bit];
							}
							if ( $last == "grph" )
								$item =& $qr->graphs[$bit];
							if ( $last == "pghd" )
								$item =& $qr->page_headers[$bit];
							if ( $last == "ghdr" )
								$item =& $gr->headers[$bit];
							if ( $last == "gtrl" )
								$item =& $gr->trailers[$bit];
							$nm = (int)$bit;
						}
				}

				$last = $bit;
			}

			$anal["graph"] =& $grph;
			$anal["quer"] =& $qr;
			$anal["crit"] =& $cr;
			$anal["column"] =& $cl;
			$anal["colname"] =& $cn;
			$anal["item"] =& $item;
			$anal["action"] =& $action;
			$anal["group"] =& $gr;
			$anal["groupname"] =& $grn;
			$anal["groupno"] =& $grno;
			$anal["number"] =$nm;
			$anal["array"] =& $actar;
			return $anal;
			
	}

	function add_maintain_fields ($match) 
	{
			$ret = false;

			$match_key = "/^set_".$match."_(.*)/";
			$updates = array();
			foreach ( $_REQUEST as $k => $v )
			{
				if ( preg_match ( $match_key, $k, $matches ) )
				{
					$updates[$matches[1]] = stripslashes($v);
				}
			}

			$anal = $this->analyse_form_item($match);

			// Based on results of analysis, decide what element we are updating ( column, query,
			// datasource etc ) 
			switch ( $anal["action"] )
			{
				case "form":
					// Delete not applicable to "Format" option
					break;

				case "assg":
					$qr =& $anal["quer"];
					$qr->add_assignment ( "Column", "Expression", "" );
					break;

				case "pghd":
					$qr =& $anal["quer"];
					$qr->create_page_header("Name", 1, "Header Text" );
					break;

				case "grph":
					$qr =& $anal["quer"];
					$qr->create_graph();
					break;

				case "plot":
					$qr =& $anal["graph"];
					$qr->create_plot("");
					break;

				case "pgft":
					$qr =& $anal["quer"];
					$qr->create_page_footer("Name", 1, "Header Text" );
					break;

				case "clnk":
					$cr =& $anal["crit"];
					$this->query->set_criteria_link ( 
						$cr->query_name, $cr->query_name, 
								"Enter Clause" );
					break;

				case "pgft":
					array_splice($this->query->page_footers, $anal["number"], 1);
					break;

				case "grps":
					$qr =& $anal["quer"];
					$ak = array_keys( $this->query->columns);
					$qr->create_group ( $this->query->columns[0]->query_name );
					break;

				case "psql":
					$qr =& $anal["quer"];
					$qr->add_pre_sql ( "-- Enter SQL" );
					break;

				case "crit":
					$qr =& $anal["quer"];
					$qu = new reportico_query();
					$qr->set_criteria_lookup(
								"CriteriaName", $qu, "", "" );
					break;

				case "qcol":
					$qr =& $anal["quer"];
					$qr->create_criteria_column ( "NewColumn", "", "",
			   						"char", 0, "###", false	);
					break;

				case "ghdr":
					$updateitem =& $anal["item"];
					$gn = $anal["groupname"];
					if ( reset ( $this->query->columns ) )
					{
						$cn = current( $this->query->columns );
						$this->query->create_group_header( $gn, $cn->query_name );
					}
					break;

				case "gtrl":
					$updateitem =& $anal["item"];
					$gn = $anal["groupname"];
					if ( reset ( $this->query->columns ) )
					{
						$cn =& current( $this->query->columns );
						$this->query->create_group_trailer
								( $gn, $cn->query_name, $cn->query_name );
					}
					break;


				case "conn":
					// Delete not applicable to Connection action
					break;
			}

			return $ret;
	}

	function moveup_maintain_fields ($match) 
	{
			$ret = false;
			$anal = $this->analyse_form_item($match);

			// Based on results of analysis, decide what element we are updating ( column, query,
			// datasource etc ) 
			switch ( $anal["action"] )
			{
				case "form":
					// Delete not applicable to "Format" option
					break;

				case "assg":
					$updateitem =& $anal["item"];
					$qr =& $anal["quer"];
					$cut = array_splice($qr->assignment, $anal["number"], 1);
					array_splice($qr->assignment, $anal["number"] - 1, 0, $cut);
					break;

				case "dord":
					$updateitem =& $anal["item"];
					$cl = $anal["quer"]->display_order_set["column"][$anal["number"]]->query_name;
					$anal["quer"]->set_column_order ( $cl, $anal["number"], true );
					break;

				case "pgft":
					array_splice($this->query->page_footers, $anal["number"], 1);
					break;

				case "grps":
					$cut = array_splice($this->query->groups, $anal["number"], 1);
					array_splice($this->query->groups, $anal["number"] - 1, 0, $cut);
					break;

				case "clnk":
					$qr =& $anal["crit"]->lookup_query;
					array_splice($qr->criteria_links, $anal["number"], 1);
					break;

				case "psql":
					$cut = array_splice($this->query->pre_sql, $anal["number"], 1);
					array_splice($this->query->pre_sql, $anal["number"] - 1, 0, $cut);
					break;

				case "crit":
					$cut = array_splice($this->query->lookup_queries, $anal["number"], 1);
					array_splice($this->query->lookup_queries, $anal["number"] - 1, 0, $cut);
					break;

				case "qcol":
					$anal["quer"]->remove_column ( $anal["colname"] );
					break;

				case "ghdr":
					$cut = array_splice($this->query->groups[$anal["groupno"]]->headers, 
								$anal["number"], 1);
					array_splice($this->query->groups[$anal["groupno"]]->headers, 
								$anal["number"] - 1, 0, $cut);
					break;

				case "gtrl":
					$cut = array_splice($this->query->groups[$anal["groupno"]]->trailers, 
								$anal["number"], 1);
					array_splice($this->query->groups[$anal["groupno"]]->trailers, 
								$anal["number"] - 1, 0, $cut);
					break;

				case "ords":
					$qr =& $anal["quer"];
					array_splice($qr->order_set, $anal["number"], 1);
					break;

				case "grph":
					array_splice($this->query->graphs, $anal["number"], 1);
					break;

				case "plot":
					array_splice($anal["graph"]->plots, $anal["number"], 1);
					break;

				case "pghd":
					array_splice($this->query->page_headers, $anal["number"], 1);
					break;

				case "conn":
					// Delete not applicable to Connection action
					break;
			}

			return $ret;
	}

	function movedown_maintain_fields ($match) 
	{
			$ret = false;
			$match_key = "/^set_".$match."_(.*)/";

			$anal = $this->analyse_form_item($match);

			// Based on results of analysis, decide what element we are updating ( column, query,
			// datasource etc ) 
			switch ( $anal["action"] )
			{
				case "form":
					// Delete not applicable to "Format" option
					break;

				case "assg":
					$updateitem =& $anal["item"];
					$qr =& $anal["quer"];
					$cut = array_splice($qr->assignment, $anal["number"], 1);
					array_splice($qr->assignment, $anal["number"] + 1, 0, $cut);
					break;

				case "dord":
					$updateitem =& $anal["item"];
					$cl = $anal["quer"]->display_order_set["column"][$anal["number"]]->query_name;
					$anal["quer"]->set_column_order ( $cl, $anal["number"] + 2, false );
					break;

				case "pgft":
					break;

				case "grps":
					$cut = array_splice($this->query->groups, $anal["number"], 1);
					array_splice($this->query->groups, $anal["number"] + 1, 0, $cut);
					break;

				case "clnk":
					$qr =& $anal["crit"]->lookup_query;
					array_splice($qr->criteria_links, $anal["number"], 1);
					break;

				case "psql":
					array_splice($this->query->pre_sql, $anal["number"], 1);
					break;

				case "crit":
					$cut = array_splice($this->query->lookup_queries, $anal["number"], 1);
					array_splice($this->query->lookup_queries, $anal["number"] + 1, 0, $cut);
					break;

				case "qcol":
					$anal["quer"]->remove_column ( $anal["colname"] );
					break;

				case "ghdr":
					$cut = array_splice($this->query->groups[$anal["groupno"]]->headers, 
								$anal["number"], 1);
					array_splice($this->query->groups[$anal["groupno"]]->headers, 
																	$anal["number"] + 1, 0, $cut);
					break;

				case "gtrl":
					$cut = array_splice($this->query->groups[$anal["groupno"]]->trailers, 
								$anal["number"], 1);
					array_splice($this->query->groups[$anal["groupno"]]->trailers, 
								$anal["number"] + 1, 0, $cut);
					break;

				case "ords":
					$qr =& $anal["quer"];
					array_splice($qr->order_set, $anal["number"], 1);
					break;

				case "grph":
					array_splice($this->query->graphs, $anal["number"], 1);
					break;

				case "plot":
					array_splice($anal["graph"]->plots, $anal["number"], 1);
					break;

				case "pghd":
					array_splice($this->query->page_headers, $anal["number"], 1);
					break;

				case "conn":
					// Delete not applicable to Connection action
					break;
			}

			return $ret;
	}

	function delete_maintain_fields ($match) 
	{
			$ret = false;
			$match_key = "/^set_".$match."_(.*)/";
			$updates = array();
			foreach ( $_REQUEST as $k => $v )
			{
				if ( preg_match ( $match_key, $k, $matches ) )
				{
					$updates[$matches[1]] = stripslashes($v);
				}
			}

			$anal = $this->analyse_form_item($match);

			// Based on results of analysis, decide what element we are updating ( column, query,
			// datasource etc ) 
			switch ( $anal["action"] )
			{
				case "form":
					// Delete not applicable to "Format" option
					break;

				case "assg":
					$updateitem =& $anal["item"];
					$qr =& $anal["quer"];
					array_splice($qr->assignment, $anal["number"], 1);
					break;

				case "pgft":
					array_splice($this->query->page_footers, $anal["number"], 1);
					break;

				case "grps":
					array_splice($this->query->groups, $anal["number"], 1);
					break;

				case "clnk":
					$qr =& $anal["crit"]->lookup_query;
					array_splice($qr->criteria_links, $anal["number"], 1);
					break;

				case "psql":
					array_splice($this->query->pre_sql, $anal["number"], 1);
					break;

				case "crit":
					array_splice($this->query->lookup_queries, $anal["number"], 1);
					break;

				case "qcol":
					$anal["quer"]->remove_column ( $anal["colname"] );
					break;

				case "ghdr":
					$anal["quer"]->delete_group_header_by_number 
							( $anal["groupname"], $anal["number"] );
					break;

				case "gtrl":
					$updateitem =& $anal["item"];
					$anal["quer"]->delete_group_trailer_by_number 
							( $anal["groupname"], $anal["number"] );
					break;

				case "ords":
					$qr =& $anal["quer"];
					array_splice($qr->order_set, $anal["number"], 1);
					break;

				case "grph":
					array_splice($this->query->graphs, $anal["number"], 1);
					break;

				case "plot":
					array_splice($anal["graph"]->plots, $anal["number"], 1);
					break;

				case "pghd":
					array_splice($this->query->page_headers, $anal["number"], 1);
					break;

				case "conn":
					// Delete not applicable to Connection action
					break;
			}

			return $ret;
	}

	function change_array_keyname(&$in_array, $in_number, $in_key)
	{
		$nm = 0;
		foreach ( $in_array as $k => $v )
		{
			if ( $nm == $in_number )
			{
				$in_array[$in_key] = $v;
				array_splice($in_array, $nm, 1, $el );
				$in_array[$in_key] = $v;
				break;
			}
			$nm++;
		}
	}

	function update_maintain_fields ($match) 
	{
			$ret = false;
			$match_key = "/^set_".$match."_(.*)/";
			$updates = array();
			foreach ( $_REQUEST as $k => $v )
			{
				if ( preg_match ( $match_key, $k, $matches ) )
				{
					if ( $k == "set_mainquerform_PreExecuteCode" )
						$updates[$matches[1]] = ($v);
					else
						$updates[$matches[1]] = stripslashes($v);
				}
			}

			$anal = $this->analyse_form_item($match);

			// Based on results of analysis, decide what element we are updating ( column, query,
			// datasource etc ) 
			switch ( $anal["action"] )
			{
				case "sqlt":
					$qr =& $anal["quer"];
					$maintain_sql = $updates["QuerySql"];
					$sql = $updates["QuerySql"];
					if ( $qr->login_check(false) )
					{
						if ( $this->query->datasource->connect() )
						{
							if ( $this->query->test_query($sql) )
							{
								$p = new reportico_sql_parser($sql);
								$p->parse();
								$p->import_into_query($qr);
							}
							else
							{
								$p = new reportico_sql_parser($sql);
								$p->parse();
								$p->import_into_query($qr);
							}
						}
					}
					break;
							
				case "form":
					$updateitem =& $anal["item"];
					foreach ( $updates as $k => $v )
					{
						$updateitem->set_attribute($k, $v);
					}
					break;

				case "assg":
					if ( $updates["AssignAggType"] )
					{
							$aggtype = "";
							$aggcol = $updates["AssignAggCol"];
							$agggroup = $updates["AssignAggGroup"];
							switch ( $updates["AssignAggType"] )
							{
									case "SUM": $aggtype = "sum"; break;
									case "MIN": $aggtype = "min"; break;
									case "MAX": $aggtype = "max"; break;
									case "COUNT": $aggtype = "count"; $aggcol = ""; break;
									case "AVERAGE": $aggtype = "avg"; break;
									case "PREVIOUS": $aggtype = "old"; $aggcol = ""; break;
									case "SUM": $aggtype = "sum"; break;
							}
							if ( $agggroup && $aggcol )
								$updates["Expression"] =  $aggtype."({".$updates["AssignAggCol"]."},{". $updates["AssignAggGroup"]."})";
							else if ( $agggroup )
								$updates["Expression"] =  $aggtype."({".$updates["AssignAggCol"]."})";
							else 
								$updates["Expression"] =  $aggtype."()";
					}

					if ( $updates["AssignGraphicBlobCol"] && $updates["AssignGraphicBlobTab"] && $updates["AssignGraphicBlobMatch"])
					{
						$updates["Expression"] = 
							"imagequery(\"SELECT ".$updates["AssignGraphicBlobCol"].
								" FROM ".$updates["AssignGraphicBlobTab"].
								" WHERE ".$updates["AssignGraphicBlobMatch"]." ='\".{".$updates["AssignGraphicReportCol"]."}.\"'\",".$updates["AssignGraphicWidth"].")";
					}

					if ( $updates["DrilldownReport"] )
					{
							$this->query->drilldown_report = $updates["DrilldownReport"];
							$q = new reportico_query();
							global $g_project;
							$q->reports_path = "projects/".$g_project;
							$reader = new reportico_xml_reader($q, $updates["DrilldownReport"], false);
							$reader->xml2query();

							$startbit= "'<a target=\"_blank\" href=\"'".session_request_item('linkbaseurl', SW_USESELF)."?xmlin=".$updates["DrilldownReport"]."&execute_mode=EXECUTE&target_format=HTML&target_show_body=1&project=".$g_project;
							$midbit = "";
							$endbit = "\">Drill</a>'";
							foreach ( $q->lookup_queries as $k => $v )
							{
									
									$testdd = "DrilldownColumn_".$v->query_name;

									if ( array_key_exists($testdd, $updates ) )
									{
										if ( $updates[$testdd] )
										{
												$midbit .= "&MANUAL_".$v->query_name."='.{".$updates[$testdd]."}.'";
												if ( $v->criteria_type == "DATERANGE" )
													$midbit .= "&MANUAL_".$v->query_name."_FROMDATE='.{".$updates[$testdd]."}.'&".
													"MANUAL_".$v->query_name."_TODATE='.{".$updates[$testdd]."}.'";
										}
									}
							}
							unset($q);
							if ( $midbit )
								$updates["Expression"] = $startbit.$midbit.$endbit;
					}

					$updateitem =& $anal["item"];

					if ( $assignname = key_value_in_array($updates, "AssignNameNew") )
					{
						$found = false;
						foreach ( $anal["quer"]->columns as $querycol )
						{
							if (  $querycol->query_name == $assignname )
							{	
								$found = true;
							}
						}

						if ( !$found ) 
						{
							$anal["quer"]->create_query_column( $assignname, "", "", "", "",
										'####.###',
										false);
						}
			
						$updates["AssignName"] = $assignname;
					}
	

					$updateitem->reportico_assignment(
					$updates["AssignName"], $updates["Expression"], $updates["Condition"]);
					break;

				case "pgft":
					$updateitem =& $anal["item"];
					$updateitem->reportico_page_end(
							$updates["LineNumber"], $updates["FooterText"]);
					break;

				case "clnk":
					$qr =& $anal["crit"]->lookup_query;
					$this->query->set_criteria_link(
								$updates["LinkFrom"], $updates["LinkTo"],
										$updates["LinkClause"], $anal["number"]);
					break;

				case "psql":
					$nm = $anal["number"];
					$this->query->pre_sql[$nm] = $updates["SQLText"];
					break;

				case "grps":
					$updateitem =& $anal["item"];
					$nm = $anal["number"];
					$this->query->groups[$anal["number"]]->group_name =  $updates["GroupName"];
					$this->query->groups[$anal["number"]]->set_attribute("before_header",$updates["BeforeGroupHeader"]);
					$this->query->groups[$anal["number"]]->set_attribute("after_header",$updates["AfterGroupHeader"]);
					$this->query->groups[$anal["number"]]->set_attribute("before_trailer",$updates["BeforeGroupTrailer"]);
					$this->query->groups[$anal["number"]]->set_attribute("after_trailer",$updates["AfterGroupTrailer"]);
					$this->query->groups[$anal["number"]]->set_format("before_header",$updates["BeforeGroupHeader"]);
					$this->query->groups[$anal["number"]]->set_format("after_header",$updates["AfterGroupHeader"]);
					$this->query->groups[$anal["number"]]->set_format("before_trailer",$updates["BeforeGroupTrailer"]);
					$this->query->groups[$anal["number"]]->set_format("after_trailer",$updates["AfterGroupTrailer"]);
					break;

				case "crit":
					$updateitem =& $anal["item"];
					$qr =& $anal["quer"];

					$updateitem->set_attribute("column_title", $updates["Title"]);
					$nm = $anal["number"];
					$updateitem->query_name = $updates["Name"];

					if ( array_key_exists("QueryTableName", $updates) )
						$updateitem->table_name = $updates["QueryTableName"];
					else
						$updateitem->table_name = "";
					$updateitem->column_name = $updates["QueryColumnName"];
					$updateitem->_use = $updates["Use"];
					$updateitem->criteria_type = $updates["CriteriaType"];
					$updateitem->criteria_list = $updates["CriteriaList"];
					$updateitem->criteria_display = $updates["CriteriaDisplay"];
					$updateitem->expand_display = $updates["ExpandDisplay"];

					if ( array_key_exists("ReturnColumn", $updates) )
					{
						$updateitem->lookup_query->set_lookup_return($updates["ReturnColumn"]);
						$updateitem->lookup_query->set_lookup_display(
								$updates["DisplayColumn"], $updates["OverviewColumn"]);
						$updateitem->lookup_query->set_lookup_expand_match(
								$updates["MatchColumn"]);
					}
					$updateitem->set_criteria_defaults(
								$updates["CriteriaDefaults"]);
					$updateitem->set_criteria_list(
								$updates["CriteriaList"]);
					break;

				case "qcol":
					$cn = $anal["colname"];
					$anal["quer"]->remove_column ( "NewColumn" );
					$anal["quer"]->create_query_column( $updates["Name"], "", "", "", "",
										'####.###',
										false
								);
					break;


				case "ords":
					break;

				case "dord":
					$cl = $anal["quer"]->display_order_set["column"][$anal["number"]]->query_name;
					$pn = $anal["number"] + 1;
					if ( $pn > $updates["OrderNumber"] )
						$anal["quer"]->set_column_order ( $cl, $updates["OrderNumber"], true );
					else
						$anal["quer"]->set_column_order ( $cl, $updates["OrderNumber"], false );

					break;

				case "ghdr":
					$updateitem =& $anal["item"];
					$gr =& $anal["group"];
					$anal["quer"]->set_group_header_by_number 
							( $anal["groupname"], $anal["number"], $updates["GroupHeaderColumn"] );
					break;

				case "gtrl":
					$updateitem =& $anal["item"];
					$gr =& $anal["group"];
					$anal["quer"]->set_group_trailer_by_number 
							( $anal["groupname"], $anal["number"], 
										$updates["GroupTrailerDisplayColumn"],
										$updates["GroupTrailerValueColumn"]
							   	);
					break;

				case "plot":
					$graph =& $anal["graph"];
					$pl =& $graph->plots[$anal["number"]];
					$pl["name"] = $updates["PlotColumn"];
					$pl["type"] = $updates["PlotType"];
					$pl["fillcolor"] = $updates["FillColor"];
					$pl["linecolor"] = $updates["LineColor"];
					$pl["legend"] = $updates["Legend"];
					break;

				case "grph":
					$qr =& $anal["quer"];
					$updateitem =& $anal["item"];
					$graph = &$qr->graphs[$anal["number"]];

					if ( !array_key_exists("GraphColumn", $updates ) )
					{
						trigger_error ( "To add a graph you need to go to the Groups menu add a group on which to trigger graph. To add a graph at the end of the report, you need to add the group called REPORT_BODY and then select this as the Group Column", E_USER_ERROR );
					}
					else
						$graph->set_graph_column($updates["GraphColumn"]);
					$graph->set_graph_color($updates["GraphColor"]);
					$graph->set_grid($updates["GridPosition"],
							$updates["XGridDisplay"],$updates["XGridColor"],
							$updates["YGridDisplay"],$updates["YGridColor"]
							);
					$graph->set_title($updates["Title"]);
					$graph->set_xtitle($updates["XTitle"]);
					$graph->set_xlabel_column($updates["XLabelColumn"]);
					$graph->set_ytitle($updates["YTitle"]);
					$graph->set_width($updates["GraphWidth"]);
					$graph->set_height($updates["GraphHeight"]);
					$graph->set_width_pdf($updates["GraphWidthPDF"]);
					$graph->set_height_pdf($updates["GraphHeightPDF"]);
					$graph->set_title_font($updates["TitleFont"], $updates["TitleFontStyle"],
						$updates["TitleFontSize"], $updates["TitleColor"]);
					$graph->set_xtitle_font($updates["XTitleFont"], $updates["XTitleFontStyle"],
						$updates["XTitleFontSize"], $updates["XTitleColor"]);
					$graph->set_ytitle_font($updates["YTitleFont"], $updates["YTitleFontStyle"],
						$updates["YTitleFontSize"], $updates["YTitleColor"]);
					$graph->set_xaxis($updates["XTickInterval"],$updates["XTickLabelInterval"],$updates["XAxisColor"]);
					$graph->set_yaxis($updates["YTickInterval"],$updates["YTickLabelInterval"],$updates["YAxisColor"]);
					$graph->set_xaxis_font($updates["XAxisFont"], $updates["XAxisFontStyle"],
						$updates["XAxisFontSize"], $updates["XAxisFontColor"]);
					$graph->set_yaxis_font($updates["YAxisFont"], $updates["YAxisFontStyle"],
						$updates["YAxisFontSize"], $updates["YAxisFontColor"]);
					$graph->set_margin_color($updates["MarginColor"]);
					$graph->set_margins($updates["MarginLeft"], $updates["MarginRight"],
						$updates["MarginTop"], $updates["MarginBottom"]);
					break;

				case "pghd":
					$updateitem =& $anal["item"];
					$updateitem->reportico_page_end(
							$updates["LineNumber"], $updates["HeaderText"]);
					break;

				case "data":
					$this->query->source_type = $updates["SourceType"];
					break;

				case "conn":
					$updateitem =& $anal["item"];
					$updateitem->set_details($updates["DatabaseType"],
												$updates["HostName"],
												$updates["ServiceName"] );
					$updateitem->set_database($updates["DatabaseName"]);
					$updateitem->user_name = $updates["UserName"];
					$updateitem->disconnect();
					if ( !$updateitem->connect() )
					{
							$this->query->error_message = $updateitem->error_message;
					};
					break;
			}

			return $ret;
	}

	function get_matching_request_item ($match) 
	{
			$ret = false;
			foreach ( $_REQUEST as $k => $v )
			{
				if ( preg_match ( $match, $k ) )
				{
					return $k;
				}
			}
			return $ret;
	}

	// Processes the HTML get/post paramters passed through on the maintain screen
	function handle_user_entry () 
	{
		// First look for a parameter beginning "submit_". This will identify
		// What the user wanted to do. 

		$hide_area = false;
		$show_area = false;
		$maintain_sql = false;
		$xmlsavefile = false;
		if ( ( $k = $this->get_matching_request_item("/^submit_/") ) )
		{
			// Strip off "_submit"
			preg_match("/^submit_(.*)/", $k, $match);

			// Now we should be left with a field element and an action
			// Lets strip the two
			$match1 = preg_split('/_/', $match[0]);
			$fld = $match1[1];
			$action = $match1[2];

			switch ( $action )
			{
				case "ADD":
					// We have chosen to set a block of data so pass through Request set and see which
					// fields belong to this set and take appropriate action
					$this->add_maintain_fields($fld);
					$show_area = $fld;
					break; 

				case "DELETE":
					// We have chosen to set a block of data so pass through Request set and see which
					// fields belong to this set and take appropriate action
					$this->delete_maintain_fields($fld);
					$show_area = $fld;
					break; 

				case "MOVEUP":
					// We have chosen to set a block of data so pass through Request set and see which
					// fields belong to this set and take appropriate action
					$this->moveup_maintain_fields($fld);
					$show_area = $fld;
					break; 

				case "MOVEDOWN":
					// We have chosen to set a block of data so pass through Request set and see which
					// fields belong to this set and take appropriate action
					$this->movedown_maintain_fields($fld);
					$show_area = $fld;
					break; 

				case "SET":
					// We have chosen to set a block of data so pass through Request set and see which
					// fields belong to this set and take appropriate action
					$this->update_maintain_fields($fld);
					$show_area = $fld;
					break; 

				case "SAVE":
					$xmlsavefile = $this->query->xmloutfile;
					break; 

				case "HIDE":
					$hide_area = $fld;
					break; 

				case "SHOW":
					$show_area = $fld;
					break; 

				case "SQL":
					$show_area = $fld;
					if ( $fld == "mainquerqury" )
					{
						// Main Query SQL Generation.
						$sql = stripslashes($_REQUEST["mainquerqury_SQL"]);

						$maintain_sql = $sql;
						if ( $this->query->login_check() )
						{
							if ( $this->query->datasource->connect() )
							{
								if ( $this->query->test_query($sql) )
								{
									$p = new reportico_sql_parser($sql);
									$p->parse();
									$p->import_into_query($this->query);
								}
								else
								{
									$p = new reportico_sql_parser($sql);
									$p->parse();
									$p->import_into_query($this->query);
								}
							}
						}
					}
					else
					{
							// It's a lookup 
							if ( preg_match("/mainquercrit(.*)qury/", $fld, $match1 ) )
							{
								$lookup = (int)$match1[1];
								$lookup_char = $match1[1];
								
								// Access the relevant crtieria item ..
								$qc = false;
								$ak = array_keys($this->query->lookup_queries);
								if ( array_key_exists ( $lookup, $ak ))
								{
										$q = $this->query->lookup_queries[$ak[$lookup]]->lookup_query;
								}
								else
								{
										$q = new reportico_query();
								}

								// Parse the entered SQL
								$sqlparm = $fld."_SQL";
								$sql = $_REQUEST[$sqlparm];
								$q->maintain_sql = $sql;
								if ( $this->query->test_query($sql) )
								{
									$q = new reportico_query();
									$p = new reportico_sql_parser($sql);
									$p->parse();
									$p->import_into_query($q);
									$this->query->set_criteria_lookup($ak[$lookup], $q, "WHAT", "NOW");
								}
							}
						}
					
					break;
							
			}
		}

		// Now work out what the maintainance screen should be showing by analysing
		// whether user pressed a SHOW button a HIDE button or keeps a maintenance item
		// show by presence of a shown value 
		if ( !$show_area )
		{
			// User has not pressed SHOW_ button - this would have been picked up in previous submit
			// So look for longest shown item - this will allow us to draw the maintenace screen with
			// the correct item maximised
			foreach ( $_REQUEST as $k => $req )
			{
				if ( preg_match("/^shown_(.*)/", $k, $match ) )
				{
						$containee = "/^".$hide_area."/";
						$container = $match[1];
						if ( !preg_match ( $containee, $container ) )
						{
							if ( strlen ( $match[1] ) > strlen ( $show_area ) )
							{
								$show_area = $match[1];
							}
						}
				}
			}

		}

		if ( !$show_area )
			$show_area = "mainquer";


		$xmlout = new reportico_xml_writer($this->query);
		$xmlout->prepare_xml_data();

		// If Save option has been used then write data to the named file and
		// use this file as the defalt input for future queries
		if ( $xmlsavefile )
		{
			if ( $this->query->allow_maintain != "SAFE" && SW_ALLOW_MAINTAIN )
			{
				$xmlout->write_file($xmlsavefile);
				$_SESSION["xmlin"] = $xmlsavefile;
				unset($_SESSION["xmlintext"]);
			}
			else
				trigger_error ( "Running in SAFE mode. Report definitions may not be saved.", E_USER_ERROR );
		}

		$xml = $xmlout->get_xmldata();

		if ( $this->query->top_level_query )
		{
			$this->query->xmlintext = $xml;
		}

		$this->query->xmlin = new reportico_xml_reader($this->query, false, $xml);
		$this->query->xmlin->show_area = $show_area;
		$this->query->maintain_sql = false;
	}

	// Works out whether a maintenance item should be shown on the screen based on the value
	// of the show_area parameter which was derived from the HTTP Request Data
	function & draw_add_button ($in_tag, $in_value = false) 
	{
		$text = "";
		$text .= '<TD>';
		$text .= '<input class="swMntButton" type="submit" name="submit_'.$in_tag.'_ADD" value="Add">';
		$text .= '</TD>';
		return $text;
	}

	// Works out whether a maintenance item should be shown on the screen based on the value
	// of the show_area parameter which was derived from the HTTP Request Data
	function & draw_movedown_button ($in_tag, $in_value = false) 
	{
		$text = "";
		$text .= '<TD class="swMntUpDownButtonCell">';
		$text .= '<input class="swMntMoveDownButton" type="submit" name="submit_'.$in_tag.'_MOVEDOWN" value="">';
		$text .= '</TD>';
		return $text;
	}

	// Works out whether a maintenance item should be shown on the screen based on the value
	// of the show_area parameter which was derived from the HTTP Request Data
	function & draw_moveup_button ($in_tag, $in_value = false) 
	{
		$text = "";
		$text .= '<TD class="swMntUpDownButtonCell">';
		$text .= '<input class="swMntMoveUpButton" type="submit" name="submit_'.$in_tag.'_MOVEUP" value="">';
		$text .= '</TD>';
		return $text;
	}

	// Works out whether a maintenance item should be shown on the screen based on the value
	// of the show_area parameter which was derived from the HTTP Request Data
	function & draw_delete_button ($in_tag, $in_value = false) 
	{
		$text = "";
		$text .= '<TD class="swMntUpDownButtonCell">';
		$text .= '<input class="swMntDeleteButton" type="submit" name="submit_'.$in_tag.'_DELETE" value="">';
		$text .= '</TD>';
		return $text;
	}

	// Works out whether a maintenance item should be shown on the screen based on the value
	// of the show_area parameter which was derived from the HTTP Request Data
	function & draw_select_box ($in_tag, $in_array, $in_value = false) 
	{
		$text = "";
		$text .= '<select class="swPrpDropSelect" name="execute_mode">';
		$text .= '<OPTION selected label="MAINTAIN" value="MAINTAIN">Maintain</OPTION>';
		$text .= '<OPTION label="PREPARE" value="PREPARE">Prepare</OPTION>';
		$text .= '</SELECT>';
		return $text;
	}

	// Draws a tab menu item within a horizontal tab menu
	function & draw_show_hide_vtab_button ($in_tag, $in_value = false, 
			$in_moveup = false, $in_movedown = false, $in_delete = true) 
	{
		$text = "";
		if ( !$this->is_showing($in_tag ) )
		{
			$text .= "<TR>";
			$text .= '<TD class="swMntVertTabMenuCellUnsel">';
			$text .= '<input class="swMntVertTabMenuButUnsel" type="submit" name="submit_'.$in_tag."_SHOW".'" value="'.$in_value.'">';
			$text .= '</TD>';
			if ( $in_delete )
				$text .= $this->draw_delete_button ($in_tag) ;
			if ( $in_moveup )
				$text .= $this->draw_moveup_button ($in_tag) ;
			if ( $in_movedown )
				$text .= $this->draw_movedown_button ($in_tag) ;
			$text .= "</TR>";
		}
		else
		{
			$text .= "<TR>";
			$text .= '<TD  class="swMntVertTabMenuCellSel">';
			$text .= '<input class="swMntVertTabMenuButSel" type="submit" name="submit_'.$in_tag."_SHOW".'" value="'.$in_value.'">';
			$text .= '</TD>';
			if ( $in_delete )
				$text .= $this->draw_delete_button ($in_tag) ;
			if ( $in_moveup )
				$text .= $this->draw_moveup_button ($in_tag) ;
			if ( $in_movedown )
				$text .= $this->draw_movedown_button ($in_tag) ;
			$text .= "</TR>";
		}
		return $text;
		
	}

	// Draws a tab menu item within a horizontal tab menu
	function & draw_show_hide_tab_button ($in_tag, $in_value = false) 
	{
		$text = "";
		if ( !$this->is_showing($in_tag ) )
		{
			$text .= '<TD class="swMntTabMenuCellUnsel">';
			$text .= '<input class="swMntTabMenuButUnsel" type="submit" name="submit_'.$in_tag."_SHOW".'" value="'.$in_value.'">';
			$text .= '</TD>';
		}
		else
		{
			$text .= '<TD  class="swMntTabMenuCellSel">';
			$text .= '<input class="swMntTabMenuButSel" type="submit" name="submit_'.$in_tag."_SHOW".'" value="'.$in_value.'">';
			$text .= '</TD>';
		}
		return $text;
		
	}

	// Works out whether a maintenance item should be shown on the screen based on the value
	// of the show_area parameter which was derived from the HTTP Request Data
	function & draw_show_hide_button ($in_tag, $in_value = false) 
	{
		$text = "";

		if ( !$this->is_showing($in_tag ) )
		{
			$text .= '<TD>';
			//$text .= '<input class="swMntTabMenuButUnsel" type="submit" name="submit_'.$in_tag."_SHOW".'" value="'.$in_value.'">';
			$text .= '<input size="1" style="visibility:hidden" type="submit" name="unshown_'.$in_tag.'" value="">';
			$text .= '</TD>';
		}
		else
		{
			$text .= '<TD>';
			//$text .= '<input class="swMntTabMenuButSel" type="submit" name="submit_'.$in_tag."_SHOW".'" value="'.$in_value.'">';
			$text .= '<input size="1" style="visibility:hidden" type="submit" name="shown_'.$in_tag.'" value="">';
			$text .= '</TD>';
		}
		return $text;
		
	}

	// Works out whether a maintenance item should be shown on the screen based on the value
	// of the show_area parameter which was derived from the HTTP Request Data
	//
	// also we want to expand selected Query Column Types immediately into format so if
	// id ends in qcolXXXXXform then its true as well
	function is_showing ($in_tag) 
	{
		$container = $this->show_area;
		$containee = $in_tag;
		$ret = false;
		$match = "/^".$containee."/";
		if ( preg_match( $match, $container ) )
			$ret = true;

		$match = "/qcol....form$/";
		if ( !$ret && preg_match( $match, $containee ) )
			$ret = true;

		$match = "/pghd....form$/";
		if ( !$ret && preg_match( $match, $containee ) )
			$ret = true;

		$match = "/pgft....form$/";
		if ( !$ret && preg_match( $match, $containee ) )
			$ret = true;

		$match = "/grph...._/";
		if ( !$ret && preg_match( $match, $containee ) )
			$ret = true;

		return $ret;
	}

	// Works out whether a maintenance item should be shown on the screen based on the value
	// of the show_area parameter which was derived from the HTTP Request Data
	function is_showing_full ($in_tag) 
	{
		$match = "/qcol....$/";
		if ( preg_match( $match, $in_tag ) )
		{
				return true;
		}
		
		$match = "/qcol....form$/";
		if ( preg_match( $match, $in_tag ) )
				return true;

		$match = "/pghd....form$/";
		if ( preg_match( $match, $in_tag ) )
				return true;

		$match = "/pghd....$/";
		if ( preg_match( $match, $in_tag ) )
				return true;

		$match = "/pgft....form$/";
		if ( preg_match( $match, $in_tag ) )
				return true;

		$match = "/pgft....$/";
		if ( preg_match( $match, $in_tag ) )
				return true;

		$match = "/grps...._/";
		if ( preg_match( $match, $in_tag ) )
		{
			return true;
		}

		if ( $in_tag."detl" == $this->show_area )
			return true;

		if ( $in_tag == $this->show_area )
			return true;

		return false;
	}

	function & xml2html (&$ar, $from_key = false) 
	{

		$text = "";

		$hold_last = false;

		$fct = 0;
		foreach ( $ar as $k => $val )
		{
			$fct++;
			if ( is_array($val) )
			{
				$oldid = $this->id;

				// To get over fact switch does not operatoe for a zero value force k to be
				// -1 if it is 0 
				if ( is_numeric($k) && (int)$k  == 0 )
					$k = "ZERO";

				switch ( $k )
				{
					case "Report":
					case "CogModule":
						$this->id = "main";
						$text .= '<TABLE class="swMntMainBox">';
						global $g_project;
		                		$text .= '<TR>';
						$text .= '<TD colspan="2">';
						$text .= '&nbsp;&nbsp;Project: '.$g_project.'&nbsp;&nbsp;&nbsp;&nbsp;';
						$text .= 'Report File <input type="text" name="xmlout" value="'.$this->query->xmloutfile.'">';
						$text .= '&nbsp;&nbsp;<input class="swLinkMenu" type="submit" name="submit_xxx_SAVE" value="Save">';
						$text .= '&nbsp;&nbsp;<input class="swLinkMenu" type="submit" name="submit_maintain_NEW" value="New Report">';
						$text .= '</TD>';
						$text .= '</TR>';
						//$text .= '<TR>';
						break;

					case "CogQuery":
					case "ReportQuery":
						$this->id .= "quer";
						//$text .= '</TR>';
						$text .= '</TABLE>';
						$text .= '<TABLE cellspacing="0" cellpadding="0" class="swMntMainBox">';
						$text .= '<TR>';
						
						// Force format Screen if none chosen
						$match = "/quer$/";
						if ( preg_match( $match, $this->show_area ) )
							$this->show_area .= "form";

						$text .= $this->draw_show_hide_tab_button ($this->id."form", "Format") ;
						$text .= $this->draw_show_hide_tab_button ($this->id."qury", "Query Details") ;
						$text .= $this->draw_show_hide_tab_button ($this->id."assg", "Assignments") ;
						$text .= $this->draw_show_hide_tab_button ($this->id."crit", "Criteria") ;
						$text .= $this->draw_show_hide_tab_button ($this->id."outp", "Output") ;
						$text .= '</TR>';
						$text .= '</TABLE>';
						$text .= '<TABLE cellspacing="0" cellpadding="0" class="swMntInnerBox">';
						break;

					case "SQL":
						$ct=count($val);
						$this->id .= "sqlt";
						break;

					case "Format":
						$ct=count($val);
						$this->id .= "form";
						if ( $this->id != "mainquerform" )
						{
							$text .= "\n<!--FORM SHOW --><TR>";
							$text .= $this->draw_show_hide_button ($this->id, "Format") ;
							$text .= "</TR>";
						}
						break;

					case 'Groups':
						$this->id .= "grps";
						if ( $this->is_showing ( $this->id ) )
						{
							$text .= '<TR class="swMntRowBlock">';
							$text .= $this->draw_add_button ($this->id, "Groups") ;
							$text .= '</TR>';
							$text .= '</TABLE>';
							$text .='<TABLE cellspacing="0" cellpadding="0" class="swMntInnerBox">';
							$text .= '<TR>';
							$text .= $this->panel_key_to_html($this->id, $val, "Group", "_key", true );
							$text .= '<TD valign="top">';
							$element_counts[$k] = count($val);
                                                        if ( count($val) > 0 )
								$text .= '<TABLE class="swMntInnerRightBox">';
						}

						break;

					case 'GroupTrailers':
						$this->id .= "gtrl";
						if ( $this->is_showing ( $this->id ) )
						{
							$text .= '<TR class="swMntRowBlock">';
							$text .= $this->draw_add_button ($this->id, "Group Trailers") ;
							$text .= '</TR>';
							$text .= '<TR>';
							$text .= $this->panel_key_to_html($this->id, $val, "Trailer", "_key", false );
							$text .= '<TD valign="top">';
							$element_counts[$k] = count($val);
                                                        if ( count($val) > 0 )
								$text .= '<TABLE class="swMntInnerRightBox">';
						}
						break;

					case 'GroupHeaders':
						$this->id .= "ghdr";
						if ( $this->is_showing ( $this->id ) )
						{
							$text .= '<TR class="swMntRowBlock">';
							$text .= $this->draw_add_button ($this->id, "Group Headers") ;
							$text .= '</TR>';
							$text .= '</TABLE>';
							$text .='<TABLE cellspacing="0" cellpadding="0" class="swMntInnerBox">';
							$text .= '<TR>';
							$text .= $this->panel_key_to_html($this->id, $val, "Header", "_key", true );
							$text .= '<TD valign="top">';
							$element_counts[$k] = count($val);
							if ( count($val) > 0 )
								$text .= '<TABLE class="swMntInnerRightBox">';
						}
						break;

					case 'PreSQLS':
						$this->id .= "psql";
						if ( $this->is_showing ( $this->id ) )
						{
							$text .= '<TR class="swMntRowBlock">';
							$text .= $this->draw_add_button ($this->id, "PreSQLS") ;
							$text .= '</TR>';
							$text .= '</TABLE>';
							$text .='<TABLE cellspacing="0" cellpadding="0" class="swMntInnerBox">';
							$text .= '<TR>';
							$text .= $this->panel_key_to_html($this->id, $val, "PreSql", "_key", true );
							$text .= '<TD valign="top">';
							$element_counts[$k] = count($val);
                                                        if ( count($val) > 0 )
								$text .= '<TABLE class="swMntInnerRightBox">';
						}
						break;


					case 'OrderColumns':
						$this->id .= "ords";
						break;

					case 'DisplayOrders':
						$this->id .= "dord";
						if ( $this->is_showing ( $this->id ) )
						{
							$text .= '<TR>';
							$text .= $this->panel_key_to_html($this->id, $val, "", "ColumnName", true, false );
							$text .= '<TD valign="top">';
							$element_counts[$k] = count($val);
                                                        if ( count($val) > 0 )
								$text .= '<TABLE class="swMntInnerRightBox">';
						}
						break;

					case 'Plots':
						$this->id .= "plot";
						if ( $this->is_showing ( $this->id ) )
						{
							$text .= '<TR class="swMntRowBlock">';
							$text .= $this->draw_add_button ($this->id, "Plots") ;
							$text .= '</TR>';
							$text .= '</TABLE>';
							$text .='<TABLE cellspacing="0" cellpadding="0" class="swMntInnerBox">';
							$text .= '<TR>';
							$text .= $this->panel_key_to_html($this->id, $val, "Plots", "_key" );
							$text .= '<TD valign="top">';
							$element_counts[$k] = count($val);
                                                        if ( count($val) > 0 )
								$text .= '<TABLE class="swMntInnerRightBox">';
						}
						break;

					case 'Graphs':
						$this->id .= "grph";
						if ( $this->is_showing ( $this->id ) )
						{
							$text .= '<TR class="swMntRowBlock">';
							$text .= $this->draw_add_button ($this->id, "Graphs") ;
							$text .= '</TR>';
							$text .= '</TABLE>';
							$text .='<TABLE cellspacing="0" cellpadding="0" class="swMntInnerBox">';
							$text .= '<TR>';
							$text .= $this->panel_key_to_html($this->id, $val, "Graphs", "_key" );
							$text .= '<TD valign="top">';
							$element_counts[$k] = count($val);
                                                        if ( count($val) > 0 )
								$text .= '<TABLE class="swMntInnerRightBox">';
						}
						break;

					case 'PageHeaders':
						$this->id .= "pghd";
						if ( $this->is_showing ( $this->id ) )
						{
							$text .= '<TR class="swMntRowBlock">';
							$text .= $this->draw_add_button ($this->id, "Page Headers") ;
							$text .= '</TR>';
							$text .= '</TABLE>';
							$text .='<TABLE cellspacing="0" cellpadding="0" class="swMntInnerBox">';
							$text .= '<TR>';
							$text .= $this->panel_key_to_html($this->id, $val, "Page Header", "_key" );
							$text .= '<TD valign="top">';
							$element_counts[$k] = count($val);
                                                        if ( count($val) > 0 )
								$text .= '<TABLE class="swMntInnerRightBox">';
						}
						break;

					case 'PageFooters':
						$this->id .= "pgft";
						if ( $this->is_showing ( $this->id ) )
						{
							$text .= '<TR class="swMntRowBlock">';
							$text .= $this->draw_add_button ($this->id, "Page Footers") ;
							$text .= '</TR>';
							$text .= '</TABLE>';
							$text .='<TABLE cellspacing="0" cellpadding="0" class="swMntInnerBox">';
							$text .= '<TR>';
							$text .= $this->panel_key_to_html($this->id, $val, "Page Footer", "_key" );
							$text .= '<TD valign="top">';
							$element_counts[$k] = count($val);
                                                        if ( count($val) > 0 )
								$text .= '<TABLE class="swMntInnerRightBox">';
						}
						break;

					case 'QueryColumns':
						$this->id .= "qcol";
						if ( $this->is_showing ( $this->id ) )
						{
							$text .= "\n<!--Debug Qcol-->";
							$text .= '<TR class="swMntRowBlock">';
							$text .= $this->draw_add_button ($this->id, "Query Columns") ;
							$text .= '</TR>';
							$text .= '</TABLE>';
							$text .='<TABLE cellspacing="0" cellpadding="0" class="swMntInnerBox">';
							$text .= '<TR>';
							$text .= $this->panel_key_to_html($this->id, $val, "", "Name" );
							$text .= '<TD valign="top">';
							$element_counts[$k] = count($val);
                                                        if ( count($val) > 0 )
								$text .= '<TABLE class="swMntInnerRightBox">';
							$text .= "\n<!--Debug Qcol-->";
						}

						break;

					case 'Output':
						$this->id .= "outp";
						$ct=count($val);
						if ( $this->id != "mainqueroutp" )
						{
							$text .= '<TR class="swMntRowBlock">';
							$text .= $this->draw_show_hide_button ($this->id, "Output") ;
							if ( $this->is_showing ( $this->id ) )
							{
								$text .= '<TD colspan="3"><TABLE><TR>';
							}
						}
						if ( $this->is_showing ( $this->id ) )
						{
							// Force format Screen if none chosen
							$match = "/outp$/";
							if ( preg_match( $match, $this->show_area ) )
								$this->show_area .= "pghd";

							$text .= '<TR class="swMntRowBlock">';
							$text .= '<TD width="100%">';
							$text .= '<TABLE cellspacing="0" cellpadding="0" class="swMntMainBox">';
							$text .= '<TR>';
							$text .= $this->draw_show_hide_tab_button ($this->id."pghd", "Page Headers") ;
							$text .= $this->draw_show_hide_tab_button ($this->id."pgft", "Page Footers") ;
							$text .= $this->draw_show_hide_tab_button ($this->id."dord", "Display Order") ;
							$text .= $this->draw_show_hide_tab_button ($this->id."grps", "Groups") ;
							$text .= $this->draw_show_hide_tab_button ($this->id."grph", "Graphs") ;
							$text .= '</TR>';
							$text .= '</TABLE>';
							$text .= '</TD>';
							$text .= '</TR>';
							$text .= '<TR class="swMntRowBlock">';
							$text .= '<TD width="100%">';
							$text .= '<TABLE cellspacing="0" cellpadding="0" class="swMntInnerBox">';
						}
						break;

					case 'Datasource':
						$this->id .= "data";
						$ct=count($val);
						if ( $this->id != "mainquerdata" )
						{
							$text .= '<TR class="swMntRowBlock">';
							$text .= $this->draw_show_hide_button ($this->id, "Data Source") ;
							if ( $this->is_showing ( $this->id ) )
							{
								$text .= '<TD colspan="3"><TABLE><TR>';
							}
						}
						break;

					case 'SourceConnection':
						$this->id .= "conn";
						$ct=count($val);
						$text .= '<TR class="swMntRowBlock">';
						$text .= '<TD colspan="3"><TABLE><TR>';
						$text .= $this->draw_show_hide_button ($this->id, "Connection") ;
						$text .= '</TR>';
						$text .= '<TR>';
						break;

					case 'EntryForm':
						break;

					case 'Query':
						$this->id .= "qury";
							$text .= '<!--Start Query-->';
						if (  $this->id == "mainquerqury" && $this->is_showing ( $this->id ) )
						{
							// Force format Screen if none chosen
							$match = "/qury$/";
							if ( preg_match( $match, $this->show_area ) )
								$this->show_area .= "sqlt";

							$text .= '<TR class="swMntRowBlock">';
							$text .= '<TD width="100%">';
							$text .= '<TABLE cellspacing="0" cellpadding="0" class="swMntMainBox">';
							$text .= '<TR>';
							$text .= $this->draw_show_hide_tab_button ($this->id."sqlt", "SQL") ;
							$text .= $this->draw_show_hide_tab_button ($this->id."qcol", "Query Columns") ;
							//$text .= $this->draw_show_hide_tab_button ($this->id."ords", "Order By") ;
							$text .= $this->draw_show_hide_tab_button ($this->id."psql", "Pre-SQLs") ;
							$text .= '</TR>';
							$text .= '</TABLE>';
							$text .= '<TABLE cellspacing="0" cellpadding="0" class="swMntInnerBox">';
						}
							$text .= '<!--End Query-->';
						break;

					case 'Criteria':

						$this->id .= "crit";
						if ( $this->id != "mainquercrit" )
						{
							$text .= '<TR class="swMntRowBlock">';
							$text .= $this->draw_show_hide_button ($this->id, "Criteria") ;
							$text .= '</TR>';
						}

							$text .= "\n<!--StartCrit".$this->id."-->";
						if ( $this->is_showing ( $this->id ) )
						{
							$text .= '<TR class="swMntRowBlock">';
							$text .= $this->draw_add_button ($this->id, "Criteria") ;
							$text .= '</TR>';
							$text .= '</TABLE>';
							$text .='<TABLE cellspacing="0" cellpadding="0" class="swMntInnerBox">';
							$text .= '<TR>';
							$text .= $this->panel_key_to_html($this->id, $val, "Criteria", "Name", true );
							$text .= '<TD valign="top">';
							$element_counts[$k] = count($val);
                                                        if ( count($val) > 0 )
								$text .= '<TABLE class="swMntInnerRightBox">';
						}
						break;

					case 'CriteriaLinks':
						$this->id .= "clnk";
						if ( $this->is_showing ( $this->id ) )
						{
							$text .= '<TR class="swMntRowBlock">';
							$text .= $this->draw_add_button ($this->id, "Link") ;
							$text .= '</TR>';
							$text .= '</TABLE>';
							$text .='<TABLE cellspacing="0" cellpadding="0" class="swMntInnerBox">';
							$text .= '<TR>';
							$text .= $this->panel_key_to_html($this->id, $val, "Links", "_key" );
							$text .= '<TD valign="top">';
							$element_counts[$k] = count($val);
                                                        if ( count($val) > 0 )
								$text .= '<TABLE class="swMntInnerRightBox">';
						}

						break;

					case 'Assignments':
						$this->id .= "assg";
						if ( $this->is_showing ( $this->id ) )
						{
							$text .= '<TR class="swMntRowBlock">';
							$text .= $this->draw_add_button ($this->id, "Assignments") ;
							$text .= '</TR>';
							$text .= '</TABLE>';
							$text .='<TABLE cellspacing="0" cellpadding="0" class="swMntInnerBox">';
							$text .= '<TR>';
							$text .= $this->panel_key_to_html($this->id, $val, "", "AssignName", true );
							$text .= '<TD valign="top">';
							$element_counts[$k] = count($val);
                                                        if ( count($val) > 0 )
								$text .= '<TABLE class="swMntInnerRightBox">';
						}

						break;

					case 'CriteriaItem':
						$this->id .= "item";
						break;

					case 'CriteriaLink':
						$this->id .= "link";
						break;

					case "0":
						break;

					case "ZERO":
						$k = 0;

					default:
						if ( is_numeric ($k ) )
						{
							$str = sprintf( "%04d", $k);
							$this->id .= $str;

							$hold_last = true;
							if ( $from_key == "Assignments" )
							{
								$ct=count($val);
								$ct++;
							}
							if ( $from_key == "Groups" )
							{
								$ct=count($val);
								$ct++;
							}
							if ( $from_key == "GroupHeaders" )
							{
								$ct=count($val);
								$ct++;
							}
							if ( $from_key == "GroupTrailers" )
							{
								$ct=count($val);
								$ct++;
							}
							if ( $from_key == "PreSQLS" )
							{
							}
							if ( $from_key == "Plots" )
							{
							}
							if ( $from_key == "Graphs" )
							{
							}
							if ( $from_key == "PageHeaders" )
							{
							}
							if ( $from_key == "PageFooters" )
							{
								$ct=count($val);
								$ct++;
							}
							if ( $from_key == "OrderColumns" )
							{
								$text .= '<TR class="swMntRowBlock">';
								$text .= $this->draw_show_hide_button ($this->id, "Order Column ".$k) ;
								$text .= $this->draw_delete_button ($this->id) ;
								$text .= '</TR>';
							}
							if ( $from_key == "DisplayOrders" )
							{
							}
							if ( $from_key == "QueryColumns" )
							{
								$ct=count($val);
								$ct++;
							}
							if ( $from_key == "Criteria" )
							{
								$this->current_criteria_name = $val["Name"];
							}
						}
						else
							$text .= "*****Got bad $k<br>";
						break;
				}

				if ( !$hold_last )
					$this->last_element = $k;

				if ( count($val) > 0 )
				{
					// Only generate HTML if the suitable element needs to be shown
					if ( $this->is_showing ( $this->id ) )
					{
						$text .= $this->xml2html($val, $k);
					}

				}

				$parent_id = $this->id;
				$this->id = $oldid;
				$this->level_ct--;

				if ( is_numeric($k) && (int)$k  == 0 )
					$k = "ZERO";

				switch ( $k )
				{
					case "Output":
						if ( $this->is_showing ( $parent_id ) )
						{
							$text .= "\n<!--End Output-->";
							$text .= '</TABLE>';
							$text .= '</TD>';
							$text .= '</TR>';
							break;
						}

					case "Report":
					case "CogModule":
						if ( $this->id )
						{
							$text .= '<!--Cog Mod-->';
							$text .= '</TABLE>';
						}
						break;

					case "CogQuery":
					case "ReportQuery":
						break;

					case "SQL":
						break;

					case "Format":
						if ( $this->is_showing ( $parent_id ) )
						if ( $this->id != "mainquer" )
						{
							$text .= "\n<!--End Format".$this->id." ".$parent_id."-->";
						}
						break;

					case 'PreSQLS':
					case 'OrderColumns':
						break;

					case 'Datasource':
						if ( $this->id != "mainquer" )
						{
							$text .= "\n<!--End Data Source-->";
							$text .= '</TR></TABLE>';
						}
						break;

 					case 'SourceConnection':
						$text .= "\n<!--End Cource Connection-->";
						$text .= '</TR></TABLE>';
						break;

					case 'EntryForm':
						break;

					case 'Query':
						if ( $parent_id == "mainquerqury" && $this->is_showing ( $parent_id ) )
						{
							$text .= "\n<!--End Query-->";
							$text .= '</TABLE>';
							$text .= '</TD>';
							$text .= '</TR>';
						}
						break;

					case 'Criteria':
					case 'QueryColumns':
					case 'GroupHeaders':
					case 'GroupTrailers':
					case 'Groups':
					case 'DisplayOrders':
					case 'Graphs':
					case 'Plots':
					case 'PreSQLS':
					case 'PageFooters':
					case 'PageHeaders':
					case 'CriteriaLink':
					case 'CriteriaLinks':
						if ( $this->is_showing ( $parent_id ) )
						{
							$text .= "\n<!--General-".$parent_id." $k-->";
							$text .= '<TR>';
							$text .= '<TD>&nbsp;</TD>';
							$text .= '</TR>';
							if ( $element_counts[$k] > 0 )
							{
								$text .= '</TABLE>';
							}
							$text .= '</TD>';
							$text .= '</TR>';
						}
						break;

					case 'Assignments':
						if ( $this->is_showing ( $parent_id ) )
						{
							$text .= "\n<!--Assignment-->";
							$text .= '<TR>';
							$text .= '<TD>&nbsp;</TD>';
							$text .= '</TR>';
							$text .= '</TABLE>';
						}
						break;

					case 'CriteriaItem':
						break;

					case "ZERO":
						$k = 0;

					default:
						if ( is_numeric ($k ) )
						{
							$match = "/assg[0-9][0-9][0-9][0-9]/";
							if ( preg_match( $match, $parent_id ) )
							{
								if ( $this->is_showing ( $parent_id ) )
									$text .= $this->assignment_aggregates( $parent_id);
							}

							$match = "/grph[0-9][0-9][0-9][0-9]/";
							if ( preg_match( $match, $parent_id ) )
							{
								if ( $this->is_showing ( $parent_id ) )
								{
										$text .= "\n<!--End grph bit-->";
										$text .= "</TABLE>";
										$text .= "</TD>";
										$text .= "</TR>";
								}
							}
							$match = "/grps[0-9][0-9][0-9][0-9]/";
							if ( preg_match( $match, $parent_id ) )
							{
								if ( $this->is_showing ( $parent_id ) )
								{
										$text .= "\n<!--End grop bit-->";
										$text .= "</TABLE>";
										$text .= "</TD>";
										$text .= "</TR>";
								}
							}
							$match = "/crit[0-9][0-9][0-9][0-9]$/";
							if ( preg_match( $match, $parent_id ) )
							{
								if ( $this->is_showing ( $parent_id ) )
								{
										$text .= "\n<!--end  crit bit ".$parent_id."-->";
										$text .= "</TABLE>";
										$text .= "</TD>";
										$text .= "</TR>";
								}
							}
						}
						break;
				}
			}
			else
			{
				// Force Group Header Trailer menu after group entry fields
				$match = "/grph[0-9][0-9][0-9][0-9]/";
				$match1 = "/grph[0-9][0-9][0-9][0-9]$/";
				if ( preg_match( $match1, $this->id ) && $fct == 1)
				{
					$match = "/grph[0-9][0-9][0-9][0-9]$/";
					if ( preg_match( $match, $this->show_area ))
						$this->show_area .= "detl";

					$text .= "\n".'<TR class="swMntRowBlock">'."\n";
					$text .= '	<TD width="100%" colspan="4">'."\n";
					$text .= '		<TABLE cellspacing="0" cellpadding="0" class="swMntMainBox">'."\n";
					$text .= '			<TR>'."\n";
					$text .= $this->draw_show_hide_tab_button ($this->id."detl", "Details") ;
					$text .= $this->draw_show_hide_tab_button ($this->id."plot", "Plots") ;
					$text .= '			</TR>'."\n";
					$text .= '		</TABLE>'."\n";
					$text .= '		<TABLE cellspacing="0" cellpadding="0" class="swMntInnerBox">'."\n";
				}

				// Force Group Header Trailer menu after group entry fields
				$match = "/grps[0-9][0-9][0-9][0-9]/";
				$match1 = "/grps[0-9][0-9][0-9][0-9]$/";
				if ( preg_match( $match1, $this->id ) && $fct == 1)
				{
					$match = "/grps[0-9][0-9][0-9][0-9]$/";
					if ( preg_match( $match, $this->show_area ))
						$this->show_area .= "detl";

					$text .= "\n".'<TR class="swMntRowBlock">'."\n";
					$text .= '	<TD width="100%" colspan="4">'."\n";
					$text .= '		<TABLE cellspacing="0" cellpadding="0" class="swMntMainBox">'."\n";
					$text .= '			<TR>'."\n";
					$text .= $this->draw_show_hide_tab_button ($this->id."detl", "Details") ;
					$text .= $this->draw_show_hide_tab_button ($this->id."ghdr", "Headers") ;
					$text .= $this->draw_show_hide_tab_button ($this->id."gtrl", "Trailers") ;
					$text .= '			</TR>'."\n";
					$text .= '		</TABLE>'."\n";
					$text .= '		<TABLE cellspacing="0" cellpadding="0" class="swMntInnerBox">'."\n";
				}

				// Force Criteria menu after group entry fields
				$match = "/crit[0-9][0-9][0-9][0-9]/";
				$match1 = "/crit[0-9][0-9][0-9][0-9]$/";
				if ( preg_match( $match1, $this->id ) && $fct == 1)
				{
					$match = "/crit[0-9][0-9][0-9][0-9]$/";
					if ( preg_match( $match, $this->show_area ))
						$this->show_area .= "detl";

					$text .= "\n<!--startcrit bit ".$this->id."-->";
					$text .= '<TR class="swMntRowBlock">'."\n";
					$text .= '	<TD width="100%" colspan="4">'."\n";
					$text .= '		<TABLE cellspacing="0" cellpadding="0" class="swMntMainBox">'."\n";
					$text .= '			<TR>'."\n";
					$text .= $this->draw_show_hide_tab_button ($this->id."detl", "Details") ;
					$text .= $this->draw_show_hide_tab_button ($this->id."qurysqlt", "SQL") ;
					$text .= $this->draw_show_hide_tab_button ($this->id."quryqcol", "Query Columns") ;
					$text .= $this->draw_show_hide_tab_button ($this->id."clnk", "Links") ;
					$text .= $this->draw_show_hide_tab_button ($this->id."quryassg", "Assignments") ;
					$text .= '			</TR>'."\n";
					$text .= '		</TABLE>'."\n";
					$text .= '		<TABLE cellspacing="0" cellpadding="0" class="swMntInnerBox">'."\n";
				}
				
				if ( $this->is_showing_full ( $this->id ) )
				{
					if ( $k == "QuerySql" )
					{
						if ( !$this->current_criteria_name )
						{
							$q =& $this->query;
						}
						else
						{
							$q =& $this->query->lookup_queries[$this->current_criteria_name]->lookup_query;
						}

						$out="";
						if ( $q->maintain_sql )
						{
							$out = $q->maintain_sql;
						}
						else
						{
							$q->build_query(false);
							$out =  $q->query_statement;
						}
						$val=$out;
					}

					if ( 
						$k != "TableSql"
						&& $k != "WhereSql"
						&& $k != "GroupSql"
						&& $k != "RowSelection"
						)
						{
						echo "\n<!-- do it $k -->";
						$text .= $this->display_maintain_field($k, $val, $fct);
						}


				}
			}
		}
		return $text;

	}

	function get_help_link($tag)
	{
		$helppage = false;
		$stub = substr($tag, 0, 12 );
		if ( $stub == "mainquercrit" )
			$helppage = "criteria";
		else if ( $stub == "mainquerassg" )
			$helppage = "assign";
		else if ( $stub == "mainquerqury" )
			$helppage = "qrydet";
		else if ( $stub == "mainqueroutp" )
			$helppage = "output";
		else if ( $stub == "mainquerform" )
			$helppage = "format";

		return $helppage;
	}

	function & display_maintain_field($tag, $val, &$tagct)
	{
		$text = "";
		$text .= "\n<!-- SETFIELD-->";
		$text .= '<TR>';
		$type = "TEXTFIELD";
		$title = $tag;
		$edit_mode = "FULL";
		$tagvals = array();

		$striptag = preg_replace("/ .*/", "", $tag);
		$showtag = preg_replace("/ /", "_", $tag);
		$subtitle = "";
		if ( preg_match("/ /", $tag ) )
			$subtitle = preg_replace("/.* /", " ", $tag);

		if ( array_key_exists($striptag, $this->field_display ) )
		{
			$arval = $this->field_display[$striptag];
			if ( array_key_exists("Title", $arval ) )
				$title = $arval["Title"].$subtitle;

			if ( array_key_exists("Type", $arval ) )
				$type = $arval["Type"];

			if ( array_key_exists("EditMode", $arval ) )
				$edit_mode = $arval["EditMode"];

			if ( array_key_exists("Values", $arval ) )
				$tagvals = $arval["Values"];

		}

		$default = get_default($striptag, ".");

		if ( $type == "HIDE" )
		{
			$tagct--;
			$test = "";
			return $text;
		}

		$helppage = $this->get_help_link($this->id);

		$text .= '<TD class="swMntSetField">';
		if ( $helppage )
		{
			$docpath = find_best_location_in_include_path( "doc/reportico/tutorial_reportico.".$helppage.".pkg.html" );
			$helpimg = find_best_location_in_include_path( "images/help.png" );
			$text .= '<a target="_blank" href="'.dirname(session_request_item('linkbaseurl', SW_USESELF)).'/'.$docpath.'#'.$helppage.'.'.$striptag.'">';
			$text .= '<img class="swMntHelpImage" alt="tab" src="'.dirname(session_request_item('linkbaseurl', SW_USESELF)).'/'.$helpimg.'">';
			$text .= '</a>&nbsp;';
		}
		$text .= $title;
		if ( $edit_mode == "SAFE"  )
			if ( SW_SAFE_DESIGN_MODE ) 
				$text .= "<br>(Turn off Safe<br>Design Mode in<br>project config.php to <br>enable this feature)";
			else 
				$text .= "<br>(Turn on Safe<br>Design Mode in<br>project config.php to <br>disable this feature)";
		$text .= '</TD>';

		// Display Field Entry
		$text .= '<TD class="swMntSetField" colspan="1">';
		switch ( $type )
		{
			case "PASSWORD":
				$text .= '<input type="password" size="40%" name="set_'.$this->id."_".$showtag.'" value="'.htmlspecialchars($val).'"><br>';
				break;

			case "TEXTFIELD":
			case "TEXTFIELDNOOK":
				$readonly = "";
				if ( $edit_mode == "SAFE" && ( $this->query->allow_maintain == "SAFE" || SW_SAFE_DESIGN_MODE ) )
					$readonly = "readonly";
				$text .= '<input type="text" size="40%" '.$readonly.' name="set_'.$this->id."_".$showtag.'" value="'.htmlspecialchars($val).'">';
				break;

			case "TEXTBOX":
				$readonly = "";
				if ( $edit_mode == "SAFE" && ( $this->query->allow_maintain == "SAFE" || SW_SAFE_DESIGN_MODE ) )
					$readonly = "readonly";
				$text .= '<textarea '.$readonly.' cols="70" rows="20" name="set_'.$this->id."_".$showtag.'" >';
				$text .= htmlspecialchars($val);
				$text .= '</textarea>';
				break;

			case "DROPDOWN":
				$text .= $this->draw_array_dropdown("set_".$this->id."_".$showtag, $tagvals, $val, false);
				break;

			case "CRITERIA":
				$keys=array_keys($this->query->lookup_queries);
				if ( !is_array($keys) )
					$key = array();
				$text .= $this->draw_array_dropdown("set_".$this->id."_".$showtag, $keys, $val, false);
				break;

			case "GROUPCOLUMNS":
				if ( !$this->current_criteria_name )
					$q =& $this->query;
				else
					$q =& $this->query->lookup_queries[$this->current_criteria_name]->lookup_query;

				$keys = array();
				$keys[] = "REPORT_BODY";
				if ( is_array($q->columns) )
					foreach ( $q->columns as $col )
						$keys[] = $col->query_name;

				$text .= $this->draw_array_dropdown("set_".$this->id."_".$showtag, $keys, $val, false);
				break;
				
			case "REPORTLIST":
				$keys = array();
				$keys[] = "";
				$testpath = find_best_location_in_include_path( $this->query->reports_path );
				if (is_dir($testpath)) 
				{
    				if ($dh = opendir($testpath)) 
					{
        				while (($file = readdir($dh)) !== false) 
						{
							if ( preg_match ( "/.*\.xml/", $file ) )
								$keys[] = $file;
        				}
        				closedir($dh);
    				}
				}
				else
					trigger_error ( "Unable to open project directory ".$this->query->reports_path );

				$text .= $this->draw_array_dropdown("set_".$this->id."_".$showtag, $keys, $val, false);

				break;
		
			case "AGGREGATETYPES":
				if ( !$this->current_criteria_name )
					$q =& $this->query;
				else
					$q =& $this->query->lookup_queries[$this->current_criteria_name]->lookup_query;

				$keys=array();
				$keys[] = "";
				$keys[] = "SUM";
				$keys[] = "AVERAGE";
				$keys[] = "MIN";
				$keys[] = "MAX";
				$keys[] = "PREVIOUS";
				$keys[] = "COUNT";
				$text .= $this->draw_array_dropdown("set_".$this->id."_".$showtag, $keys, $val, false);
				break;
				
			case "QUERYCOLUMNS":
				if ( !$this->current_criteria_name )
					$q =& $this->query;
				else
					$q =& $this->query->lookup_queries[$this->current_criteria_name]->lookup_query;

				$keys=array();
				if ( $q && is_array($q->columns) )
					foreach ( $q->columns as $col )
						$keys[] = $col->query_name;

				$text .= $this->draw_array_dropdown("set_".$this->id."_".$showtag, $keys, $val, false);
				break;
				
			case "QUERYCOLUMNSOPTIONAL":
				if ( !$this->current_criteria_name )
					$q =& $this->query;
				else
					$q =& $this->query->lookup_queries[$this->current_criteria_name]->lookup_query;

				$keys=array();
				$keys[] = "";
				if ( is_array($q->columns) )
					foreach ( $q->columns as $col )
						$keys[] = $col->query_name;

				$text .= $this->draw_array_dropdown("set_".$this->id."_".$showtag, $keys, $val, false);
				break;
				
			case "QUERYGROUPS":
				$q =& $this->query;

				$keys=array();
				if ( is_array($q->columns) )
					foreach ( $q->groups as $col )
						$keys[] = $col->group_name;

				$text .= $this->draw_array_dropdown("set_".$this->id."_".$showtag, $keys, $val, false);
				break;
		}

			$text .= '<TD class="swMntSetField" colspan="1">';
		if ( $default )
		{
			$text .= '&nbsp;('.$default.')';
		}
		else
			$text .= '&nbsp;';
		$text .= '</TD>';

		if ( $tagct == 1 )
		{
			$text .= "\n<!-- TAG 1-->";
			$text .= '<TD colspan="1">';
			if ( $type != "TEXTFIELDNOOK" )	
				$text .= '<input class="swMntButton" type="submit" name="submit_'.$this->id.'_SET" value="Ok">';
			else
				$text .= "&nbsp;";
			$text .= '</TD>';
		}
		$text .= '</TR>';

		return $text;
	}

	function draw_array_dropdown ($name, $ar, $val, $addblank )
	{
		$text = "";

		if ( count($ar) == 0 )
		{
			$text .= '<input type="text" size="40%" name="'.$name.'" value="'.htmlspecialchars($val).'"><br>';
			return;
		}

		$text .= '<SELECT name="'.$name.'">';

		if ( $addblank )
			if ( !$val )
				$text .= '<OPTION selected label="" value=""></OPTION>';
			else
				$text .= '<OPTION label="" value=""></OPTION>';

		foreach ( $ar as $k => $v )
		{
			if ( $v == $val )
				$text .= '<OPTION selected label="'.$v.'" value="'.$v.'">'.$v.'</OPTION>';
			else
				$text .= '<OPTION label="'.$v.'" value="'.$v.'">'.$v.'</OPTION>';
		}
		$text .= '</SELECT>';

		return $text;
	}

	function & assignment_aggregates($in_parent)
	{
		$text = "";
		$tagct = 1;
		$tmpid = $this->id;
		$this->id = $in_parent;
		$text .= '<TR><TD>&nbsp;</TD></TD>';
		$text .= '<TR><TD class="swMntSetField"><b>Aggregates</b></TD></TR>';
		$text .= $this->display_maintain_field("AssignAggType", false, $tagct);
		$tagct++;
		$text .= $this->display_maintain_field("AssignAggCol", false, $tagct);
		$tagct++;
		$text .= $this->display_maintain_field("AssignAggGroup", false, $tagct);
		$tagct++;

		$tagct = 1;
		$text .= '<TR><TD>&nbsp;</TD></TD>';
		$text .= '<TR><TD class="swMntSetField"><b>Database Graphic</b></TD></TR>';
		$text .= $this->display_maintain_field("AssignGraphicBlobCol", false, $tagct);
		$tagct++;
		$text .= $this->display_maintain_field("AssignGraphicBlobTab", false, $tagct);
		$tagct++;
		$text .= $this->display_maintain_field("AssignGraphicBlobMatch", false, $tagct);
		$tagct++;
		$text .= $this->display_maintain_field("AssignGraphicWidth", false, $tagct);
		$tagct++;
		$text .= $this->display_maintain_field("AssignGraphicReportCol", false, $tagct);
		$tagct++;

		$tagct = 1;
		$text .= '<TR><TD>&nbsp;</TD></TD>';
		$text .= '<TR><TD class="swMntSetField"><b>Drilldown</b></TD></TR>';
		$text .= $this->display_maintain_field("DrilldownReport", $this->query->drilldown_report, $tagct);
		$tagct++;

		if ( $this->query->drilldown_report )
		{
				$q = new reportico_query();
				global $g_project;
				$q->reports_path = "projects/".$g_project;
				$reader = new reportico_xml_reader($q, $this->query->drilldown_report, false);
				$reader->xml2query();
				foreach ( $q->lookup_queries as $k => $v )
				{

						$text .= $this->display_maintain_field("DrilldownColumn ".$v->query_name, false, $tagct);
				}
				unset($q);
		}

		$this->id = $tmpid;

		return $text;
	}

	function & panel_key_to_html_row($id, &$ar, $labtext, $labindex )
	{
		$text = "";
		$text .= '<TR>';
		foreach ( $ar as $key => $val )
		{
			$text .= '<TD>';

			$padstring = $id.str_pad($key, 4, "0", STR_PAD_LEFT);
			if ( $labindex == "_key" )
				$text .= $this->draw_show_hide_button ($padstring, $labtext." ".$key) ;
			else
				$text .= $this->draw_show_hide_button ($padstring, $labtext." ".$val[$labindex]) ;
			$text .= $this->draw_delete_button ($padstring) ;
			$text .= '</TD>';
		}
		$text .= '</TR>';

		return $text;
	}

	function & panel_key_to_html($id, &$ar, $labtext, $labindex, 
			$draw_move_buttons = false, $draw_delete_button = true )
	{
		$text = "";
		$text .= '<TD valign="top" class="swMntMidSection">';
		$text .= '<TABLE class="swMntMidSectionTable">';

		$ct = 0;
		foreach ( $ar as $key => $val )
		{
			$drawup = false;
			$drawdown = false;
			if ( $draw_move_buttons )
			{
				if ( $ct > 0 )
					$drawup = true;
				if ( $ct < count($ar) - 1 )
					$drawdown = true;
			}


			$padstring = $id.str_pad($key, 4, "0", STR_PAD_LEFT);
			if ( $labindex == "_key" )
				$text .= $this->draw_show_hide_vtab_button ($padstring, $labtext." ".$key, $drawup, $drawdown, $draw_delete_button) ;
			else
				$text .= $this->draw_show_hide_vtab_button ($padstring, $labtext." ".$val[$labindex], $drawup, $drawdown, $draw_delete_button) ;
			$ct++;
		}
		$text .= '<TR><TD>&nbsp;</TD></TR>';
		$text .= '</TABLE>';
		$text .= '</TD>';


		return $text;
	}

	function xml2php () 
	{

		$code = "";

		$code .= "<?php\n\n\n";
		$code .= "\n\t// ------------------------------------------------\n";
		$code .=   "\t// --            Main Query Setup                --\n";
		$code .=   "\t// ------------------------------------------------\n";

		$code .= "\n\tinclude_once(\"cog/cog.php\");";
		$code .= "\n";
		$code .= "\t\$a = new reportico_query();\n";

		if ( array_key_exists("CogModule",$this->data ) )
			$q =& $this->data["CogModule"];
		else
			$q =& $this->data["Report"];

		$criteria_links = array();

		$ds = false;
		foreach ( $q as $cogquery )
		{

			$code .= "\n";
			// Set Query Attributes
			foreach ( $cogquery["Format"] as $att => $val )
			{
				if ( $val )
					$code .= "\t\$a->set_attribute(\"$att\", \"".addcslashes($val,'"')."\");\n";
			}

			$code .= "\n\t// Set Data Connectivity\n";
			// Set DataSource
			foreach ( $cogquery["Datasource"] as $att => $val )
			{
				if ( $att == "SourceType" )
					$code .= "\t\$a->source_type = \"$val\";\n";

				if ( $att == "SourceConnection" )
				{
					$ar =& $val;
					$dt = $val["DatabaseType"];
					$dn = $val["DatabaseName"];
					$hn = $val["HostName"];
					$sn = $val["ServiceName"];
					$un = $val["UserName"];
					$pw = $val["Password"];
					$code .= "\t\$ds = new reportico_datasource(\"$dt\", \n\t\t\"$hn\", \n\t\t\"$sn\" \n\t\t);\n";
					$code .= "\t\$ds->set_database(\"$dn\");\n";
					$code .= "\t\$a->set_datasource(\$ds);\n";
				}
			}

			// Set Query Columns
			if ( ! ($ef =& $this->get_array_element($cogquery,"EntryForm")) )
			{
				$this->ErrorMsg = "No EntryForm tag within Format";
				return false;
			}

			if ( ! ($qu =& $this->get_array_element($ef,"Query")) )
			{
				$this->ErrorMsg = "No Query tag within EntryForm";
				return false;
			}

			$code .= "\n\t// Set Main Query Details\n";
			$code .= "\t\$a->table_text = \"".$this->get_array_element($qu,"TableSql")."\";\n";
			$code .= "\t\$a->where_text = \"".$this->get_array_element($qu,"WhereSql")."\";\n";
			$code .= "\t\$a->group_text = \"".$this->get_array_element($qu,"GroupSql")."\";\n";
			$code .= "\t\$a->rowselection = \"".$this->get_array_element($qu,"RowSelection")."\";\n";


			if ( ! ($qc =& $this->get_array_element($qu,"QueryColumns")) )
			{
				$this->ErrorMsg = "No QueryColumns tag within Query";
				return false;
			}

	
			$code .= "\n\t// Set Main Query Columns\n";
			// Generate reportico_query_column for each column found
			foreach ( $qc as $col )
			{
				$code .= "\n\t// Setup Column ".$col["Name"]."\n";
				$in_query = true;
				if ( !$col["ColumnName"] )
					$in_query = false;

				$tmptext = "false";
				if ( $in_query )
					$tmptext = "true";
				$code .= "\t\$a->create_criteria_column (".
					"\n\t\t\"".$col["Name"]."\",".
					"\"".$col["TableName"]."\",".
					"\"".$col["ColumnName"]."\",".
					"\"".$col["ColumnType"]."\",".
					"\"".$col["ColumnLength"]."\",".
					"\""."###.##"."\",".
					$tmptext.");\n";

				$code .= "\n";
				// Set any Attributes
				if ( ($fm =& $this->get_array_element($col,"Format")) )
				{
					foreach ( $fm as $att => $val )
					{
						if ( $val )
							$code .= "\t\$a->set_column_attribute(\"".$col["Name"]."\", \"$att\", \"$val\" );\n";
					}
				}

			}

			$code .= "\n\t// Set Query Ordering\n";
			// Generate Order By List
			if ( ($oc =& $this->get_array_element($qu,"OrderColumns")) )
			{
				// Generate reportico_query_column for each column found
				foreach ( $oc as $col )
				{
					if ( !$col["Name"] )
						return;
					$code .= "\t\$a->create_order_column (\"".
						$col["Name"]."\", \"".
						$col["OrderType"]."\");\n";
				}
			}


			$code .= "\n\t// Set Query Pre-SQL\n";
			// Generate Query Assignments
			if ( ($pq =& $this->get_array_element($qu,"PreSQLS")) )
			{
				foreach ( $pq as $col )
				{
					$code .= "\t\$a->add_pre_sql(\"".$col["SQLText"]."\");\n";
				}
			}

			// Generate Query Assignments
			$code .= "\n\t// Set Query Assignments\n";
			if ( ($as =& $this->get_array_element($ef,"Assignments")) )
			{
				foreach ( $as as $col )
				{
					$code .= "\t\$a->add_assignment ( \"".$col["AssignName"]."\", \"".$col["Expression"]."\", \"". $col["Condition"]."\");\n";
				}
			}

			// Generate Output Information...
			$code .= "\n\t// ------------------------------------------------\n";
			$code .=   "\t// --             Output Section                 --\n";
			$code .=   "\t// ------------------------------------------------\n";

			if ( ($op =& $this->get_array_element($ef,"Output")) )
			{
				// Generate Page Headers
				$code .= "\n\t// Set Page Headers\n";
				if ( ($ph =  $this->get_array_element($op, "PageHeaders")) )
				{
					foreach ( $ph as $k => $phi )
					{
						$code .= "\t\$a->create_page_header(".$k.", ".$phi["LineNumber"].", ".$phi["HeaderText"]." );\n";
						if ( ($fm =& $this->get_array_element($phi,"Format")) )
							foreach ( $fm as $att => $val )
							{
								$code .= "\$a->set_page_header_attribute(".$k.", ".$att.", ".$val." );\n";
							}
					}
				}

				// Generate Page Footers
				$code .= "\n\t// Set Page Footers\n";
				if ( ($ph =  $this->get_array_element($op, "PageFooters")) )
				{
					foreach ( $ph as $k => $phi )
					{
						$code .= "\$a->create_page_footer($k, ".$phi["LineNumber"].", ".$phi["FooterText"]." );\n";
						if ( ($fm =& $this->get_array_element($phi,"Format")) )
							foreach ( $fm as $att => $val )
							{
								$code .= "\$a->set_page_footer_attribute($k, $att, $val );\n";
							}
					}
				}
				
				// Generate Display Orders
				$code .= "\n\t// Set Display Order\n";
				if ( ($ph =  $this->get_array_element($op, "DisplayOrders")) )
				{
					foreach ( $ph as $k => $phi )
					{
						$code .= "\t\$a->set_column_order(\"".$phi["ColumnName"]."\", ".$phi["OrderNumber"]." );\n";
					}
				}

				if ( ($ph =  $this->get_array_element($op, "Groups")) )
				{
					foreach ( $ph as $k => $phi )
					{
						$gpname = $phi["GroupName"];
						$code .= "\t\$grn =& \$a->create_group( \"$gpname\" );\n";
						
						$grn =& $this->query->create_group( $gpname );
						$code .= "\tif ( array_key(\"BeforeGroupHeader\", \$phi ) )";
						$code .= "\t{";
						$code .= "\t\t\$grn->set_attribute(\"before_header\",\$phi[\"BeforeGroupHeader\"]);";
						$code .= "\t\t\$grn->set_attribute(\"after_header\",\$phi[\"AfterGroupHeader\"]);";
						$code .= "\t\t\$grn->set_attribute(\"before_trailer\",\$phi[\"BeforeGroupTrailer\"]);";
						$code .= "\t\t\$grn->set_attribute(\"after_trailer\",\$phi[\"AfterGroupTrailer\"]);";
						$code .= "\t\t\$grn->set_format(\"before_header\",\$phi[\"BeforeGroupHeader\"]);";
						$code .= "\t\t\$grn->set_format(\"after_header\",\$phi[\"AfterGroupHeader\"]);";
						$code .= "\t\t\$grn->set_format(\"before_trailer\",\$phi[\"BeforeGroupTrailer\"]);";
						$code .= "\t\t\$grn->set_format(\"after_trailer\",\$phi[\"AfterGroupTrailer\"]);";
						$code .= "\t}";
		
						if ( ($gp =& $this->get_array_element($phi,"GroupHeaders")) )
							foreach ( $gp as $att => $val )
							{
									$code .= "\t\$a->create_group_header(\"$gpname\", \"".$val["GroupHeaderColumn"]."\");\n";
							}

						if ( ($gp =& $this->get_array_element($phi,"GroupTrailers")) )
							foreach ( $gp as $att => $val )
							{
									$code .= "\t\$a->create_group_trailer(\"$gpname\", \"".$val["GroupTrailerDisplayColumn"]."\", \"".
																	$val["GroupTrailerValueColumn"]."\");\n";
							}
					}
				}
				// if ( ($gph =  $this->get_array_element($op, "Graphs")) )
				// {
					// foreach ( $gph as $k => $gphi )
					// {
						// $code .= "\t\$a->create_page_header(".$k.", ".$gphi["LineNumber"].", ".$phi["HeaderText"]." );\n";
						// if ( ($fm =& $this->get_array_element($phi,"Format")) )
							// foreach ( $fm as $att => $val )
							// {
								// $code .= "\$a->set_page_header_attribute(".$k.", ".$att.", ".$val." );\n";
							// }
					// }
				// }

			} // Output

			$code .= "\n\t// ------------------------------------------------\n";
			$code .=   "\t// --            Criteria Setup                  --\n";
			$code .=   "\t// ------------------------------------------------\n";

			// Check for Criteria Items ...
			if ( ($crt =& $this->get_array_element($ef,"Criteria")) )
			{
			foreach ( $crt as $ci )
			{
				$critnm = $this->get_array_element($ci, "Name") ;
				$crittb = $this->get_array_element($ci, "QueryTableName") ;
				$critcl = $this->get_array_element($ci, "QueryColumnName") ;
				if ( $crittb )
				{
					$critcl = $crittb.".".$critcl;
					$crittb = "";
				}
				$crittp = $this->get_array_element($ci, "CriteriaType") ;
				$critlt = $this->get_array_element($ci, "CriteriaList") ;
				$critds = $this->get_array_element($ci, "CriteriaDisplay") ;
				$critexp = $this->get_array_element($ci, "ExpandDisplay") ;

				if ( $crittp == "ANYCHAR" ) $crittp = "TEXTFIELD";
				if ( $critds == "ANYCHAR" ) $critds = "TEXTFIELD";
				if ( $critexp == "ANYCHAR" ) $critexp = "TEXTFIELD";

				$critmatch = $this->get_array_element($ci, "MatchColumn") ;
				$critdefault = $this->get_array_element($ci, "CriteriaDefaults") ;
				$crittitle = $this->get_array_element($ci, "Title") ;
				$crit_lookup_return = $this->get_array_element($ci, "ReturnColumn");
				$crit_lookup_display = $this->get_array_element($ci, "DisplayColumn");
				$crit_criteria_display = $this->get_array_element($ci, "OverviewColumn");

				// Generate Query Columns
				if ( !($ciq =& $this->get_array_element($ci,"Query")) )
				{
					continue;
				}

				$code .= "\n\t// Setup Criteria Item $critnm\n";
				$code .= "\t// ---------------------------\n";
				$code .= "\t\$critquery = new reportico_query();\n";

				// Generate Criteria Query Columns
				if ( ($ciqc =& $this->get_array_element($ciq,"QueryColumns")) )
				{
					foreach ( $ciqc as $ccol )
					{
						$code .= "\n\t// Setup Column ".$col["Name"]."\n";
						$in_query = true;
						if ( !$ccol["TableName"] )
							$in_query = false;

						$tmptext = "false";
						if ( $in_query )
							$tmptext = "true";
						$code .= "\t\$critquery->create_criteria_column (".
							"\n\t\t\"".$ccol["Name"]."\",".
							"\"".$ccol["TableName"]."\",".
							"\"".$ccol["ColumnName"]."\",".
							"\"".$ccol["ColumnType"]."\",".
							"\"".$ccol["ColumnLength"]."\",".
							"\""."###.##"."\",".
							$tmptext.");\n";
						
					}
				}
				// Generate Order By List
            			$code .= "\n\t// Set Criteria Query Ordering\n";
				if ( ($coc =& $this->get_array_element($ciq,"OrderColumns")) )
				{
					// Generate reportico_query_column for each column found
					foreach ( $coc as $col )
					{
						$code .= "\t\$critquery->create_order_column (".
							$col["Name"].",".
							$col["OrderType"].");\n";
					}
				}
	
					
				$code .= "\n\t// Set Criteria Query Assignments\n";
				if ( ($as =& $this->get_array_element($ciq,"Assignments")) )
				{
					foreach ( $as as $ast )
					{
						$code .= "\t\$critquery->add_assignment ( \"".$ast["AssignName"]."\", \"".$ast["Expression"]."\", \"". $ast["Condition"]."\");\n";
					}
				}

				// Generate Criteria Links  In Array for later use
				if ( ($cl =& $this->get_array_element($ci,"CriteriaLinks")) )
				{
					foreach  ( $cl as $clitem )
					{
						$criteria_links[] = array ( 
									"LinkFrom" => $clitem["LinkFrom"],
									"LinkTo" => $clitem["LinkTo"],
									"LinkClause" => $clitem["LinkClause"]
									);
					}
				}

				// Set Query SQL Text
				$code .= "\t\$critquery->table_text = \"".$this->get_array_element($ciq,"TableSql")."\";\n";
				$code .= "\t\$critquery->where_text = \"".$this->get_array_element($ciq,"WhereSql")."\";\n";
				$code .= "\t\$critquery->group_text = \"".$this->get_array_element($ciq,"GroupSql")."\";\n";
				$code .= "\t\$critquery->rowselection = \"".$this->get_array_element($ciq,"RowSelection")."\";\n";
				$code .= "\t\$critquery->set_lookup_return(\"$crit_lookup_return\");\n";
				$code .= "\t\$critquery->set_lookup_display(\"$crit_lookup_display\", \"$crit_criteria_display\");\n";
				$code .= "\t\$critquery->set_lookup_expand_match(\"$critmatch\");\n";
				$code .= "\n";
				$code .= "\t\$a->set_criteria_lookup(\"$critnm\", \$critquery, \"$crittb\", \"$critcl\");\n";
				$code .= "\t\$a->set_criteria_input(\"$critnm\", \"$crittp\", \"$critds\", \"$critexp\", \"$crituse\");\n";
				$code .= "\t\$a->set_criteria_list(\"$critlt\");\n";
				$code .= "\t\$a->set_criteria_attribute(\"$critnm\", \"column_title\", \"$crittitle\");\n";
				$code .= "\t\$a->set_criteria_defaults(\"$critnm\", \"$critdefault\");\n";
					
			} // End Criteria Item

            $code .= "\n\t// Set Criteria Links\n";
			// Set up any Criteria Links
			foreach ( $criteria_links as $cl )
			{
					$code .= "\t\$a->set_criteria_link(\"".$cl["LinkFrom"]."\", \"".$cl["LinkTo"]."\", \"".$cl["LinkClause"]."\");\n";
			}
		}
		}
        $code .= "\n\t// Run the Report\n";
        $code .= "\t\$a->execute();";
		$code .= "\n\n?>";

		// Now we have code set in a string output sensible according to 
		// whether browser is used or not.
		if ( array_key_exists("HTTP_USER_AGENT", $_SERVER ) )
		{
			// Send to browser
			echo "<PRE>";
			echo htmlspecialchars($code);
			echo "</PRE>";
		}
		else
		{
			echo $code;
		}

	}

	function xml2query () 
	{
		global $conf;
		
		if ( array_key_exists("CogModule",$this->data ) )
			$q =& $this->data["CogModule"];
		else
			$q =& $this->data["Report"];

		$criteria_links = array();

		// Generate Output Information...
		$ds = false;
		if ( !$q )
			return;
		foreach ( $q as $cogquery )
		{
			// Set Query Attributes
			foreach ( $cogquery["Format"] as $att => $val )
			{
				$this->query->set_attribute($att, $val);
			}

			// Set DataSource
			foreach ( $cogquery["Datasource"] as $att => $val )
			{
				if ( $att == "SourceType" )
					$this->query->source_type = $val;

				if ( $att == "SourceConnection" )
				{
					$ar =& $val;
					$dt = $val["DatabaseType"];
					$dt = "";
					// Is this correct?
					$dn = $val["DatabaseName"];
					$hn = $val["HostName"];
					$sn = $val["ServiceName"];
					$un = $val["UserName"];
					$pw = $val["Password"];
					$ds = new reportico_datasource($dt, $hn, $sn );
					$ds->set_database($dn);
					$this->query->set_datasource($ds);
				}
			}

			// Set Query Columns
			if ( ! ($ef =& $this->get_array_element($cogquery,"EntryForm")) )
			{
				$this->ErrorMsg = "No EntryForm tag within Format";
				return false;
			}

			if ( ! ($qu =& $this->get_array_element($ef,"Query")) )
			{
				$this->ErrorMsg = "No Query tag within EntryForm";
				return false;
			}
			
			$dolireport = str_replace('.xml', '', $_GET["xmlin"]);
			
			switch ($dolireport)
			{
				case "Products":
					$dolentity=	"entity IN (".getEntity('product', 1).")";
					break;
				case "Thirds":
					$dolentity=	"entity IN (".getEntity('societe', 1).")";
					break;
				Case "Contacts":
					$dolentity=	"entity IN (".getEntity('societe', 1).")";
					break;
				default:
					$dolentity= "entity=".$conf->entity;
			}
	
			$this->query->table_text = $this->get_array_element($qu,"TableSql");
			$this->query->where_text = str_replace("entity=1", $dolentity, $this->get_array_element($qu,"WhereSql"));
			$this->query->group_text = $this->get_array_element($qu,"GroupSql");
			$this->query->rowselection = $this->get_array_element($qu,"RowSelection");
		
			$has_cols = true;
			if ( ! ($qc =& $this->get_array_element($qu,"QueryColumns")) )
			{
				$this->ErrorMsg = "No QueryColumns tag within Query";
				$has_cols = false;
			}

			// Generate reportico_query_column for each column found
			if ( $has_cols )
			{
				foreach ( $qc as $col )
				{
					$in_query = true;
					if ( !$col["ColumnName"] )
						$in_query = false;

					$this->query->create_criteria_column 
					(
						$col["Name"],
						$col["TableName"],
						$col["ColumnName"],
						$col["ColumnType"],
						$col["ColumnLength"],
						"###.##",
						$in_query
					);
	
					// Set any Attributes
					if ( ($fm =& $this->get_array_element($col,"Format")) )
					{
						foreach ( $fm as $att => $val )
						{
							$this->query->set_column_attribute($col["Name"], $att, $val );
						}
					}
				}


				// Generate Order By List
				if ( ($oc =& $this->get_array_element($qu,"OrderColumns")) )
				{
					// Generate reportico_query_column for each column found
					foreach ( $oc as $col )
					{
						if ( !$col["Name"] )
							return;
						$this->query->create_order_column 
						(
							$col["Name"],
							$col["OrderType"]
						);
					}
				}

				// Generate Query Assignments
				if ( ($as =& $this->get_array_element($ef,"Assignments")) )
				{
					foreach ( $as as $col )
					{
						if ( array_key_exists("AssignName", $col ) )
							$this->query->add_assignment ( $col["AssignName"], $col["Expression"], $col["Condition"]);
						else
							$this->query->add_assignment ( $col["Name"], $col["Expression"], $col["Condition"]);
					}
				}
			}


			// Generate Query Assignments
			if ( ($pq =& $this->get_array_element($qu,"PreSQLS")) )
			{
				foreach ( $pq as $col )
				{
					$this->query->add_pre_sql($col["SQLText"]);
				}
			}

			// Generate Output Information...
			if ( ($op =& $this->get_array_element($ef,"Output")) )
			{
				// Generate Page Headers
				if ( ($ph =  $this->get_array_element($op, "PageHeaders")) )
				{
					foreach ( $ph as $k => $phi )
					{
						$this->query->create_page_header($k, $phi["LineNumber"], $phi["HeaderText"] );
						if ( ($fm =& $this->get_array_element($phi,"Format")) )
							foreach ( $fm as $att => $val )
							{
								$this->query->set_page_header_attribute($k, $att, $val );
							}
					}
				}

				// Generate Page Footers
				if ( ($ph =  $this->get_array_element($op, "PageFooters")) )
				{
					foreach ( $ph as $k => $phi )
					{
						$this->query->create_page_footer($k, $phi["LineNumber"], $phi["FooterText"] );
						if ( ($fm =& $this->get_array_element($phi,"Format")) )
							foreach ( $fm as $att => $val )
							{
								$this->query->set_page_footer_attribute($k, $att, $val );
							}
					}
				}

				// Generate Display Orders
				if ( $has_cols && ($ph =  $this->get_array_element($op, "DisplayOrders")) )
				{
					foreach ( $ph as $k => $phi )
					{
						$this->query->set_column_order($phi["ColumnName"], $phi["OrderNumber"] );
					}
				}

				if ( $has_cols && ($ph =  $this->get_array_element($op, "Groups")) )
				{
					foreach ( $ph as $k => $phi )
					{
						if ( array_key_exists("GroupName", $phi ) )
							$gpname = $phi["GroupName"];
						else
							$gpname = $phi["Name"];

						$grn =$this->query->create_group( $gpname );

						if ( array_key_exists("BeforeGroupHeader", $phi ) )
						{
							$grn->set_attribute("before_header",$phi["BeforeGroupHeader"]);
							$grn->set_attribute("after_header",$phi["AfterGroupHeader"]);
							$grn->set_attribute("before_trailer",$phi["BeforeGroupTrailer"]);
							$grn->set_attribute("after_trailer",$phi["AfterGroupTrailer"]);
							$grn->set_format("before_header",$phi["BeforeGroupHeader"]);
							$grn->set_format("after_header",$phi["AfterGroupHeader"]);
							$grn->set_format("before_trailer",$phi["BeforeGroupTrailer"]);
							$grn->set_format("after_trailer",$phi["AfterGroupTrailer"]);
						}
		
						if ( ($gp =& $this->get_array_element($phi,"GroupHeaders")) )
							foreach ( $gp as $att => $val )
							{
									$this->query->create_group_header($gpname, $val["GroupHeaderColumn"]);
							}

						if ( ($gp =& $this->get_array_element($phi,"GroupTrailers")) )
							foreach ( $gp as $att => $val )
							{
									$this->query->create_group_trailer($gpname, $val["GroupTrailerDisplayColumn"], 
																	$val["GroupTrailerValueColumn"]);
							}
					}
				}

				// Generate Graphs
				if ( $has_cols && ($gph =  $this->get_array_element($op, "Graphs")) )
				{
					foreach ( $gph as $k => $gphi )
					{
						$ka = array_keys($gphi);
						$gph =& $this->query->create_graph();

						$gph->set_graph_column($gphi["GraphColumn"]);

						$gph->set_title($gphi["Title"]);
						$gph->set_xtitle($gphi["XTitle"]);
						$gph->set_xlabel_column($gphi["XLabelColumn"]);
						$gph->set_ytitle($gphi["YTitle"]);
						//$gph->set_ylabel_column($gphi["YLabelColumn"]);
						//////HERE!!!
						if ( array_key_exists("GraphWidth", $gphi ) )
						{
							$gph->set_width($gphi["GraphWidth"]);
							$gph->set_height($gphi["GraphHeight"]);
							$gph->set_width_pdf($gphi["GraphWidthPDF"]);
							$gph->set_height_pdf($gphi["GraphHeightPDF"]);
						}
						else
						{
							$gph->set_width($gphi["Width"]);
							$gph->set_height($gphi["Height"]);
						}

						if ( array_key_exists("GraphColor", $gphi ) )
						{
							$gph->set_graph_color($gphi["GraphColor"]);
							$gph->set_grid($gphi["GridPosition"],
								$gphi["XGridDisplay"],$gphi["XGridColor"],
								$gphi["YGridDisplay"],$gphi["YGridColor"]
								);
							$gph->set_title_font($gphi["TitleFont"], $gphi["TitleFontStyle"],
								$gphi["TitleFontSize"], $gphi["TitleColor"]);
							$gph->set_xtitle_font($gphi["XTitleFont"], $gphi["XTitleFontStyle"],
								$gphi["XTitleFontSize"], $gphi["XTitleColor"]);
							$gph->set_ytitle_font($gphi["YTitleFont"], $gphi["YTitleFontStyle"],
								$gphi["YTitleFontSize"], $gphi["YTitleColor"]);
							$gph->set_xaxis($gphi["XTickInterval"],$gphi["XTickLabelInterval"],$gphi["XAxisColor"]);
							$gph->set_yaxis($gphi["YTickInterval"],$gphi["YTickLabelInterval"],$gphi["YAxisColor"]);
							$gph->set_xaxis_font($gphi["XAxisFont"], $gphi["XAxisFontStyle"],
								$gphi["XAxisFontSize"], $gphi["XAxisFontColor"]);
							$gph->set_yaxis_font($gphi["YAxisFont"], $gphi["YAxisFontStyle"],
								$gphi["YAxisFontSize"], $gphi["YAxisFontColor"]);
							$gph->set_margin_color($gphi["MarginColor"]);
							$gph->set_margins($gphi["MarginLeft"], $gphi["MarginRight"],
								$gphi["MarginTop"], $gphi["MarginBottom"]);
						}
						foreach ( $gphi["Plots"] as $pltk => $pltv )
						{
							$pl =& $gph->create_plot($pltv["PlotColumn"]);
                			$pl["type"] = $pltv["PlotType"];
                			$pl["fillcolor"] = $pltv["FillColor"];
                			$pl["linecolor"] = $pltv["LineColor"];
                			$pl["legend"] = $pltv["Legend"];
						}
					}
				}

			} // Output

			// Check for Criteria Items ...

			if ( ($crt =& $this->get_array_element($ef,"Criteria")) )
			{
			foreach ( $crt as $ci )
			{
				$critnm = $this->get_array_element($ci, "Name") ;
				$crittb = $this->get_array_element($ci, "QueryTableName") ;
				$critcl = $this->get_array_element($ci, "QueryColumnName") ;
				if ( $crittb )
				{
					$critcl = $crittb.".".$critcl;
					$crittb = "";
				}
				$crittp = $this->get_array_element($ci, "CriteriaType") ;
				$critlt = $this->get_array_element($ci, "CriteriaList") ;
				$crituse = $this->get_array_element($ci, "Use") ;
				$critds = $this->get_array_element($ci, "CriteriaDisplay") ;
				$critexp = $this->get_array_element($ci, "ExpandDisplay") ;
				$critmatch = $this->get_array_element($ci, "MatchColumn") ;
				$critdefault = $this->get_array_element($ci, "CriteriaDefaults") ;
				$crittitle = $this->get_array_element($ci, "Title") ;
				$crit_lookup_return = $this->get_array_element($ci, "ReturnColumn");
				$crit_lookup_display = $this->get_array_element($ci, "DisplayColumn");
				$crit_criteria_display = $this->get_array_element($ci, "OverviewColumn");

				if ( $crittp == "ANYCHAR" ) $crittp = "TEXTFIELD";
				if ( $critds == "ANYCHAR" ) $critds = "TEXTFIELD";
				if ( $critexp == "ANYCHAR" ) $critexp = "TEXTFIELD";

				// Generate Query Columns
				if ( !($ciq =& $this->get_array_element($ci,"Query")) )
				{
					continue;
				}

				$critquery = new reportico_query();

				// Generate Criteria Query Columns
				if ( ($ciqc =& $this->get_array_element($ciq,"QueryColumns")) )
				{
					foreach ( $ciqc as $ccol )
					{
						$in_query = true;
						if ( !$ccol["ColumnName"] )
							$in_query = false;

						$critquery->create_criteria_column 
						(
							$ccol["Name"],
							$ccol["TableName"],
							$ccol["ColumnName"],
							$ccol["ColumnType"],
							$ccol["ColumnLength"],
							"###.##",
							$in_query
						);
					}
				}
				// Generate Order By List
				if ( ($coc =& $this->get_array_element($ciq,"OrderColumns")) )
				{
					// Generate reportico_query_column for each column found
					foreach ( $coc as $col )
					{
						$critquery->create_order_column 
						(
							$col["Name"],
							$col["OrderType"]
						);
					}
				}
	
					
				if ( ($as =& $this->get_array_element($ciq,"Assignments")) )
				{
					foreach ( $as as $ast )
					{
						if ( array_key_exists("AssignName", $ast ) )
							$critquery->add_assignment ( $ast["AssignName"], $ast["Expression"], $ast["Condition"]);
						else
							$critquery->add_assignment ( $ast["Name"], $ast["Expression"], $ast["Condition"]);
					}
				}

				// Generate Criteria Links  In Array for later use
				if ( ($cl =& $this->get_array_element($ci,"CriteriaLinks")) )
				{
					foreach  ( $cl as $clitem )
					{
						$criteria_links[] = array ( 
									"LinkFrom" => $clitem["LinkFrom"],
									"LinkTo" => $clitem["LinkTo"],
									"LinkClause" => $clitem["LinkClause"]
									);
					}
				}

				// Set Query SQL Text
				
				$dolicrit = strtolower($ci['Name']);
				
				switch ($dolicrit)
				{
					case "product":
						$dolentity=	"entity IN (".getEntity('product', 1).")";
						break;
					case "customer":
						$dolentity=	"entity IN (".getEntity('societe', 1).")";
						break;
					case "category":
						$dolentity=	"entity IN (".getEntity('category', 1).")";
						break;
					default:
						$dolentity= "entity=".$conf->entity;
				}

				$critquery->table_text = $this->get_array_element($ciq,"TableSql");
				$critquery->where_text = str_replace("entity=1", $dolentity, $this->get_array_element($ciq,"WhereSql"));
				$critquery->group_text = $this->get_array_element($ciq,"GroupSql");
				$critquery->rowselection = $this->get_array_element($ciq,"RowSelection");
				$critquery->set_lookup_return($crit_lookup_return);
				$critquery->set_lookup_display($crit_lookup_display, $crit_criteria_display);
				$critquery->set_lookup_expand_match($critmatch);
				$this->query->set_criteria_lookup($critnm, $critquery, $crittb, $critcl);
				$this->query->set_criteria_input($critnm, $crittp, $critds, $critexp, $crituse);
				$this->query->set_criteria_list($critnm, $critlt);
				$this->query->set_criteria_attribute($critnm, "column_title", $crittitle);
				
				$this->query->set_criteria_defaults($critnm, $critdefault);
					
			} // End Criteria Item

			// Set up any Criteria Links
			foreach ( $criteria_links as $cl )
			{
					$this->query->set_criteria_link($cl["LinkFrom"], $cl["LinkTo"], $cl["LinkClause"]);
			}
		}
		}

	}
}


/**
 * Class reportico_xml_writer
 *
 * Responsible for converting the current report back into XML format
 * and for saving that report XML back to disk. 
 */
class reportico_xml_writer
{
	var $panel_type;
	var $query = NULL;
	var $visible = true;
	var $text = "";
	var $program = "";
	var $xml_version = "1.0";
	var $xmldata;

	function reportico_xml_writer(&$in_query)
	{
		$this->query = &$in_query;
	}

	function set_visibility($in_visibility)
	{
		$this->visible = $in_visibility;
	}

	function add_panel(&$in_panel)
	{
		$this->panels[] = &$in_panel;
	}


	function prepare_xml_data()
	{
		$xmlval = new reportico_xmlval ( "Report" );

		$cq =& $xmlval->add_xmlval ( "ReportQuery" );

		$at =& $cq->add_xmlval ( "Format" );

		// Query Attributes
		foreach  ( $this->query->attributes as $k => $v )
		{
				$el =& $at->add_xmlval ( $k, $v );
		}


		//$el =& $cq->add_xmlval ( "Name", $this->query->name );

		// Export Data Connection Details
		$ds =& $cq->add_xmlval ( "Datasource" );
		$el =& $ds->add_xmlval ( "SourceType", $this->query->source_type );

		$cn =& $ds->add_xmlval ( "SourceConnection" );
		switch ( $this->query->source_type )
		{
			case "database":
			case "informix":
			case "mysql":
			case "sqlite-2":
			case "sqlite-3":
				$el =& $cn->add_xmlval ( "DatabaseType",$this->query->datasource->driver );
				$el =& $cn->add_xmlval ( "DatabaseName",$this->query->datasource->database );
				$el =& $cn->add_xmlval ( "HostName",$this->query->datasource->host_name );
				$el =& $cn->add_xmlval ( "ServiceName",$this->query->datasource->service_name );
				$el =& $cn->add_xmlval ( "UserName",$this->query->datasource->user_name );
				$el =& $cn->add_xmlval ( "Password",$this->query->datasource->password );
				break;

			default:
				$el =& $cn->add_xmlval ( "DatabaseType",$this->query->datasource->driver );
				$el =& $cn->add_xmlval ( "DatabaseName",$this->query->datasource->database );
				$el =& $cn->add_xmlval ( "HostName",$this->query->datasource->host_name );
				$el =& $cn->add_xmlval ( "ServiceName",$this->query->datasource->service_name );
				$el =& $cn->add_xmlval ( "UserName",$this->query->datasource->user_name );
				$el =& $cn->add_xmlval ( "Password",$this->query->datasource->password );
				break;

		}

		$this->xmldata =& $xmlval;
		
		// Export Main Entry Form Parameters
		$ef =& $cq->add_xmlval ( "EntryForm" );

		// Export Main Query Parameters
		$qr =& $ef->add_xmlval ( "Query" );
		$el =& $qr->add_xmlval ( "TableSql", $this->query->table_text );
		$el =& $qr->add_xmlval ( "WhereSql", $this->query->where_text );
		$el =& $qr->add_xmlval ( "GroupSql", $this->query->group_text );
		$el =& $qr->add_xmlval ( "RowSelection", $this->query->rowselection );
		$sq =& $qr->add_xmlval ( "SQL" );
		$el =& $sq->add_xmlval ( "QuerySql", "" );
		$qcs =& $qr->add_xmlval ( "QueryColumns" );
		foreach ( $this->query->columns as $col )
		{
			$qc =& $qcs->add_xmlval ( "QueryColumn" );
			$el =& $qc->add_xmlval ( "Name", $col->query_name );
			$el =& $qc->add_xmlval ( "TableName", $col->table_name );
			$el =& $qc->add_xmlval ( "ColumnName", $col->column_name );
			$el =& $qc->add_xmlval ( "ColumnType", $col->column_type );
			$el =& $qc->add_xmlval ( "ColumnLength", $col->column_length );

			// Column Attributes
			$at =& $qc->add_xmlval ( "Format" );
			foreach  ( $col->attributes as $k => $v )
				//if ( $v )
					$el =& $at->add_xmlval ( $k, $v );

		}

		$qos =& $qr->add_xmlval ( "OrderColumns" );
		foreach ( $this->query->order_set as $col )
		{
			$qoc =& $qos->add_xmlval ( "OrderColumn" );
			$el =& $qoc->add_xmlval ( "Name", $col->query_name );
			$el =& $qoc->add_xmlval ( "OrderType", $col->order_type );
		}

		$prcr =& $qr->add_xmlval ( "PreSQLS" );
		foreach ( $this->query->pre_sql as $prsq )
		{
			$sqtx =& $prcr->add_xmlval ( "PreSQL" );
			$el =& $sqtx->add_xmlval ( "SQLText", $prsq );
		}


		// Output Assignments
		$as =& $ef->add_xmlval ( "Assignments" );
		foreach ( $this->query->assignment as $col )
		{
			$qcas =& $as->add_xmlval ( "Assignment" );
			$el =& $qcas->add_xmlval ( "AssignName", $col->query_name );
			$el =& $qcas->add_xmlval ( "AssignNameNew", "" );
			$el =& $qcas->add_xmlval ( "Expression", $col->raw_expression );
			$el =& $qcas->add_xmlval ( "Condition", $col->raw_criteria );
		}


		// Add Lookup Attributes As Separate Criteria Item
		$cr =& $ef->add_xmlval ( "Criteria" );
		foreach ( $this->query->lookup_queries as $lq )
		{
			// find which columns are for returning displaying etc
			$lookup_return_col = "";
			$lookup_display_col = "";
			$lookup_abbrev_col = "";
				
			foreach ( $lq->lookup_query->columns as $cqc )
			{
				if ( $cqc->lookup_return_flag )
				{
					$lookup_return_col = $cqc->query_name;
				}
				if ( $cqc->lookup_display_flag )
				{
					$lookup_display_col = $cqc->query_name;
				}
				if ( $cqc->lookup_abbrev_flag )
				{
					$lookup_abbrev_col = $cqc->query_name;
				}
			}
			$ci =& $cr->add_xmlval ( "CriteriaItem" );
			$el =& $ci->add_xmlval ( "Name", $lq->query_name );
			$el =& $ci->add_xmlval ( "Title", $lq->get_attribute("column_title") );
			$el =& $ci->add_xmlval ( "QueryTableName", $lq->table_name );
			$el =& $ci->add_xmlval ( "QueryColumnName", $lq->column_name );
			$el =& $ci->add_xmlval ( "CriteriaType", $lq->criteria_type );
			if ( defined("SW_DYNAMIC_ORDER_GROUP" ) )
				$el =& $ci->add_xmlval ( "Use", $lq->_use );
			$el =& $ci->add_xmlval ( "CriteriaDisplay", $lq->criteria_display );
			$el =& $ci->add_xmlval ( "ExpandDisplay", $lq->expand_display );
			$el =& $ci->add_xmlval ( "ReturnColumn", $lookup_return_col );
			$el =& $ci->add_xmlval ( "DisplayColumn", $lookup_display_col );
			$el =& $ci->add_xmlval ( "OverviewColumn", $lookup_abbrev_col );
			$el =& $ci->add_xmlval ( "MatchColumn", $lq->lookup_query->match_column );
			$el =& $ci->add_xmlval ( "CriteriaDefaults", $lq->defaults_raw );
			$el =& $ci->add_xmlval ( "CriteriaList", $lq->criteria_list );
			$q2 =& $ci->add_xmlval ( "Query" );
			$el =& $q2->add_xmlval ( "TableSql", $lq->lookup_query->table_text );
			$el =& $q2->add_xmlval ( "WhereSql", $lq->lookup_query->where_text );
			$el =& $q2->add_xmlval ( "GroupSql", $lq->lookup_query->group_text );
			$el =& $q2->add_xmlval ( "RowSelection", $lq->lookup_query->group_text );
			$sq2 =& $q2->add_xmlval ( "SQL" );
			$el =& $sq2->add_xmlval ( "QuerySql", "" );
					
			$qcs2 =& $q2->add_xmlval ( "QueryColumns" );
			foreach ( $lq->lookup_query->columns as $lc )
			{

				$qc2 =& $qcs2->add_xmlval ( "QueryColumn" );
				$el =& $qc2->add_xmlval ( "Name", $lc->query_name );
				$el =& $qc2->add_xmlval ( "TableName", $lc->table_name );
				$el =& $qc2->add_xmlval ( "ColumnName", $lc->column_name );
				$el =& $qc2->add_xmlval ( "ColumnType", $lc->column_type );
				$el =& $qc2->add_xmlval ( "ColumnLength", $lc->column_length );

				// Column Attributes
				$at =& $qc2->add_xmlval ( "Format" );
				foreach  ( $lc->attributes as $k => $v )
					if ( $v )
						$el =& $at->add_xmlval ( $k, $v );
			}

			$qos2 =& $q2->add_xmlval ( "OrderColumns" );
			foreach ( $lq->lookup_query->order_set as $col )
			{
				$qoc2 =& $qos2->add_xmlval ( "OrderColumn" );
				$el =& $qoc2->add_xmlval ( "Name", $col->query_name );
				$el =& $qoc2->add_xmlval ( "OrderType", $col->order_type );
			}


			// Output Assignments
			$ascr =& $q2->add_xmlval ( "Assignments" );
			foreach ( $lq->lookup_query->assignment as $asg )
			{
				$qc =& $ascr->add_xmlval ( "Assignment" );
				$el =& $qc->add_xmlval ( "AssignName", $asg->query_name );
				$el =& $qc->add_xmlval ( "AssignNameNew", "" );
				$el =& $qc->add_xmlval ( "Expression", $asg->raw_expression );
				$el =& $qc->add_xmlval ( "Condition", $asg->raw_criteria );
			}


			$clcr =& $ci->add_xmlval ( "CriteriaLinks" );
			foreach ( $lq->lookup_query->criteria_links as $ky => $lk )
			{
				$clicr =& $clcr->add_xmlval ( "CriteriaLink" );
				$el =& $clicr->add_xmlval ( "LinkFrom", $lk["link_from"] );
				$el =& $clicr->add_xmlval ( "LinkTo", $lk["tag"] );
				$el =& $clicr->add_xmlval ( "LinkClause", $lk["clause"] );
			}
		}

		// Output Report Output Details
		$op =& $ef->add_xmlval ( "Output" );
		{
			$ph =& $op->add_xmlval ( "PageHeaders" );
			foreach ( $this->query->page_headers as $k => $val )
			{
				$phi =& $ph->add_xmlval ( "PageHeader" );
				$el =& $phi->add_xmlval ( "LineNumber", $val->line );
				$el =& $phi->add_xmlval ( "HeaderText", $val->text );

				$phf =& $phi->add_xmlval ( "Format" );
				foreach  ( $val->attributes as $k => $v )
					if ( $v )
						$el =& $phf->add_xmlval ( $k, $v );
			}

			$pt =& $op->add_xmlval ( "PageFooters" );
			foreach ( $this->query->page_footers as $val )
			{
				$pti =& $pt->add_xmlval ( "PageFooter" );
				$el =& $pti->add_xmlval ( "LineNumber", $val->line );
				$el =& $pti->add_xmlval ( "FooterText", $val->text );

				$ptf =& $pti->add_xmlval ( "Format" );
				foreach  ( $val->attributes as $k => $v )
					if ( $v )
						$el =& $ptf->add_xmlval ( $k, $v );
			}

			$do =& $op->add_xmlval ( "DisplayOrders" );
			$ct = 0;
			if ( count($this->query->display_order_set) > 0 )
			foreach ( $this->query->display_order_set["itemno"] as $val )
			{
				$doi =& $do->add_xmlval ( "DisplayOrder" );
				$el =& $doi->add_xmlval ( "ColumnName", $this->query->display_order_set["column"][$ct]->query_name);
				$el =& $doi->add_xmlval ( "OrderNumber", $this->query->display_order_set["itemno"][$ct] );
				$ct++;
			}

			$gp =& $op->add_xmlval ( "Groups" );
			foreach ( $this->query->groups as $k => $val )
			{
				$gpi =& $gp->add_xmlval ( "Group" );
				$el =& $gpi->add_xmlval ( "GroupName", $val->group_name );
				$el =& $gpi->add_xmlval ( "BeforeGroupHeader", $val->get_attribute("before_header"));
				$el =& $gpi->add_xmlval ( "AfterGroupHeader", $val->get_attribute("after_header"));
				$el =& $gpi->add_xmlval ( "BeforeGroupTrailer", $val->get_attribute("before_trailer"));
				$el =& $gpi->add_xmlval ( "AfterGroupTrailer", $val->get_attribute("after_trailer"));

				$gph =& $gpi->add_xmlval ( "GroupHeaders" );
				foreach ( $val->headers as $val2 )
				{
					$gphi =& $gph->add_xmlval ( "GroupHeader" );
					$el =& $gphi->add_xmlval ( "GroupHeaderColumn", $val2->query_name );
				}

				$gpt =& $gpi->add_xmlval ( "GroupTrailers" );
				foreach ( $val->trailers as $k2 => $val2 )
				{
					if ( is_array ( $val2) )
					foreach ( $val2 as $val3 )
					{
					$gpti =& $gpt->add_xmlval ( "GroupTrailer" );
					$el =& $gpti->add_xmlval ( "GroupTrailerDisplayColumn", $k2 );
					$el =& $gpti->add_xmlval ( "GroupTrailerValueColumn", $val3->query_name );
					}
				}
			}

			$ggphs =& $op->add_xmlval ( "Graphs" );
			foreach ( $this->query->graphs as $k => $v )
			{
				$ggrp =& $ggphs->add_xmlval ( "Graph" );
				$el =& $ggrp->add_xmlval ( "GraphColumn", $v->graph_column );
				$el =& $ggrp->add_xmlval ( "GraphColor", $v->graphcolor );
				$el =& $ggrp->add_xmlval ( "Title", $v->title );
				$el =& $ggrp->add_xmlval ( "GraphWidth", $v->width );
				$el =& $ggrp->add_xmlval ( "GraphHeight", $v->height );
				$el =& $ggrp->add_xmlval ( "GraphWidthPDF", $v->width_pdf );
				$el =& $ggrp->add_xmlval ( "GraphHeightPDF", $v->height_pdf );
				$el =& $ggrp->add_xmlval ( "XTitle", $v->xtitle );
				$el =& $ggrp->add_xmlval ( "YTitle", $v->ytitle );
				$el =& $ggrp->add_xmlval ( "GridPosition", $v->gridpos );
				$el =& $ggrp->add_xmlval ( "XGridDisplay", $v->xgriddisplay );
				$el =& $ggrp->add_xmlval ( "XGridColor", $v->xgridcolor );
				$el =& $ggrp->add_xmlval ( "YGridDisplay", $v->ygriddisplay );
				$el =& $ggrp->add_xmlval ( "YGridColor", $v->ygridcolor );
				$el =& $ggrp->add_xmlval ( "XLabelColumn", $v->xlabel_column );

				$el =& $ggrp->add_xmlval ( "TitleFont", $v->titlefont );
				$el =& $ggrp->add_xmlval ( "TitleFontStyle", $v->titlefontstyle );
				$el =& $ggrp->add_xmlval ( "TitleFontSize", $v->titlefontsize );
				$el =& $ggrp->add_xmlval ( "TitleColor", $v->titlecolor );
				
				$el =& $ggrp->add_xmlval ( "XTitleFont", $v->xtitlefont );
				$el =& $ggrp->add_xmlval ( "XTitleFontStyle", $v->xtitlefontstyle );
				$el =& $ggrp->add_xmlval ( "XTitleFontSize", $v->xtitlefontsize );
				$el =& $ggrp->add_xmlval ( "XTitleColor", $v->xtitlecolor );
				
				$el =& $ggrp->add_xmlval ( "YTitleFont", $v->ytitlefont );
				$el =& $ggrp->add_xmlval ( "YTitleFontStyle", $v->ytitlefontstyle );
				$el =& $ggrp->add_xmlval ( "YTitleFontSize", $v->ytitlefontsize );
				$el =& $ggrp->add_xmlval ( "YTitleColor", $v->ytitlecolor );
				
				$el =& $ggrp->add_xmlval ( "XAxisColor", $v->xaxiscolor );
				$el =& $ggrp->add_xmlval ( "XAxisFont", $v->xaxisfont );
				$el =& $ggrp->add_xmlval ( "XAxisFontStyle", $v->xaxisfontstyle );
				$el =& $ggrp->add_xmlval ( "XAxisFontSize", $v->xaxisfontsize );
				$el =& $ggrp->add_xmlval ( "XAxisFontColor", $v->xaxisfontcolor );
				
				$el =& $ggrp->add_xmlval ( "YAxisColor", $v->yaxiscolor );
				$el =& $ggrp->add_xmlval ( "YAxisFont", $v->yaxisfont );
				$el =& $ggrp->add_xmlval ( "YAxisFontStyle", $v->yaxisfontstyle );
				$el =& $ggrp->add_xmlval ( "YAxisFontSize", $v->yaxisfontsize );
				$el =& $ggrp->add_xmlval ( "YAxisFontColor", $v->yaxisfontcolor );
				
				$el =& $ggrp->add_xmlval ( "XTickInterval", $v->xtickinterval );
				$el =& $ggrp->add_xmlval ( "YTickInterval", $v->ytickinterval );
				$el =& $ggrp->add_xmlval ( "XTickLabelInterval", $v->xticklabelinterval );
				$el =& $ggrp->add_xmlval ( "YTickLabelInterval", $v->yticklabelinterval );
			
				$el =& $ggrp->add_xmlval ( "MarginColor", $v->margincolor );
		
				$el =& $ggrp->add_xmlval ( "MarginLeft", $v->marginleft );
				$el =& $ggrp->add_xmlval ( "MarginRight", $v->marginright );
				$el =& $ggrp->add_xmlval ( "MarginTop", $v->margintop );
				$el =& $ggrp->add_xmlval ( "MarginBottom", $v->marginbottom );

				$gplt =& $ggrp->add_xmlval ( "Plots" );
				foreach ( $v->plots as $k => $val2 )
				{
					$gpltd =& $gplt->add_xmlval ( "Plot" );
					$el =& $gpltd->add_xmlval ( "PlotColumn", $val2["name"] );
					$el =& $gpltd->add_xmlval ( "PlotType", $val2["type"] );
					$el =& $gpltd->add_xmlval ( "LineColor", $val2["linecolor"] );
					$el =& $gpltd->add_xmlval ( "DataType", $val2["datatype"] );
					$el =& $gpltd->add_xmlval ( "Legend", $val2["legend"] );
					$el =& $gpltd->add_xmlval ( "FillColor",$val2["fillcolor"] );
				}
			}

		} // Output Section
	}

	function generate_web_service($in_report)
	{

		if ( !preg_match( "/(.*)\.xml/", $in_report, $matches ) )
		{
				trigger_error ( "XML Report Configuration File $in_report must be in form {reportname}.xml" );
				return;
		}

		$stub = $matches[1];
		$wsdlfile = $matches[1].".wsdl";
		$srvphpfile = $matches[1]."_wsv.php";
		$cltphpfile = $matches[1]."_wcl.php";

		$this->prepare_web_service_file("wsdl.tpl", $wsdlfile, $stub);
		$this->prepare_web_service_file("soapclient.tpl", $cltphpfile, $stub);
		$this->prepare_web_service_file("soapserver.tpl", $srvphpfile, $stub);
		
	}
	
	function prepare_web_service_file($templatefile, $savefile = false, $instub)
	{
		global $g_project;
		$smarty = new smarty();
	 	$smarty->compile_dir = find_best_location_in_include_path( "templates_c" );

		$smarty->compile_dir = "/tmp";
		$smarty->assign('WS_SERVICE_NAMESPACE', SW_SOAP_NAMESPACE);
		$smarty->assign('WS_SERVICE_CODE', $instub);
		$smarty->assign('WS_SERVICE_NAME', $instub);
		$smarty->assign('PROJECT', $g_project);
		$smarty->assign('WS_SERVICE_BASEURL', SW_SOAP_SERVICEBASEURL);
		$smarty->assign('WS_REPORTNAME', $instub);
		$smarty->debug = true;

		$cols = array();
		$cols[] = array (
				"name" => "ReportName",
				"type" => "char",
				"length" => 0
			);
		foreach ( $this->query->columns as $col )
		{
			$cols[] = array (
				"name" => $col->query_name,
				"type" => $col->column_type,
				"length" => $col->column_length
				);
		}
		$smarty->assign("COLUMN_ITEMS", $cols);

		$crits = array();
		foreach ( $this->query->lookup_queries as $lq )
		{
			$crits[] = array (
				"name" => $lq->query_name
					);
		}
		$smarty->assign("CRITERIA_ITEMS", $crits);

		header('Content-Type: text/html');
		if ( $savefile )
		{
			$data = $smarty->fetch($templatefile, null, null, false );
			echo "<PRE>";
			echo "====================================================";
			echo "Writing $savefile from template $templatefile";
			echo "====================================================";
			echo htmlspecialchars($data);
			echo "</PRE>";
			$this->write_report_file($savefile, $data);
		}
		else
			$smarty->display($templatefile);
		
	}
	
	function prepare_wsdl_data($savefile = false)
	{
		$smarty = new smarty();
 		$smarty->compile_dir = find_best_location_in_include_path( "templates_c" );

		$smarty->assign('WS_SERVICE_NAMESPACE', SW_SOAP_NAMESPACE);
		$smarty->assign('WS_SERVICE_CODE', SW_SOAP_SERVICECODE);
		$smarty->assign('WS_SERVICE_NAME', SW_SOAP_SERVICENAME);
		$smarty->assign('WS_SERVICE_URL', SW_SOAP_SERVICEURL);
		$smarty->debugging = true;

		
		$cols = array();
		$cols[] = array (
				"name" => "ReportName",
				"type" => "char",
				"length" => 0
			);
		foreach ( $this->query->columns as $col )
		{
			$cols[] = array (
				"name" => $col->query_name,
				"type" => $col->column_type,
				"length" => $col->column_length
				);
		}
		$smarty->assign("COLUMN_ITEMS", $cols);

		$crits = array();
		foreach ( $this->query->lookup_queries as $lq )
		{
			$crits[] = array (
				"name" => $lq->query_name
					);
		}
		$smarty->assign("CRITERIA_ITEMS", $crits);

		header('Content-Type: text/xml');
		if ( $savefile )
		{
			$data = $smarty->fetch('wsdl.tpl', null, null, false );
			$this->write_report_file($savefile, $data);
		}
		else
			$smarty->display('wsdl.tpl');
		
	}
	
	function get_xmldata()
	{
		$text = '<?xml version="'.$this->xml_version.'"?>';
		$text .= $this->xmldata->unserialize();
		return $text;
	}

	function write()
	{
		//header('Content-Type: text/xml');
		header('Content-Type: text/html');
		//echo '<?xml version="'.$this->xml_version.'"?s>';
		echo '<HTML><BODY><PRE>';
		//$this->xmldata->write();
		$xmltext = $this->xmldata->unserialize();
		echo htmlspecialchars($xmltext);
		echo '</PRE></BODY></HTML>';
	}

	function write_report_file($filename, &$writedata)
	{
		global $g_project;
		$fn = $this->query->reports_path."/".$filename;
		if ( ! ($fd = fopen($fn, "w" )) )
		{
			return false;
		}

		if ( ! fwrite ($fd, $writedata ) )
		{
			return false;
		}

		fclose($fd);

		return(true);

	}

	function write_file($filename)
	{

		global $g_project;

		
		if ( !$filename )
		{	
			trigger_error ( "Unable to save - you must specify a file name with the suffix '.xml'" , E_USER_ERROR );
			return false;
		}

		if ( !preg_match("/\.xml$/", $filename ) )
		{	
			$filename = $filename.".xml";
			trigger_error ( "Unable to save - you must specify a file with a '.xml' suffix" , E_USER_ERROR );
			return false;
		}

		$projdir = "projects/".$g_project;
		if ( !is_file($projdir) )
			find_file_to_include($projdir, $projdir);

		if ( $projdir && is_dir($projdir))
		{
			$fn = $projdir."/".$filename;
			if ( ! ($fd = fopen($fn, "w" )) )
			{
				return false;
			}
		}
		else
			trigger_error ( "Unable to open project area $g_project to save file $filename ". 
				$this->query->reports_path."/".$filename." Not Found", E_USER_ERROR );
		

		if ( ! fwrite ($fd, '<?xml version="'.$this->xml_version.'"?>' ) )
		{
			return false;
		}

		$xmltext = $this->xmldata->unserialize();
		if ( ! fwrite ($fd, $xmltext) )
		{
			return false;
		}

		fclose($fd);

	}

}

/**
 * Class reportico_xmlval
 *
 * Stores the definition of a single tag within an XML report definition
 */
class reportico_xmlval
{
	var $name;
	var $value;
	var $attributes;
	var $ns;
	var $xmltext = "";
	var $elements = array();

	function reportico_xmlval ( $name, $value = false, $attributes = array() )
	{
		$this->name = $name;
		$this->value = $value;
		$this->attributes = $attributes;
	}

	function &add_xmlval ( $name, $value = false, $attributes = false )
	{
		$element = new reportico_xmlval($name, htmlspecialchars($value), $attributes);
		$this->elements[] =& $element;
		return $element;
	}

	function unserialize ( )
	{
		$this->xmltext .= "<";
		$this->xmltext .= $this->name;

		if ( $this->attributes )
		{
			$infor = true;
			foreach  ( $this->attributes as $k => $v )
			{
				if ( $v )
				{
					if ( $infor )
						$this->xmltext .= " ";
					else
						$infor = true;
					$this->xmltext .= $k.'="'.$v.'"';
				}
					
			}
		}

		$this->xmltext .= ">";

		if ( $this->value )
		{
			$this->xmltext .= $this->value;
		}
		else
			foreach ( $this->elements as $el )
			{
				$this->xmltext .= $el->unserialize();
			}

		$this->xmltext .= "</";
		$this->xmltext .= $this->name;
		$this->xmltext .= ">";

		return $this->xmltext;
	}

	function write ( )
	{
		echo "<";
		echo $this->name;

		if ( $this->attributes )
		{
			$infor = true;
			foreach  ( $this->attributes as $k => $v )
			{
				if ( $v )
				{
					if ( $infor )
						echo " ";
					else
						$infor = true;
					echo $k.'="'.$v.'"';
				}
					
			}
		}

		echo ">";

		if ( $this->value )
		{
			echo $this->value;
		}
		else
			foreach ( $this->elements as $el )
				$el->write();

		echo "</";
		echo $this->name;
		echo ">";
	}
}
