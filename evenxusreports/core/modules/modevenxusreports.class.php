<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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

require_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

require_once DOL_DOCUMENT_ROOT .'/evenxusreports/class/instalarreportes.php';



/**
 *  Descripcion y activacion de la clase del modulo extendiendo DolibarrModules
 */
class modEvenxusReports extends DolibarrModules
{
	/**
	 *   Constructor. Define nombres, constantes, carpetas, cajas , permisos
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
                global $langs,$conf;

                $this->db = $db;

                /**
                 *  Identificador de modulo, debe de ser unico  
                 *  Dentro de Inicio-> Utilidades del Sistema -> Info. Dolibarr -> Modulos se puede ver una lista de los Id Modules ocupados
                 */
		$this->numero = 7700000;
		// Identificando el modulo para permisos y menus (Mejor en minus pues luego se usa en codigo)
		$this->rights_class = 'evenxusreports';
               
		/**
                 *  La familia puede ser  'crm','financial','hr','projects','products','ecm','technic','other'
                 *  Sirve para elegir en que grupo de modulos aparecera en la pantalla de activacion de modulos
                 */
		$this->family = "crm";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Reportes avanzados";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '1.0';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// En que pagina del setup aparecera (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='generic';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /mymodule/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /mymodule/core/modules/barcode)
		// for specific css file (eg: /mymodule/css/mymodule.css.php)
		//$this->module_parts = array(
		//                        	'triggers' => 0,                                 	// Set this to 1 if module has its own trigger directory (core/triggers)
		//							'login' => 0,                                    	// Set this to 1 if module has its own login method directory (core/login)
		//							'substitutions' => 0,                            	// Set this to 1 if module has its own substitution function file (core/substitutions)
		//							'menus' => 0,                                    	// Set this to 1 if module has its own menus handler directory (core/menus)
		//							'theme' => 0,                                    	// Set this to 1 if module has its own theme directory (core/theme)
		//                        	'tpl' => 0,                                      	// Set this to 1 if module overwrite template dir (core/tpl)
		//							'barcode' => 0,                                  	// Set this to 1 if module has its own barcode directory (core/modules/barcode)
		//							'models' => 0,                                   	// Set this to 1 if module has its own models directory (core/modules/xxx)
		//							'css' => array('/mymodule/css/mymodule.css.php'),	// Set this to relative path of css file if module has its own css file
	 	//							'js' => array('/mymodule/js/mymodule.js'),          // Set this to relative path of js file if module must load a js on all pages
		//							'hooks' => array('hookcontext1','hookcontext2')  	// Set here all hooks context managed by module
		//							'dir' => array('output' => 'othermodulename'),      // To force the default directories names
		//							'workflow' => array('WORKFLOW_MODULE1_YOURACTIONTYPE_MODULE2'=>array('enabled'=>'! empty($conf->module1->enabled) && ! empty($conf->module2->enabled)', 'picto'=>'yourpicto@mymodule')) // Set here all workflow context managed by module
		//                        );
		$this->module_parts = array();

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp");
		$this->dirs = array();

