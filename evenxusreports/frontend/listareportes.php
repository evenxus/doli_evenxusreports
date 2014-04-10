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

require_once ("../../main.inc.php");         // Acceso al main de Doli, obligatorio

$langs->load("evenxusreports@evenxus");


// Seguridad
//if (!$user->rights->hexagono->peliculas->list) accessforbidden(); 

/*
 * Listado
 */

llxHeader();    // Cabecera

$sortfield		= GETPOST('sortfield','alpha');  // Campo indice
$sortorder		= GETPOST('sortorder','alpha');  // Sentido orden

// ******************************************************************************************
// Control de limites y paginacion 
// ******************************************************************************************
$page=GETPOST('page','int');
if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;
// ******************************************************************************************
// Obtiene los campos de filtro LIKE pasados desde cajas de texto 
// ******************************************************************************************
$busqueda_nombre=GETPOST('busqueda_nombre');
$busqueda_rowid=GETPOST('busqueda_rowid');
// ******************************************************************************************
// Creamos SQL
// ******************************************************************************************
if (! $sortfield) $sortfield="nombre"; // Campo de orden por defecto
if (! $sortorder) $sortorder="ASC";   // Orden ascendente por defecto
$sql = "SELECT rowid,codigo,nombre,detalle,activo FROM ".MAIN_DB_PREFIX."evr_reports ";
$sql.= " WHERE 1=1 "; // <- Prefiltro para añadir luego otros sin problemas
if ($busqueda_rowid)       $sql.= " AND rowid LIKE '%".$db->escape($busqueda_rowid)."%'";    // Filtro LIKE segun caja
if ($busqueda_nombre)      $sql.= " AND nombre LIKE '%".$db->escape($busqueda_nombre)."%'";  // Filtro LIKE segun caja
$sql.= " ORDER BY $sortfield $sortorder";                   // Aplicando campo y sentido del orden
$sql.= " ".$db->plimit($conf->liste_limit+1, $offset);      // Creando offset para las siguientes pages
// ******************************************************************************************
// Y lanzamos la consulta
$resql=$db->query($sql);
// Si tenemos resultado valido(aun sin lineas)
if ($resql) {
    // Obtenemos el numero de lineas
    $num = $db->num_rows($resql);  
    $i = 0;
    // Ponemos nombre del listado
    print_barre_liste($langs->trans("LISTAPELICULAS"), $page, $_SERVER["PHP_SELF"], '&$busqueda_rowid='.$busqueda_rowid.'&busqueda_nombre='.$busqueda_nombre, $sortfield, $sortorder,'',$num);
    // Pintamos tabla de resultados
    print '<table class="liste" width="100%">';
    print '<tr class="liste_titre">';
    // Parametros de busqueda
    $param='&amp;busqueda_rowid='.$busqueda_rowid;
    $param.='&amp;busqueda_nombre='.$busqueda_nombre;
    // Titulos de las columnas
    print_liste_field_titre($langs->trans("ID"), $_SERVER["PHP_SELF"], "rowid","","$param",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("NOMBRE"), $_SERVER["PHP_SELF"], "nombre","","$param",' colspan="2" ',$sortfield,$sortorder); // <-El Atributo colspan=2 "agranda" la ultima columna

    print "</tr>";
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    //***************************************************************************************    
    // Filtros de busqueda
    //***************************************************************************************
    print '<tr class="liste_titre">';
    print '<td  class="liste_titre">';
    print '<input type="text" class="flat" size="3" name="busqueda_rowid" value="'.$busqueda_rowid.'">'; 
    print '</td>';
    print '<td class="liste_titre">';
    print '<input type="text" class="flat" size="24" name="busqueda_nombre" value="'.$busqueda_nombre.'">'; // Caja de filtrado
    print '</td>';
    
    print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    print "</td></td>";
    print "</tr>\n";
    //***************************************************************************************
    print '</form>';

    $var=true;
    // LINEAS - Recorremos los resultados y pintamos las lineas
    while ($i < min($num,$limit)) {
        $obj = $db->fetch_object($resql);
        $var=!$var;
        print '<tr '.$bc[$var].'>'; // <- Aqui se crea las lineas de colores alternos
        print '<td width="100" nowrap="nowrap"><a href="peliculas.php?state=query&pelicula='.$obj->rowid.'">'.img_picto($langs->trans("MOSTRARPELICULA"),"pelicula.png@hexagono").'</a>'.$obj->id_pelicula.'</td>';
        print '<td  colspan="2">'.$obj->nombre.'</td>'; // <-El Atributo colspan=2 "agranda" la ultima columna
        print "</tr>";
        $i++;
    }
    $db->free($resql);
    print "</table>";
}
else {
    dol_print_error($db); // Error en SQL
}
$db->close(); // Cerramos base
llxFooter();  // Pie

?>
