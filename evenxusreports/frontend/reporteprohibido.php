<?php
/* Copyright (C) 2013-     Santiago Garcia      <babelsistemas@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */



require_once '../../main.inc.php';

// Seguridad
//if ($state =='create'           && !$user->rights->evenxusreports->peliculas->create) accessforbidden(); 

$actualizar_report_auto=1;

$c =    '<link rel=stylesheet href="../css/estilos.css" type="text/css">'.
        '<script src="../js/comunes.js" type="text/javascript"></script>';

$langs->load("evenxusreports@evenxus");

llxHeader($c,"",$langs->trans("")); 

print '<center>';
print '<img id="logoevenxus" src="../img/forbidden.png"><br><br>';
print '<h1 id="titulomodulo" >'.$langs->trans("AccesoOFF").'</h1>';
print '<br><br>';
print $langs->trans("AccesoOFFRazon");
print '<br></center>';

llxFooter();


