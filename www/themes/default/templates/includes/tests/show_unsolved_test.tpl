{if $T_SHOW_CONFIRMATION}
            {assign var = 't_show_side_menu' value = true}
            	{if $T_TEST_STATUS.status == 'incomplete' && $T_TEST_DATA->time.pause}
            		{assign var = "resume_test" value = "1"}	{*This means we are resuming a paused test, rather than starting a new one*}
            	{/if}
                <table class = "testHeader">
                    <tr><td id = "testName">{$T_TEST_DATA->test.name}</td></tr>
                    <tr><td id = "testDescription">{$T_TEST_DATA->test.description}</td></tr>
                    <tr><td>
                            <table class = "testInfo">
                                <tr>
								{if $T_UNIT.ctg_type != 'feedback'}
									<td rowspan = "7" id = "testInfoImage"><img src = "images/32x32/tests.png" alt = "{$T_TEST_DATA->test.name}" title = "{$T_TEST_DATA->test.name}"/></td>
                                {else}
									<td rowspan = "2" id = "testInfoImage"><img src = "images/32x32/feedback.png" alt = "{$T_TEST_DATA->test.name}" title = "{$T_TEST_DATA->test.name}"/></td>
								{/if}
									<td id = "testInfoLabels"></td>
                                    <td></td></tr>
							{if $T_UNIT.ctg_type != 'feedback'}
                                <tr><td>{$smarty.const._TESTDURATION}:&nbsp;</td>
                                    <td>
                                    {if $T_TEST_DATA->options.duration}
                                        {if $T_TEST_DATA->convertedDuration.hours}{$T_TEST_DATA->convertedDuration.hours}     {$smarty.const._HOURS}&nbsp;{/if}
                                        {if $T_TEST_DATA->convertedDuration.minutes}{$T_TEST_DATA->convertedDuration.minutes} {$smarty.const._MINUTES}&nbsp;{/if}
                                        {if $T_TEST_DATA->convertedDuration.seconds}{$T_TEST_DATA->convertedDuration.seconds} {$smarty.const._SECONDS}{/if}
                                    {else}
                                        {$smarty.const._UNLIMITED}
                                    {/if}
                                    </td></tr>
							{/if}
                                <tr><td>{$smarty.const._NUMOFQUESTIONS}:&nbsp;</td>
                                    <td>
							{if $T_TEST_DATA->options.user_configurable && !$resume_test}
										<input type = "text" id = "user_configurable" value = "" size = "3"> ({$smarty.const._MAXIMUM} {$T_TEST_QUESTIONS_NUM})
							{else}
								{$T_TEST_QUESTIONS_NUM}
							{/if}
									</td></tr>
							{if $T_UNIT.ctg_type != 'feedback'}
									<tr><td>{$smarty.const._QUESTIONSARESHOWN}:&nbsp;</td>
										<td>{if $T_TEST_DATA->options.onebyone}{$smarty.const._ONEBYONEQUESTIONS}{else}{$smarty.const._ALLTOGETHER}{/if}</td></tr>
								{if $T_TEST_STATUS.status == 'incomplete' && $T_TEST_DATA->time.pause}
									<tr><td>{$smarty.const._YOUPAUSEDTHISTESTON}:&nbsp;</td>
										<td>#filter:timestamp_time-{$T_TEST_DATA->time.pause}#</td></tr>
								{else}
									<tr><td>{$smarty.const._DONETIMESSOFAR}:&nbsp;</td>
										<td>{if $T_TEST_STATUS.timesDone}{$T_TEST_STATUS.timesDone}{else}0{/if}&nbsp;{$smarty.const._TIMES}</td></tr>
									<tr><td>{if $T_TEST_STATUS.timesLeft !== false }{$smarty.const._YOUCANDOTHETEST}:&nbsp;</td>
										<td>{if $T_TEST_STATUS.timesLeft > 0} {$T_TEST_STATUS.timesLeft}{elseif $T_TEST_STATUS.timesLeft < 0}1{else}0{/if}&nbsp;{$smarty.const._TIMESMORE}{/if}</td></tr>
									<tr><td>{if $T_TEST_DATA->options.test_password}{$smarty.const._TESTPASSWORD}:&nbsp;</td>
										<td><input type = "text" id = "test_password" name = "test_password"/></td>{else}<td>&nbsp;</td>{/if}</tr>
								{/if}
							{/if}
                            </table>
                        </td>
                    <tr><td id = "testProceed">
                    {if $resume_test}
                        <input class = "flatButton" type = "button" name = "submit_sure" value = "{$smarty.const._RESUMETEST}&nbsp;&raquo;" onclick = "window.location=window.location.toString()+'&resume=1'" />
                    {else}
						{if $T_UNIT.ctg_type != 'feedback'}
							{assign var = 'buttonValue' value = $smarty.const._PROCEEDTOTEST}
						{else}
							{assign var = 'buttonValue' value = $smarty.const._PROCEEDTOFEEDBACK}
						{/if}
                        <input class = "flatButton" type = "button" name = "submit_sure" value = "{$buttonValue}&nbsp;&raquo;" onclick = "window.location=window.location.toString()+'&confirm=1'+($('test_password') ? '&test_password='+encodeURIComponent($('test_password').value) : '')+($('user_configurable') && parseInt($('user_configurable').value) ? '&user_configurable='+$('user_configurable').value : '')" />
                    {/if}
                    </td></tr>
                </table>
{elseif $smarty.get.test_analysis}
            {assign var = 'title' value = "`$title`&nbsp;&raquo;&nbsp;<a class = 'titleLink' href = '`$smarty.server.PHP_SELF`?ctg=content&view_unit=`$smarty.get.view_unit`&test_analysis=1'>`$smarty.const._TESTANALYSISFORTEST` &quot;`$T_TEST_DATA->test.name`&quot;</a>"}

                <div class = "headerTools">
                    <span>
                        <img src = "images/16x16/arrow_left.png" alt = "{$smarty.const._VIEWSOLVEDTEST}" title = "{$smarty.const._VIEWSOLVEDTEST}">
                        <a href = "{$smarty.server.PHP_SELF}?ctg=content&view_unit={$smarty.get.view_unit}">{$smarty.const._VIEWSOLVEDTEST}</a>
                    </span>
                    {if $T_TEST_STATUS.testIds|@sizeof > 1}
                    <span>
                        <img src = "images/16x16/go_into.png" alt = "{$smarty.const._JUMPTOEXECUTION}" title = "{$smarty.const._JUMPTOEXECUTION}">
                        &nbsp;{$smarty.const._JUMPTOEXECUTION}
                        <select onchange = "location.toString().match(/show_solved_test/) ? location = location.toString().replace(/show_solved_test=\d+/, 'show_solved_test='+this.options[this.selectedIndex].value) : location = location + '&show_solved_test='+this.options[this.selectedIndex].value">
                            {if $smarty.get.show_solved_test}{assign var = "selected_test" value = $smarty.get.show_solved_test}{else}{assign var = "selected_test" value = $T_TEST_STATUS.lastTest}{/if}
                            {foreach name = "test_analysis_list" item = "item" key = "key" from = $T_TEST_STATUS.testIds}
                                <option value = "{$item}" {if $selected_test == $item}selected{/if}>#{$smarty.foreach.test_analysis_list.iteration} - #filter:timestamp_time-{$T_TEST_STATUS.timestamps[$key]}#</option>
                            {/foreach}
                        </select>
                    </span>
                    {/if}
                </div>
                <table class = "test_analysis">
                    <tr><td>{$T_CONTENT_ANALYSIS}</td></tr>
                    <tr><td>
                    	<div id = "graph_table"><div id = "proto_chart" class = "proto_graph"></div></div>
                    	<script>var show_test_graph = true;</script>
                    </td></tr>
                </table>
{else}				
        {if $T_TEST_STATUS.status == '' || $T_TEST_STATUS.status == 'incomplete'}
            {capture name = "test_footer"}
            <table class = "formElements" style = "width:100%">
                <tr><td colspan = "2">&nbsp;</td></tr>
                {if $T_TEST_DATA->options.onebyone}
                	<tr><td colspan = "2">&nbsp;</td></tr>
                	<tr><td colspan = "2">&nbsp;</td></tr>
                 	<tr><td colspan = "2">&nbsp;</td></tr>
                  	<tr><td colspan = "2">&nbsp;</td></tr>
                   	<tr><td colspan = "2">&nbsp;</td></tr>
                    <tr><td colspan = "2">&nbsp;</td></tr>
                {/if}
                <tr><td colspan = "2" class = "submitCell" style = "text-align:center">{$T_TEST_FORM.submit_test.html}&nbsp;{$T_TEST_FORM.pause_test.html}</td></tr>
            </table>
            {/capture}
        {/if}
        {if !$T_NO_TEST}
       
			{if !$T_TEST_DATA->options.redirect || ($T_TEST_STATUS.status != 'completed' && $T_TEST_STATUS.status != 'failed' && $T_TEST_STATUS.status != 'passed')}
			{$T_TEST_FORM.javascript}
					{if $T_TEST_STATUS.status == '' || $T_TEST_STATUS.status == 'incomplete'}
						{if $T_TEST_DATA->options.custom_class == 'ancient_greek'}
							<div style = "display:none" class = "ancient_greek">
							<button class = "anc_gre_btn" onclick="testAppendChar('\u1FB7'); return false;">&#x1FB7;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FB6'); return false;">&#x1FB6;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F84'); return false;">&#x1F84;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F85'); return false;">&#x1F85;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F05'); return false;">&#x1F05;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F01'); return false;">&#x1F01;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F00'); return false;">&#x1F00;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F04'); return false;">&#x1F04;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F06'); return false;">&#x1F06;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F07'); return false;">&#x1F07;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F80'); return false;">&#x1F80;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F81'); return false;">&#x1F81;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F86'); return false;">&#x1F86;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F87'); return false;">&#x1F87;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FB3'); return false;">&#x1FB3;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F71'); return false;">&#x1F71;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FB4'); return false;">&#x1FB4;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F15'); return false;">&#x1F15;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F14'); return false;">&#x1F14;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F11'); return false;">&#x1F11;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F10'); return false;">&#x1F10;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F73'); return false;">&#x1F73;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F94'); return false;">&#x1F94;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F95'); return false;">&#x1F95;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FC7'); return false;">&#x1FC7;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FC6'); return false;">&#x1FC6;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F24'); return false;">&#x1F24;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F25'); return false;">&#x1F25;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F20'); return false;">&#x1F20;</button> 
							<img src = "images/16x16/minus.png" onClick = "jQuery('.ancient_greek').removeClass('alwaysonscreen');"><br /><button class = "anc_gre_btn" onclick="testAppendChar('\u1F21'); return false;">&#x1F21;</button> 
