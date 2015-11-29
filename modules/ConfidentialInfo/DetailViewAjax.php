<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
global $currentModule;
$modObj = CRMEntity::getInstance($currentModule);

$ajaxaction = $_REQUEST["ajxaction"];
if($ajaxaction == 'DETAILVIEW')
{
	$crmid = $_REQUEST['recordid'];
	$tablename = $_REQUEST['tableName'];
	$fieldname = $_REQUEST['fldName'];
	$fieldvalue = utf8RawUrlDecode($_REQUEST['fieldValue']); 
	if($crmid != '')
	{
		$rsps = $adb->query('select * from vtiger_cicryptinfo limit 1');
		if (empty($rsps) or $adb->num_rows($rsps)==0) {
			echo ':#:FAILURE';
		} else {
			$row = $adb->fetch_array($rsps);
			$cidwspinfo = ConfidentialInfo::zx524bxzvb5xd($_REQUEST['cidwspinfo']);
			if (sha1($cidwspinfo)!=$row['paswd']) {
				echo ':#:FAILURE';
			} else {
		
		$modObj->retrieve_entity_info($crmid, $currentModule);
		$modObj->column_fields = $modObj->decryptFields($modObj->column_fields,$cidwspinfo);
		$modObj->column_fields[$fieldname] = $fieldvalue;
		$modObj->column_fields = $modObj->encryptFields($modObj->column_fields,$cidwspinfo);
		$modObj->id = $crmid;
		$modObj->mode = 'edit';
		$modObj->save($currentModule);
		if($modObj->id != '')
		{
			echo ':#:SUCCESS';
		}else
		{
			echo ':#:FAILURE';
		}
		}}   
	}else
	{
		echo ':#:FAILURE';
	}
} elseif($ajaxaction == "LOADRELATEDLIST" || $ajaxaction == "DISABLEMODULE"){
	require_once 'include/ListView/RelatedListViewContents.php';
}
?>