<?php
/**
 * Copyright (C) 2013-     Santiago Garcia      <babelsistemas@gmail.com>
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

/**
 * Esta clase permite crear y gestionar barras de progreso para seguir procesos que se dilaten en el 
 * tiempo...
 */


require_once ("../../main.inc.php");        // Acceso al main de Doli, obligatorio

class barraprogreso{
    var $actualanterior=-1;
    /**
     * Devuelve cabecera del HTML con el BLOCKUI
     * 
     * @return string
     */
    function cabecera() {
        $cabecera='<script src="'.DOL_URL_ROOT.'/evenxus/js/jquery.blockUI.js" type="text/javascript"></script>';
        return $cabecera;
    }
    /**
     * Bloqueamos pantalla
     * 
     * @return string
     */
    function BloqueoPantalla() {
        print '<!--BLOQUEO -->
            <script>
                $.blockUI({ message: null }); 
            </script>';
        $this->ActualizarPantalla();
    }
    /**
     * Desbloqueamos pantalla
     * 
     * @return string
     */
    function DesbloqueoPantalla() {
        print '<script>$.unblockUI();</script>';
        $this->ActualizarPantalla();
    }
    /**
     * Pintamos la barra inicial
     * 
     * @return string
     */
    function PintarBarraProgreso() {
        print ' 
        <div id="progress" style="margin: 0 auto 0 auto;width:50%;height:25px;border:1px solid #000000;"></div>
        <!-- Progress information -->
        <div id="information" style="margin: 0 auto 0 auto;width:50%;"></div>';
        $this->ActualizarPantalla();
    }
    /**
     * Actualizamos la barra
     * 
     * @param type $numero
     * @param type $total
     * @param type $mensaje
     */
    function ActualizarBarraProgreso($numero,$total,$mensaje) {
        $porcentaje = intval($numero/$total * 100)."%";
        $mensajehtml = '<center>'.$numero.' '.$mensaje.' '.$total.'</center>';
        print '<script language="javascript">
            document.getElementById("progress").innerHTML="<div style=\"width:'.$porcentaje.';height:25px;background-color:#3E8DB8;\">&nbsp;&nbsp;</div>";
            document.getElementById("information").innerHTML="'.$mensajehtml.'";
            </script>';
        $this->ActualizarPantalla();
    }
    /**
     * Actualizamos la barra con tantos por ciento
     * 
     * @param type $numero
     * @param type $total
     * @param type $mensaje
     * @return type
     */
    function ActualizarBarraProgresoTanto($numero,$total,$mensaje) {
        if ($total>0) { $T=$numero/$total;}
        else {$T=1;}
        $porcentaje = intval($T * 100)."%";
        if (intval($porcentaje)>$this->actualanterior) {
            $this->actualanterior=intval($porcentaje);
            $mensajehtml = '<center>'.$this->actualanterior.'% '.$mensaje.'</center>';
            print '<script language="javascript">
                document.getElementById("progress").innerHTML="<div style=\"width:'.$this->actualanterior.'%;height:25px;background-color:#3E8DB8;\">&nbsp;&nbsp;</div>";
                document.getElementById("information").innerHTML="'.$mensajehtml.'";
                </script>';
        }
        $this->ActualizarPantalla();
    }    
    /**
     * Funcion de actulizar la pantalla, enviamos 64Kb de espacios vacios porque si el buffer es menor
     * de 64kb el navegador no actualiza
     * 
     */
    function ActualizarPantalla()
    {
            echo  str_repeat(' ',1024*64);     // RELLENAMOS BUFFER PARA QUE SALGA                         
            ob_flush();                         // LANZAMOS BUFFER
    }
}


?>
