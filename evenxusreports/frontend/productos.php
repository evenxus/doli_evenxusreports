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
dol_include_once("/evenxusreports/class/comunes.php");
dol_include_once("/evenxusreports/class/filtros.php");
dol_include_once("/evenxus/class/datos.php");

$CodigoReporte=8001051;
$reporte = "productos";
$actualizar_report_auto=1;

$langs->load($reporte."@evenxusreports");

// Seguridad
if (!$user->rights->evenxusreports->reports->productos) accessforbidden(); 
if (ReporteActivo($CodigoReporte)==0) accessforbidden(); 



$c =    '<link rel=stylesheet href="../css/estilos.css" type="text/css">'.
        '<script src="../js/evenxusreports.js" type="text/javascript"></script>'.
        '<script src="../js/comunes.js" type="text/javascript"></script>';

$langs->load("evenxusreports@evenxus");

llxHeader($c,"",$langs->trans("")); 

$Filtros = new filtros();

print_fiche_titre($langs->trans("ListadoProductos"),"","../img/$reporte.png",1);
print SaltaLinea(1);
// *****************************************************************************************************************************
// Filtros
print $Filtros->ProductoDH();
// *****************************************************************************************************************************
print SaltaLinea(10);
// *****************************************************************************************************************************
// Botonera
print '<center>';
print BotoneraImprimir();
print '<div id="buttongap">&nbsp;</div>';
print BotoneraExportar();
print '</center>';
print '<br><br>';
print PiePagina();

// *****************************************************************************************************************************

llxFooter();

print "<script type='text/javascript'>
    function ProcesarReporte(Modo,Salida)
    {
            // Nombre reporte
            var jasper='$reporte.jasper';
            
            // Preparo parametros comunes
            var params = new Array()
            params=ParametrosComunesReporte(jasper,Modo,'".$langs->getDefaultLang()."',Salida);
            
            // Añado parametros solo del propio reporte, como filtros y ordenes
            
            // Filtro parametro CP
            var producto_desde = document.getElementById('producto_desde').value;
            if (producto_desde == '')  { producto_desde='0'; }
            var producto_hasta = document.getElementById('producto_hasta').value;
            if (producto_hasta == '')  { producto_hasta='ZZZZZZZZZZZZ'; }
            
            var i=params.length;
            params[i++]  =  'PRODUCTO_DESDE='+producto_desde;
            params[i++]  =  'PRODUCTO_HASTA='+producto_hasta;
            
            // Lanzo reporte".
            EvenxusLanzarReport($reporte,$actualizar_report_auto)."
            if (err!==null) { alert(err);   return err; }
    }
    </script>";