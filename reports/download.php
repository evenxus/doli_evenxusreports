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

$file = $_GET["file"];
$cvs = $_GET["cvs"];

$gestor = fopen($file, "r");
$buf = fread($gestor, filesize($file));
fclose($gestor);

$len = strlen($buf);

if (!$cvs)
{
	header("Content-Type: application/pdf");
	header("Content-Length: ".$len);
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");		
	header('Content-Disposition: inline; filename=report.pdf');
}
else
{
	header("Content-type: application/octet-stream");
	header("Content-Disposition: inline; filename=report.csv");
	header("Pragma: no-cache");
	header("Expires: 0");
}

print($buf);
unlink($file);
?>