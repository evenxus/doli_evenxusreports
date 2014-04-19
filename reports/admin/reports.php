<?php
/* Copyright (C) 2011      Juanjo Menent        <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	    \file       htdocs/admin/reports.php
 *		\ingroup    setup
 *		\brief      Page to administer reports and groups reports
 */

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");	// For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/core/class/html.formadmin.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formcompany.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

$langs->load("other");
$langs->load("admin");
$langs->load("reports@reports");

if (! $user->admin) accessforbidden();

$acts[0] = "activate";
$acts[1] = "disable";
$actl[0] = img_picto($langs->trans("Disabled"),'switch_off');
$actl[1] = img_picto($langs->trans("Activated"),'switch_on');

$listoffset=GETPOST('listoffset');
$listlimit=GETPOST('listlimit')>0?GETPOST('listlimit'):1000;
$active = 1;

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0 ; }
$offset = $listlimit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Table Names
$tabname[1] = MAIN_DB_PREFIX."reports_group";
$tabname[2] = MAIN_DB_PREFIX."reports_report";

// Table Labels
$tablib[1] = "DictionnaryReportsGroup";
$tablib[2] = "DictionnaryReportsReport";

// SQL for selects
$tabsql[1] = "SELECT rowid, code, name, active FROM ".MAIN_DB_PREFIX."reports_group";
$tabsql[2] = "SELECT r.rowid, r.code, r.xmlin, r.name as name, r.active, g.code as fk_group FROM ".MAIN_DB_PREFIX."reports_report as r, ".MAIN_DB_PREFIX."reports_group as g WHERE r.fk_group=g.rowid or r.fk_group=0";


// Order
$tabsqlsort[1] ="code ASC";
$tabsqlsort[2] ="name ASC";

// Showing Fields
$tabfield[1] = "code,name";
$tabfield[2] = "code,name,fk_group";  

// Editable Fields
$tabfieldvalue[1] = "code,name";
$tabfieldvalue[2] = "name,fk_group";   

// Insertable Fields
$tabfieldinsert[1] = "code,name";
$tabfieldinsert[2] = "name,fk_group";

// Nom du rowid si le champ n'est pas de type autoincrement
// Example: "" if id field is "rowid" and has autoincrement on
//          "nameoffield" if id field is not "rowid" or has not autoincrement on
$tabrowid[1] = "";
$tabrowid[2] = "";

// Showing
$tabcond[1] = true;
$tabcond[2] = true;

$msg='';


/*
 * Actions
 */
