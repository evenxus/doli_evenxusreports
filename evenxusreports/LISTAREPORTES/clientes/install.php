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
 
$idReporte			=	8001001;
$NombreReporte		=   "clientes";

$NombreJasper 		=   $NombreReporte.".jasper";
$NombreIdiomaJasper = 	$NombreReporte.".properties";
$NombreFiltros		=   $NombreReporte.".php";
$NombreIdioma 		=   $NombreReporte.".lang";

// Cargamos idiomas Doli
rename(DOL_DOCUMENT_ROOT."/evenxusreports/upload/".$NombreReporte."/es_ES/".$NombreIdioma,DOL_DOCUMENT_ROOT."/evenxusreports/langs/es_ES/".$NombreIdioma); // Español
// Cargamos idiomas del reporte
$langs->load("clientes@evenxusreports");

// Cargamos reporte.jasper
print $langs->trans("InstalandoFicheroReporte")."<br>"; 
rename(DOL_DOCUMENT_ROOT."/evenxusreports/upload/".$NombreReporte."/".$NombreJasper,DOL_DOCUMENT_ROOT."/evenxusreports/reports/".$NombreJasper);
// Creando carpetas de idiomas jasper
rcopy(DOL_DOCUMENT_ROOT."/evenxusreports/upload/".$NombreReporte."/en_GB/",DOL_DOCUMENT_ROOT."/evenxusreports/reports/en_GB/");
rcopy(DOL_DOCUMENT_ROOT."/evenxusreports/upload/".$NombreReporte."/es_ES/",DOL_DOCUMENT_ROOT."/evenxusreports/reports/es_ES/");

// Cargamos pantalla de filtros
print $langs->trans("InstalandoFiltrosReporte")."<br>"; 
rename("../upload/".$NombreReporte."/".$NombreFiltros,"../frontend/".$NombreFiltros);

// Creamos entradas de Menu
print $langs->trans("CreandoMenus")."<br>"; 
// Padre (Menu de grupo) por eso el -1
CrearMenu($idReporte,$NombreReporte,8001000,-1,"100001","clientes.php","Terceros",""); 
// Entrada menu
CrearMenu($idReporte,$NombreReporte,8001001,8001000,"100002",$NombreFiltros,"Clientes",$NombreReporte);

// Creamos entrada de reporte en base de datos para activacion y desinstalacion
print $langs->trans("CreandoReporte")."<br>"; 

// Ficheros de la desinstalacion
$ficheros=	 "/frontend/".$NombreFiltros.
			",/langs/es_ES/".$NombreIdioma.
			",/reports/".$NombreJasper.
			",/reports/en_GB/".$NombreIdiomaJasper.
			",/reports/es_ES/".$NombreIdiomaJasper;
							 
AddReporte($idReporte, "Clientes","societe","ListadoClientes",$ficheros,1);
// Creamos enlace de seguridad
print $langs->trans("CreandoPermisosReporte")."<br>"; 
AddPermiso($idReporte,"PermitirUsarListadoClientes",$NombreReporte);

