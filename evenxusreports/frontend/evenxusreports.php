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
print '<img id="logoevenxus" src="../img/logoevenxus.png"><br>';
print '<h1 id="titulomodulo" >EVENXUS REPORTS</h1>';
print '</center>';
print '<br>';
print '<h3><b>Evenxus Reports</b> es un modulo de Dolibarr para gestión avanzada de informes y graficas desde el navegador Mozilla Firefox.<br><br>';
print 'Utiliza como "motor" de reportes Jasper Reports lo que permite un diseño avanzado de reportes.<br><br>';
print 'Jasper Reports en un motor escrito totalmente en Java y para enlazar con el navegador se debe instalar un plugin.<br><br>';
print '<center>';
print 'Clic para instalar<br>';
print '<button id="plugin" onclick="DescargarPlugin(\''.$dolibarr_main_url_root.'/evenxusreports/xpi/evenxusreports@evenxus.xpi\')">Descargar Plugin</button>';
print '</center>';
print '<br>El plugin contiene todo los necesario para poder utilizar el modulo en el navegador Mozilla Firefox.<br><br>';
print 'Mas información y soporte en : <b><a href="http://www.evenxus.com" target="_blank">www.evenxus.com</a></b><br><br>';
print 'Tanto el modulo como el plugin son open source y puedes descargarlo desde nuestro repositorio en Github.<br><br>'; 
print 'Repositorio : <b><a href="https://github.com/evenxus/doli_evenxusreports" target="_blank">https://github.com/evenxus/doli_evenxusreports</a></b><br><br>';
print 'Evenxus Reports usa codigo fuente libre de : <br>';
print '<ul>
      <li type="square">Jasper Report : Libreria de gestión de reportes.</li>
      <li type="square">Jasper Studio : Diseñador de reportes.</li>      
      <li type="square">JasperStarter : Lanzador de reportes desde script.</li>
      </ul>';
print '</h3>';



llxFooter();


