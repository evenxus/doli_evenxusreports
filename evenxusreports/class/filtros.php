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
 * 
 * Almacen de filtros comunes a todos los listados
 * 
 */
class filtros {
    // Codigo postal DESDE-HASTA
    function CodigoPostalDH() {
        global $langs;
        $CodigoPostal = $langs->trans('CodigoPostal');
        $Desde = $langs->trans('Desde');
        $Hasta = $langs->trans('Hasta');       
        $cadena =   "<tr>
                    <td><div class='titulofiltro'>$CodigoPostal : </div></td>
                    <td>$Desde : <input type='text' class ='cp' id='cp_desde'></td>
                    <td>$Hasta : <input type='text' class ='cp' id='cp_hasta'></td>    
                    </tr>
                    <script type='text/javascript'>
                    // Formatos
                    $(document).ready(function(){
                        $('.cp').attr('maxlength','5');
                    });
                    </script><br><br>";
        return $cadena;
    }

    // Clientes DESDE-HASTA
    function ClientesDH($orden) {
        global $langs;
        $Clientes = $langs->trans('Clientes');
        $Desde = $langs->trans('Desde');
        $Hasta = $langs->trans('Hasta');       
        $cadena =   "<tr>
                    <td><div class='titulofiltro'>$Clientes : </div></td>
                    <td>$Desde : ".$this->lista_terceros('','cliente_desde','s.client = 1 OR s.client = 3',$orden,1)."</td>
                    <td>$Hasta : ".$this->lista_terceros('','cliente_hasta','s.client = 1 OR s.client = 3',$orden,1)."</td>
                    </tr>
                    <br><br>";
        return $cadena;
    }    
    