		// Config pages. Put here list of php page, stored into mymodule/admin directory, to use to setup module.
		$this->config_page_url = array("mysetuppage.php@mymodule");

		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(5,0);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,0);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("evenxusreports@evenxusreports");

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0)
		// );
		$this->const = array();

		// Array to add new pages in new tabs
            	// Example: $this->tabs = array('objecttype:+tabname1:Title1:mylangfile@mymodule:$user->rights->mymodule->read:/mymodule/mynewtab1.php?id=__ID__',  	// To add a new tab identified by code tabname1
                //                              'objecttype:+tabname2:Title2:mylangfile@mymodule:$user->rights->othermodule->read:/mymodule/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2
                 //                              'objecttype:-tabname':NU:conditiontoremove);                                                     						// To remove an existing tab identified by code tabname
		// where objecttype can be
		// 'thirdparty'       to add a tab in third party view
		// 'intervention'     to add a tab in intervention view
		// 'order_supplier'   to add a tab in supplier order view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'invoice'          to add a tab in customer invoice view
		// 'order'            to add a tab in customer order view
		// 'product'          to add a tab in product view
		// 'stock'            to add a tab in stock view
		// 'propal'           to add a tab in propal view
		// 'member'           to add a tab in fundation member view
		// 'contract'         to add a tab in contract view
		// 'user'             to add a tab in user view
		// 'group'            to add a tab in group view
		// 'contact'          to add a tab in contact view
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
            $this->tabs = array();

             // Dictionnaries
            //BABEL//   if (! isset($conf->mymodule->enabled)) $conf->mymodule->enabled=0;
		$this->dictionnaries=array();
            /* Example:
            if (! isset($conf->mymodule->enabled)) $conf->mymodule->enabled=0;	// This is to avoid warnings
            $this->dictionnaries=array(
            'langs'=>'mylangfile@mymodule',
            'tabname'=>array(MAIN_DB_PREFIX."table1",MAIN_DB_PREFIX."table2",MAIN_DB_PREFIX."table3"),		// List of tables we want to see into dictonnary editor
            'tablib'=>array("Table1","Table2","Table3"),													// Label of tables
            'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),	// Request to select fields
            'tabsqlsort'=>array("label ASC","label ASC","label ASC"),																					// Sort order
            'tabfield'=>array("code,label","code,label","code,label"),																					// List of fields (result of select to show dictionnary)
            'tabfieldvalue'=>array("code,label","code,label","code,label"),																				// List of fields (list of fields to edit a record)
            'tabfieldinsert'=>array("code,label","code,label","code,label"),																			// List of fields (list of fields for insert)
            'tabrowid'=>array("rowid","rowid","rowid"),																									// Name of columns with primary key (try to always name it 'rowid')
            'tabcond'=>array($conf->mymodule->enabled,$conf->mymodule->enabled,$conf->mymodule->enabled)												// Condition to show each dictionnary
        );
        */

                // Boxes
		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
                $this->boxes = array();			// List of boxes
		$r=0;
		// Example:
		
		$this->boxes[$r][1] = "box_peliculas.php";
		$r++;
		/*
                $this->boxes[$r][1] = "myboxb.php";
		$r++;
		*/
                
                $langs->load("evenxusreports@evenxusreports");
                
		// Permisos
		$this->rights = array();		// Array de permisos del modulo
		$r=0;

                $this->rights[$r][0] = 7700001;                          // Id unico de permiso
                $this->rights[$r][1] = $langs->trans("PermitirCargarReportes");      // Etiqueta del permiso
                $this->rights[$r][3] = 0;                               // Por defecto activo(1) o desactivo(0) para nuevos usuarios
                $this->rights[$r][4] = 'cargarreporte';                 // Para testear el permiso segun $user->rights->permkey->level1->level2
                $this->rights[$r][5] = 'use';
                $r++;
                $this->rights[$r][0] = 7700002;                          // Id unico de permiso
                $this->rights[$r][1] = $langs->trans("PermitirListaReportes");     // Etiqueta del permiso
                $this->rights[$r][3] = 0;                               // Por defecto activo(1) o desactivo(0) para nuevos usuarios
                $this->rights[$r][4] = 'listareporte';                  // Para testear el permiso segun $user->rights->permkey->level1->level2
                $this->rights[$r][5] = 'list';
                $r++;

		// Menu principal
		$this->menus = array();			// Array de menus
		// Declarando nuevos menus
                $this->menu[0]=array(  'fk_menu'=>0,			        // 0 Es menu superior
                                        'type'=>'top',			        // This is a Top menu entry
                                        'titre'=>$langs->trans("Reportes"),    
                                        'mainmenu'=>'reportes',
                                        'leftmenu'=>'0',
                                        'url'=>'/evenxusreports/frontend/evenxusreports.php',
                                        'langs'=>'evenxusreports@evenxusreports',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                        'position'=>120,
                                        'enabled'=>'1',						    // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
                                        'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
                                        'target'=>'',
                                        'picto'=>'',
                                        'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both

		$r++;		
                // Menu izquierdo
		$this->menu[3]=array(	'fk_menu'=>'r=0',		// Usa r= donde r es el indice del menu padre(El indice superior es el menu superior)
					'type'=>'left',			// This is a Left menu entry
                                        'titre'=>'Evenxus Reports',
                                        'mainmenu'=>'reportes',
                                        'url'=>'/evenxusreports/frontend/evenxusreports.php',
                                        'langs'=>'evenxusreports@evenxusreports',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                        'position'=>100000,
                                        'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
                                        'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
                                        'target'=>'',
                                        'user'=>0);
		$r++;		
		$this->menu[4]=array(	'fk_menu'=>'r=3',	
					'type'=>'left',		
                                        'titre'=>$langs->trans('CargarReporte'),
                                        'mainmenu'=>'reportes',
                                        'url'=>'/evenxusreports/frontend/cargarreporte.php',
                                        'langs'=>'evenxusreports@evenxusreports',	
                                        'position'=>100001,
                                        'enabled'=>'1',			
                                        'perms'=>'$user->rights->evenxusreports->cargarreporte->use',			
                                        'target'=>'',
                                        'user'=>0);                
		$r++;		
		$this->menu[5]=array(	'fk_menu'=>'r=3',	
					'type'=>'left',		
                                        'titre'=>$langs->trans('ListaReportes'),
                                        'mainmenu'=>'reportes',
                                        'url'=>'/evenxusreports/frontend/listareportes.php',
                                        'langs'=>'evenxusreports@evenxusreports',	
                                        'position'=>100002,
                                        'enabled'=>'1',			
                                        'perms'=>'$user->rights->evenxusreports->listareporte->list',			
                                        'target'=>'',
                                        'user'=>0);                   


		// Exports
		$r=1;

		// Example:
		// $this->export_code[$r]=$this->rights_class.'_'.$r;
		// $this->export_label[$r]='CustomersInvoicesAndInvoiceLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
                // $this->export_enabled[$r]='1';                               // Condition to show export in list (ie: '$user->id==3'). Set to 1 to always show when module is enabled.
		// $this->export_permission[$r]=array(array("facture","facture","export"));
		// $this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.cp'=>'Zip','s.ville'=>'Town','s.fk_pays'=>'Country','s.tel'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode','f.rowid'=>"InvoiceId",'f.facnumber'=>"InvoiceRef",'f.datec'=>"InvoiceDateCreation",'f.datef'=>"DateInvoice",'f.total'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.tva'=>"TotalVAT",'f.paye'=>"InvoicePaid",'f.fk_statut'=>'InvoiceStatus','f.note'=>"InvoiceNote",'fd.rowid'=>'LineId','fd.description'=>"LineDescription",'fd.price'=>"LineUnitPrice",'fd.tva_tx'=>"LineVATRate",'fd.qty'=>"LineQty",'fd.total_ht'=>"LineTotalHT",'fd.total_tva'=>"LineTotalTVA",'fd.total_ttc'=>"LineTotalTTC",'fd.date_start'=>"DateStart",'fd.date_end'=>"DateEnd",'fd.fk_product'=>'ProductId','p.ref'=>'ProductRef');
		// $this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.cp'=>'company','s.ville'=>'company','s.fk_pays'=>'company','s.tel'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",'fd.price'=>"invoice_line",'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_tx'=>"invoice_line",'fd.qty'=>"invoice_line",'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.fk_product'=>'product','p.ref'=>'product');
		// $this->export_sql_start[$r]='SELECT DISTINCT ';
		// $this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'facturedet as fd, '.MAIN_DB_PREFIX.'societe as s)';
		// $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
		// $this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture';
		// $this->export_sql_order[$r] .=' ORDER BY s.nom';
		// $r++;
	}

	/**
	 *	Funcion llamada cuando el modulo se activa
         *      Añade constantes, cajas , permisos y menus(Si estan definidos en el constructor) a la base de datos Dolibarr
	 *	Tambien crea las carpetas de datos
	 *
         *      @param      string	$options    Opciones cuando activamos el modulo ('', 'noboxes')
	 *      @return     int         1 if OK, 0 if KO
	 */
	function init($options='')
	{
                require_once DOL_DOCUMENT_ROOT .'/evenxus/class/datos.php';
                $de = new DatosEvenxus();
                
                $sql = array();
		$result=$this->load_tables();
                $this->_init($sql, $options);            
                
                // Añade menus automaticos del modulo segun reportes instalados
                global $db;
                $sqlModulo= "SELECT * FROM ".MAIN_DB_PREFIX."evr_menu_reports";
                $result=$db->query($sqlModulo);
                if ($result>0) {
                    $fila = $result->fetch_array();
                    while ($fila) {
                        $codigomenu         = $fila[codigomenu];
                        $codigomenupadre    = $fila[codigomenupadre];
                        $orden              = $fila[orden];
                        $filtros            = $fila[filtros];
                        $titulo             = $fila[titulo];
                        CrearMenu($codigomenu, $codigomenupadre,$orden,$filtros, $titulo,0);
                        $fila = $result->fetch_array();
                    }
                }
                // Recrea permisos de reportes a nivel de usuario
                RecrearPermisosReportes();
                return 1;
	}

	/**
	 *	Funcion llamada cuando el modulo es desactivado
         *      Elimina de la base de datos constantes, cajas y permisos
	 *	Las carpetas de datos NO son borradas
	 *
         *      @param      string	$options    Opciones ('', 'noboxes')
	 *      @return     int         1 if OK, 0 if KO
	 */
	function remove($options='')
	{
                // Borra menus automaticos del modulo
                global $db;
                $sqlModulo = "DELETE * FROM ".MAIN_DB_PREFIX."menu WHERE module='evenxusreports'";
                $db->query($sqlModulo);
                
                $sql = array();
		return $this->_remove($sql, $options);
	}


	/**
	 *	Crea las tablas indices y datos requeridos por el modulo
	 *	Ficheros llx_table1.sql, llx_table1.key.sql llx_data.sql 
	 * 	Se almacenan en  /mymodule/sql/
	 *	Esta funcion es llamada por this->init
	 *	@return		int	<= 0 if KO, >0 if OK
	 */
	function load_tables()
	{
		return $this->_load_tables('/hexagono/sql/');
	}
}

?>