if ($_POST["actionadd"] || $_POST["actionmodify"])
{
    $listfield=explode(',',$tabfield[$_POST["id"]]);
    $listfieldinsert=explode(',',$tabfieldinsert[$_POST["id"]]);
    $listfieldmodify=explode(',',$tabfieldinsert[$_POST["id"]]);
    $listfieldvalue=explode(',',$tabfieldvalue[$_POST["id"]]);

    // Check that all fields are filled
    $ok=1;
    foreach ($listfield as $f => $value)
    {
        if ((! isset($_POST[$value]) || $_POST[$value]=='')
        && $listfield[$f] != 'decalage'  // Fields that are not mandatory
        && $listfield[$f] != 'module'   // Fields that are not mandatory
        && $listfield[$f] != 'xmlin')
        {
            $ok=0;
            $fieldnamekey=$listfield[$f];
            // We take translate key of field
            if ($fieldnamekey == 'name')  $fieldnamekey='Name';
            if ($fieldnamekey == 'code')   $fieldnamekey='Code';
            $msg.=$langs->trans("ErrorFieldRequired",$langs->transnoentities($fieldnamekey)).'<br>';
        }
    }
    // Autres verif
    if ($tabname[$_POST["id"]] == MAIN_DB_PREFIX."c_actioncomm" && isset($_POST["type"]) && $_POST["type"]=='system') 
    {
        $ok=0;
        $msg.="Value 'system' for type is reserved. You can use 'user' as value to add your own record.<br>";
    }
    if (isset($_POST["code"]) && $_POST["code"]=='0') 
    {
        $ok=0;
        $msg.="Code can't contains value 0<br>";
    }

    // If verif ok and action add, we adding the line
    if ($ok && $_POST["actionadd"])
    {
        if ($tabrowid[$_POST["id"]])
        {
            $newid=0;
            $sql = "SELECT max(".$tabrowid[$_POST["id"]].") newid from ".$tabname[$_POST["id"]];
            $result = $db->query($sql);
            if ($result)
            {
                $obj = $db->fetch_object($result);
                $newid=($obj->newid + 1);

            } else 
            {
                dol_print_error($db);
            }
        }

        // Add new entry
        $sql = "INSERT INTO ".$tabname[$_POST["id"]]." (";
        // List of fields
        if ($tabrowid[$_POST["id"]] &&
        ! in_array($tabrowid[$_POST["id"]],$listfieldinsert)) $sql.= $tabrowid[$_POST["id"]].",";
        $sql.= $tabfieldinsert[$_POST["id"]];
        $sql.=",active)";
        $sql.= " VALUES(";
        // List of values
        if ($tabrowid[$_POST["id"]] &&
        ! in_array($tabrowid[$_POST["id"]],$listfieldinsert)) $sql.= $newid.",";
        $i=0;
        foreach ($listfieldinsert as $f => $value)
        {
            if ($i) $sql.=",";
            if ($_POST[$listfieldvalue[$i]] == '') $sql.="null";
            else $sql.="'".$db->escape($_POST[$listfieldvalue[$i]])."'";
            $i++;
        }
        $sql.=",1)";

        dol_syslog("actionadd sql=".$sql);
        $result = $db->query($sql);
        if ($result)	// Add is ok
        {
            $oldid=$_POST["id"];
            $_POST=array('id'=>$oldid);	// Clean $_POST array, we keep only
            $id=$_POST["id"]; 
        }
        else
        {
            if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                $msg=$langs->trans("ErrorRecordAlreadyExists").'<br>';
            }
            else {
                dol_print_error($db);
            }
        }
    }

    // If verif ok and action modify, we modify the line
    if ($ok && $_POST["actionmodify"])
    {
        if ($tabrowid[$_POST["id"]]) { $rowidcol=$tabrowid[$_POST["id"]]; }
        else { $rowidcol="rowid"; }

        // Modify entry
        $sql = "UPDATE ".$tabname[$_POST["id"]]." SET ";
        
        if ($tabrowid[$_POST["id"]] && !in_array($tabrowid[$_POST["id"]],$listfieldmodify))
        {
            $sql.= $tabrowid[$_POST["id"]]."=";
            $sql.= "'".$db->escape($_POST["rowid"])."', ";
        }
        $i = 0;
        foreach ($listfieldmodify as $field)
        {
            if ($i) $sql.=",";
            $sql.= $field."=";
            if ($_POST[$listfieldvalue[$i]] == '') $sql.="null";
            else $sql.="'".$db->escape($_POST[$listfieldvalue[$i]])."'";
            $i++;
        }
        $sql.= " WHERE ".$rowidcol." = '".$_POST["rowid"]."'";

        dol_syslog("actionmodify sql=".$sql);
       
        $resql = $db->query($sql);
        if (! $resql)
        {
            $msg=$db->error();
        }
    }

    if ($msg) $msg='<div class="error">'.$msg.'</div>';
    $id=$_POST["id"];
}

if ($_POST["actioncancel"])
{
    $id=$_GET["id"]; 
}

if ($_REQUEST['action'] == 'confirm_delete' && $_REQUEST['confirm'] == 'yes')       // delete
{
	$id=$_GET["id"];
    if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
    else { $rowidcol="rowid"; }

    $sql = "DELETE from ".$tabname[$id]." WHERE ".$rowidcol."='".$_GET["rowid"]."'";

    dol_syslog("delete sql=".$sql);
    $result = $db->query($sql);
    if (! $result)
    {
        if ($db->errno() == 'DB_ERROR_CHILD_EXISTS')
        {
            $msg='<div class="error">'.$langs->trans("ErrorRecordIsUsedByChild").'</div>';
        }
        else
        {
            dol_print_error($db);
        }
    }
}

