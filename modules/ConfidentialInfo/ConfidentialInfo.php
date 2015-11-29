<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once('data/CRMEntity.php');
require_once('data/Tracker.php');

class ConfidentialInfo extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name = 'vtiger_confidentialinfo';
	var $table_index= 'confidentialinfoid';
	var $column_fields = Array();

	/** Indicator if this is a custom module or standard module */
	var $IsCustomModule = true;

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_confidentialinfocf', 'confidentialinfoid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_confidentialinfo', 'vtiger_confidentialinfocf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_confidentialinfo'   => 'confidentialinfoid',
	    'vtiger_confidentialinfocf' => 'confidentialinfoid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename => columnname) */
		// tablename should not have prefix 'vtiger_'
		'confidentialinfono' => Array('confidentialinfo' => 'confidentialinfono'),
		'cireference' => Array('confidentialinfo' => 'cireference'),
		'cicategory' => Array('confidentialinfo' => 'cicategory'),
		'cirelto' => Array('confidentialinfo' => 'cirelto'),
		'ciasset' => Array('confidentialinfo' => 'ciasset'),
		'Assigned To' => Array('crmentity' =>'smownerid')
	);
	var $list_fields_name = Array(
		/* Format: Field Label => fieldname */
		'confidentialinfono' => 'confidentialinfono',
		'cireference' => 'cireference',
		'cicategory' => 'cicategory',
		'cirelto' => 'cirelto',
		'ciasset' => 'ciasset',
		'Assigned To' => 'assigned_user_id'
	);

	// Make the field link to detail view from list view (Fieldname)
	var $list_link_field = 'confidentialinfono';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename => columnname) */
		// tablename should not have prefix 'vtiger_'
		'confidentialinfono' => Array('confidentialinfo' => 'confidentialinfono'),
		'cireference' => Array('confidentialinfo' => 'cireference'),
		'cicategory' => Array('confidentialinfo' => 'cicategory'),
		'cirelto' => Array('confidentialinfo' => 'cirelto'),
		'ciasset' => Array('confidentialinfo' => 'ciasset'),
	);
	var $search_fields_name = Array(
		/* Format: Field Label => fieldname */
		'confidentialinfono' => 'confidentialinfono',
		'cireference' => 'cireference',
		'cicategory' => 'cicategory',
		'cirelto' => 'cirelto',
		'ciasset' => 'ciasset',
	);

	// For Popup window record selection
	var $popup_fields = Array('cireference');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'cireference';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'cireference';

	// Required Information for enabling Import feature
	var $required_fields = Array('refto'=>1);

	// Callback function list during Importing
	var $special_functions = Array('set_import_assigned_user');

	var $default_order_by = 'cireference';
	var $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	var $mandatory_fields = Array('createdtime', 'modifiedtime', 'cireference');

	// List of fields that will not be encrypted
	static $nonEncryptedFields = array('confidentialinfoid','confidentialinfono','cireference','cicategory','cirelto','ciasset','description','record_id','record_module');

	function __construct() {
		global $log;
		$this_module = get_class($this);
		$this->column_fields = getColumnFields($this_module);
		$this->db = PearDatabase::getInstance();
		$this->log = $log;
		$sql = 'SELECT 1 FROM vtiger_field WHERE uitype=69 and tabid = ? limit 1';
		$tabid = getTabid($this_module);
		$result = $this->db->pquery($sql, array($tabid));
		if ($result and $this->db->num_rows($result)==1) {
			$this->HasDirectImageField = true;
		}
		$result = $this->db->query('select * from vtiger_confidentialinfocf limit 1');
		if (!empty($result) and $this->db->num_rows($result)==1) {
			foreach ($this->db->getFieldsDefinition($result) as $fldinfo) {
				if ($fldinfo->type=='string' and $fldinfo->max_length<765) {   // 765 = varchar(255)
					$this->db->query('ALTER TABLE vtiger_confidentialinfocf CHANGE '.$fldinfo->name.' '.$fldinfo->name.' VARCHAR(255)');
				}
			}
		}
	}

	function getSortOrder() {
		global $currentModule;

		$sortorder = $this->default_sort_order;
		if($_REQUEST['sorder']) $sortorder = $this->db->sql_escape_string($_REQUEST['sorder']);
		else if($_SESSION[$currentModule.'_Sort_Order']) 
			$sortorder = $_SESSION[$currentModule.'_Sort_Order'];

		return $sortorder;
	}

	function getOrderBy() {
		global $currentModule;
		
		$use_default_order_by = '';		
		if(PerformancePrefs::getBoolean('LISTVIEW_DEFAULT_SORTING', true)) {
			$use_default_order_by = $this->default_order_by;
		}
		
		$orderby = $use_default_order_by;
		if($_REQUEST['order_by']) $orderby = $this->db->sql_escape_string($_REQUEST['order_by']);
		else if($_SESSION[$currentModule.'_Order_By'])
			$orderby = $_SESSION[$currentModule.'_Order_By'];
		return $orderby;
	}

	function save_module($module) {
		if ($this->HasDirectImageField) {
			$this->insertIntoAttachment($this->id,$module);
		}
		if (empty($this->mode)) {
			$this->set_cinfo_history($this->id,'create','');
		} else {
			$this->set_cinfo_history($this->id,'update','');
		}
	}

	/**
	 * Return query to use based on given modulename, fieldname
	 * Useful to handle specific case handling for Popup
	 */
	function getQueryByModuleField($module, $fieldname, $srcrecord, $query='') {
		// $srcrecord could be empty
	}

	/**
	 * Get list view query (send more WHERE clause condition if required)
	 */
	function getListQuery($module, $usewhere='') {
		$query = "SELECT vtiger_crmentity.*, $this->table_name.*";

		// Keep track of tables joined to avoid duplicates
		$joinedTables = array();

		// Select Custom Field Table Columns if present
		if(!empty($this->customFieldTable)) $query .= ", " . $this->customFieldTable[0] . ".* ";

		$query .= " FROM $this->table_name";

		$query .= "	INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = $this->table_name.$this->table_index";

		$joinedTables[] = $this->table_name;
		$joinedTables[] = 'vtiger_crmentity';

		// Consider custom table join as well.
		if(!empty($this->customFieldTable)) {
			$query .= " INNER JOIN ".$this->customFieldTable[0]." ON ".$this->customFieldTable[0].'.'.$this->customFieldTable[1] .
				" = $this->table_name.$this->table_index";
			$joinedTables[] = $this->customFieldTable[0];
		}
		$query .= " LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid";
		$query .= " LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid";

		$joinedTables[] = 'vtiger_users';
		$joinedTables[] = 'vtiger_groups';

		$linkedModulesQuery = $this->db->pquery("SELECT distinct fieldname, columnname, relmodule FROM vtiger_field" .
				" INNER JOIN vtiger_fieldmodulerel ON vtiger_fieldmodulerel.fieldid = vtiger_field.fieldid" .
				" WHERE uitype='10' AND vtiger_fieldmodulerel.module=?", array($module));
		$linkedFieldsCount = $this->db->num_rows($linkedModulesQuery);

		for($i=0; $i<$linkedFieldsCount; $i++) {
			$related_module = $this->db->query_result($linkedModulesQuery, $i, 'relmodule');
			$fieldname = $this->db->query_result($linkedModulesQuery, $i, 'fieldname');
			$columnname = $this->db->query_result($linkedModulesQuery, $i, 'columnname');

			$other = CRMEntity::getInstance($related_module);
			vtlib_setup_modulevars($related_module, $other);

			if(!in_array($other->table_name, $joinedTables)) {
				$query .= " LEFT JOIN $other->table_name ON $other->table_name.$other->table_index = $this->table_name.$columnname";
				$joinedTables[] = $other->table_name;
			}
		}

		global $current_user;
		$query .= $this->getNonAdminAccessControlQuery($module,$current_user);
		$query .= "	WHERE vtiger_crmentity.deleted = 0 ".$usewhere;
		return $query;
	}

	/**
	 * Apply security restriction (sharing privilege) query part for List view.
	 */
	function getListViewSecurityParameter($module) {
		global $current_user;
		require('user_privileges/user_privileges_'.$current_user->id.'.php');
		require('user_privileges/sharing_privileges_'.$current_user->id.'.php');

		$sec_query = '';
		$tabid = getTabid($module);

		if($is_admin==false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1
			&& $defaultOrgSharingPermission[$tabid] == 3) {

				$sec_query .= " AND (vtiger_crmentity.smownerid in($current_user->id) OR vtiger_crmentity.smownerid IN 
					(
						SELECT vtiger_user2role.userid FROM vtiger_user2role 
						INNER JOIN vtiger_users ON vtiger_users.id=vtiger_user2role.userid 
						INNER JOIN vtiger_role ON vtiger_role.roleid=vtiger_user2role.roleid 
						WHERE vtiger_role.parentrole LIKE '".$current_user_parent_role_seq."::%'
					) 
					OR vtiger_crmentity.smownerid IN 
					(
						SELECT shareduserid FROM vtiger_tmp_read_user_sharing_per 
						WHERE userid=".$current_user->id." AND tabid=".$tabid."
					) 
					OR (";

					// Build the query based on the group association of current user.
					if(sizeof($current_user_groups) > 0) {
						$sec_query .= " vtiger_groups.groupid IN (". implode(",", $current_user_groups) .") OR ";
					}
					$sec_query .= " vtiger_groups.groupid IN 
						(
							SELECT vtiger_tmp_read_group_sharing_per.sharedgroupid 
							FROM vtiger_tmp_read_group_sharing_per
							WHERE userid=".$current_user->id." and tabid=".$tabid."
						)";
				$sec_query .= ")
				)";
		}
		return $sec_query;
	}

	/**	Function used to get the Confidential Information history
	 *	@param $id - confidentialid
	 *	return $return_data - array with header and the entries in format Array('header'=>$header,'entries'=>$entries_list) where as $header and $entries_list are array which contains all the column values of a row
	 */
	function get_cinfo_history($id) {
		global $log, $adb, $mod_strings, $app_strings, $current_user;
		$log->debug("Entering get_cinfo_history(".$id.") method ...");
	
		$query = 'select * from vtiger_confidentialinfohistory where ciid = ? order by whenacts desc';
		$result=$adb->pquery($query, array($id));
		$noofrows = $adb->num_rows($result);
	
		$header[] = $mod_strings['whomacts'];
		$header[] = $mod_strings['whenacts'];
		$header[] = $mod_strings['action'];
		$header[] = $mod_strings['comment'];
		
		while($row = $adb->fetch_array($result)) {
			$entries = Array();
			$entries[] = $row['whomacts'];
			$entries[] = DateTimeField::convertToUserFormat($row['whenacts']);
			$entries[] = getTranslatedString($row['action'],'ConfidentialInfo');
			$entries[] = $row['comment'];
			$entries_list[] = $entries;
		}
		$return_data = Array('header'=>$header,'entries'=>$entries_list);
		$log->debug("Exiting get_cinfo_history method ...");
		return $return_data;
	}

	/**	Function used to create a Confidential Information history log record
	 *  @param $record - id to associate log with
	 *	@param $action - action being registered. These will recognized and translated:
	 *	 	'create' => 'Created',
	 *		'retrieve' => 'Viewed',
	 *		'update' => 'Modified',
	 *		'delete' => 'Deleted',
	 *		'accessok' => 'Access Granted',
	 *		'accessnok' => 'Access Denied',
	 *  @param $comment - comment to add to the log
	 *	return true
	 */
	static function set_cinfo_history($record,$action,$comment) {
		global $log, $adb, $mod_strings, $app_strings, $current_user;
		$log->debug("Entering set_cinfo_history(".$id.") method ...");
	
		$query = 'insert into vtiger_confidentialinfohistory (ciid,whomacts,whomactsid,action,whenacts,comment) values (?,?,?,?,?,?)';

		$values = array();
		$values[] = $record;
		$values[] = getUserFullName($current_user->id);
		$values[] = $current_user->id;
		$values[] = $action;
		$values[] = date('Y-m-d H:i');
		$values[] = $comment;
	
		$result=$adb->pquery($query, $values);

		$log->debug("Exiting set_cinfo_history method ...");
		return true;
	}

	/**
	 * Create query to export the records.
	 */
	function create_export_query($where)
	{
		global $current_user;
		$thismodule = $_REQUEST['module'];
/*
		include("include/utils/ExportUtils.php");

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery($thismodule, "detail_view");

		$fields_list = getFieldsListFromQuery($sql);

		$query = "SELECT $fields_list, vtiger_users.user_name AS user_name 
				FROM vtiger_crmentity INNER JOIN $this->table_name ON vtiger_crmentity.crmid=$this->table_name.$this->table_index";

		if(!empty($this->customFieldTable)) {
			$query .= " INNER JOIN ".$this->customFieldTable[0]." ON ".$this->customFieldTable[0].'.'.$this->customFieldTable[1] .
				" = $this->table_name.$this->table_index";
		}

		$query .= " LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid";
		$query .= " LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid = vtiger_users.id and vtiger_users.status='Active'";

		$linkedModulesQuery = $this->db->pquery("SELECT distinct fieldname, columnname, relmodule FROM vtiger_field" .
				" INNER JOIN vtiger_fieldmodulerel ON vtiger_fieldmodulerel.fieldid = vtiger_field.fieldid" .
				" WHERE uitype='10' AND vtiger_fieldmodulerel.module=?", array($thismodule));
		$linkedFieldsCount = $this->db->num_rows($linkedModulesQuery);

		$rel_mods[$this->table_name] = 1;
		for($i=0; $i<$linkedFieldsCount; $i++) {
			$related_module = $this->db->query_result($linkedModulesQuery, $i, 'relmodule');
			$fieldname = $this->db->query_result($linkedModulesQuery, $i, 'fieldname');
			$columnname = $this->db->query_result($linkedModulesQuery, $i, 'columnname');

			$other = CRMEntity::getInstance($related_module);
			vtlib_setup_modulevars($related_module, $other);

			if($rel_mods[$other->table_name]) {
				$rel_mods[$other->table_name] = $rel_mods[$other->table_name] + 1;
				$alias = $other->table_name.$rel_mods[$other->table_name];
				$query_append = "as $alias";
			} else {
				$alias = $other->table_name;
				$query_append = '';
				$rel_mods[$other->table_name] = 1;
			}

			$query .= " LEFT JOIN $other->table_name $query_append ON $alias.$other->table_index = $this->table_name.$columnname";
		}

		$query .= $this->getNonAdminAccessControlQuery($thismodule,$current_user);
		$where_auto = " vtiger_crmentity.deleted=0";

		if($where != '') $query .= " WHERE ($where) AND $where_auto";
		else $query .= " WHERE $where_auto";

		return $query;
*/
		return 'select 0';
	}

	/**
	 * Initialize this instance for importing.
	 */
	function initImport($module) {
		$this->db = PearDatabase::getInstance();
		$this->initImportableFields($module);
	}

	/**
	 * Create list query to be shown at the last step of the import.
	 * Called From: modules/Import/UserLastImport.php
	 */
	function create_import_query($module) {
		global $current_user;
/*
		$query = "SELECT vtiger_crmentity.crmid, case when (vtiger_users.user_name not like '') then vtiger_users.user_name else vtiger_groups.groupname end as user_name, $this->table_name.* FROM $this->table_name
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = $this->table_name.$this->table_index
			LEFT JOIN vtiger_users_last_import ON vtiger_users_last_import.bean_id=vtiger_crmentity.crmid
			LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
			LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid
			WHERE vtiger_users_last_import.assigned_user_id='$current_user->id'
			AND vtiger_users_last_import.bean_type='$module'
			AND vtiger_users_last_import.deleted=0";
		return $query;
*/
		return 'select 0';
	}

	/**
	 * Delete the last imported records.
	 */
	function undo_import($module, $user_id) {
		global $adb;
		$count = 0;
		$query1 = "select bean_id from vtiger_users_last_import where assigned_user_id=? AND bean_type='$module' AND deleted=0";
		$result1 = $adb->pquery($query1, array($user_id)) or die("Error getting last import for undo: ".mysql_error());
		while ( $row1 = $adb->fetchByAssoc($result1))
		{
			$query2 = "update vtiger_crmentity set deleted=1 where crmid=?";
			$result2 = $adb->pquery($query2, array($row1['bean_id'])) or die("Error undoing last import: ".mysql_error());
			$count++;
		}
		return $count;
	}

	/**
	 * Transform the value while exporting
	 */
	function transform_export_value($key, $value) {
		return parent::transform_export_value($key, $value);
	}

	/**
	 * Function which will set the assigned user id for import record.
	 */
	function set_import_assigned_user()
	{
		global $current_user, $adb;
		$record_user = $this->column_fields["assigned_user_id"];

		if($record_user != $current_user->id){
			$sqlresult = $adb->pquery("select id from vtiger_users where id = ? union select groupid as id from vtiger_groups where groupid = ?", array($record_user, $record_user));
			if($this->db->num_rows($sqlresult)!= 1) {
				$this->column_fields["assigned_user_id"] = $current_user->id;
			} else {
				$row = $adb->fetchByAssoc($sqlresult, -1, false);
				if (isset($row['id']) && $row['id'] != -1) {
					$this->column_fields["assigned_user_id"] = $row['id'];
				} else {
					$this->column_fields["assigned_user_id"] = $current_user->id;
				}
			}
		}
	}

	/**
	 * Function which will give the basic query to find duplicates
	 */
	function getDuplicatesQuery($module,$table_cols,$field_values,$ui_type_arr,$select_cols='') {
		$select_clause = "SELECT ". $this->table_name .".".$this->table_index ." AS recordid, vtiger_users_last_import.deleted,".$table_cols;

		// Select Custom Field Table Columns if present
		if(isset($this->customFieldTable)) $query .= ", " . $this->customFieldTable[0] . ".* ";

		$from_clause = " FROM $this->table_name";

		$from_clause .= " INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = $this->table_name.$this->table_index";

		// Consider custom table join as well.
		if(isset($this->customFieldTable)) {
			$from_clause .= " INNER JOIN ".$this->customFieldTable[0]." ON ".$this->customFieldTable[0].'.'.$this->customFieldTable[1] .
				" = $this->table_name.$this->table_index";
		}
		$from_clause .= " LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
						LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid";

		$where_clause = " WHERE vtiger_crmentity.deleted = 0";
		$where_clause .= $this->getListViewSecurityParameter($module);

		if (isset($select_cols) && trim($select_cols) != '') {
			$sub_query = "SELECT $select_cols FROM $this->table_name AS t " .
				" INNER JOIN vtiger_crmentity AS crm ON crm.crmid = t.".$this->table_index;
			// Consider custom table join as well.
			if(isset($this->customFieldTable)) {
				$sub_query .= " LEFT JOIN ".$this->customFieldTable[0]." tcf ON tcf.".$this->customFieldTable[1]." = t.$this->table_index";
			}
			$sub_query .= " WHERE crm.deleted=0 GROUP BY $select_cols HAVING COUNT(*)>1";
		} else {
			$sub_query = "SELECT $table_cols $from_clause $where_clause GROUP BY $table_cols HAVING COUNT(*)>1";
		}

		$query = $select_clause . $from_clause .
					" LEFT JOIN vtiger_users_last_import ON vtiger_users_last_import.bean_id=" . $this->table_name .".".$this->table_index .
					" INNER JOIN (" . $sub_query . ") AS temp ON ".get_on_clause($field_values,$ui_type_arr,$module) .
					$where_clause .
					" ORDER BY $table_cols,". $this->table_name .".".$this->table_index ." ASC";

		return $query;
	}

	/*
	 * Function to encrypt an array of fields
	 * @param array('fieldname'=>'fieldvalue'),  for example column_fields array, MUST be a field of this module
	 * @param passwd, the current company wide password
	 * @returns the same array with values it can encrypt encrypted
	 */
	static function encryptFields($fields,$passwd) {
		global $adb, $log;
		if (empty($fields) or !is_array($fields)) return false;
		$passrs = $adb->query('select * from vtiger_cicryptinfo limit 1');
		if ($adb->num_rows($passrs)==0) return false;
		$passinfo = $adb->fetch_array($passrs);
		if ($passinfo['paswd']!=sha1($passwd)) return false;
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_256,'',MCRYPT_MODE_CFB, '');
		$key = substr($passwd, 0, mcrypt_enc_get_key_size($td));
		$ciiv = ConfidentialInfo::jejbs654sdf9s($passinfo['ciiv']);
		mcrypt_generic_init($td, $key, $ciiv);
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			if (in_array($fname,ConfidentialInfo::$nonEncryptedFields) or !ConfidentialInfo::isEncryptable($fname)) {
				$encfields[$fname] = $fvalue;
			} else {
				if (is_array($fvalue)) {
					$encfields[$fname] = ConfidentialInfo::encryptArray($fvalue,$td);
				} else {
					$ef = mcrypt_generic($td,$fvalue);
					$encfields[$fname] = base64_encode($ef);
				}
			}
		}
		mcrypt_generic_deinit ($td);
		mcrypt_module_close($td);
		return $encfields;
	}

	static function encryptArray($fields,$td) {
		global $adb, $log;
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			$ef = mcrypt_generic($td,$fvalue);
			$encfields[$fname] = base64_encode($ef);
		}
		return $encfields;
	}

	static function isEncryptable($fname) {
		global $adb, $log;
		$fldrs = $adb->pquery('select uitype, typeofdata from vtiger_field where fieldname=? and tabid=?',array($fname,getTabid('ConfidentialInfo')));
		$fldinfo = $adb->fetch_array($fldrs);
		list($ftype,$void) = explode('~', $fldinfo['typeofdata']);
		switch($ftype){
			case 'T':
			case 'D':
			case 'DT':
			case 'N':
			case 'NN':
			case 'I': return false;
			default: ;  // continue and check webservice types
		}
		$fldrs = $adb->pquery("select fieldtype from vtiger_ws_fieldtype where uitype=?", array($fldinfo['uitype']));
		$fldinfo = $adb->fetch_array($fldrs);
		switch($fldinfo['fieldtype']){
			case 'boolean':
			case 'owner':
			case 'file':
			case 'currency':
			case 'reference': return false;
			default: ;  // true
		};
		return true;
	}

	/*
	 * Function to decrypt an array of fields
	* @param array('fieldname'=>'fieldvalue'),  for example column_fields array, MUST be a field of this module
	* @param passwd, the current company wide password
	* @returns the same array with values it can decrypt decrypted
	*/
	static function decryptFields($fields,$passwd) {
		global $adb, $log;
		if (empty($fields) or !is_array($fields)) return false;
		$passrs = $adb->query('select * from vtiger_cicryptinfo limit 1');
		if ($adb->num_rows($passrs)==0) return false;
		$passinfo = $adb->fetch_array($passrs);
		if ($passinfo['paswd']!=sha1($passwd)) return false;
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_256,'',MCRYPT_MODE_CFB, '');
		$key = substr($passwd, 0, mcrypt_enc_get_key_size($td));
		$ciiv = ConfidentialInfo::jejbs654sdf9s($passinfo['ciiv']);
		mcrypt_generic_init($td, $key, $ciiv);
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			if (in_array($fname,ConfidentialInfo::$nonEncryptedFields) or !ConfidentialInfo::isEncryptable($fname)) {
				$encfields[$fname] = $fvalue;
			} else {
				if (strpos($fvalue,' |##| ')>0) {
					$valueArr = explode(' |##| ', $fvalue);
					$decflds = ConfidentialInfo::decryptArray($valueArr, $td);
					$encfields[$fname] = implode(' |##| ', $decflds);
				} else {
					$encfields[$fname] = @mdecrypt_generic($td,base64_decode($fvalue));
				}
			}
		}
		mcrypt_generic_deinit ($td);
		mcrypt_module_close($td);
		return $encfields;
	}

	static function decryptArray($fields,$td) {
		global $adb, $log;
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			$encfields[$fname] = @mdecrypt_generic($td,base64_decode($fvalue));
		}
		return $encfields;
	}

	static function jejbs654sdf9s($hexstr) {
		$strcode = '';
		for ($i = 0; $i < strlen($hexstr); $i=$i+2) {
			$strcode.= chr(hexdec('0x'.$hexstr[$i].$hexstr[$i+1]));
		}
		return $strcode;
	}

	static function k87rgsz5f4g9eer($name) {
		srand((double)microtime()*1000000);
		$strCharNumber = rand(48,56);
		for ($i = 0; $i < strlen($name); $i++) {
			$strChar = chr((ord($name[$i]) + $strCharNumber) % 255);
			$strcode.= $strChar;
		}
		return chr($strCharNumber).bin2hex($strcode);
	}

	static function zx524bxzvb5xd($name) {
		$strCharNumber = ord($name[0]);
		$strcode = '';
		for ($i = 1; $i < strlen($name); $i=$i+2) {
			$numc = hexdec('0x'.$name[$i].$name[$i+1]);
			$numc = $numc-$strCharNumber;
			if ($numc < 0) $numc = 255 - $numc;
			$strChar = chr($numc);
			$strcode.= $strChar;
		}
		return $strcode;
	}

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		if($event_type == 'module.postinstall') {
			// TODO Handle post installation actions
			$this->setModuleSeqNumber('configure', $modulename, 'CIINFO-', '000001');
			$module = Vtiger_Module::getInstance($modulename);
			$mod = Vtiger_Module::getInstance('Accounts');
			$mod->setRelatedList($module, 'ConfidentialInfo',array('ADD'),'get_dependents_list');
			$mod = Vtiger_Module::getInstance('Contacts');
			$mod->setRelatedList($module, 'ConfidentialInfo',array('ADD'),'get_dependents_list');
			$mod = Vtiger_Module::getInstance('Assets');
			$mod->setRelatedList($module, 'ConfidentialInfo',array('ADD'),'get_dependents_list');
		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
	}

	/**
	 * Handle saving related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	// function save_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle deleting related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function delete_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle getting related list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function get_related_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }

	/**
	 * Handle getting dependents list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function get_dependents_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }
}
?>
