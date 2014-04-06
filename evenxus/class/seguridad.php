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

require_once ("../../main.inc.php");        // Acceso al main de Doli, obligatorio

/**
 * FUNCIONES DE OFUSCACION, BASICAMENTE PARA PASAR PARAMETROS SQL POR URL SIN PROBLEMAS
 * 
 * 
 */


class SeguridadEvenxus {
    /**
     * Oculta una cadena y la devuelve lista para ser enviada como parametro de URL
     * 
     * @param type $string
     * @return type
     */
    function OcultarParametroURL($string) {
        return base64_encode(utf8_encode($string));
    }
    /**
     * Desoculta una cadena enviada como parametro de URL
     * 
     * @param type $stringsucia
     * @return type
     */
    function MostrarParametroURL($stringsucia) {
        return utf8_decode(base64_decode($stringsucia));
    }
}

