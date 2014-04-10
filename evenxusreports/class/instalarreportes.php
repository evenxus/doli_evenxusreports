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

function CrearMenu($rowid,$fk_menu,$position,$NombrePHP,$Titulo,$Archivar) {
    global $db;
    global $id_menu_superior;
    $sql="DELETE FROM ".MAIN_DB_PREFIX."menu WHERE rowid=$rowid";
    $db->begin();
    $result1=$db->query($sql);
    if ($result1==1)  {
        $db->commit();
    }        
    else { $db->rollback(); }
    $sql ="INSERT INTO ".MAIN_DB_PREFIX."menu (rowid,menu_handler, module, type, mainmenu, fk_menu, position, url, titre, langs, perms) " .
          "VALUES ($rowid,'all', 'evenxusreports', " .
          "'left', 'reportes', $fk_menu, $position, " .
          "'/evenxusreports/frontend/$NombrePHP', '$Titulo', 'evenxusreports@evenxusreports', '1');";    
    $db->begin();    
    $result2=$db->query($sql);
    if ($result2==1)  {
        $db->commit();
    }    
    else { $db->rollback(); }
    if ($Archivar==1) {
        $sql="DELETE FROM ".MAIN_DB_PREFIX."evr_menu_reports WHERE rowid=$rowid";
        $db->begin();            
        $result3=$db->query($sql);
        if ($result3==1)  {
            $db->commit();
        }            
        else { $db->rollback(); }
        $raiz=0;
        if ($id_menu_superior==$fk_menu) { $raiz=1;}
        $sql="INSERT INTO ".MAIN_DB_PREFIX."evr_menu_reports (rowid,padre,raiz,orden,filtros,titulo) ".
             "VALUES ($rowid,$fk_menu,$raiz,'$position','$NombrePHP','$Titulo');";
        $db->begin();    
        $result4=$db->query($sql);
        if ($result4==1)  {
            $db->commit();
        }    
        else { $db->rollback(); }
    }
}

function ObtenerIDMenuSuperior($nombremodulo) {
    global $db;
    $id=-1;
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."menu WHERE module='$nombremodulo' AND fk_menu=0";
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