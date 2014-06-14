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

$CodigoReporte=8001001;
$reporte = "clientes";
$actualizar_report_auto=1;

// Seguridad
if (!$user->rights->evenxusreports->reports->clientes) ReporteProhibido(); 
if (ReporteActivo($CodigoReporte)==false) ReporteDesactivado(); 


$c =    '<link rel=stylesheet href="../css/estilos.css" type="text/css">'.
        '<script src="../js/evenxusreports.js" type="text/javascript"></script>'.
        '<script src="../js/comunes.js" type="text/javascript"></script>';

llxHeader($c,"",$langs->trans("")); 

$Filtros = new filtros();

print_fiche_titre($langs->trans("ListadoClientes"),"","../img/$reporte.png",1);
print SaltaLinea(1);
// *****************************************************************************************************************************
// Filtros
print $Filtros->ClientesDH("rowid");
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
            params=ParametrosComunesReporte(jasper,Modo,'".$langs->getDefaultLang()."',Salida);
            
            // Añado parametros solo del propio reporte, como filtros y ordenes
            
            // Filtro parametro CP
            var cp_desde = document.getElementById('cp_desde').value;
            if (cp_desde == '')  { cp_desde='0'; }
            var cp_hasta = document.getElementById('cp_hasta').value;
            if (cp_hasta == '')  { cp_hasta='ZZZZZZZZZZZZ'; }
            var cliente_desde = document.getElementById('cliente_desde').value;
            if (cliente_desde == -1)  { cliente_desde=0; }
            var cliente_hasta = document.getElementById('cliente_hasta').value;
            if (cliente_hasta == -1)  { cliente_hasta=999999999999; }
            
            var i=params.length;
            params[i++]  =  'CP_DESDE='+cp_desde;
            params[i++]  =  'CP_HASTA='+cp_hasta;
            params[i++]  =  'CLIENTE_DESDE='+cliente_desde;
            params[i++]  =  'CLIENTE_HASTA='+cliente_hasta;
            
            params[i++] = 'FILTRO=".FILTRO_DETALLE().";
            // Lanzo reporte".
            EvenxusLanzarReport($reporte,$actualizar_report_auto)."
            if (err!==null) { alert(err);   return err; }
    }
    </script>";

/**
 * Texto de filtro al pie del reporte
 * 
 * @global type $langs
 * @return string
 */
function FILTRO_DETALLE() {
    global $langs;    
    $FD=$langs->trans("Desde"). " ".$langs->trans("Cliente"). " : ' + cliente_desde + '";
    $FD=$FD." - ";
    $FD=$FD.$langs->trans("Hasta"). " ".$langs->trans("Cliente"). " : ' + cliente_hasta + '";
    $FD=$FD."\\n";
    $FD=$FD.$langs->trans("Desde"). " ".$langs->trans("CodigoPostal"). " : ' + cp_desde + '";
    $FD=$FD." - ";
    $FD=$FD.$langs->trans("Hasta"). " ".$langs->trans("CodigoPostal"). " : ' + cp_hasta + '";
    $FD=$FD."'";
    return $FD;    
}