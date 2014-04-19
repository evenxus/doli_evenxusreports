<?php
/* Copyright (C) 2008		Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2013	Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012		Regis Houssin 	   <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *  \file       /reports/pre.inc.php
 *  \brief      File to manage left menu by default
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res) $res=@include("../../main.inc.php");	// For "custom" directory	

/**
 *	\brief		Function called by page to show menus (top and left)
 */
function llxHeader($head = "")
{
	global $db, $user, $conf, $langs;

	top_menu($head);

	$menu = new Menu();
	
	$leftmenu=GETPOST('leftmenu','alpha');
	
	$sql = "SELECT rowid, code, name, active FROM ".MAIN_DB_PREFIX."reports_group";
	$resql = $db->query($sql);
	if ($resql)
	{
		$numr = $db->num_rows($resql);
		$i = 0;
		while ($i < $numr)
		{
			$objp = $db->fetch_object($resql);
			if ($objp->active)
			{
				if($objp->name=='noAssigned')
					$name=$langs->trans("NoAssigned");
				else
				{
					$key=$langs->trans("group".strtoupper($objp->code));
					$namegroup=($objp->code && $key != "group".strtoupper($objp->code))?$key:$objp->name;
				}
					
				
				
				$sql2="SELECT code, name, xmlin FROM ".MAIN_DB_PREFIX."reports_report WHERE fk_group=".$objp->rowid;
				$sql2.=" AND active=1";
				
				$resql2 = $db->query($sql2);
				if ($resql2)
				{
					$numg = $db->num_rows($resql2);
					$j = 0;
					
					if ($numg)
						$menu->add('/reports/index.php?leftmenu='.$objp->name.'&amp;mainmenu=Reports', $namegroup,0,1,'',$objp->name);
					while ($j < $numg)
					{
						$objr=$db->fetch_object($resql2);
						
						$key=$langs->trans("report".strtoupper($objr->code));
						$name=($objr->code && $key != "report".strtoupper($objr->code))?$key:$objr->name;
							
						if ($leftmenu==$objp->name) $menu->add('/reports/report.php?execute_mode=PREPARE&project=Dolibarr&target_output=HTML&xmlin='.$objr->xmlin, $name,1);
						
						$j++;
					}
				}
				
				
			}
			$i++;
		}
		
		$menu->add('/reports/askreport.php', $langs->trans("MoreReports"));
				
	}
	$helpurl='EN:Module_Reports|FR:Module_Reports_FR|ES:M&oacute;dulo_Reports';
	left_menu($menu->liste,$helpurl);  
}
?>