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
/**
 * 
 * Almacen de filtros comunes a todos los listados
 * 
 */
class filtros {
    
    // Codigo postal DESDE-HASTA
    function CodigoPostalDH() {
        global $langs;
        $CodigoPostal = $langs->trans('CodigoPostal');
        $Desde = $langs->trans('Desde');
        $Hasta = $langs->trans('Hasta');       
        $cadena =   "<tr>
                    <td><div class='titulofiltro'>$CodigoPostal : </div></td>
                    <td>$Desde : <input type='text' class ='cp' id='cp_desde'></td>
                    <td>$Hasta : <input type='text' class ='cp' id='cp_hasta'></td>    
                    </tr>
                    <script type='text/javascript'>
                    // Formatos
                    $(document).ready(function(){
                        $('.cp').attr('maxlength','5');
                    });
                    </script>";
        return $cadena;
    }
    
    // Articulos DESDE-HASTA
    function ProductoDH() {
        global $langs;
        $Producto = $langs->trans('Productos');
        $Desde = $langs->trans('Desde');
        $Hasta = $langs->trans('Hasta');       
        $cadena =   "<tr>
                    <td><div class='titulofiltro'>$Producto : </div></td>
                    <td>$Desde : <input type='text' class ='producto' id='producto_desde'></td>
                    <td>$Hasta : <input type='text' class ='producto' id='producto_hasta'></td>    
                    </tr>
                    <script type='text/javascript'>
                    // Formatos
                    $(document).ready(function(){
                        $('.producto').attr('maxlength','10');
                    });
                    </script>";
        return $cadena;
    }
    
}

