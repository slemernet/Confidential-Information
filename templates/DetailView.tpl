{include file="DetailView.tpl"}
<input id="cidwspinfo" name="cidwspinfo" type="hidden" value="{$cidwspinfo}">
<div style="position:absolute;text-align:center;width: 100%;top:160px;pointer-events:none;"><span id="countdown"></span></div>
<script type="text/javascript" src="modules/ConfidentialInfo/jquery.countDown.js"></script>
<script language="javascript">
jQuery(document).ready(function() {ldelim}
	jQuery('#countdown').countDown({ldelim}
		startNumber: {$CITimeout},
		callBack: function(me) {ldelim}
			window.location="index.php?module=ConfidentialInfo&action=index";
		{rdelim}
	{rdelim});
{rdelim});
</script>