if ($_GET["action"] == $acts[0])       // activate
{
	$id=$_GET["id"];
    if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
    else { $rowidcol="rowid"; }

    if ($_GET["rowid"]) {
        $sql = "UPDATE ".$tabname[$id]." SET active = 1 WHERE ".$rowidcol."='".$_GET["rowid"]."'";
    }
    elseif ($_GET["code"]) {
        $sql = "UPDATE ".$tabname[$id]." SET active = 1 WHERE code='".$_GET["code"]."'";
    }

    $result = $db->query($sql);
    if (!$result)
    {
        dol_print_error($db);
    }
}

if ($_GET["action"] == $acts[1])       // disable
{
	$id=$_GET["id"];
    if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
    else { $rowidcol="rowid"; }

    if ($_GET["rowid"]) {
        $sql = "UPDATE ".$tabname[$id]." SET active = 0 WHERE ".$rowidcol."='".$_GET["rowid"]."'";
    }
    elseif ($_GET["code"]) {
        $sql = "UPDATE ".$tabname[$id]." SET active = 0 WHERE code='".$_GET["code"]."'";
    }

    $result = $db->query($sql);
    if (!$result)
    {
        dol_print_error($db);
    }
}


/*
 * View
 */

$html = new Form($db);
$formadmin=new FormAdmin($db);
$helpurl='EN:Module_Reports|FR:Module_Reports_FR|ES:M&oacute;dulo_Reports';
llxHeader('','',$helpurl);
dol_include_once('/reports/class/utils.class.php');

$titre=$langs->trans("ReportsSetup");
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print_fiche_titre($titre,$linkback,'setup');

print "<br>\n";


/*
 * Confirmation de la suppression de la ligne
 */
