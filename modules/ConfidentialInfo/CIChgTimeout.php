<?php
/*************************************************************************************************
 * Copyright 2013 JPL TSolucio, S.L. -- This file is a part of the Confidential Information Module
 * You can copy, adapt and distribute the work under the "Attribution-NonCommercial-ShareAlike"
 * Vizsage Public License (the "License"). You may not use this file except in compliance with the
 * License. Roughly speaking, non-commercial users may share and modify this code, but must give credit
 * and share improvements. However, for proper details please read the full License, available at
 * http://vizsage.com/license/Vizsage-License-BY-NC-SA.html and the handy reference for understanding
 * the full license at http://vizsage.com/license/Vizsage-Deed-BY-NC-SA.html. Unless required by
 * applicable law or agreed to in writing, any software distributed under the License is distributed
 * on an  "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and limitations under the
 * License terms of Creative Commons Attribution-NonCommercial-ShareAlike 3.0 (the License).
 *************************************************************************************************
 *  Module       : ConfidentialInfo
 *  Version      : 5.4.0
 *  Author       : JPL TSolucio, S. L.
 *  Promotion    : Object Solutions
 *************************************************************************************************/
require_once("Smarty_setup.php");
require_once("include/utils/utils.php");
global $adb,$log,$current_language,$app_strings,$theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
$smarty = new vtigerCRM_Smarty();
$smarty->assign('APP', $app_strings);
$mod =  array_merge(
		return_module_language($current_language,'ConfidentialInfo'),
		return_module_language($current_language,'Settings'));
$smarty->assign("MOD", $mod);
$smarty->assign("THEME",$theme);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("MODULE_NAME", 'ConfidentialInfo');
$smarty->assign("MODULE_ICON", 'modules/ConfidentialInfo/Timeout.png');
$smarty->assign("MODULE_TITLE", $mod['ChangeTimeout']);
$smarty->assign("MODULE_Description", $mod['ChangeTimeoutDesc']);

$rsps = $adb->query('select timeout from vtiger_cicryptinfo limit 1');

if ($adb->num_rows($rsps)==0) {
	$smarty->assign('CIERROR','true');
	$smarty->assign('CIERRORMSG',getTranslatedString('InitNotDoneError','ConfidentialInfo'));
} elseif ($adb->num_rows($rsps)==1) {
	$tovrequest = vtlib_purify($_REQUEST['timeoutval']);
	if (!empty($tovrequest) and is_numeric($tovrequest)) {
		$tovalue = $tovrequest;
		$adb->pquery('update vtiger_cicryptinfo set timeout = ?',array($tovrequest));
		$smarty->assign("CIMessage",getTranslatedString('TimeoutValueUpdate','ConfidentialInfo'));
	} else {
		$row = $adb->fetch_array($rsps);
		$tovalue = $row['timeout'];
	}
	$smarty->assign('CIERROR','false');
	$smarty->assign('CITimeout',$tovalue);
} else {
	$smarty->assign('CIERROR','true');
	$smarty->assign('CIERRORMSG',getTranslatedString('DBInfoError','ConfidentialInfo'));
}
$smarty->display(vtlib_getModuleTemplate('ConfidentialInfo', 'CIChgTimeout.tpl'));
?>