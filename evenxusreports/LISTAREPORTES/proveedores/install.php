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
 
$idReporte			=	8001021;
$NombreReporte		=   "proveedores";
$ModuloReporte		=	"societe";

$NombreJasper 		=   $NombreReporte.".jasper";
$NombreIdiomaJasper = 	$NombreReporte.".properties";
$NombreFiltros		=   $NombreReporte.".php";
$NombreIdioma 		=   $NombreReporte.".lang";

// Ficheros de la desinstalacion ( Son todos los ficheros instalados despues )
$ficheros=	 "/frontend/".$NombreFiltros.
			",/langs/es_ES/".$NombreIdioma.
			",/langs/en_US/".$NombreIdioma.
			",/reports/".$NombreJasper.
			",/img/".$NombreReporte.".png".			
			",/img/tiles/".$NombreReporte.".png".			
			",/reports/en_US/".$NombreIdiomaJasper.
			",/reports/es_ES/".$NombreIdiomaJasper;
// Creamos reporte
$installOK=AddReporte($idReporte, "Proveedores",$ModuloReporte,"ListadoProveedores",$ficheros);

if ($installOK)
{
// Cargamos idiomas de Dolibarr
rename(DOL_DOCUMENT_ROOT."/evenxusreports/upload/".$NombreReporte."/es_ES/".$NombreIdioma,DOL_DOCUMENT_ROOT."/evenxusreports/langs/es_ES/".$NombreIdioma); // Español
rename(DOL_DOCUMENT_ROOT."/evenxusreports/upload/".$NombreReporte."/en_US/".$NombreIdioma,DOL_DOCUMENT_ROOT."/evenxusreports/langs/en_US/".$NombreIdioma); // Ingles
// Añadimos idioma 
AddIdioma($idReporte,$NombreReporte,$NombreIdioma);
// Cargamos idiomas del reporte
$langs->load($NombreReporte."@evenxusreports");

// Cargamos reporte.jasper
print $langs->trans("InstalandoFicheroReporte")."<br>"; 
rename(DOL_DOCUMENT_ROOT."/evenxusreports/upload/".$NombreReporte."/".$NombreJasper,DOL_DOCUMENT_ROOT."/evenxusreports/reports/".$NombreJasper);
// Creando idiomas jasper
rename(DOL_DOCUMENT_ROOT."/evenxusreports/upload/".$NombreReporte."/en_US/$NombreReporte.properties",DOL_DOCUMENT_ROOT."/evenxusreports/reports/en_US/".$NombreReporte.".properties");
rename(DOL_DOCUMENT_ROOT."/evenxusreports/upload/".$NombreReporte."/es_ES/$NombreReporte.properties",DOL_DOCUMENT_ROOT."/evenxusreports/reports/es_ES/".$NombreReporte.".properties");

// Cargamos pantalla de filtros
print $langs->trans("InstalandoFiltrosReporte")."<br>"; 
rename("../upload/".$NombreReporte."/".$NombreFiltros,"../frontend/".$NombreFiltros);

// Creamos entradas de Menu
print $langs->trans("CreandoMenus")."<br>"; 
// Padre (Menu de grupo) por eso el -1
CrearMenu($idReporte,$NombreReporte,8001000,-1,"100001","index.php?module=Terceros","Terceros",""); 
// Entrada menu
CrearMenu($idReporte,$NombreReporte,8001021,8001000,"100052",$NombreFiltros,"Proveedores",$NombreReporte);

// Creamos entrada de reporte en base de datos para activacion y desinstalacion
print $langs->trans("CreandoReporte")."<br>"; 

// Copiamos tile
rename(DOL_DOCUMENT_ROOT."/evenxusreports/upload/".$NombreReporte."/tile.png",DOL_DOCUMENT_ROOT."/evenxusreports/img/tiles/".$NombreReporte.".png"); 

// Copiamos logo
rename(DOL_DOCUMENT_ROOT."/evenxusreports/upload/".$NombreReporte."/logo.png",DOL_DOCUMENT_ROOT."/evenxusreports/img/".$NombreReporte.".png"); 
						 
// Creamos enlace de seguridad
print $langs->trans("CreandoPermisosReporte")."<br>"; 
AddPermiso($idReporte,"PermitirUsarListadoProveedores",$NombreReporte);
}