if ($_GET['action'] == 'delete')
{
    $ret=$html->form_confirm($_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$_GET["rowid"].'&code='.$_GET["code"].'&id='.$_GET["id"], $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete','',0,1);
    if ($ret == 'html') print '<br>';
}

/*
 * Show groups and reports
 */

for ($id = 1; $id <= 2; $id++)
{
   
    $sql=$tabsql[$id];
    if ($_GET["sortfield"])
    {
        $sql.= " ORDER BY ".$_GET["sortfield"];
        if ($_GET["sortorder"])
        {
            $sql.=" ".strtoupper($_GET["sortorder"]);
        }
        $sql.=", ";
        // Remove from default sort order the choosed order
        $tabsqlsort[$id]=preg_replace('/'.$_GET["sortfield"].' '.$_GET["sortorder"].',/i','',$tabsqlsort[$id]);
        $tabsqlsort[$id]=preg_replace('/'.$_GET["sortfield"].',/i','',$tabsqlsort[$id]);
    }
    else {
        $sql.=" ORDER BY ";
    }
    $sql.=$tabsqlsort[$id];
    $sql.=$db->plimit($listlimit+1,$offset);

    $fieldlist=explode(',',$tabfield[$id]);

    
    if($id==1)
    	print_titre($langs->trans("ReportsGroups"));
    else
    {
    	print "<br>";
    	print_titre($langs->trans("Reports"));
    }
    
    print '<form action="reports.php" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<table class="noborder" width="100%">';

    // Form to add a new line
    if ($tabname[$id] && $id==1)
    {
        $alabelisused=0;
        $var=false;

        $fieldlist=explode(',',$tabfield[$id]);
        
        // Line for title
        print '<tr class="liste_titre">';
        foreach ($fieldlist as $field => $value)
        {

            $valuetoshow=ucfirst($fieldlist[$field]);   // Default
            if ($fieldlist[$field]=='name')            { $valuetoshow=$langs->trans("Name"); }
            if ($fieldlist[$field]=='code')            { $valuetoshow=$langs->trans("Code"); }
            
			if ($valuetoshow != '')
			{
				print '<td>';
				print $valuetoshow;
				print '</td>';
			}

        }
        print '<td colspan="3">';
        print '<input type="hidden" name="id" value="'.$id.'">';
        print '&nbsp;</td>';
        print '</tr>';

        // Line to type new values
        print "<tr ".$bc[$var].">";

        $obj='';
        // If data was already input, we define them in obj to populate input fields.
        if ($_POST["actionadd"])
        {
            foreach ($fieldlist as $key=>$val)
            {
                if (! empty($_POST[$val])) $obj->$val=$_POST[$val];

            }
        }

        fieldList($fieldlist,$obj);

        print '<td colspan="3" align="right"><input type="submit" class="button" name="actionadd" value="'.$langs->trans("Add").'"></td>';
        print "</tr>";

        if ($alabelisused) 
        {
            print '<tr><td colspan="'.(count($fieldlist)+2).'">* '.$langs->trans("LabelUsedByDefault").'.</td></tr>';
        }
        print '<tr><td colspan="'.(count($fieldlist)+2).'">&nbsp;</td></tr>';
    

    	// List of available values in database
	    $resql=$db->query($sql);
	    if ($resql)
	    {
	        $num = $db->num_rows($resql);
	        $i = 0;
	        $var=true;
	        if ($num)
	        {
	            // There is several pages
	            if ($num > $listlimit)
	            {
	                print '<tr class="none"><td align="right" colspan="'.(3+count($fieldlist)).'">';
	                print_fleche_navigation($page,$_SERVER["PHP_SELF"],'&id='.GETPOST('id'),($num > $listlimit),$langs->trans("Page").' '.($page+1));
	                print '</td></tr>';
	            }
	
	            // Title of lines
	            print '<tr class="liste_titre">';
	            foreach ($fieldlist as $field => $value)
	            {
	                $showfield=1;							  	// PDefault
	                $valuetoshow=ucfirst($fieldlist[$field]);   // Default
	                if ($fieldlist[$field]=='name')            { $valuetoshow=$langs->trans("Name"); }
	                if ($fieldlist[$field]=='code')            { $valuetoshow=$langs->trans("Code"); }
	               
	                if ($showfield)
	                {
	                    print_liste_field_titre($valuetoshow,"reports.php",$fieldlist[$field],($page?'page='.$page.'&':'').'&id='.GETPOST("id"),"","",$sortfield,$sortorder);
	                }
	            }
	            print_liste_field_titre($langs->trans("Status"),"reports.php","active",($page?'page='.$page.'&':'').'&id='.GETPOST("id"),"",'align="center"',$sortfield,$sortorder);
	            print '<td colspan="2"  class="liste_titre">&nbsp;</td>';
	            print '</tr>';
	
	            // Lines with values
	            while ($i < $num)
	            {
	                $obj = $db->fetch_object($resql);
	                $var=!$var;

	                print "<tr ".$bc[$var].">";
	                if ($_GET["action"] == 'modify' && $_GET["id"]== 1 && ($_GET["rowid"] == ($obj->rowid?$obj->rowid:$obj->code)))
	                {
	                    print '<form action="reports.php" method="post">';
	                    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	                    print '<input type="hidden" name="id" value="'.GETPOST("id").'">';
	                    print '<input type="hidden" name="page" value="'.$page.'">';
	                    print '<input type="hidden" name="rowid" value="'.$_GET["rowid"].'">';
	                    fieldList($fieldlist,$obj);
	                    print '<td colspan="3" align="right"><a name="'.($obj->rowid?$obj->rowid:$obj->code).'">&nbsp;</a><input type="submit" class="button" name="actionmodify" value="'.$langs->trans("Modify").'">';
	                    print '&nbsp;<input type="submit" class="button" name="actioncancel" value="'.$langs->trans("Cancel").'"></td>';
	                }
	                else
	                {
	                    foreach ($fieldlist as $field => $value)
	                    {
	                        $showfield=1;
	                        $valuetoshow=$obj->$fieldlist[$field];
	                    	if ($valuetoshow=='noAssigned') 
	                        {
	                            $valuetoshow=$langs->trans('NoAssigned');
	                        }
	                        
	                        else if ($fieldlist[$field]=='name') 
	                        {
                            	$key=$langs->trans("group".strtoupper($obj->code));
                            	$valuetoshow=($obj->code && $key != "group".strtoupper($obj->code))?$key:$obj->$fieldlist[$field];
	                        }
	                       
	                        if ($showfield) print '<td>'.$valuetoshow.'</td>';
	                    }
	
	                    print '<td align="center" nowrap="nowrap">';
	               
	                    $iserasable=1; 
	                 
	                    if (isset($obj->code) && $obj->code == '0000') $iserasable=0;
	
	                    if ($iserasable) 
	                    {
	                        print '<a href="'.$_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.($obj->rowid?$obj->rowid:$obj->code).'&amp;code='.$obj->code.'&amp;id='.$id.'&amp;action='.$acts[$obj->active].'">'.$actl[$obj->active].'</a>';
	                    } 
	                    else 
	                    {
	                        print $langs->trans("AlwaysActive");
	                    }
	                    print "</td>";
	
	                    // Modify link
	                    if ($iserasable) 
	                    {
	                        print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.($obj->rowid?$obj->rowid:$obj->code).'&amp;code='.$obj->code.'&amp;id='.$id.'&amp;action=modify#'.($obj->rowid?$obj->rowid:$obj->code).'">'.img_edit().'</a></td>';
	                    } 
	                    else 
	                    {
	                        print '<td>&nbsp;</td>';
	                    }
	                    // Delete link
	                    if ($iserasable) {
	                        print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.($obj->rowid?$obj->rowid:$obj->code).'&amp;code='.$obj->code.'&amp;id='.$id.'&amp;action=delete">'.img_delete().'</a></td>';
	                    } else {
	                        print '<td>&nbsp;</td>';
	                    }
	                    print "</tr>\n";
	                }
	                $i++;
	            }
	        }
	    }
	    
	    $report =  glob("../includes/reportico/projects/Dolibarr/*.xml",GLOB_BRACE);
  		foreach ($report as $rep)
  		{
			$nombres[]=array_pop(preg_split('|[/]|',$rep));
  		}
    }
	else 
	{
		$alabelisused=0;
        $var=false;
        $fieldlist=explode(',',$tabfield[$id]);
        
        foreach($nombres as $rep)
        {
        	
        	$namerep=preg_split('|[.]|',$rep);
        	
        	$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."reports_report WHERE xmlin='".$rep."'";
        	$resql=$db->query($sql);
	    	if ($resql)
	    	{
	        	$num = $db->num_rows($resql);
	        	if($num==0)
	        	{
					// Add new entry
        			$sql = "INSERT INTO ".$tabname[$id]." (";
        			$sql.= "xmlin";
        			$sql.= ",fk_group";
        			$sql.= ",name";
        			$sql.=",active)";
        			$sql.= " VALUES(";
  					$sql.= "'".$rep."'";
  					$sql.= ",'1'";
  					$sql.= ",'".$namerep[0]."'";
       				$sql.=",1)";
					$result = $db->query($sql);
        		}
        
	    	}
        }
        
        $sql=$tabsql[2];
        
        
		$resql=$db->query($sql);
	    if ($resql)
	    {
	        $num = $db->num_rows($resql);
	        $i = 0;
	        $var=true;
	        if ($num)
	        {
	            // There is several pages
	            if ($num > $listlimit)
	            {
	                print '<tr class="none"><td align="right" colspan="'.(3+count($fieldlist)).'">';
	                print_fleche_navigation($page,$_SERVER["PHP_SELF"],'&id='.GETPOST('id'),($num > $listlimit),$langs->trans("Page").' '.($page+1));
	                print '</td></tr>';
	            }
	
	            // Title of lines
	            print '<tr class="liste_titre">';
	            foreach ($fieldlist as $field => $value)
	            {
	                $showfield=1;							  	// Default
	                $valuetoshow=ucfirst($fieldlist[$field]);   // Default
	                if ($fieldlist[$field]=='name')            { $valuetoshow=$langs->trans("Name"); }
	                if ($fieldlist[$field]=='code')            { $valuetoshow=$langs->trans("Code"); }
	            	if ($fieldlist[$field]=='fk_group')        { $valuetoshow=$langs->trans("Group"); }
	            	if ($fieldlist[$field]=='xmlin')           { $valuetoshow=$langs->trans("File"); }
	               
	                if ($showfield)
	                {
	                    print_liste_field_titre($valuetoshow,"reports.php",$fieldlist[$field],($page?'page='.$page.'&':'').'&id='.GETPOST("id"),"","",$sortfield,$sortorder);
	                }
	            }
	            print_liste_field_titre($langs->trans("Status"),"reports.php","active",($page?'page='.$page.'&':'').'&id='.GETPOST("id"),"",'align="center"',$sortfield,$sortorder);
	            print '<td colspan="2"  class="liste_titre">&nbsp;</td>';
	            print '</tr>';
	
	            // Lines with values
	            while ($i < $num)
	            {
	                $obj = $db->fetch_object($resql);
	                $var=!$var;

	                print "<tr ".$bc[$var].">";
	                if ($_GET["action"] == 'modify' && $_GET["id"]== 2 && ($_GET["rowid"] == ($obj->rowid?$obj->rowid:$obj->code)))
	                {
	                    print '<form action="reports.php" method="post">';
	                    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	                    print '<input type="hidden" name="id" value="'.GETPOST("id").'">';
	                    print '<input type="hidden" name="page" value="'.$page.'">';
	                    print '<input type="hidden" name="rowid" value="'.$_GET["rowid"].'">';
	                    fieldList($fieldlist,$obj,true);
	                    print '<td colspan="3" align="right"><a name="'.($obj->rowid?$obj->rowid:$obj->code).'">&nbsp;</a><input type="submit" class="button" name="actionmodify" value="'.$langs->trans("Modify").'">';
	                    print '&nbsp;<input type="submit" class="button" name="actioncancel" value="'.$langs->trans("Cancel").'"></td>';
	                }
	                else
	                {
	                    foreach ($fieldlist as $field => $value)
	                    {
	                        $showfield=1;
	                        $valuetoshow=$obj->$fieldlist[$field];
	                    	
	                        if ($valuetoshow=='0000') 
	                        {
	                            $valuetoshow=$langs->trans('NoAssigned');
	                        }
	                        
	                        else if ($fieldlist[$field]=='fk_group') 
	                        {
                            	$key=$langs->trans("group".strtoupper($obj->fk_group));
                            	$valuetoshow=($obj->fk_group && $key != "group".strtoupper($obj->fk_group))?$key:$obj->$fieldlist[$field];
	                        }
	                        
	                    	else if ($fieldlist[$field]=='name') 
	                        {
                            	$key=$langs->trans("report".strtoupper($obj->code));
                            	$valuetoshow=($obj->code && $key != "report".strtoupper($obj->code))?$key:$obj->$fieldlist[$field];
	                        }
	                       
	                        if ($showfield) print '<td>'.$valuetoshow.'</td>';
	                        
	                    }
	
	                    print '<td align="center" nowrap="nowrap">';

	                    $iserasable=1; 
	
	                    if ($iserasable) 
	                    {
	                        print '<a href="'.$_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.($obj->rowid?$obj->rowid:$obj->code).'&amp;code='.$obj->code.'&amp;id='.$id.'&amp;action='.$acts[$obj->active].'">'.$actl[$obj->active].'</a>';
	                    }    
	                    else 
	                    {
	                        print $langs->trans("AlwaysActive");
	                    }
	                    print "</td>";
	
	                    // Modify link
	                    if ($iserasable) 
	                    {
	                        print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.($obj->rowid?$obj->rowid:$obj->code).'&amp;code='.$obj->code.'&amp;id='.$id.'&amp;action=modify#'.($obj->rowid?$obj->rowid:$obj->code).'">'.img_edit().'</a></td>';
	                    } 
	                    else 
	                    {
	                        print '<td>&nbsp;</td>';
	                    }
	                    // Delete link
	                    if ($iserasable) 
	                    {
	                        print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.($obj->rowid?$obj->rowid:$obj->code).'&amp;code='.$obj->code.'&amp;id='.$id.'&amp;action=delete">'.img_delete().'</a></td>';
	                    } 
	                    else 
	                    {
	                        print '<td>&nbsp;</td>';
	                    }
	                    print "</tr>\n";
	                }
	                $i++;
	            }
	        }
	    }
        
	}
    
    print '</table>';

    print '</form>';
    
}

dol_htmloutput_mesg($msg);

llxFooter();

$db->close();

/**
 *	Show field
 *
 * 	@param		array	$fieldlist		Array of fields
 * 	@param		Object	$obj			If we show a particular record, obj is filled with record fields
 *	@return		void		
 */
function fieldList($fieldlist,$obj='', $report=false)
{
    global $conf,$langs,$db;
    global $elementList,$sourceList;

    $html = new Form($db);
    $formadmin = new FormAdmin($db);
    $formcompany = new FormCompany($db);

    foreach ($fieldlist as $field => $value)
    {

        if ($fieldlist[$field] == 'fk_group') 
        {
	        $out='';
	        $GroupArray=array();
	        $label=array();
	
	        $selected=$obj->fk_group;
	        $htmlname="fk_group";
	        $htmloption='';
	        
	       	$sql = "SELECT rowid, code , name";
	        $sql.= " FROM ".MAIN_DB_PREFIX."reports_group";
	        $sql.= " ORDER BY code ASC";
	        
	        $resql=$db->query($sql);
	        if ($resql)
	        {
	            $out.= '<select id="select'.$htmlname.'" class="flat selectgroup" name="'.$htmlname.'" '.$htmloption.'>';
	            $num = $db->num_rows($resql);
	            $i = 0;
	            if ($num)
	            {
	                $foundselected=false;
	
	            	while ($i < $num)
	            	{
	                    $obj = $db->fetch_object($resql);
	                    $GroupArray[$i]['rowid'] 	= $obj->rowid;
	                    $GroupArray[$i]['code'] 	= $obj->code;
	                    
	                    $key=$langs->trans("group".strtoupper($obj->code));
                        $valuetoshow=($obj->code && $key != "group".strtoupper($obj->code))?$key:$obj->name;
	                    
	                    
	                    if($obj->name!="noAssigned")
	                    	$GroupArray[$i]['name']		= $valuetoshow;
	                    else
	                    	$GroupArray[$i]['name']= $langs->trans("NoAssigned");
	                    	
	                	$label[$i] 	= $GroupArray[$i]['name'];
	                    $i++;
	                }
	
	                array_multisort($label, SORT_ASC, $GroupArray);
	
	                foreach ($GroupArray as $row)
	                {
	                	if ($selected && $selected != '-1' && ($selected == $row['rowid'] || $selected == $row['code'] || $selected == $row['name']) ) 
	                	{
	                        $foundselected=true;
	                        $out.= '<option value="'.$row['rowid'].'" selected="selected">';
	                    } 
	                    else 
	                    {
	                        $out.= '<option value="'.$row['rowid'].'">';
	                    }
	                    $out.= $row['name'];
	                    if ($row['code']) $out.= ' ('.$row['code'] . ')';
	                    $out.= '</option>';
	                }
	            }
	            $out.= '</select>';
	        }
            	
            print '<td>';
           	print $out;
            print '</td>';
        }
        
        elseif ($fieldlist[$field] == 'code' && !$report) 
        {
			print '<td><input type="text" class="flat" value="'.$obj->$fieldlist[$field].'" size="10" name="'.$fieldlist[$field].'"></td>';
        }
        
    	elseif ($fieldlist[$field] == 'code' && $report) 
        {
			print '<td>'.$obj->$fieldlist[$field].'</td>';
        }
        elseif($fieldlist[$field] == 'xmlin')
        {
        	print '<td>'.$obj->$fieldlist[$field].'</td>';
        }			
        else
        {
            print '<td>';
            print '<input type="text" '.($fieldlist[$field]=='libelle'?'size="32" ':'').' class="flat" value="'.$obj->$fieldlist[$field].'" name="'.$fieldlist[$field].'">';
            print '</td>';
        }
    }
}

?>