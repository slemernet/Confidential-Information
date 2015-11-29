{include file='Buttons_List1.tpl'}	
<form name="GetPassword" method="POST" action="index.php" style="margin:30px">
<h2>{'GetPassword'|@getTranslatedString:$MODULE}</h2>
{if $BadPassword eq 'true'}<h3 style="color:red">{'IncorrectPassword'|@getTranslatedString:$MODULE}</h3>{/if}
<input type="hidden" name="module" value="{$MODULE}">
<input type="hidden" name="record" value="{$ID}">
<input type="hidden" name="mode" value="{$MODE}">
<input type="hidden" name="action" value="{$smarty.request.action}">
<input type="hidden" name="parenttab" value="{$CATEGORY}">
<input type="hidden" name="return_module" value="{$RETURN_MODULE}">
<input type="hidden" name="return_id" value="{$RETURN_ID}">
<input type="hidden" name="return_action" value="{$RETURN_ACTION}">
<input type="hidden" name="return_viewname" value="{$RETURN_VIEWNAME}">
<input type="hidden" name="createmode" value="{$CREATEMODE}" />
<input type="hidden" name="cirelto" id="cirelto" value="{$smarty.request.cirelto|@vtlib_purify}">
<input type="hidden" name="cirelto_type" id="cirelto_type" type="hidden" value="{$smarty.request.cirelto_type|@vtlib_purify}">
<input type="hidden" name="ciasset" id="ciasset" value="{$smarty.request.ciasset|@vtlib_purify}">
<input type="hidden" name="ciasset_type" id="ciasset_type" type="hidden" value="{$smarty.request.ciasset_type|@vtlib_purify}">
<input type="hidden" name="isDuplicate" id="isDuplicate" type="hidden" value="{$isDuplicate}">
<input id="cidwspinfo" name="cidwspinfo" type="password" value="">
<input title="{$MOD.Send}" accessKey="S" class="crmbutton small save" type="submit" name="button" value="  {$MOD.Send}  " style="width:70px" >
</form>
<script language="javascript">
jQuery(document).ready(function() {ldelim}
	jQuery('#cidwspinfo').focus();
{rdelim});
</script>