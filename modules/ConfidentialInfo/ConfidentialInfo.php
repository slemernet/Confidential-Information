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
	var $HasDirectImageField = false;
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
		parent::__construct();
		$result = $this->db->query('select * from vtiger_confidentialinfocf limit 1');
		if (!empty($result) and $this->db->num_rows($result)==1) {
			foreach ($this->db->getFieldsDefinition($result) as $fldinfo) {
				if ($fldinfo->type=='string' and $fldinfo->max_length<765) {   // 765 = varchar(255)
					$this->db->query('ALTER TABLE vtiger_confidentialinfocf CHANGE '.$fldinfo->name.' '.$fldinfo->name.' tinyblob');
				}
			}
		}
	}

	function trash($module, $record) {
		$this->set_cinfo_history($record,'delete','');
		parent::trash($module, $record);
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
		$return_data = Array('header'=>$header,'entries'=>$entries_list,'navigation'=>array('',''));
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
		$log->debug("Entering set_cinfo_history($record) method ...");

		$query = 'insert into vtiger_confidentialinfohistory (ciid,whomacts,whomactsid,action,whenacts,comment) values (?,?,?,?,?,?)';

		$values = array();
		$values[] = $record;
		$values[] = getUserFullName($current_user->id);
		$values[] = $current_user->id;
		$values[] = $action;
		$values[] = date('Y-m-d H:i');
		$values[] = $comment;

		$result=$adb->pquery($query, $values);

		$log->debug('Exiting set_cinfo_history method ...');
		return true;
	}

	/**
	 * Create query to export the records.
	 */
	function create_export_query($where) {
		return 'select 0';
	}

	/**
	 * Create list query to be shown at the last step of the import.
	 * Called From: modules/Import/UserLastImport.php
	 */
	function create_import_query($module) {
		return 'select 0';
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
		switch (coreBOS_Settings::getSetting('CINFO_EncryptMethod', 'mcrypt')) {
			case 'libsodium':
			;
			break;
			case 'openssl':
			;
			break;
			case 'mcrypt':
			default:
				return ConfidentialInfo::encryptFields_mcrypt($fields,$passwd,$passinfo['ciiv']);
			break;
		}
	}

	/*
	 * Function to decrypt an array of fields
	* @param array('fieldname'=>'fieldvalue'),  for example column_fields array, MUST be a field of this module
	* @param passwd, the current company wide password
	* @returns the same array with values it can decrypt decrypted
	*/
	static function decryptFields($fields,$passwd,$nonce='') {
		global $adb, $log;
		if (empty($fields) or !is_array($fields)) return false;
		$passrs = $adb->query('select * from vtiger_cicryptinfo limit 1');
		if ($adb->num_rows($passrs)==0) return false;
		if (empty($nonce)) {
			$nonce = $adb->query_result($passrs, 0, 'ciiv');
		}
		switch (coreBOS_Settings::getSetting('CINFO_EncryptMethod', 'mcrypt')) {
			case 'libsodium':
			;
			break;
			case 'openssl':
			;
			break;
			case 'mcrypt':
			default:
				return ConfidentialInfo::decryptFields_mcrypt($fields,$passwd,$nonce);
			break;
		}
	}

	/*
	 * Function to get a nonce
	* @returns nonce
	*/
	static function getNONCE() {
		switch (coreBOS_Settings::getSetting('CINFO_EncryptMethod', 'mcrypt')) {
			case 'libsodium':
			;
			break;
			case 'openssl':
			;
			break;
			case 'mcrypt':
			default:
				$td = mcrypt_module_open(MCRYPT_RIJNDAEL_256,'',MCRYPT_MODE_CFB, '');
				$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td),MCRYPT_RAND);
				return $iv;
			break;
		}
	}

	/*
	 * Function to decrypt and encrypt all record with a new password
	 * @params password
	 */
	public static function migrate2NewPassword($cinewpass, $cioldpass, $oldnonce, $alreadyDecrypted=false) {
		global $adb;
		set_time_limit(0);
		$focus = CRMEntity::getInstance('ConfidentialInfo');

		$fldrs = $adb->query('select fieldname,tablename from vtiger_field where tabid='.getTabid('ConfidentialInfo'));

		$wfldsmn = array();
		while ($fld = $adb->fetch_array($fldrs)) {
			$fname = $fld['fieldname'];
			if (!in_array($fname,ConfidentialInfo::$nonEncryptedFields) and ConfidentialInfo::isEncryptable($fname)) {
				$wfldsmn[] = $fname;
			}
		}
		if (count($wfldsmn)>0) {
			$cirs = $adb->query('select vtiger_confidentialinfo.confidentialinfoid,' . implode(',',$wfldsmn) . '
				from vtiger_confidentialinfo
				inner join vtiger_confidentialinfocf on vtiger_confidentialinfo.confidentialinfoid=vtiger_confidentialinfocf.confidentialinfoid');
			$ciupd = 'update vtiger_confidentialinfo
				inner join vtiger_confidentialinfocf on vtiger_confidentialinfo.confidentialinfoid=vtiger_confidentialinfocf.confidentialinfoid
				set ' . implode('=?,',$wfldsmn).'=? where vtiger_confidentialinfo.confidentialinfoid=?';
			$cnt=0;
			while ($ci = $adb->fetch_array($cirs)) {
				$cifmn = array();
				foreach ($wfldsmn as $fld) {
					$cifmn[$fld] = $ci[$fld];
				}
				if (!$alreadyDecrypted) {
					$cifmn = ConfidentialInfo::decryptFields($cifmn, $cioldpass, $oldnonce);
				}
				$cifmn = ConfidentialInfo::encryptFields($cifmn, $cinewpass);
				$cifmn[] = $ci['confidentialinfoid'];
				$adb->pquery($ciupd,$cifmn);
				$cnt++;
				if ($cnt % 10==0) echo ".";
			}
		}
		return $adb->num_rows($cirs);
	}

	public static function encryptFields_mcrypt($fields,$passwd,$nonce) {
		global $adb, $log;
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_256,'',MCRYPT_MODE_CFB, '');
		$key = substr($passwd, 0, mcrypt_enc_get_key_size($td));
		$ciiv = ConfidentialInfo::jejbs654sdf9s($nonce);
		mcrypt_generic_init($td, $key, $ciiv);
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			if (in_array($fname,ConfidentialInfo::$nonEncryptedFields) or !ConfidentialInfo::isEncryptable($fname)) {
				$encfields[$fname] = $fvalue;
			} else {
				if (is_array($fvalue)) {
					$encfields[$fname] = ConfidentialInfo::encryptArray_mcrypt($fvalue,$td);
				} elseif (empty($fvalue)) {
					$encfields[$fname] = '';
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

	static function encryptArray_mcrypt($fields,$td) {
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

	static function decryptFields_mcrypt($fields,$passwd,$nonce) {
		global $adb, $log;
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_256,'',MCRYPT_MODE_CFB, '');
		$key = substr($passwd, 0, mcrypt_enc_get_key_size($td));
		$ciiv = ConfidentialInfo::jejbs654sdf9s($nonce);
		mcrypt_generic_init($td, $key, $ciiv);
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			if (in_array($fname,ConfidentialInfo::$nonEncryptedFields) or !ConfidentialInfo::isEncryptable($fname)) {
				$encfields[$fname] = $fvalue;
			} else {
				if (strpos($fvalue,' |##| ')>0) {
					$valueArr = explode(' |##| ', $fvalue);
					$decflds = ConfidentialInfo::decryptArray_mcrypt($valueArr, $td);
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

	static function decryptArray_mcrypt($fields,$td) {
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
		$strcode = '';
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
			$this->setModuleSeqNumber('configure', $modulename, 'CINFO-', '000001');
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
