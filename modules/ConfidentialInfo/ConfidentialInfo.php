<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once 'data/CRMEntity.php';
require_once 'data/Tracker.php';

class ConfidentialInfo extends CRMEntity {
	public $db;
	public $log;

	public $table_name = 'vtiger_confidentialinfo';
	public $table_index= 'confidentialinfoid';
	public $column_fields = array();

	/** Indicator if this is a custom module or standard module */
	public $IsCustomModule = true;
	public $HasDirectImageField = false;
	/**
	 * Mandatory table for supporting custom fields.
	 */
	public $customFieldTable = array('vtiger_confidentialinfocf', 'confidentialinfoid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	public $tab_name = array('vtiger_crmentity', 'vtiger_confidentialinfo', 'vtiger_confidentialinfocf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	public $tab_name_index = array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_confidentialinfo'   => 'confidentialinfoid',
		'vtiger_confidentialinfocf' => 'confidentialinfoid',
	);

	/**
	 * Mandatory for Listing (Related listview)
	 */
	public $list_fields = array (
		/* Format: Field Label => array(tablename => columnname) */
		// tablename should not have prefix 'vtiger_'
		'confidentialinfono' => array('confidentialinfo' => 'confidentialinfono'),
		'cireference' => array('confidentialinfo' => 'cireference'),
		'cicategory' => array('confidentialinfo' => 'cicategory'),
		'cirelto' => array('confidentialinfo' => 'cirelto'),
		'ciasset' => array('confidentialinfo' => 'ciasset'),
		'Assigned To' => array('crmentity' =>'smownerid'),
	);
	public $list_fields_name = array(
		/* Format: Field Label => fieldname */
		'confidentialinfono' => 'confidentialinfono',
		'cireference' => 'cireference',
		'cicategory' => 'cicategory',
		'cirelto' => 'cirelto',
		'ciasset' => 'ciasset',
		'Assigned To' => 'assigned_user_id',
	);

	// Make the field link to detail view from list view (Fieldname)
	public $list_link_field = 'confidentialinfono';

	// For Popup listview and UI type support
	public $search_fields = array(
		/* Format: Field Label => array(tablename => columnname) */
		// tablename should not have prefix 'vtiger_'
		'confidentialinfono' => array('confidentialinfo' => 'confidentialinfono'),
		'cireference' => array('confidentialinfo' => 'cireference'),
		'cicategory' => array('confidentialinfo' => 'cicategory'),
		'cirelto' => array('confidentialinfo' => 'cirelto'),
		'ciasset' => array('confidentialinfo' => 'ciasset'),
	);
	public $search_fields_name = array(
		/* Format: Field Label => fieldname */
		'confidentialinfono' => 'confidentialinfono',
		'cireference' => 'cireference',
		'cicategory' => 'cicategory',
		'cirelto' => 'cirelto',
		'ciasset' => 'ciasset',
	);

	// For Popup window record selection
	public $popup_fields = array('cireference');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	public $sortby_fields = array();

	// For Alphabetical search
	public $def_basicsearch_col = 'cireference';

	// Column value to use on detail view record text display
	public $def_detailview_recname = 'cireference';

	// Required Information for enabling Import feature
	public $required_fields = array('refto'=>1);

	// Callback function list during Importing
	public $special_functions = array('set_import_assigned_user');

	public $default_order_by = 'cireference';
	public $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	public $mandatory_fields = array('createdtime', 'modifiedtime', 'cireference');

	// List of fields that will not be encrypted
	static $nonEncryptedFields = array('confidentialinfoid','confidentialinfono','cireference','cicategory','cirelto','ciasset','description','record_id','record_module');

	public function __construct() {
		parent::__construct();
		$result = $this->db->query('select * from vtiger_confidentialinfocf limit 1');
		if (!empty($result)) {
			foreach ($this->db->getFieldsDefinition($result) as $fldinfo) {
				if ($fldinfo->type=='253') {
					$this->db->query('ALTER TABLE vtiger_confidentialinfocf CHANGE '.$fldinfo->name.' '.$fldinfo->name.' blob');
				}
			}
		}
	}

