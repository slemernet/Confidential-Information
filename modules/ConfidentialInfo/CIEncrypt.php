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
$cioldpass = vtlib_purify($_REQUEST['oldpassword']);
$cinewpass = vtlib_purify($_REQUEST['newpassword']);
$cireppass = vtlib_purify($_REQUEST['reppassword']);
$cifirstrun = vtlib_purify($_REQUEST['cifirstrun']);
if (!empty($cifirstrun) and $cinewpass==$cireppass) {
	$td = mcrypt_module_open(MCRYPT_RIJNDAEL_256,'',MCRYPT_MODE_CFB, '');
	$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td),MCRYPT_RAND);
	$adb->pquery('insert into vtiger_cicryptinfo (`paswd`,`ciiv`,`lastchange`,`lastchangeby`,timeout) values (?,?,?,?,60)',array(
		sha1($cinewpass),
		$iv,
		date('Y-m-d'),
		getUserFullName($current_user->id)
	));
	ConfidentialInfo::set_cinfo_history(0,'Initial Encryption','');
}
$passwderror = true;
$passrs = $adb->query('select * from vtiger_cicryptinfo limit 1');
if ($adb->num_rows($passrs)==1) {
	$passinfo = $adb->fetch_array($passrs);
	if (($passinfo['paswd']==sha1($cioldpass) or !empty($cifirstrun)) and $cinewpass==$cireppass) {
		set_time_limit(0);
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_256,'',MCRYPT_MODE_CFB, '');
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td),MCRYPT_RAND);
		$adb->pquery('update vtiger_cicryptinfo set `paswd`=?,`ciiv`=?,`lastchange`=?,`lastchangeby`=?',array(
			sha1($cinewpass),
			bin2hex($iv),
			date('Y-m-d'),
			getUserFullName($current_user->id)
		));
		if (empty($cifirstrun)) ConfidentialInfo::set_cinfo_history(0,'Password Change','Information encrypted');

		$focus = CRMEntity::getInstance($currentModule);
		
		$fldrs = $adb->query('select fieldname,tablename from vtiger_field where tabid='.getTabid($currentModule));
		
		$wfldsmn = $wfldscf = array();
		while ($fld = $adb->fetch_array($fldrs)) {
			$fname = $fld['fieldname'];
			if (!in_array($fname,ConfidentialInfo::$nonEncryptedFields) and ConfidentialInfo::isEncryptable($fname)) {
				if ($fld['tablename']=='vtiger_confidentialinfo')
					$wfldsmn[] = $fname;
				else
					$wfldscf[] = $fname;
			}
		}
		$wfldsmncnt = count($wfldsmn);
		$wfldscfcnt = count($wfldscf);
		if ($wfldsmncnt+$wfldscfcnt>0) {
			$cirs = $adb->query('select vtiger_confidentialinfo.confidentialinfoid,'.implode(',',$wfldsmn).implode(',',$wfldscf).'
					from vtiger_confidentialinfo
					inner join vtiger_confidentialinfocf on vtiger_confidentialinfo.confidentialinfoid=vtiger_confidentialinfocf.confidentialinfoid');
			$ciupdmn = 'update vtiger_confidentialinfo set '.implode('=?,',$wfldsmn).'=? where confidentialinfoid=?';
			$ciupdcf = 'update vtiger_confidentialinfocf set '.implode('=?,',$wfldscf).'=? where confidentialinfoid=?';
			$cnt=0;
			while ($ci = $adb->fetch_array($cirs)) {
				$cifmn = $cifcf = $cifmnupd = $cifcfupd = array();
				$td = mcrypt_module_open(MCRYPT_RIJNDAEL_256,'',MCRYPT_MODE_CFB, '');
				$key = substr($cioldpass, 0, mcrypt_enc_get_key_size($td));
				$ciiv = ConfidentialInfo::jejbs654sdf9s($passinfo['ciiv']);
				mcrypt_generic_init($td, $key, $ciiv);
				if ($wfldsmncnt>0) {
					foreach ($wfldsmn as $fld) {
						if (empty($cifirstrun))
							$cifmn[$fld] = mdecrypt_generic($td,base64_decode($ci[$fld]));
						else
							$cifmn[$fld] = $ci[$fld];
					}
				}
				if ($wfldscfcnt>0) {
					foreach ($wfldscf as $fld) {
						if (empty($cifirstrun))
							$cifcf[$fld] = mdecrypt_generic($td,base64_decode($ci[$fld]));
						else
							$cifcf[$fld] = $ci[$fld];
					}
				}
				mcrypt_generic_deinit($td);
				mcrypt_module_close($td);
				$td = mcrypt_module_open(MCRYPT_RIJNDAEL_256,'',MCRYPT_MODE_CFB, '');
				$key = substr($cinewpass, 0, mcrypt_enc_get_key_size($td));
				mcrypt_generic_init($td, $key, $iv);
				if ($wfldsmncnt>0) {
					foreach ($wfldsmn as $fld) {
						$cifcryp = mcrypt_generic($td,$cifmn[$fld]);
						$cifmnupd[] = base64_encode($cifcryp);
					}
					$cifmnupd[] = $ci['confidentialinfoid'];
					$adb->pquery($ciupdmn,$cifmnupd);
				}
				if ($wfldscfcnt>0) {
					foreach ($wfldscf as $fld) {
						$cifcryp = mcrypt_generic($td,$cifcf[$fld]);
						$cifcfupd[] = base64_encode($cifcryp);
					}
					$cifcfupd[] = $ci['confidentialinfoid'];
					$adb->pquery($ciupdcf,$cifcfupd);
				}
				mcrypt_generic_deinit ($td);
				mcrypt_module_close($td);
				$cnt++;
				if ($cnt % 10==0) echo ".";
			}
		}
		$nrows = $adb->num_rows($cirs);
		echo "<br><h2>&nbsp;&nbsp;".getTranslatedString('PasswordChanged','ConfidentialInfo').(empty($nrows) ? '0':$nrows).'</h2>';
		$passwderror = false;
	}
}

if ($passwderror) {
	include_once 'modules/ConfidentialInfo/CIChgPassword.php';
}

?>