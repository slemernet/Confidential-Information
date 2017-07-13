{*<!--
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
 *  Module     : ConfidentialInfo
 *  Version    : 5.4.0
 *  Author     : JPL TSolucio, S. L.
 *  Promotion  : Object Solutions
 *************************************************************************************************/
-->*}

<table align="center" border="0" cellpadding="0" cellspacing="0" width="98%">
<tbody>
<tr>
<td valign="top"><img src="{'showPanelTopLeft.gif'|@vtiger_imageurl:$THEME}"></td>
<td class="showPanelBg" style="padding: 10px;" valign="top" width="100%">
<br>
<div align=center>
{include file='SetMenu.tpl'}
<!-- DISPLAY -->
<div id="view">
{include file='modules/ConfidentialInfo/ModuleTitle.tpl'}
{if $CIERROR eq 'true'}
<h2>{$CIERRORMSG}</h2>
{else}
<script type='text/javascript' language='javascript'>
function set_password(form) {ldelim}
	if (!{$CIFIRSTRUN} && trim(form.oldpassword.value) == "") {ldelim}
		alert("{'ERR_ENTER_OLD_PASSWORD'|@getTranslatedString:'Users'}");
		return false;
	{rdelim}
	if (trim(form.newpassword.value) == "") {ldelim}
		alert("{'ERR_ENTER_NEW_PASSWORD'|@getTranslatedString:'Users'}");
		return false;
	{rdelim}
	if (trim(form.reppassword.value) == "") {ldelim}
		alert("{'ERR_ENTER_CONFIRMATION_PASSWORD'|@getTranslatedString:'Users'}");
		return false;
	{rdelim}

	if (trim(form.newpassword.value) == trim(form.reppassword.value)) {ldelim}
		return confirm("{'MakeSure'|@getTranslatedString:'ConfidentialInfo'}");
	{rdelim} else {ldelim}
		alert("{'ERR_REENTER_PASSWORDS'|@getTranslatedString:'Users'}");
		return false;
	{rdelim}
{rdelim}
</script>
<form name="CIEditView" method="POST" action="index.php" onsubmit="VtigerJS_DialogBox.block();">
<input type="hidden" name="module" value="{$MODULE_NAME}">
<input type="hidden" name="action" value="CIEncrypt">
<table class="tableHeading" width="100%" border="0" cellspacing="0" cellpadding="5" align="center">
{if $CIFIRSTRUN eq 'false'}
<tr>
<td width='40%' class='dvtCellLabel' nowrap>{$MOD.LastChangedOn}</td><td width='60%' class='dvtCellInfo'>{$CILastChangedOn}</td>
</tr>
<tr>
<td width='40%' class='dvtCellLabel' nowrap>{$MOD.LastChangedBy}</td><td width='60%' class='dvtCellInfo'>{$CILastChangedBy}</td>
</tr>
<tr>
<td width='40%' class='dvtCellLabel' nowrap>{$MOD.oldpassword}<span style="color:red;display:{if $passwderror eq 'true'}block{else}none{/if};"><br/>{$MOD.IncorrectPassword}</span></td><td width='60%' class='dvtCellInfo'><input type=password size=35 class='small' id='oldpassword' name='oldpassword'></td>
</tr>
{else}
<tr>
<td colspan=2>{$MOD.cifirstrun}<input type="hidden" name="cifirstrun" value="true"></td>
</tr>
{/if}
<tr>
<td width='40%' class='dvtCellLabel' nowrap>{$MOD.newpassword}</td><td width='60%' class='dvtCellInfo'><input type=password size=35 class='small' id='newpassword' name='newpassword'></td>
</tr>
<tr>
<td width='40%' class='dvtCellLabel' nowrap>{$MOD.repeatpassword}</td><td width='60%' class='dvtCellInfo'><input type=password size=35 class='small' id='reppassword' name='reppassword'></td>
</tr>
<tr>
<td width='40%' class='dvtCellLabel' nowrap>{$MOD.encryptionMethod}</td><td width='60%' class='dvtCellInfo'>
<select class='small' id='cryptmethod' name='cryptmethod'>
{if $MCRYPTLOADED}<option value="mcrypt" {$MCRYPTSELECTED}>mcrypt</option>{/if}
{if $OPENSSLLOADED}
<option value="openssl" {$OPENSSLSELECTED}>openssl</option>
<option value="pki" {$OPENSSLPKISELECTED}>openssl pki</option>
{/if}
{if $LIBSODIUMLOADED}<option value="libsodium" {$LIBSODIUMSELECTED}>libsodium</option>{/if}
</select>
{if $MCRYPTLOADED}
<span style="color:darkgreen">mcrypt {'Loaded'|@getTranslatedString:$MODULE_NAME}.</span>
{else}
<span style="color:red">mcrypt {'NotLoaded'|@getTranslatedString:$MODULE_NAME}.</span>
{/if}
{if $LIBSODIUMLOADED}
<span style="color:darkgreen">libsodium {'Loaded'|@getTranslatedString:$MODULE_NAME}.</span>
{else}
<span style="color:red">libsodium {'NotLoaded'|@getTranslatedString:$MODULE_NAME}.</span>
{/if}
{if $OPENSSLLOADED}
<span style="color:darkgreen">openssl {'Loaded'|@getTranslatedString:$MODULE_NAME}.</span>
{else}
<span style="color:red">openssl {'NotLoaded'|@getTranslatedString:$MODULE_NAME}.</span>
{/if}
</td>
</tr>
<tr>
<td width='40%' class='dvtCellLabel' nowrap>{$MOD.PKIKeyDirectory}</td><td width='60%' class='dvtCellInfo'><input type="text" class='' id='pkidir' name='pkidir' value="{$PKIKEYDIR}"></td>
</tr>
<tr>
<td colspan=2 align="center"><input title="{$MOD.Encrypt}" accessKey="{$APP.LBL_EDIT_BUTTON_KEY}" class="crmbutton small edit" type="submit" name="encrypt" language=javascript onclick='if (!set_password(this.form)) return false;' value="&nbsp;{$MOD.Encrypt}&nbsp;">&nbsp;</td>
</tr>
</table>
</form>
{/if}
</div>
</td>
</tr>
</table>

</td>
</tr>
</table>
</div>
</td>
<td valign="top"><img src="{'showPanelTopRight.gif'|@vtiger_imageurl:$THEME}"></td>
</tr>
</tbody>
</table>
<br>
