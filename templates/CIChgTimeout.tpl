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
<form name="CIEditView" method="POST" action="index.php" onsubmit="VtigerJS_DialogBox.block();">
<input type="hidden" name="module" value="{$MODULE_NAME}">
<input type="hidden" name="action" value="CIChgTimeout">
{if $CIMessage neq ''}{$CIMessage}<br/>{/if}
<table class="tableHeading" width="100%" border="0" cellspacing="0" cellpadding="5" align="center">
<tr>
<td width='20%' class='dvtCellLabel' nowrap>{$MOD.TimeoutValue}</td><td width='80%' class='dvtCellInfo'><input type=text size=5 maxlength=5 class='small' id='timeoutval' name='timeoutval' value="{$CITimeout}"></td>
</tr>
<tr>
<td colspan=2 align="center"><input title="{$APP.LBL_SAVE_LABEL}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmbutton small edit" type="submit" name="citimeout" language=javascript value="&nbsp;{$APP.LBL_SAVE_BUTTON_LABEL}&nbsp;">&nbsp;</td>
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