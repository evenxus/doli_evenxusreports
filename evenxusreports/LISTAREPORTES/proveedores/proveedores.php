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
require_once DOL_DOCUMENT_ROOT."/evenxusreports/class/comunes.php";
require_once DOL_DOCUMENT_ROOT."/evenxusreports/class/filtros.php";
require_once DOL_DOCUMENT_ROOT."/evenxus/class/datos.php";

$CodigoReporte=8001021;
$reporte = "proveedores";
$actualizar_report_auto=1;

// Seguridad
if (!$user->rights->evenxusreports->reports->clientes) ReporteProhibido(); 
if (ReporteActivo($CodigoReporte)==false) ReporteDesactivado(); 


$c =    '<link rel=stylesheet href="../css/estilos.css" type="text/css">'.
        '<script src="../js/evenxusreports.js" type="text/javascript"></script>'.
        '<script src="../js/comunes.js" type="text/javascript"></script>';

$langs->load("evenxusreports@evenxus");

llxHeader($c,"",$langs->trans("")); 

$Filtros = new filtros();

print_fiche_titre($langs->trans("ListadoProveedores"),"","../img/$reporte.png",1);
print SaltaLinea(1);
// *****************************************************************************************************************************
// Filtros
print $Filtros->CodigoPostalDH();
// *****************************************************************************************************************************
print SaltaLinea(10);
// *****************************************************************************************************************************
// Botonera
print '<center>';
print BotoneraImprimir();
print '<div id="buttongap">&nbsp;</div>';
print BotoneraExportar();
print '</center>';
// *****************************************************************************************************************************
print '<br><br>';
print PiePagina();
llxFooter();

print "<script type='text/javascript'>
    function ProcesarReporte(Modo,Salida)
    {
            // Nombre reporte
            var jasper='$reporte.jasper';
            
            // Preparo parametros comunes
            var params = new Array()
            var Idioma = '".DOL_DOCUMENT_ROOT."/evenxusreports/reports/".$langs->getDefaultLang()."';
            params=ParametrosComunesReporte(jasper,Modo,Idioma,Salida);
            
            // Añado parametros solo del propio reporte, como filtros y ordenes
            
            // Filtro parametro CP
            var cp_desde = document.getElementById('cp_desde').value;
            if (cp_desde == '')  { cp_desde='0'; }
            var cp_hasta = document.getElementById('cp_hasta').value;
            if (cp_hasta == '')  { cp_hasta='ZZZZZZZZZZZZ'; }
            
            var i=params.length;
            params[i++]  =  'CP_DESDE='+cp_desde;
            params[i++]  =  'CP_HASTA='+cp_hasta;
            
            // Lanzo reporte".
            EvenxusLanzarReport($reporte,$actualizar_report_auto)."
            if (err!==null) { alert(err);   return err; }
    }
    </script>";
