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
 * Crea una entrada de menu en Dolibarr de forma directa
 * 
 */
function CrearMenu($CodigoMenu,$CodigoMenuPadre,$position,$NombrePHP,$Titulo,$Archivar) {
    $InsertId=0;
    global $db;
    
    require_once DOL_DOCUMENT_ROOT .'/evenxus/class/datos.php';
    $de = new DatosEvenxus();

    // Asginamos fk_menu o id de menu padre
    if ($CodigoMenuPadre==-1) {  // Menu superior
        $fk_menu = ObtenerIDMenuSuperior();
    } 
    else {
        // Si no es un menu superior consulta el id actual de su padre
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."evr_menu_reports WHERE codigomenu='$CodigoMenuPadre'"; 
        $fk_menu = $de->Valor($sql, "idactual");
    }
    // Alta en menu dolibarr
    $sql ="INSERT INTO ".MAIN_DB_PREFIX."menu (menu_handler, module, type, mainmenu, fk_menu, position, url, titre, langs, perms) " .
          "VALUES ('all', 'evenxusreports', " .
          "'left', 'reportes', $fk_menu, $position, " .
          "'/evenxusreports/frontend/$NombrePHP', '$Titulo', 'evenxusreports@evenxusreports', '1');";    
    $db->begin();    
    $result=$db->query($sql);
    
    if ($result==1)  {
        $db->commit();
        // Archivamos en nuestro gestor de menus
        
        // Primero borramos menu anterior si lo hay con el mismo ID
        $sql="DELETE FROM ".MAIN_DB_PREFIX."evr_menu_reports WHERE codigomenu=$CodigoMenu";
        $db->begin();    
        $result2=$db->query($sql);
        if ($result2==1)  {$db->commit(); }
        
        // Obtenemos el ID que ha resultado de la insercion en los menus de Dolibarr
        $InsertId=$de->Valor("SELECT * FROM ".MAIN_DB_PREFIX."menu WHERE fk_menu='$fk_menu' AND position='$position' AND url='/evenxusreports/frontend/$NombrePHP'", "rowid");

        $sql="INSERT INTO ".MAIN_DB_PREFIX."evr_menu_reports (codigomenu,codigomenupadre,idactual,orden,filtros,titulo) ".
             "VALUES ($CodigoMenu,$CodigoMenuPadre,'$InsertId','$position','$NombrePHP','$Titulo');";      
        $result=$db->query($sql);
        if ($result==1)  {
            $db->commit();
        }
    }    
    else { $db->rollback(); }
    return $InsertId;    
}

function ObtenerIDMenuSuperior() {
    global $db;
    $id=-1;
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."menu WHERE module='evenxusreports' AND fk_menu=0";
    $resql=$db->query($sql);
    if ($resql==1) {
        $obj = $db->fetch_object($resql);
        $id=$obj->rowid;
    }
    return $id;
}
/**
 * Añade un reporte a la base de datos, si existe uno anterior con el mismo codigo lo borra primero
 * 
 * @global type $db
 * @param type $codigo
 * @param type $nombre
 * @param type $detalle
 * @param type $reporte
 * @param type $filtros
 * @param type $activo
 */
function AddReporte($codigo,$nombre,$detalle,$activo) {
    global $db;
    // REPORTES
    $sql="DELETE FROM ".MAIN_DB_PREFIX."evr_reports WHERE codigo=$codigo";
    $db->query($sql);
    $db->begin();
    $sql ="INSERT INTO ".MAIN_DB_PREFIX."evr_reports (codigo,nombre,detalle,activo) " .
              "VALUES ($codigo,'$nombre','$detalle',$activo);";    
    $result2=$db->query($sql);
    if ($result2==1)  {
        $db->commit();
    }    
    else { $db->rollback(); }
}