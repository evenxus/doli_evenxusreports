<?php
/* Copyright (C) 2011-2012 Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2012 	   Regis Houssin <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 *     \file       /reports/report.php
 *     \ingroup    2report
 *     \brief      Page to launch a report
 *     \author	   Juanjo Menent
 */


require("./pre.inc.php");
require_once("./includes/reportico/reportico.php");

$langs->load("reports@reports");

$mode = GETPOST("execute_mode");
$report = GETPOST("xmlin");
$helpurl='EN:Module_Reports|FR:Module_Reports_FR|ES:M&oacute;dulo_Reports';
llxHeader('','',$helpurl);
dol_include_once('/reports/class/utils.class.php');

$a = new reportico_query();
$a->embedded_report = true;
$a->forward_url_get_parameters = "execute_mode=".$mode."&project=Dolibarr&xmlin=".$report;
$a->execute();

llxFooter();

$db->close();

?>