<button class = "anc_gre_btn" onclick="testAppendChar('\u1F21'); return false;">&#x1F21;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F26'); return false;">&#x1F26;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F27'); return false;">&#x1F27;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F90'); return false;">&#x1F90;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F91'); return false;">&#x1F91;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F96'); return false;">&#x1F96;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F97'); return false;">&#x1F97;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F75'); return false;">&#x1F75;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FC3'); return false;">&#x1FC3;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FC4'); return false;">&#x1FC4;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FD6'); return false;">&#x1FD6;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F35'); return false;">&#x1F35;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F34'); return false;">&#x1F34;</button>
 <button class = "anc_gre_btn" onclick="testAppendChar('\u1F31'); return false;">&#x1F31;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F30'); return false;">&#x1F30;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F36'); return false;">&#x1F36;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F37'); return false;">&#x1F37;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F77'); return false;">&#x1F77;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F78'); return false;">&#x1F78;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F79'); return false;">&#x1F79;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F44'); return false;">&#x1F44;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F45'); return false;">&#x1F45;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F40'); return false;">&#x1F40;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F41'); return false;">&#x1F41;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FF7'); return false;">&#x1FF7;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FA4'); return false;">&#x1FA4;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FA5'); return false;">&#x1FA5;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FA1'); return false;">&#x1FA1;</button> <br /><button class = "anc_gre_btn" onclick="testAppendChar('\u1FF6'); return false;">&#x1FF6;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F64'); return false;">&#x1F64;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F65'); return false;">&#x1F65;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F61'); return false;">&#x1F61;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F60'); return false;">&#x1F60;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F66'); return false;">&#x1F66;</button> 
 <button class = "anc_gre_btn" onclick="testAppendChar('\u1F67'); return false;">&#x1F67;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FF4'); return false;">&#x1FF4;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FA0'); return false;">&#x1FA0;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FA6'); return false;">&#x1FA6;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FA7'); return false;">&#x1FA7;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F7D'); return false;">&#x1F7D;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FF3'); return false;">&#x1FF3;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FE6'); return false;">&#x1FE6;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F56'); return false;">&#x1F56;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F57'); return false;">&#x1F57;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F55'); return false;">&#x1F55;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F54'); return false;">&#x1F54;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F51'); return false;">&#x1F51;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F50'); return false;">&#x1F50;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FE6'); return false;">&#x1FE6;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1F7B'); return false;">&#x1F7B;</button> <button class = "anc_gre_btn" onclick="testAppendChar('\u1FE5'); return false;">&#x1FE5;</button>
							</div>
						{/if}
					{/if}
				<form {$T_TEST_FORM.attributes}>
					{$T_TEST_FORM.hidden}
					{$T_TEST}
					{$smarty.capture.test_footer}
				</form>


					{if $T_TEST_DATA->options.custom_class != ''}
						<script>
						{literal}
							jQuery(".{/literal}{$T_TEST_DATA->options.custom_class}{literal}").css('display','block');
						{/literal}
						</script>
					{/if}

					{if $T_TEST_DATA->options.custom_class == 'ancient_greek'}
						<script>
						{literal}
								jQuery(window).scroll(function () {
    								if (jQuery(window).scrollTop() > 100) {
       									jQuery('.ancient_greek').addClass('alwaysonscreen');
    								} else {
	        							jQuery('.ancient_greek').removeClass('alwaysonscreen');
    								}
								});
						{/literal}
						</script>					
					{/if}

					{if $T_TEST_DATA->options.custom_class == 'ancient_greek'}
						{literal}
						<script>
						if(jQuery('.ancient_greek').is(':visible')) {
							var last_focused_input_element;
							function testAppendChar(character){
								var currentValue = jQuery('input[name="'+last_focused_input_element+'"]').val();
								jQuery('input[name="'+last_focused_input_element+'"]').val(currentValue + character);
							}
							jQuery('input').click(function () {
							last_focused_input_element = jQuery(':focus').attr('name');
							})
						}
						</script>
						{/literal}
					{/if}		
			{else}
				<table class = "doneTestInfo">
                    <tr><td>
						{if $T_UNIT.ctg_type != 'feedback'}

							{if $T_TEST_DATA->options.redirect}
								<div class = "mediumHeader">{$smarty.const._THANKYOUFORCOMPLETING} "{$T_TEST_DATA->test.name}"</div>
							{/if}
							{$smarty.const._THETESTISDONE} {$T_TEST_STATUS.timesDone} {$smarty.const._TIMES}
							{if $T_TEST_DATA->options.redoable}
								{$smarty.const._ANDCANBEDONE}
								{if $T_TEST_STATUS.timesLeft > 0} {$T_TEST_STATUS.timesLeft}{else}0{/if}
								{$smarty.const._TIMESMORE}
							{/if}
									
						{else}
							<div class = "mediumHeader">{'%x'|str_replace:$T_TEST_DATA->lesson:$smarty.const._THANKYOUFORCOMPLETING} "{$T_TEST_DATA->test.name}"</div>
						{/if}
					</td></tr>
					 <tr><td>
						<div class = "headerTools">
							{if $T_TEST_STATUS.lastTest && ($T_TEST_STATUS.timesLeft > 0 || $T_TEST_STATUS.timesLeft === false)}
								<span id = "redoLink">
										<img src = "images/16x16/undo.png" alt = "{$smarty.const._USERREDOTEST}" title = "{$smarty.const._USERREDOTEST}" border = "0" style = "vertical-align:middle">
										<a href = "javascript:void(0)" id="redoLinkHref" onclick = "redoTest(this)" style = "vertical-align:middle">{$smarty.const._USERREDOTEST}</a></span>


							{/if}

							{if $smarty.const.G_VERSIONTYPE != "community"}	{*#cpp#ifndef COMMUNITY*}
								{if $T_TEST_STATUS.lastTest && ($T_TEST_STATUS.timesLeft > 0 || $T_TEST_STATUS.timesLeft === false) && $T_TEST_STATUS.completedTest.score != 100 && $T_TEST_DATA->options.redo_wrong == 1}
									<span id = "redoWrongLink">
											<img src = "images/16x16/undo.png" alt = "{$smarty.const._USERREDOWRONG}" title = "{$smarty.const._USERREDOWRONG}" border = "0" style = "vertical-align:middle">
											<a href = "javascript:void(0)" id="redoWrongLinkHref" onclick = "redoWrongTest(this)" style = "vertical-align:middle">{$smarty.const._USERREDOWRONG}</a></span>

								{/if}
							{/if} {*#cpp#endif*}
						</div>
					</table>

				<div style = "display:none">
					{$T_TEST_FORM.javascript}
					<form {$T_TEST_FORM.attributes}>
						{$T_TEST_FORM.hidden}
						{$T_TEST}
						{$smarty.capture.test_footer}
					</form>
				</div>
			{/if}
        {/if}
{/if}
