jQuery(document).ready(function($) {
  
  $("#iflychat_show_admin_list").change(function() {
    if ($("#iflychat_show_admin_list").val() == '1') {
	  $("[id^='iflychat_support_']").parent().parent().show();
	}
	else {
	  $("[id^='iflychat_support_']").parent().parent().hide();
	}
  });
  $("#iflychat_show_admin_list").change();
});