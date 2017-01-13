<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once('Smarty_setup.php');

global $mod_strings, $app_strings, $currentModule, $current_user, $theme, $log;

$smarty = new vtigerCRM_Smarty();

require_once 'modules/Vtiger/DetailView.php';

$cidwspinfo = vtlib_purify($_REQUEST['cidwspinfo']);
if (empty($cidwspinfo)) {
	$smarty->assign('ID', $record);
	$smarty->display(vtlib_getModuleTemplate('ConfidentialInfo', 'GetPassword.tpl'));
} else {
	$rsps = $adb->query('select * from vtiger_cicryptinfo limit 1');
	if (empty($rsps) or $adb->num_rows($rsps)==0) {
		$smarty->display(vtlib_getModuleTemplate('ConfidentialInfo', 'ErrorPassword.tpl'));
	} else {
		$row = $adb->fetch_array($rsps);
		if (sha1($cidwspinfo)!=$row['paswd']) {
			$smarty->assign('ID', $record);
			$smarty->assign('BadPassword', 'true');
			$focus->set_cinfo_history($record,'accessnok','');
			$smarty->display(vtlib_getModuleTemplate('ConfidentialInfo', 'GetPassword.tpl'));
		} else {
$smarty->assign('CITimeout', $row['timeout']);
$smarty->assign("cidwspinfo", $focus->k87rgsz5f4g9eer($cidwspinfo));
$focus->set_cinfo_history($record,'retrieve','DetailView');
// decrypt here
$focus->column_fields = $focus->decryptFields($focus->column_fields,$cidwspinfo);

$blocks = getBlocks($currentModule,'detail_view','',$focus->column_fields);
$smarty->assign('BLOCKS', $blocks);
$smarty->assign('FIELDS',$focus->column_fields);

$smarty->display(vtlib_getModuleTemplate('ConfidentialInfo', 'DetailView.tpl'));
?>
