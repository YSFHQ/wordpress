jQuery(document).ready( function($) {

	var update_groups_ui  = function(data,txtStatus) {
		$("#pp_new_user_groups").html(data);
	}

	var ajax_ui_failure = function(data,txtStatus) {
		return;
	}

	var ajax_ui = function(op,handler) {
		var data = { 'pp_ajax_user_ui': op };
		$.ajax({url:ppUser.ajaxurl, data:data, dataType:"html", success:handler, error:ajax_ui_failure});
	}

	$('<div id="pp_new_user_groups"></div>').insertBefore('p.submit');
	ajax_ui('new_user_groups_ui',update_groups_ui);
});