	public function retrieve_entity_info($record, $module, $deleted=false) {
		parent::retrieve_entity_info($record, $module, $deleted);
		$result = $this->db->pquery('select *
			from vtiger_confidentialinfo
			inner join vtiger_confidentialinfocf on vtiger_confidentialinfocf.confidentialinfoid = vtiger_confidentialinfo.confidentialinfoid
			where vtiger_confidentialinfo.confidentialinfoid=?',array($record));
		if (!empty($result) and $this->db->num_rows($result)==1) {
			$row = $this->db->raw_query_result_rowdata($result);
			foreach ($this->db->getFieldsDefinition($result) as $fldinfo) {
				if ($fldinfo->type=='252') { // blob > so we undo html_encode comming from peardatabase
					$this->column_fields[$fldinfo->name] = $row[$fldinfo->name];
				}
			}
		}
	}

	public function trash($module, $record) {
		$this->set_cinfo_history($record,'delete','');
		parent::trash($module, $record);
	}

	public function save_module($module) {
		if ($this->HasDirectImageField) {
			$this->insertIntoAttachment($this->id, $module);
		}
		if (empty($this->mode)) {
			$this->set_cinfo_history($this->id,'create','');
		} else {
			$this->set_cinfo_history($this->id,'update','');
		}
	}

	/**	Function used to get the Confidential Information history
	 *	@param $id - confidentialid
	 *	return $return_data - array with header and the entries in format array('header'=>$header,'entries'=>$entries_list) where as $header and $entries_list are array which contains all the column values of a row
	 */
	public function get_cinfo_history($id) {
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
			$entries = array();
			$entries[] = $row['whomacts'];
			$entries[] = DateTimeField::convertToUserFormat($row['whenacts']);
			$entries[] = getTranslatedString($row['action'],'ConfidentialInfo');
			$entries[] = $row['comment'];
			$entries_list[] = $entries;
		}
		$return_data = array('header'=>$header,'entries'=>$entries_list,'navigation'=>array('',''));
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
	public static function set_cinfo_history($record,$action,$comment) {
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
	public function create_export_query($where) {
		return 'select 0';
	}

	/**
	 * Create list query to be shown at the last step of the import.
	 * Called From: modules/Import/UserLastImport.php
	 */
	public function create_import_query($module) {
		return 'select 0';
	}

	/*
	 * Function to encrypt an array of fields
	 * @param array('fieldname'=>'fieldvalue'),  for example column_fields array, MUST be a field of this module
	 * @param passwd, the current company wide password
	 * @returns the same array with values it can encrypt encrypted
	 */
	public static function encryptFields($fields,$passwd) {
		global $adb, $log;
		if (empty($fields) or !is_array($fields)) return false;
		$passrs = $adb->query('select * from vtiger_cicryptinfo limit 1');
		if ($adb->num_rows($passrs)==0) return false;
		$passinfo = $adb->fetch_array($passrs);
		if ($passinfo['paswd']!=sha1($passwd)) return false;
		switch (coreBOS_Settings::getSetting('CINFO_EncryptMethod', 'mcrypt')) {
			case 'libsodium':
				return ConfidentialInfo::encryptFields_libsodium($fields,$passwd,$passinfo['ciiv']);
			break;
			case 'openssl':
				return ConfidentialInfo::encryptFields_openssl($fields,$passwd,$passinfo['ciiv']);
			break;
			case 'pki':
				$privatekey = $passinfo['ciiv'].'/private.key';
				if (file_exists($privatekey) or empty($_REQUEST['mode'])) {
					return ConfidentialInfo::encryptFields_pki($fields,'file://'.$passinfo['ciiv'].'/public.key');
				} elseif (!empty($_REQUEST['mode']) and !empty($_REQUEST['record'])) {
					$result = $adb->pquery('select *
						from vtiger_confidentialinfo
						inner join vtiger_confidentialinfocf on vtiger_confidentialinfocf.confidentialinfoid = vtiger_confidentialinfo.confidentialinfoid
						where vtiger_confidentialinfo.confidentialinfoid=?',array($_REQUEST['record']));
					if (!empty($result) and $adb->num_rows($result)==1) {
						$row = $adb->raw_query_result_rowdata($result);
						foreach ($adb->getFieldsDefinition($result) as $fldinfo) {
							if ($fldinfo->type=='252') { // blob > so we undo html_encode comming from peardatabase
								$fields[$fldinfo->name] = $row[$fldinfo->name];
							}
						}
						return $fields;
					} else {
						return $fields;
					}
				} else {
					return $fields;
				}
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
	public static function decryptFields($fields, $passwd, $nonce='', $method='') {
		global $adb, $log;
		if (empty($fields) or !is_array($fields)) return false;
		$passrs = $adb->query('select * from vtiger_cicryptinfo limit 1');
		if ($adb->num_rows($passrs)==0) return false;
		if (empty($nonce)) {
			$nonce = $adb->query_result($passrs, 0, 'ciiv');
		}
		if (empty($method)) {
			$method = coreBOS_Settings::getSetting('CINFO_EncryptMethod', 'mcrypt');
		}
		switch ($method) {
			case 'libsodium':
				return ConfidentialInfo::decryptFields_libsodium($fields,$passwd,$nonce);
			break;
			case 'openssl':
				return ConfidentialInfo::decryptFields_openssl($fields,$passwd,$nonce);
			break;
			case 'pki':
				return ConfidentialInfo::decryptFields_pki($fields,'file://'.$adb->query_result($passrs, 0, 'ciiv').'/private.key');
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
	public static function getNONCE() {
		switch (coreBOS_Settings::getSetting('CINFO_EncryptMethod', 'mcrypt')) {
			case 'libsodium':
				return substr(bin2hex(\Sodium\randombytes_buf(\Sodium\CRYPTO_SECRETBOX_NONCEBYTES)),0,\Sodium\CRYPTO_SECRETBOX_NONCEBYTES);
			break;
			case 'openssl':
				return substr(bin2hex(random_bytes(16)),0,16);
			break;
			case 'pki':
				return (empty($_REQUEST['pkidir']) ? 'modules/ConfidentialInfo' : vtlib_purify($_REQUEST['pkidir']));
			break;
			case 'mcrypt':
			default:
				$td = mcrypt_module_open(MCRYPT_RIJNDAEL_256,'',MCRYPT_MODE_CFB, '');
				$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td),MCRYPT_RAND);
				return substr(bin2hex($iv),0,mcrypt_enc_get_iv_size($td));
			break;
		}
	}

	/*
	 * Function to decrypt and encrypt all record with a new password
	 * @params password
	 */
	public static function migrate2NewPassword($frommethod, $cinewpass, $cioldpass, $oldnonce, $alreadyDecrypted=false) {
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
			for ($cirow = 0;$cirow<$adb->num_rows($cirs);$cirow++) {
				$ci = $adb->raw_query_result_rowdata($cirs,$cirow);
				$cifmn = array();
				foreach ($wfldsmn as $fld) {
					$cifmn[$fld] = $ci[$fld];
				}
				if (!$alreadyDecrypted) {
					$cifmn = ConfidentialInfo::decryptFields($cifmn, $cioldpass, $oldnonce, $frommethod);
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

	public static function encryptFields_libsodium($fields,$passwd,$nonce) {
		global $log;
		$key = substr(sha1($passwd),0,\Sodium\CRYPTO_SECRETBOX_KEYBYTES);
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			if (in_array($fname,ConfidentialInfo::$nonEncryptedFields) or !ConfidentialInfo::isEncryptable($fname)) {
				$encfields[$fname] = $fvalue;
			} else {
				if (is_array($fvalue)) {
					$encfields[$fname] = ConfidentialInfo::encryptarray_libsodium($fvalue, $nonce, $key);
				} elseif (empty($fvalue)) {
					$encfields[$fname] = '';
				} else {
					$encfields[$fname] = \Sodium\crypto_secretbox($fvalue, $nonce, $key);
				}
			}
		}
		return $encfields;
	}

	public static function encryptarray_libsodium($fields, $nonce, $key) {
		global $log;
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			$encfields[$fname] = \Sodium\crypto_secretbox($fvalue, $nonce, $key);
		}
		return $encfields;
	}

	public static function encryptFields_pki($fields,$publickey) {
		global $log;
		// Get the public Key of the recipient
		$key = openssl_pkey_get_public($publickey);
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			if (in_array($fname,ConfidentialInfo::$nonEncryptedFields) or !ConfidentialInfo::isEncryptable($fname)) {
				$encfields[$fname] = $fvalue;
			} else {
				if (is_array($fvalue)) {
					$encfields[$fname] = ConfidentialInfo::encryptarray_pki($fvalue, $key);
				} elseif (empty($fvalue)) {
					$encfields[$fname] = '';
				} else {
					openssl_public_encrypt($fvalue, $encrypted, $key);
					$encfields[$fname] = $encrypted;
				}
			}
		}
		openssl_free_key($key);
		return $encfields;
	}

	public static function encryptarray_pki($fields, $key) {
		global $log;
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			openssl_public_encrypt($fvalue, $encrypted, $key);
			$encfields[$fname] = $encrypted;
		}
		return $encfields;
	}

	public static function encryptFields_openssl($fields,$passwd,$nonce) {
		global $log;
		$key = substr(sha1($passwd),0,16);
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			if (in_array($fname,ConfidentialInfo::$nonEncryptedFields) or !ConfidentialInfo::isEncryptable($fname)) {
				$encfields[$fname] = $fvalue;
			} else {
				if (is_array($fvalue)) {
					$encfields[$fname] = ConfidentialInfo::encryptarray_openssl($fvalue, $nonce, $key);
				} elseif (empty($fvalue)) {
					$encfields[$fname] = '';
				} else {
					$encfields[$fname] = openssl_encrypt($fvalue, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $nonce);
				}
			}
		}
		return $encfields;
	}

	public static function encryptarray_openssl($fields, $nonce, $key) {
		global $log;
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			$encfields[$fname] = openssl_encrypt($fvalue, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $nonce);
		}
		return $encfields;
	}

	public static function encryptFields_mcrypt($fields,$passwd,$nonce) {
		global $log;
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_256,'',MCRYPT_MODE_CFB, '');
		$key = substr($passwd, 0, mcrypt_enc_get_key_size($td));
		mcrypt_generic_init($td, $key, $nonce);
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			if (in_array($fname,ConfidentialInfo::$nonEncryptedFields) or !ConfidentialInfo::isEncryptable($fname)) {
				$encfields[$fname] = $fvalue;
			} else {
				if (is_array($fvalue)) {
					$encfields[$fname] = ConfidentialInfo::encryptarray_mcrypt($fvalue,$td);
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

	public static function encryptarray_mcrypt($fields,$td) {
		global $log;
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			$ef = mcrypt_generic($td,$fvalue);
			$encfields[$fname] = base64_encode($ef);
		}
		return $encfields;
	}

	public static function isEncryptable($fname) {
		global $adb, $log;
		$fldrs = $adb->pquery('select uitype, typeofdata from vtiger_field where fieldname=? and tabid=?',array($fname,getTabid('ConfidentialInfo')));
		if (!$fldrs or $adb->num_rows($fldrs)==0) return true;
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

	public static function decryptFields_mcrypt($fields,$passwd,$nonce) {
		global $log;
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_256,'',MCRYPT_MODE_CFB, '');
		$key = substr($passwd, 0, mcrypt_enc_get_key_size($td));
		mcrypt_generic_init($td, $key, $nonce);
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			if (in_array($fname,ConfidentialInfo::$nonEncryptedFields) or !ConfidentialInfo::isEncryptable($fname)) {
				$encfields[$fname] = $fvalue;
			} else {
				if (strpos($fvalue,' |##| ')>0) {
					$valueArr = explode(' |##| ', $fvalue);
					$decflds = ConfidentialInfo::decryptarray_mcrypt($valueArr, $td);
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

	public static function decryptarray_mcrypt($fields,$td) {
		global $log;
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			$encfields[$fname] = @mdecrypt_generic($td,base64_decode($fvalue));
		}
		return $encfields;
	}

	public static function decryptFields_libsodium($fields,$passwd,$nonce) {
		global $log;
		$key = substr(sha1($passwd),0,\Sodium\CRYPTO_SECRETBOX_KEYBYTES);
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			if (in_array($fname,ConfidentialInfo::$nonEncryptedFields) or !ConfidentialInfo::isEncryptable($fname)) {
				$encfields[$fname] = $fvalue;
			} else {
				if (strpos($fvalue,' |##| ')>0) {
					$valueArr = explode(' |##| ', $fvalue);
					$decflds = ConfidentialInfo::decryptarray_libsodium($valueArr,  $nonce, $key);
					$encfields[$fname] = implode(' |##| ', $decflds);
				} else {
					$encfields[$fname] = \Sodium\crypto_secretbox_open($fvalue, $nonce, $key);
				}
			}
		}
		return $encfields;
	}

	public static function decryptarray_libsodium($fields, $nonce, $key) {
		global $log;
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			$encfields[$fname] = \Sodium\crypto_secretbox_open($fvalue, $nonce, $key);
		}
		return $encfields;
	}

	static function decryptFields_pki($fields,$privatekey) {
		global $log;
		// Get the private Key
		$key = openssl_pkey_get_private($privatekey);
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			if (in_array($fname,ConfidentialInfo::$nonEncryptedFields) or !ConfidentialInfo::isEncryptable($fname)) {
				$encfields[$fname] = $fvalue;
			} else {
				if ($key === false) {
					$encfields[$fname] = '';
				} else {
					if (strpos($fvalue,' |##| ')>0) {
						$valueArr = explode(' |##| ', $fvalue);
						$decflds = ConfidentialInfo::decryptarray_pki($valueArr, $key);
						$encfields[$fname] = implode(' |##| ', $decflds);
					} else {
						openssl_private_decrypt($fvalue, $decrypted, $key);
						$encfields[$fname] = $decrypted;
					}
				}
			}
		}
		if ($key) openssl_free_key($key);
		return $encfields;
	}

	public static function decryptarray_pki($fields, $key) {
		global $log;
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			openssl_private_decrypt($fvalue, $decrypted, $key);
			$encfields[$fname] = $decrypted;
		}
		return $encfields;
	}

	public static function decryptFields_openssl($fields,$passwd,$nonce) {
		global $log;
		$key = substr(sha1($passwd),0,16);
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			if (in_array($fname,ConfidentialInfo::$nonEncryptedFields) or !ConfidentialInfo::isEncryptable($fname)) {
				$encfields[$fname] = $fvalue;
			} else {
				if (strpos($fvalue,' |##| ')>0) {
					$valueArr = explode(' |##| ', $fvalue);
					$decflds = ConfidentialInfo::decryptarray_openssl($valueArr,  $nonce, $key);
					$encfields[$fname] = implode(' |##| ', $decflds);
				} else {
					$encfields[$fname] = openssl_decrypt($fvalue, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $nonce);
				}
			}
		}
		return $encfields;
	}

