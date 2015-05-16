{* chat module control panel template *}

<link href="{$T_CHAT_MODULE_BASELINK}css/control_panel.css" rel="stylesheet" type="text/css">
<script type="text/javascript">
	var modulechatbaselink = '{$T_CHAT_MODULE_BASELINK}';
	var modulechatbasedir = '{$T_CHAT_MODULE_BASEDIR}';
	var modulechatbaseurl = '{$T_CHAT_MODULE_BASEURL}';
</script>



{capture name = 't_set_chatheartbeat'}
	
	{$T_CHAT_CHATHEARTBEAT_FORM.javascript}
	
	<form {$T_CHAT_CHATHEARTBEAT_FORM.attributes}>
		
		{$T_CHAT_CHATHEARTBEAT_FORM.hidden}
        
        <table class="formElements">
			<tr>
				<td class="labelCell">{$T_CHAT_CHATHEARTBEAT_FORM.engine_rate.label}:&nbsp;</td>
				<td class="elementCell">{$T_CHAT_CHATHEARTBEAT_FORM.engine_rate.html}</td>
			</tr>
			{if $T_CHAT_CHATHEARTBEAT_FORM.engine_rate.error}
			<tr>
				<td></td>
				<td class = "formError">{$T_CHAT_CHATHEARTBEAT_FORM.engine_rate.error}</td>
			</tr>
			{/if}

			<tr>
				<td class="labelCell">{$T_CHAT_CHATHEARTBEAT_FORM.refresh_rate.label}:&nbsp;</td>
				<td class="elementCell">{$T_CHAT_CHATHEARTBEAT_FORM.refresh_rate.html}</td>
			</tr>
			{if $T_CHAT_CHATHEARTBEAT_FORM.refresh_rate.error}
			<tr>
				<td></td>
				<td class = "formError">{$T_CHAT_CHATHEARTBEAT_FORM.refresh_rate.error}</td>
			</tr>
			{/if}
			<tr>
				<td></td>
				<td class="submitCell">{$T_CHAT_CHATHEARTBEAT_FORM.submit.html}</td>
			</tr>
		</table>
	</form>
	
	
{/capture}





{capture name = 't_create_log'}

	{$T_CHAT_CREATE_LOG_FORM.javascript}
	
	<form {$T_CHAT_CREATE_LOG_FORM.attributes}>
		
		{$T_CHAT_CREATE_LOG_FORM.hidden}
		
		<table class="formElements">
		<tr>
			<td class="labelCell">{$T_CHAT_CREATE_LOG_FORM.lesson_title.label}:&nbsp;</td>
			<td class="elementCell">
				{$T_CHAT_CREATE_LOG_FORM.lesson_title.html}&nbsp;
				<img id = "busy" src = "{$T_CHAT_MODULE_BASELINK}img/loading.gif" width="15px" height="15px" style="display:none;" alt = "loading" title = "loading"/>
				<div id = "autocomplete_lessons" class = "autocomplete"></div>
			</td>
		</tr>
		<tr>
			<td class="labelCell">{$smarty.const._CHAT_FROM_DATE}:&nbsp;</td>
			<td class="elementCell">{$T_CHAT_CREATE_LOG_FORM.from.html}</td>
		</tr>
		<tr>
			<td class="labelCell">{$smarty.const._CHAT_UNTIL_DATE}:&nbsp;</td>
			<td class="elementCell">{$T_CHAT_CREATE_LOG_FORM.until.html}</td>
		</tr>
		<tr>
			<td></td>
			<td class="submitCell">{$T_CHAT_CREATE_LOG_FORM.submit.html}<span class="caution"></span></td>
		</tr>
		</table>
		{$T_LOG}
	</form>
{/capture}

{capture name = 't_clear_logs'}
	{$smarty.const._CHAT_CLEAR_HISTORY_DESCR}<br />
	<input type="checkbox" id="clearLessonLogs" style="border:none;outline:none;"/> {$smarty.const._CHAT_CLEAR_ALSO_LESSON_HISTORY_DESCR}<br /><br />
	<input type="button"  class="flatButton" value="Clear Logs" onclick="javascript:clearU2ULogs(); return false;" />
{/capture}

{capture name = 't_chat_tab_code'}
	<div class="tabber">
		{if $smarty.get.createLog==1}
			<div class="tabbertab">
				{eF_template_printBlock tabber = "chat_engine_rate" title=$smarty.const._CHAT_MODULE_RATES data=$smarty.capture.t_set_chatheartbeat image=$T_CHAT_BASELINK|cat:'img/chat.png' absoluteImagePath = 1}
			</div>
			<div class="tabbertab tabbertabdefault">
				{eF_template_printBlock tabber = "create_log" title=$smarty.const._CHAT_CREATE_LOG data=$smarty.capture.t_create_log image=$T_CHAT_BASELINK|cat:'img/chat.png' absoluteImagePath = 1}
			</div>
			<div class="tabbertab">
				{eF_template_printBlock tabber = "clear_logs" title=$smarty.const._CHAT_CLEAR_HISTORY data=$smarty.capture.t_clear_logs image=$T_CHAT_BASELINK|cat:'img/chat.png' absoluteImagePath = 1}
			</div>
		{else}
			<div class="tabbertab">
				{eF_template_printBlock tabber = "chat_engine_rate" title=$smarty.const._CHAT_MODULE_RATES data=$smarty.capture.t_set_chatheartbeat image=$T_CHAT_BASELINK|cat:'img/chat.png' absoluteImagePath = 1}
			</div>
			<div class="tabbertab">
				{eF_template_printBlock tabber = "create_log" title=$smarty.const._CHAT_CREATE_LOG data=$smarty.capture.t_create_log image=$T_CHAT_BASELINK|cat:'img/chat.png' absoluteImagePath = 1}
			</div>
			<div class="tabbertab">
				{eF_template_printBlock tabber = "clear_logs" title=$smarty.const._CHAT_CLEAR_HISTORY data=$smarty.capture.t_clear_logs image=$T_CHAT_BASELINK|cat:'img/chat.png' absoluteImagePath = 1}
			</div>
		{/if}
	</div>
{/capture}
{eF_template_printBlock title=$smarty.const._CHAT_CHAT data=$smarty.capture.t_chat_tab_code help = 'Chat'}
