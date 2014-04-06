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
 * Gestion de logs
 */


require_once ("../../main.inc.php");        // Acceso al main de Doli, obligatorio


class Log{
    var $NivelLog;             // Cuanto mayor este liston menos log -> 1 Log exhaustivo - 2 Logs normales - 3 NO LOG
    var $LogDoli;              // Activa el log de Doli
    var $LogPantalla;          // Activa el log a pantalla
    
    function __construct($LogDoli,$LogPantalla,$NivelLog=1) {
       $this->LogDoli=$LogDoli;
       $this->LogPantalla=$LogPantalla;       
       $this->NivelLog=$NivelLog;
    }
    /**
     * Logea un proceso con la cadena $textolog
     * 
     * Si las variables de clase :
     *  - $LogPantalla = 1 -> Entonces muestra el texto de log por pantalla
     *  - $LogDoli     = 1 -> Entonces emite un mensaje de log al fichero de logs de Dolibarr
     * 
     * Comprueba tambien si se ha pasado la variable $nivel, esta variable de compara con la variable
     * de clase $NivelLog, si la pasada es mayor o igual se produce el LOG
     * 
     * @param type $textolog
     * @param type $nivel
     */
    public function Log($textolog,$nivel = 2) {
        // Si indicamos un nivel de log igual o mayor del establecido el log se produce, sino no
        if ($this->NivelLog<=$nivel) {
            if ($this->LogPantalla==1) {
                print "<br>$textolog";
                flush();              
            }
            if ($this->LogDoli==1) {
                dol_syslog($textolog);        
            }
        }
    }
}
?>
