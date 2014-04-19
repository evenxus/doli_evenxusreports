<?php

/* Copyright (C) 2013-     Santiago Garcia      <babelsistemas@gmail.com>
 * Copyright (C) 2014-     Enfirme              <enfirme@gmail.com>
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
 * PAGINA QUE CONTIENE LA LISTA DE TODOS LAS RECOGIDAS CON SU ESTADO
 */
require_once ("../../main.inc.php");                 // Acceso al main de Doli, obligatorio
require_once (DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php'); // Para leer los archivos de una carpeta
require_once (DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/evenxus/class/datos.php');     // Ayuda en acceso a datos Doli
require_once (DOL_DOCUMENT_ROOT . '/evenxusreports/class/instalarreportes.php');
require_once (DOL_DOCUMENT_ROOT . '/evenxusreports/class/comunes.php');

$form = new Form($db);                  // Usado para gestioanr forms(p.e. lanzar los paneles de confirmacion en ajax)
$Datos = new DatosEvenxus();            // Ayuda en accesos a datos

// Seguridad 
if (!$user->rights->evenxusreports->listareporte->list) { accessforbidden(); }

// Carga idiomas de todos los reportes
$idiomas=CargarIdiomas();
foreach ($idiomas as &$idioma) {
    $langs->load($idioma);
}


/*
 * Listado
 */
$c =    '<link rel=stylesheet href="../css/estilos.css" type="text/css">';
        
llxHeader($c);    // Cabecera
// ******************************************************************************************
// Obtiene los parametros pasados
// ******************************************************************************************
$state = GETPOST('state', 'alpha');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');  // Campo indice
$sortorder = GETPOST('sortorder', 'alpha');  // Sentido orden
$page = GETPOST('page', 'int');         // Pagina
$boton = $_REQUEST["modification"];     // Boton pulsado
$registros = $_REQUEST["registros"];
$codigo = GETPOST('codigo', 'alpha');    //Codigo del informe


/*
 * Actions
 */

// Activa listado
if ($action == 'set') {
    $actualizar = 1;
    $sqlactualizar = InsertOrUpdateInforme($codigo, $actualizar);
    $results = $Datos->Query($sqlactualizar);
    // Crear menu
    $de = new DatosEvenxus();
    $sql="SELECT * FROM ".MAIN_DB_PREFIX."evr_menu_reports WHERE codigoreporte=".$codigo." ORDER by codigomenupadre";
    $results = $Datos->Query($sql);
    $fila = $results->fetch_array();
    while ($fila) {
        CrearMenu($fila['codigoreporte'],$fila['nombrereporte'],$fila['codigomenu'],$fila['codigomenupadre'],$fila['orden'],$fila['filtros'],$fila['titulo'],$fila['nombrereporte']);
         $fila = $results->fetch_array();
    }    
    header("Location: listareportes.php");
 }
// Desactiva listado
if ($action == 'reset') {
    $actualizar = 0;
    $sqlactualizar = InsertOrUpdateInforme($codigo, $actualizar);
    $results = $Datos->Query($sqlactualizar);
    // Borro menu
    $de = new DatosEvenxus();
    $sql="SELECT * FROM ".MAIN_DB_PREFIX."evr_menu_reports WHERE codigomenu=".$codigo;
    $idactual=$de->Valor($sql, "idactual");
    $sql="DELETE FROM ".MAIN_DB_PREFIX."menu WHERE rowid=".$idactual;
    $Datos->Query($sql);
    header("Location: listareportes.php");    
}
// Hemos pulsado la papelera
if ($action == 'delete') {
    $actualizar = 0;
    BorrarReporte($codigo);
    header("Location: listareportes.php");
    exit;
}

// ******************************************************************************************
//                                          LISTADO
// ******************************************************************************************
// Control de limites y paginacion 
// ******************************************************************************************
if ($page == -1) {
    $page = 0;
}
$limit = $conf->liste_limit;
$offset = $limit * $page;
// ******************************************************************************************
// Creamos y lanzamos SQL
// ******************************************************************************************
if (!$sortfield)
    $sortfield = "rowid"; // Campo de orden por defecto
if (!$sortorder)
    $sortorder = "ASC";    // Orden ascendente por defecto
$sql = CrearConsultaSQL($sortfield, $sortorder, $offset);
$rsInformes = $db->query($sql);
if ($rsInformes) {
    //***************************************************************************************    
    // Contamos el total de registros (Con y Sin paginacion)
    //***************************************************************************************    
    $TotalRegistrosConPaginacion = $db->num_rows($rsInformes);
    $TotalRegistrosSinPaginacion = $Datos->Registros($sql . filtros(), "N");
    //***************************************************************************************    
    // Ponemos titulo del listado
    //***************************************************************************************    
    print_barre_liste($langs->trans("LISTADEREPORTESEVENXUS"), $page, $_SERVER["PHP_SELF"], '&$busqueda_codigo=' . $busqueda_codigo . '&busqueda_nombre=' . $busqueda_nombre, $sortfield, $sortorder, '', $TotalRegistrosConPaginacion, $TotalRegistrosSinPaginacion);
    print '<form method="POST" name="formulario" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="state" value="modify">';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="page" value="' . $page . '">';
    print "<table width='100%'>";
    print "<tr></tr></table>";
    //***************************************************************************************    
    // Pintamos tabla de resultados 
    //***************************************************************************************    
    print '<table class="liste" width="100%">';
    print '<tr class="liste_titre">';
    //***************************************************************************************        
    // Parametros de busqueda
    //***************************************************************************************    
    $param = '&amp;busqueda_terminal=' . $busqueda_terminal;
    $param.='&amp;busqueda_codigo=' . $busqueda_codigo;
    $param.='&amp;busqueda_proveedor=' . $busqueda_nombre;
    $param.='&filtro_operario=' . $codigo;

//***************************************************************************************    
    // Titulos de las columnas
    //***************************************************************************************    
    print_liste_field_titre($langs->trans("CampoCodigo"), $_SERVER["PHP_SELF"], "codigo", "", "$param", '', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans("CampoInforme"), $_SERVER["PHP_SELF"], "nombre", "", "$param", '', $sortfield, $sortorder); // <-El Atributo colspan=2 "agranda" la ultima columna
    print_liste_field_titre($langs->trans("CampoModulo"), $_SERVER["PHP_SELF"], "modulo", "", "$param", '', $sortfield, $sortorder); // <-El Atributo colspan=2 "agranda" la ultima columna	
    print_liste_field_titre($langs->trans("CampoActivo"), $_SERVER["PHP_SELF"], "activo", "", "$param", 'colspan="2"', $sortfield, $sortorder); // <-El Atributo colspan=2 "agranda" la ultima columna	
    //print_liste_field_titre($langs->trans("activo"), $_SERVER["PHP_SELF"], "activo", "", "$param", '', $sortfield, $sortorder); // <-El Atributo colspan=2 "agranda" la ultima columna	
    print_liste_field_titre($langs->trans("CampDetalle"), $_SERVER["PHP_SELF"], "detalle", "", "", 'colspan="2"'); // <-El Atributo colspan=2 "agranda" la ultima columna    	
    print '</tr>';

    //***************************************************************************************    
    // Filtros de busqueda
    //***************************************************************************************
    print '<tr class="liste_titre">';
    print '<td  class="liste_titre">';
    print '</td>';
    print '<td  class="liste_titre">';
    print '</td>';
    print '<td  class="liste_titre">';
    print '</td>';
    print '<td  class="liste_titre">';
    print '</td>';
    print '<td  class="liste_titre">';
    print '</td>';
    print '<td colspan="2" class="liste_titre" align="center">';
    print "</td>";
    print "</tr>\n";
    //***************************************************************************************
    $var = true; // Alternar linea de colores
    print '<input type="hidden" name="registros" value="' . $TotalRegistrosConPaginacion . '">';
    $i = 0;
    while ($i < min($TotalRegistrosConPaginacion, $limit)) {
        //***************************************************************************************
        // LINEAS - Recorremos los resultados y pintamos las lineas
        //***************************************************************************************
        $rowInformes = $rsInformes->fetch_array();
        $var = !$var;
        // Si el módulo esta activo mostramos el informe, sino no.

        print '<tr ' . $bc[$var] . '>'; // <- Aqui se crea las lineas de colores alternos
        print '<input type="hidden" name="codigo' . $i . '" value="' . $rowInformes['rowid'] . '">';
        print '<td width="100" nowrap="nowrap">' . img_picto($langs->trans("MOSTRARRUTA"), "reporte16x16.png@evenxusreports") . $rowInformes['codigo'] . '</td>';
        print '<td width="100" nowrap="nowrap">' . $langs->trans($rowInformes['nombre']) . '</td>';
        print '<td width="150" nowrap="nowrap">' . Modulo_Nombre($rowInformes['modulo']) . '</td>';
        print '<td  width="100" align="left" valign="middle">';
        if (ModuloActivo($rowInformes['modulo']) == true) {
            // Module non actif
            $seleccionado = $rowInformes['activo'];
            if ($seleccionado == 1) {
                print '<a href="listareportes.php?action=reset&codigo=' . $rowInformes['codigo'] . '">';
                print img_picto($langs->trans("Enabled"), 'switch_on');
            } else {
                print '<a href="listareportes.php?action=set&codigo=' . $rowInformes['codigo'] . '">';
                print img_picto($langs->trans("Disabled"), 'switch_off');
            }
            print "</a></td>";
        } else {
            print "<div id='modulo-off'>modulo-off</div>";
        }
        print '</td>';
        print '<td width="50" nowrap="nowrap">';
        print '<input id="desinstalar-reporte'.$rowInformes['codigo'].'" type="button" name="desinstalar-reporte'.$rowInformes['codigo'].'"  value="'.$langs->trans("DESINSTALARLISTADO").'" onclick="">';
        print $form->formconfirm("listareportes.php?codigo=".$rowInformes['codigo'],$langs->trans("DESINSTALARLISTADOTITULO"),$langs->trans("EXPLICADESINSTALARLISTADO"),"delete","",0,"desinstalar-reporte".$rowInformes['codigo']);           
        print ' &nbsp; &nbsp; ';           
        print "</a></td>";
        print '<td align="left" width="350" valign="middle" colspan="2">';
        print $langs->trans($rowInformes['detalle']);
        print "</td>";
        print '</tr>';
        $i++;
    }
    unset($rowInformes);
    $db->free($rsInformes);
    print '</table></form>';
}
print '<br><br>'.PiePagina();             
llxFooter();  // Pie


/**
 * Crea la consulta SQL del listado
 * 
 * @param type $sortfield   Orden
 * @param type $sortorder   Sentido
 * @param type $offset      Inicio
 * @param type $limit       Limite de registro a mostrar
 * @return type Cadena con el SQL
 */
function CrearConsultaSQL($sortfield, $sortorder, $offset) {
    global $conf, $db;

    $sql = "select  * from " . MAIN_DB_PREFIX . "evr_reports ";
    $sql.= " where 1=1";
    $sql.= filtros();
    $sql.= " ORDER BY $sortfield $sortorder";
    $sql.= " " . $db->plimit($conf->liste_limit + 1, $offset);      // Creando offset para las siguientes pages    
    return $sql;
}

/**
 * Crea filtros en funcion de los parametros recibidos
 * 
 * @global type $busqueda_codigo
 * @global type $busqueda_nombre
 * @global type $db
 * @return string
 */
function filtros() {
    global $busqueda_codigo, $db;
    $sql = "";
    if ($busqueda_codigo) {
        $sql.= " AND reg.codigo LIKE '%" . $db->escape($busqueda_codigo) . "%' ";    // Filtro LIKE segun caja
    }
    return $sql;
}



/**
 * Devuelve el nombre del módulo
 * 
 * @param: $nom_modulo . Nombre del módulo. terceros->'societe'
 */
function Modulo_Nombre($nom_modulo) {
    global $db;
    // Busca todos los módulos que están cargados en dolibarr.
    // Esto lo hace mirando el nombre de las carpetas que son módulos.
    $modules = array();
    $modulesdir = dolGetModulesDirs();
    // Recorremos las carpetas
    foreach ($modulesdir as $dir) {
        // Abre cada una de las carpetas
        $handle = @opendir(dol_osencode($dir));
        if (is_resource($handle)) {
            while (($file = readdir($handle)) !== false) {
                if (is_readable($dir . $file) && substr($file, 0, 3) == 'mod' && substr($file, dol_strlen($file) - 10) == '.class.php') {
                    // Capturamos el nobre de la carpeta del módulo.
                    $modName = substr($file, 0, dol_strlen($file) - 10);
                    if ($modName) {

                        // Para coger el nombre del módulo en su propio "Lenguaje",
                        // hay que leer para cada módulo el fichero langs correspondiente
                        include_once $dir . $file;
                        $objMod = new $modName($db);

                        // Load all lang files of module
                        if (isset($objMod->langfiles) && is_array($objMod->langfiles)) {
                            foreach ($objMod->langfiles as $domain) {
                                // si el nombre del modulo es el pasado a la función.
                                if (strtoupper($modName) == strtoupper('mod' . $nom_modulo)) {
                                    $nombre = $objMod->getName();
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    return $nombre;
}

/**
 * Comprueba si el registro esta activo a desactivo
 * Sino existe el registro lo crea, sino actualiza su valor
 */
function InsertOrUpdateInforme($codigo, $actualizar) {
    global $Datos;

    $estado = 0;
    If ($actualizar == 1)
        $estado = 1;
    $codigo = $Datos->Valor("SELECT codigo FROM " . MAIN_DB_PREFIX . "evr_reports WHERE codigo='" . $codigo . "'", "codigo");
    if ($codigo != "") {
        $sql = "UPDATE " . MAIN_DB_PREFIX . "evr_reports SET activo=" . $actualizar . " WHERE codigo='" . $codigo . "'";
    } else {
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "evr_reports  (codigo,nombre,detalle) VALUES ('" . $codigo . "', '" . $nombre . "'," . $actualizar . ")";
    }
    return $sql;
}


