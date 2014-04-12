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

$NombreReporte=  "clientes";

$NombreJasper=   $NombreReporte.".jasper";
$NombreFiltros=  $NombreReporte.".php";

// Movemos jasper
print "Instalando fichero reporte...<br><br>";
rename("../upload/".$NombreReporte."/".$NombreJasper,"../reports/".$NombreJasper);

// Movemos pantalla de filtros
print "Instalando pantalla de filtros del reporte...<br><br>";
rename("../upload/".$NombreReporte."/".$NombreFiltros,"../frontend/".$NombreFiltros);

// Creamos entradas de Menu
print "Creando entradas de menu...<br><br>";

// Padre (Menu de grupo) por eso el -1
CrearMenu(7701000,-1,"100001","clientes.php","Terceros",$NombreReporte); 
// Entrada menu
CrearMenu(7701001,7701000,"100002",$NombreFiltros,"Clientes",$NombreReporte);

// Creamos entrada de reporte en base de datos
AddReporte(7701001, "Clientes","Listado de clientes",1);

// Creamos enlace de seguridad
AddPermiso(7701001,"Permitir usar listado de clientes",$NombreReporte);