	public static function decryptarray_openssl($fields, $nonce, $key) {
		global $log;
		$encfields = array();
		foreach ($fields as $fname=>$fvalue) {
			$encfields[$fname] = openssl_decrypt($fvalue, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $nonce);
		}
		return $encfields;
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

	public static function zx524bxzvb5xd($name) {
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
	public function vtlib_handler($modulename, $event_type) {
		if ($event_type == 'module.postinstall') {
			// TODO Handle post installation actions
			$this->setModuleSeqNumber('configure', $modulename, 'CINFO-', '000001');
			$module = Vtiger_Module::getInstance($modulename);
			$mod = Vtiger_Module::getInstance('Accounts');
			$mod->setRelatedList($module, 'ConfidentialInfo', array('ADD'), 'get_dependents_list');
			$mod = Vtiger_Module::getInstance('Contacts');
			$mod->setRelatedList($module, 'ConfidentialInfo', array('ADD'), 'get_dependents_list');
			$mod = Vtiger_Module::getInstance('Assets');
			$mod->setRelatedList($module, 'ConfidentialInfo', array('ADD'), 'get_dependents_list');
		} elseif ($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} elseif ($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} elseif ($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} elseif ($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} elseif ($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
	}

	/**
	 * Handle saving related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	// public function save_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle deleting related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//public function delete_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle getting related list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//public function get_related_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }

	/**
	 * Handle getting dependents list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//public function get_dependents_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }
}
?>
