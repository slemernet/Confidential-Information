<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once 'Smarty_setup.php';

global $mod_strings, $app_strings, $currentModule, $current_user, $theme, $log;

$smarty = new vtigerCRM_Smarty();

require_once 'modules/Vtiger/DetailView.php';
$smarty->assign('RETURN_MODULE', isset($_REQUEST['return_module']) ? vtlib_purify($_REQUEST['return_module']) : '');
$smarty->assign('RETURN_ACTION', isset($_REQUEST['return_action']) ? vtlib_purify($_REQUEST['return_action']) : '');
$smarty->assign('RETURN_ID', isset($_REQUEST['return_id']) ? vtlib_purify($_REQUEST['return_id']) : '');
$smarty->assign('RETURN_VIEWNAME', isset($_REQUEST['return_viewname']) ? vtlib_purify($_REQUEST['return_viewname']) : '');
$smarty->assign('CREATEMODE', isset($_REQUEST['createmode']) ? vtlib_purify($_REQUEST['createmode']) : '');

$cidwspinfo = isset($_REQUEST['cidwspinfo']) ? vtlib_purify($_REQUEST['cidwspinfo']) : '';
if (empty($cidwspinfo)) {
	$smarty->assign('ID', $record);
	$smarty->assign('BadPassword', 'false');
	$smarty->display(vtlib_getModuleTemplate('ConfidentialInfo', 'GetPassword.tpl'));
} else {
	$rsps = $adb->query('select * from vtiger_cicryptinfo limit 1');
	if (empty($rsps) || $adb->num_rows($rsps)==0) {
		$smarty->assign('OPERATION_MESSAGE', getTranslatedString('ErrorPassword', $currentModule));
		$smarty->display('modules/Vtiger/OperationNotPermitted.tpl');
	} else {
		$row = $adb->fetch_array($rsps);
		if (sha1($cidwspinfo)!=$row['paswd']) {
			$smarty->assign('ID', $record);
			$smarty->assign('BadPassword', 'true');
			$focus->set_cinfo_history($record, 'accessnok', '');
			$smarty->display(vtlib_getModuleTemplate('ConfidentialInfo', 'GetPassword.tpl'));
		} else {
			$smarty->assign('CITimeout', $row['timeout']);
			$smarty->assign("cidwspinfo", $focus->k87rgsz5f4g9eer($cidwspinfo));
			$focus->set_cinfo_history($record, 'retrieve', 'DetailView');
			// decrypt here
			$focus->column_fields = $focus->decryptFields($focus->column_fields, $cidwspinfo);

			$blocks = getBlocks($currentModule, 'detail_view', '', $focus->column_fields);
			$smarty->assign('BLOCKS', $blocks);
			$smarty->assign('FIELDS', $focus->column_fields);

			$smarty->display(vtlib_getModuleTemplate('ConfidentialInfo', 'DetailView.tpl'));
		}
	}
}
?>