    // Proveedores DESDE-HASTA
    function ProveedoresDH($orden) {
        global $langs;
        $Proveedores = $langs->trans('Proveedores');
        $Desde = $langs->trans('Desde');
        $Hasta = $langs->trans('Hasta');       
        $cadena =   "<tr>
                    <td><div class='titulofiltro'>$Proveedores : </div></td>
                    <td>$Desde : ".$this->lista_terceros('','proveedor_desde','s.fournisseur = 1',$orden,1)."</td>
                    <td>$Hasta : ".$this->lista_terceros('','proveedor_hasta','s.fournisseur = 1',$orden,1)."</td>
                    </tr>
                    <br><br>";
        return $cadena;
    }
    
    
    // Articulos DESDE-HASTA
    function ProductoDH() {
        global $langs;
        $Producto = $langs->trans('Productos');
        $Desde = $langs->trans('Desde');
        $Hasta = $langs->trans('Hasta');       
        $cadena =   "<tr>
                    <td><div class='titulofiltro'>$Producto : </div></td>
                    <td>$Desde : <input type='text' class ='producto' id='producto_desde'></td>
                    <td>$Hasta : <input type='text' class ='producto' id='producto_hasta'></td>    
                    </tr>
                    <script type='text/javascript'>
                    // Formatos
                    $(document).ready(function(){
                        $('.producto').attr('maxlength','10');
                    });
                    </script><br><br>";
        return $cadena;
    }
    
    
    
    
    
  
    /**
     *  MUESTRA UN COMBO O AJAX DE SELECCION DE TERCEROS
     *  Output html form to select a third party 
     *  VARIACION DE LA ORIGINAL select_thirdparty_list QUE ESTA EN (htdocs/core/class/html.form.class.php)
     *
     *	@param	string	$selected       Preselected type
     *	@param  string	$htmlname       Name of field in form
     *  @param  string	$filter         Optionnal filters criteras (example: 's.rowid <> x')
     *  @param  string	$order          Orden de los datos
     *	@param	int		$showempty		Add an empty field
     * 	@param	int		$showtype		Show third party type in combolist (customer, prospect or supplier)
     * 	@param	int		$forcecombo		Force to use combo box
     *  @param	array	$event			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     *  @param	string	$filterkey		Filter on key value
     *  @param	int		$outputmode		0=HTML select string, 1=Array
     *  @param	int		$limit			Limit number of answers
     * 	@return	string					HTML string with
     */
    function lista_terceros($selected='',$htmlname='socid',$filter='',$order='nom ASC',$showempty=0, $showtype=0, $forcecombo=0, $event=array(), $filterkey='', $outputmode=0, $limit=20)
    {
        global $conf,$user,$langs,$db;

        $out='';
        $outarray=array();

        // On recherche les societes
        $sql = "SELECT s.rowid, s.nom, s.client, s.fournisseur, s.code_client, s.code_fournisseur";
        $sql.= " FROM ".MAIN_DB_PREFIX ."societe as s";
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE s.entity IN (".getEntity('societe', 1).")";
        if (! empty($user->societe_id)) $sql.= " AND s.rowid = ".$user->societe_id;
        if ($filter) $sql.= " AND (".$filter.")";
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        if (! empty($conf->global->COMPANY_HIDE_INACTIVE_IN_COMBOBOX)) $sql.= " AND s.status<>0 ";
        // Add criteria
        if ($filterkey && $filterkey != '')
        {
			$sql.=" AND (";
        	if (! empty($conf->global->COMPANY_DONOTSEARCH_ANYWHERE))   // Can use index
        	{
        		$sql.="(s.name LIKE '".$filterkey."%'";
        		$sql.=")";
        	}
        	else
        	{
        		// For natural search
        		$scrit = explode(' ', $filterkey);
        		foreach ($scrit as $crit) {
        			$sql.=" AND (s.name LIKE '%".$crit."%'";
        			$sql.=")";
        		}
        	}
        	if (! empty($conf->barcode->enabled))
        	{
        		$sql .= " OR s.barcode LIKE '".$filterkey."'";
        	}
        	$sql.=")";
        }
        $sql.= " ORDER BY ".$order;

        dol_syslog(get_class($this)."::select_thirdparty_list sql=".$sql);
        $resql=$db->query($sql);
        if ($resql)
        {
            if ($conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT && ! $forcecombo)
            {
                //$minLength = (is_numeric($conf->global->COMPANY_USE_SEARCH_TO_SELECT)?$conf->global->COMPANY_USE_SEARCH_TO_SELECT:2);
                $out.= ajax_combobox($htmlname, $event, $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
            }

            // Construct $out and $outarray
            $out.= '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'">'."\n";
            if ($showempty) $out.= '<option value="-1"></option>'."\n";
            $num = $db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $db->fetch_object($resql);
                    $label='';
                    if ($conf->global->SOCIETE_ADD_REF_IN_LIST) {
                    	if (($obj->client) && (!empty($obj->code_client))) {
                    		$label = $obj->code_client. ' - ';
                    	}
                    	if (($obj->fournisseur) && (!empty($obj->code_fournisseur))) {
                    		$label .= $obj->code_fournisseur. ' - ';
                    	}
                    	$label.=' '.$obj->nom;
                    }
                    else
                    {
                    	$label=$obj->nom;
                    }

                    if ($showtype)
                    {
                        if ($obj->client || $obj->fournisseur) $label.=' (';
                        if ($obj->client == 1 || $obj->client == 3) $label.=$langs->trans("Customer");
                        if ($obj->client == 2 || $obj->client == 3) $label.=($obj->client==3?', ':'').$langs->trans("Prospect");
                        if ($obj->fournisseur) $label.=($obj->client?', ':'').$langs->trans("Supplier");
                        if ($obj->client || $obj->fournisseur) $label.=')';
                    }
                    if ($selected > 0 && $selected == $obj->rowid)
                    {
                        $out.= '<option value="'.$obj->rowid.'" selected="selected">'.$label.'</option>';
                    }
                    else
					{
                        $out.= '<option value="'.$obj->rowid.'">'.$label.'</option>';
                    }

                    array_push($outarray, array('key'=>$obj->rowid, 'value'=>$obj->name, 'label'=>$obj->name));

                    $i++;
                    if (($i % 10) == 0) $out.="\n";
                }
            }
            $out.= '</select>'."\n";
        }
        else
        {
            dol_print_error($db);
        }

        if ($outputmode) return $outarray;
        return $out;
    }
    
    
    
    
    
    
    
}

