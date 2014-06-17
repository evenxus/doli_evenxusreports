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
dol_include_once('/evenxusreports/class/comunes.php');


$actualizar_report_auto=1;

$c =    '<link rel=stylesheet href="../css/estilos.css" type="text/css">'.
        '<script src="../js/comunes.js" type="text/javascript"></script>';



llxHeader($c,"",$langs->trans("")); 

print '<center>';
print '<img id="logoevenxus" src="../img/logoevenxus.png"><br>';
print '<h1 id="titulomodulo" >EVENXUS REPORTS</h1>';
print '</center>';
print '<br>';
print '<h3>';

print $langs->trans("Parrafo1");
print '<center>';
print 'Plugin<br>';
print '<button id="plugin" onclick="DescargarPlugin()">Descargar Plugin</button>';
print '</center>';
print '<br><br>';
print $langs->trans("Parrafo2");
print '<br><br>';
print $langs->trans("Parrafo3").' <b><a href="http://www.evenxus.com" target="_blank">www.evenxus.com</a></b>';
print '<br><br>';
print $langs->trans("Parrafo4").' <b><a href="https://github.com/evenxus/doli_evenxusreports" target="_blank">https://github.com/evenxus/doli_evenxusreports</a></b>';
print '<br><br>';
print $langs->trans("Parrafo5");
print '<br><br>';
llxFooter();
print "<script type='text/javascript'>
    
    function DescargarPlugin() {
        window.open('".RUTAXPI."','_blank');    
    }
    </script>";

