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

function CrearMenu($rowid,$modulo,$menuprincipal,$fk_menu,$position,$NombrePHP,$Titulo,$Idioma) {
    global $db;
    $db->begin();
    $sql ="INSERT INTO ".MAIN_DB_PREFIX."menu (rowid,menu_handler, module, type, mainmenu, fk_menu, position, url, titre, langs, perms) " .
          "VALUES ($rowid,'all', '$modulo', " .
          "'left', '$menuprincipal', $fk_menu, $position, " .
          "'/$modulo/frontend/$NombrePHP', '$Titulo', '$Idioma', '1');";    
    $result=$db->query($sql);
    if ($result==1)  {
        $db->commit();
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