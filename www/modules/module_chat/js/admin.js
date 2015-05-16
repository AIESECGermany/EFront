if ($('autocomplete_lessons')) { 
	new Ajax.Autocompleter(
		"autocomplete", 
		"autocomplete_lessons", 
		"ask.php?ask_type=lesson",
		{paramName: "preffix",
		 afterUpdateElement : function (t, li) {
		 $J.get(modulechatbaselink+"admin.php?force=getLessonFromId&loglessonid="+li.id, 
				 function(response){
			 		response = jQuery.parseJSON(response);
					$J('#autocomplete').val(response.name);														
					$J('#hidden_lesson_id').val(response.id);
 				}
		 );
		}, 
		indicator : "busy"}); 
}

function clearU2ULogs() {
	var x = window.confirm("This action is irreversible! Are you sure?")
	if (x) {
		if ($J('#clearLessonLogs').is(':checked')) {
			$J.get(modulechatbaselink + "admin.php?force=clearAllLogs",
					function(data) {
						alert(data);
					});
		} else {
			$J.get(modulechatbaselink + "admin.php?force=clearU2ULogs",
					function(data) {
						alert(data);
					});
		}
	}
}

function exportChatAdmin() {

	var logArray = [];
	jQuery("table#admin_chat_logs tr").each(function() {
	   var arrayOfThisRow = [];
	   var tableData = jQuery(this).find('td');
	   if (tableData.length > 0) {
	       tableData.each(function() { arrayOfThisRow.push(jQuery(this).text()); });
	       logArray.push(arrayOfThisRow);
	   }
	});
	
	
	jQuery.post(modulechatbaseurl+'&createLog=1', {exportAdminChat: 1, logTitle: jQuery('#autocomplete').val(), data: logArray},
		function(response) {
			$('popup_frame').src = 'view_file.php?file='+response;
	});
}
