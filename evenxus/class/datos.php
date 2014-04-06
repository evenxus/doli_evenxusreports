<?php
/**
 * Copyright (C) 2013-     Santiago Garcia      <babelsistemas@gmail.com>
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

require_once ("../../main.inc.php");        // Acceso al main de Doli, obligatorio

/**
 * FUNCIONES COMUNES DE ACCESO A DATOS, UTILIZA COMO  BASE $db DE DOLIBARR
 */

class DatosEvenxus {
    var $db;    // Base de datos Doli
    function __construct()  {
        global $db;
        $this->db = $db;
    }    

    /**
     * Dado un SQL y una cadena para generar el LOG, actualiza la base de datos
     * 
     * @param Cadena $sql
     * @param Cadena $log
     * @return Entero 0 Si no ha podido actualizar, 1 Si ha podido actualizar
     */
    function ActualizarDatos($sql,$log)  {
        $ok=0;
        $this->db->begin();
        dol_syslog($log, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql) {  
            $this->db->commit();   
            $ok=1;
        }
        else {  
            $this->db->rollback(); 
            $ok=0;
        }   
        return $ok;
    }
    /**
     * Recibe SQL y devuelve N registros si existe y 0 sino 
     * 
     * @param Cadena $sql Consulta SQL
     * @return Entero Numero de registros encontrados 
     */
    function Existe($sql) {
        $num=0;
        $resql=$this->db->query($sql);
        if ($resql) {
            $num = $resql->num_rows;
            
			
        }    
        return $num;
    }
    /**
     *  Dado un SQL devuelve el valor de $campo para esa SQL
     * @param type $sql
     * @param type $campo
     * @return type
     */
    function Valor($sql,$campo) {
        $valorcampo="";
        dol_syslog(get_class($this)."::Valor sql=".$sql." campo=".$campo, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql) {
             $obj = $this->db->fetch_object($resql);
             $valorcampo=$obj->$campo;
        }
        return $valorcampo;
    }  
    /**
     * Lanza y loguea un insert-update-select
     * 
     * @param type $sql
     * @return type
     */
    function Query($sql) {
        dol_syslog(get_class($this)."::Valor sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        return $resql;
    }
    function Reg($rs) {
        $obj = $this->db->fetch_object($rs);
        return $obj;
    }
    /**
     * Devuelve el numero de registros
     * 
     * @param  type $sql
     * @return type
     */
    function Registros($sql_count,$camponumero) {
        $n=$this->Valor($sql_count,$camponumero);
        return $n;    
    }
    function SQLRegistros($sql) {
        $resql=$this->db->query($sql);
        return $this->db->num_rows($resql);
    }
    
    function CanonizaNumero($numero) {
        $numero = str_replace(",",".",$numero); 
        return $numero;
    }
    function CanonizaSQL($sql) {
        
    }
    
}

