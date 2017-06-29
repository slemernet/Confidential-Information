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
global $mod_strings, $app_strings, $currentModule, $current_user, $theme, $singlepane_view;
include_once 'modules/ConfidentialInfo/ConfidentialInfo.php';
$cioldpass = isset($_REQUEST['oldpassword']) ? vtlib_purify($_REQUEST['oldpassword']) : '';
$cinewpass = isset($_REQUEST['newpassword']) ? vtlib_purify($_REQUEST['newpassword']) : '';
$cireppass = isset($_REQUEST['reppassword']) ? vtlib_purify($_REQUEST['reppassword']) : '';
$cifirstrun = isset($_REQUEST['cifirstrun']) ? vtlib_purify($_REQUEST['cifirstrun']) : false;
$cryptmethod = isset($_REQUEST['cryptmethod']) ? vtlib_purify($_REQUEST['cryptmethod']) : 'mcrypt';
$oldcryptmethod = coreBOS_Settings::getSetting('CINFO_EncryptMethod', 'mcrypt');
if (!empty($cifirstrun) and $cinewpass==$cireppass) {
	$iv = ConfidentialInfo::getNONCE();
	$adb->pquery('insert into vtiger_cicryptinfo (`paswd`,`ciiv`,`lastchange`,`lastchangeby`,timeout) values (?,?,?,?,60)',array(
		sha1($cinewpass),
		$iv,
		date('Y-m-d'),
		getUserFullName($current_user->id)
	));
	coreBOS_Settings::setSetting('CINFO_EncryptMethod', $cryptmethod);
	ConfidentialInfo::set_cinfo_history(0,'Initial Encryption','');
}
$passwderror = true;
$passrs = $adb->query('select * from vtiger_cicryptinfo limit 1');
if ($adb->num_rows($passrs)==1) {
	$passinfo = $adb->fetch_array($passrs);
	if (($passinfo['paswd']==sha1($cioldpass) or !empty($cifirstrun)) and $cinewpass==$cireppass) {
		coreBOS_Settings::setSetting('CINFO_EncryptMethod', $cryptmethod);
		$iv = ConfidentialInfo::getNONCE();
		$adb->pquery('update vtiger_cicryptinfo set `paswd`=?,`ciiv`=?,`lastchange`=?,`lastchangeby`=?',array(
			sha1($cinewpass),
			$iv,
			date('Y-m-d'),
			getUserFullName($current_user->id)
		));
		if (empty($cifirstrun)) ConfidentialInfo::set_cinfo_history(0,'Password Change','Information encrypted');
		$nrows = ConfidentialInfo::migrate2NewPassword($oldcryptmethod, $cinewpass, $cioldpass, $passinfo['ciiv'], $cifirstrun);
		echo "<br><h2>&nbsp;&nbsp;".getTranslatedString('PasswordChanged','ConfidentialInfo').(empty($nrows) ? '0':$nrows).'</h2>';
		$passwderror = false;
	}
}

if ($passwderror) {
	include_once 'modules/ConfidentialInfo/CIChgPassword.php';
}

?>