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
        '<script src="../js/evenxusreports.js" type="text/javascript"></script>'.        
        '<script src="../js/comunes.js" type="text/javascript"></script>';

llxHeader($c,"",$langs->trans("")); 
print_fiche_titre($langs->trans("ConfigurarReportes"),"","../img/configurar.png",1);
print '<center>';
print '<img id="logoevenxus" src="../img/logoevenxus.png"><br>';
print '<h1 id="titulomodulo" >EVENXUS REPORTS</h1>';
print '<br>';
print $langs->trans("ParrafoConfig1");
print '<br><br>';
print '<input class="botonconfig" id="listareportes" type="button" name="listareportes"  value="'.$langs->trans("ListaReportes").'" onclick="ListaReportes()">';
print '<div id="buttongapconfig">&nbsp;</div>';
print '<input class="botonconfig" id="adquirir" type="button" name="adquirir"  value="'.$langs->trans("CargarReporte").'" onclick="CargarReporte()">';
print '<div id="buttongapconfig">&nbsp;</div>';
print '<input class="botonconfig" id="descargarplugin" type="button" name="descargarplugin"  value="'.$langs->trans("DescargarPlugin").'" onclick="DescargarPlugin()">';
print '<div id="buttongapconfig">&nbsp;</div>';
print '<input class="botonconfig" id="adquirir" type="button" name="adquirir"  value="'.$langs->trans("AdquirirReporte").'" onclick="AdquirirReporte()">';
print '<div id="buttongapconfig">&nbsp;</div>';
print '<input class="botonconfig" id="acercade" type="button" name="acercade"  value="'.$langs->trans("AcercaDe").'" onclick="AcercaDe()">';
print '</center>';


print '<br><br>'.PiePagina();                 

print "<script type='text/javascript'>
    
    function CargarReporte()  {
        location.href=URL_DOLI_BASE('".basename(DOL_DOCUMENT_ROOT)."')+'/evenxusreports/frontend/cargarreporte.php';
    }        
    function ListaReportes()  {
        location.href=URL_DOLI_BASE('".basename(DOL_DOCUMENT_ROOT)."')+'/evenxusreports/frontend/listareportes.php';
    }    
    function AdquirirReporte()  {
        window.open('http://www.evenxus.com/?page_id=178','_blank');    
    }

    function DescargarPlugin() {
        window.open('".RUTAXPI."','_blank');    
    }
    function AcercaDe()  {
        window.open(URL_DOLI_BASE('".basename(DOL_DOCUMENT_ROOT)."')+'/evenxusreports/frontend/evenxusreports.php','_self');    
    }    
    </script>";
llxFooter();


