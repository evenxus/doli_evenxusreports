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
 * Crea una entrada de menu en Dolibarr de forma directa, para los listados instalados dede fichero (install.php)
 * 
 * @global type $db
 * @param type $NombreReporte       Nombre del reporte = Nombre del fichero de idiomas
 * @param type $CodigoMenu          Idenfificador del menu >7701000 - Es el id del menu
 * @param type $CodigoMenuPadre     Idenfificador del padre del que cuelga el menu >7701000 o -1 si es un principal
 * @param type $position            Orden o peso dentro de las opciones
 * @param type $NombrePHP           Nombre del PHP al que llamara(dentro del /evenxusreports/frontend/)
 * @param type $Titulo              Titulo del menu
 * @param string $NombrePermiso     Permiso de activacion o 1 si no lo tiene
 * @return type                     La id del menu creado
 */
function CrearMenu($NombreReporte,$CodigoMenu,$CodigoMenuPadre,$position,$NombrePHP,$Titulo,$NombrePermiso) {
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
    // Si no lleva nombre de permiso se permite siempre
    if ($NombrePermiso=='') {
        $permiso="1";
    }
    else {
        $permiso="\$user->rights->evenxusreports->reports->$NombrePermiso";
    }
    // Alta en menu dolibarr
    $sql ="INSERT INTO ".MAIN_DB_PREFIX."menu (menu_handler, module, type, mainmenu, fk_menu, position, url, titre, langs, perms) " .
          "VALUES ('all', 'evenxusreports', " .
          "'left', 'reportes', $fk_menu, $position, " .
          "'/evenxusreports/frontend/$NombrePHP', '$Titulo', '$NombreReporte@evenxusreports', '$permiso');";    
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
/**
 * Devuelve el id del menu superior de evenxusreports
 * 
 * @global type $db
 * @return type
 */
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
 * @param type $codigo      Codigo de reporte
 * @param type $nombre      Nombre del reporte
 * @param type $detalle     Descripccion del reporte
 * @param type $ficheros    Ficheros con ruta relativa completa que componen el reporte (separados por comas)
 * @param type $activo      Indica si el reporte esta activo 1 o no 0
 */
function AddReporte($codigo,$nombre,$detalle,$ficheros,$activo) {
    global $db;
    // REPORTES
    $sql="DELETE FROM ".MAIN_DB_PREFIX."evr_reports WHERE codigo=$codigo";
    $db->query($sql);
    $db->begin();
    $sql ="INSERT INTO ".MAIN_DB_PREFIX."evr_reports (codigo,nombre,detalle,ficheros,activo) " .
              "VALUES ($codigo,'$nombre','$detalle','$ficheros',$activo);";    
    $result2=$db->query($sql);
    if ($result2==1)  {
        $db->commit();
    }    
    else { $db->rollback(); }
}

/**
 * Crea un permiso para el reporte y lo activa para todos los usuarios
 * 
 * @global type $db
 * @param type $codigo      Codigo del permiso
 * @param type $detalle     Detalle del permiso
 * @param type $reporte     'nombre' del permiso para llamarlo luego
 */
function AddPermiso($codigo,$detalle,$reporte) {
    global $db;
    // Creando permiso
    $sql="DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE id=$codigo";
    $db->query($sql);    
    $sql="INSERT INTO ".MAIN_DB_PREFIX."rights_def (id,libelle,module,entity,perms,subperms,type,bydefault) " .
         "VALUES ($codigo,'$detalle','evenxusreports',1,'reports','$reporte','w',1)";
    $db->begin();    
    $result=$db->query($sql);
    if ($result==1)  {
        $db->commit();
    }    
    else { $db->rollback(); }
    
    // Anotando permisos en backup permisos para poder recuperarlos tras un OFF-ON en el modulo
    $sql="DELETE FROM ".MAIN_DB_PREFIX."evr_def_permisos WHERE id=$codigo";
    $db->query($sql);    
    $sql="INSERT INTO ".MAIN_DB_PREFIX."evr_def_permisos (id,libelle,module,entity,perms,subperms,type,bydefault) " .
         "VALUES ($codigo,'$detalle','evenxusreports',1,'reports','$reporte','w',1)";
    $db->begin();    
    $result=$db->query($sql);
    if ($result==1)  {
        $db->commit();
    }    
    else { $db->rollback(); }
    
    // Asignando permisos de listado para cada usuario
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."user";
    $res=$db->query($sql);    
    if ($res>0) {
        $fila = $res->fetch_array();
        while ($fila) {
            $IdUsuario = $fila[rowid];
            // Creando permiso para usuario
            $sql= "INSERT INTO ".MAIN_DB_PREFIX."user_rights (fk_user,fk_id) " .
                  "VALUES ($IdUsuario,$codigo)";
            $db->begin();
            $result=$db->query($sql);
            if ($result==1)  {
                $db->commit();
            }    
            else { $db->rollback(); }
            $fila = $res->fetch_array();            
        }
    }
}
/*
 * Recrear permisos a nivel de reporte para todos los usuarios cuando se hace OFF-ON en el modulo
 * Vuelve a crear todos los permisos de los reportes
 */
function RecrearPermisosReportes() {
    global $db;
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."evr_def_permisos";
    $res=$db->query($sql);    
    if ($res>0) {
        $fila = $res->fetch_array();
        while ($fila) {    
            $id         = $fila[id];
            $libelle    = $fila[libelle];
            $module     = $fila[module];
            $entity     = $fila[entity];            
            $perms      = $fila[perms];            
            $subperms   = $fila[subperms];            
            $type       = $fila[type];            
            $bydefault  = $fila[bydefault];            
            $sql="INSERT INTO ".MAIN_DB_PREFIX."rights_def (id,libelle,module,entity,perms,subperms,type,bydefault) " .
                 "VALUES ($id,'$libelle','$module',$entity,'$perms','$subperms','$type',$bydefault)";
            $db->begin();
            $result=$db->query($sql);
            if ($result==1)  {
                
                $db->commit();
            }    
            else { $db->rollback(); }
            $fila = $res->fetch_array();            
        }
    }
}


