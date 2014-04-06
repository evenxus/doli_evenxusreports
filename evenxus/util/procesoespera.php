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


/*
 * Carga una pagina con head y footer de Doli y lanza la pantalla de espera mientras un proceso que se pasa como parametro
 * en la URL se realiza, el proceso es una pagina en PHP que viene en la propia URL en una variable GET llamada "proceso", 
 * los demas parametros que recibe se trasladan sin mas a esa pagina "proceso". 
 * Una vez terminado el proceso el loader se quita de la pantalla y se redirecciona a la pagina "regreso" que es otra pagina PHP 
 * encapsulada tambien en la URL.
 */

set_time_limit (0);

require_once ("../../main.inc.php");               // Acceso al main de Doli, obligatorio
  
$proceso   = "";    // Nombre de la pagina PHP a procesar (Esta es la pagina que tarda mucho)
$regreso   = "";    // NOmbre de la pagina PHP que cargara cuando el proceso termnie
$urltoload = "?";   // URL completa es decir proceso+parametros

// Recodifico los parametros que traspasare al proceso, tal cual vienen
foreach ($_GET as $Variable => $Valor) {
    if ($Variable=="proceso") {    
        $proceso=$Valor; // Esta variable guarda el nombre del fichero PHP a ejecutar que tardar mucho rato
    }    
    else {
        if ($Variable=="regreso") {    
            $regreso=$Valor; // Esta variable guarda a la pagina que volveremos
        }
        else {
            $urltoload.= $Variable ."=".$Valor."&";    // Reconstruyo los parametros
        }
    }
} $urltoload = $proceso.trim($urltoload, '&'); // Reconstruyo la URL

// Montamos la cabecera para pasarla a Dolibarr
$cabecera='
    <link rel="stylesheet" type="text/css" href="../js/estilos.css" />
    <script src="../js/jquery.blockUI.js" type="text/javascript"></script>  
    <link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
    <script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>    
    <script>
        $(document).ready(function(){
            $("#enlaceajax").on("cargar",function(){
                $("#cargando").css("display", "inline");
                $.blockUI({ message: null }); 
                $("#destino").load("'.$urltoload.'", function()
                {
                    $("#cargando").css("display", "none");
                    $.unblockUI();
                    '.'document.location.href= "'.$regreso.'";'.'  
                });
            });
        })
        ;
    </script>
         <script>
$(function() {
$( "#progressbar" ).progressbar({
value: 37
});
});
</script>';
//ob_implicit_flush(true);
llxHeader($cabecera);    // Cabecera

// Contenigo JS para cargar el Loader
print '
      <a href="#" id="enlaceajax"></a>
      <div id="cargando" class="cargador" >
            <img src="../img/inprogress.gif" class="ajax-loader"/>
      </div>
      <script>
        $(document).ready(function() { 
            $("#enlaceajax").trigger("cargar");
        });  
      </script>
      <div id="destino"></div>
      <div id="progressbar"></div>';

llxFooter();  // Pie
      
?>






