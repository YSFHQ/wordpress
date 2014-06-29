<?php
/**
 * @package iflychat
 * @version 2.2.0
 */
/*
Plugin Name: iFlyChat
Plugin URI: http://wordpress.org/extend/plugins/iflychat/
Description: One on one chat, Multiple chatrooms, Embedded chatrooms
Author: Shashwat Srivastava, Shubham Gupta - iFlyChat Team
Version: 2.2.0
Author URI: https://iflychat.com/
*/

if(!function_exists('is_plugin_active_for_network')) {
  require_once(ABSPATH . '/wp-admin/includes/plugin.php');
}

define('DRUPALCHAT_EXTERNAL_HOST', 'http://api'.iflychat_get_option('iflychat_ext_d_i').'.iflychat.com');
define('DRUPALCHAT_EXTERNAL_PORT', '80');
define('DRUPALCHAT_EXTERNAL_A_HOST', 'https://api'.iflychat_get_option('iflychat_ext_d_i').'.iflychat.com');
define('DRUPALCHAT_EXTERNAL_A_PORT', '443');

function iflychat_get_hash_session() {
  $data = uniqid(mt_rand(), TRUE);
  $hash = base64_encode(hash('sha256', $data, TRUE));
  return strtr($hash, array('+' => '-', '/' => '_', '=' => ''));
}

function iflychat_get_user_id() {
  global $current_user;
  get_currentuserinfo();
  global $wpdb;
  if($current_user->ID) {
    return strval($current_user->ID);
  }
  else {
	  return "0-". iflychat_get_current_guest_id();
  }
}

function iflychat_get_user_name() {
  global $current_user;
  get_currentuserinfo();
  global $wpdb;
  if($current_user->ID) {
    return (empty($current_user->display_name)?$current_user->user_login:$current_user->display_name);
  }
  else {
    return iflychat_get_current_guest_name();
  }
}

function iflychat_init() {
  if(iflychat_path_check() && ((iflychat_get_option('iflychat_only_loggedin') == "no") || is_user_logged_in())) {
    load_plugin_textdomain('iflychat', false, basename( dirname( __FILE__ ) ) . '/languages' );
    global $current_user;
    get_currentuserinfo();
    $my_settings = array(
      //'uid' => iflychat_get_user_id(),
	    //'username' => iflychat_get_user_name(),
      'current_timestamp' => time(),
      'polling_method' => "3",
      'pollUrl' => " ",
      'sendUrl' => " ",
      'statusUrl' => " ",
      'status' => "1",
      'goOnline' => 'Go Online',
      'goIdle' => 'Go Idle',
      'newMessage' => __('New chat message!', 'iflychat'),
      'images' => plugin_dir_url( __FILE__ ) . 'themes/light/images/',
      'sound' => plugin_dir_url( __FILE__ ) . '/swf/sound.swf',
      'soundFile' => plugin_dir_url( __FILE__ ) . 'wav/notification.mp3',
      'noUsers' => "<div class=\"item-list\"><ul><li class=\"drupalchatnousers even first last\">No users online</li></ul></div>",
      'smileyURL' => plugin_dir_url( __FILE__ ) . 'smileys/very_emotional_emoticons-png/png-32x32/',
      'addUrl' => " ",
	    'notificationSound' => (iflychat_get_option('iflychat_notification_sound', "yes") == "yes")?"1":"2",
	    'basePath' => get_site_url() . "/",
	    'useStopWordList' => iflychat_get_option('iflychat_use_stop_word_list'),
	    'blockHL' => iflychat_get_option('iflychat_stop_links'),
	    'allowAnonHL' => iflychat_get_option('iflychat_allow_anon_links'),
	    'iup' => (iflychat_get_option('iflychat_user_picture') == 'yes')?'1':'2',
	    'open_chatlist_default' => (iflychat_get_option('iflychat_minimize_chat_user_list')=='2')?'1':'2',
	    'admin' => iflychat_check_chat_admin()?'1':'0',
    );
	if(iflychat_check_chat_admin()) {
	  global $wp_roles;
	  $my_settings['arole'] = $wp_roles->get_names();
	}
	global $current_user;
    get_currentuserinfo();
	if(iflychat_get_option('iflychat_user_picture') == 'yes') {
    //$my_settings['up'] = iflychat_get_user_pic_url();
	  $my_settings['default_up'] = 'http://www.gravatar.com/avatar/00000000000000000000000000000000?d=mm&size=24';
	  $my_settings['default_cr'] = plugin_dir_url( __FILE__ ) . 'themes/'.iflychat_get_option('iflychat_theme').'/images/default_room.png';
	  $my_settings['default_team'] = plugin_dir_url( __FILE__ ) . 'themes/'.iflychat_get_option('iflychat_theme').'/images/default_room.png';
	}

  //$my_settings['upl'] = iflychat_get_user_profile_url();

	if($my_settings['polling_method'] == "3") {
	  if (is_ssl()) {
        $my_settings['external_host'] = DRUPALCHAT_EXTERNAL_A_HOST;
        $my_settings['external_port'] = DRUPALCHAT_EXTERNAL_A_PORT;
        $my_settings['external_a_host'] = DRUPALCHAT_EXTERNAL_A_HOST;
        $my_settings['external_a_port'] = DRUPALCHAT_EXTERNAL_A_PORT;
	  }
	  else {
	    $my_settings['external_host'] = DRUPALCHAT_EXTERNAL_HOST;
        $my_settings['external_port'] = DRUPALCHAT_EXTERNAL_PORT;
		$my_settings['external_a_host'] = DRUPALCHAT_EXTERNAL_HOST;
        $my_settings['external_a_port'] = DRUPALCHAT_EXTERNAL_PORT;
	  }
	}
  $my_settings['text_currently_offline'] = __('drupalchat_user is currently offline.', 'iflychat');
  $my_settings['text_is_typing'] = __('drupalchat_user is typing...', 'iflychat');
	$my_settings['text_close'] = __('Close', 'iflychat');
	$my_settings['text_minimize'] = __('Minimize', 'iflychat');
	$my_settings['text_mute'] = __('Click to Mute', 'iflychat');
	$my_settings['text_unmute'] = __('Click to Unmute', 'iflychat');
	$my_settings['text_available'] = __('Available', 'iflychat');
	$my_settings['text_idle'] = __('Idle', 'iflychat');
	$my_settings['text_busy'] = __('Busy', 'iflychat');
	$my_settings['text_offline'] = __('Offline', 'iflychat');
	$my_settings['text_lmm'] = __('Load More Messages', 'iflychat');
  $my_settings['text_nmm'] = __('No More Messages', 'iflychat');
  $my_settings['text_clear_room'] = __('Clear all messages', 'iflychat');
	$my_settings['msg_p'] = __('Type and Press Enter', 'iflychat');
  $my_settings['text_search_bar'] = __('Type here to search', 'iflychat');
  $my_settings['searchBar'] = (iflychat_get_option('iflychat_enable_search_bar') == '1')?'1':'2';
  $my_settings['renderImageInline'] = (iflychat_get_option('iflychat_allow_render_images') == '1')?'1':'2';
	if(iflychat_check_chat_admin()) {
		$my_settings['text_ban'] = __('Ban', 'iflychat');
		$my_settings['text_ban_ip'] = __('Ban IP', 'iflychat');
		$my_settings['text_kick'] = __('Kick', 'iflychat');
		$my_settings['text_ban_window_title'] = __('Banned Users', 'iflychat');
		$my_settings['text_ban_window_default'] = __('No users have been banned currently.', 'iflychat');
		$my_settings['text_ban_window_loading'] = __('Loading banned user list...', 'iflychat');
		$my_settings['text_manage_rooms'] = __('Manage Rooms', 'iflychat');
		$my_settings['text_unban'] = __('Unban', 'iflychat');
		$my_settings['text_unban_ip'] = __('Unban IP', 'iflychat');
  }
	if(iflychat_get_option('iflychat_show_admin_list') == '1') {
	    $my_settings['text_support_chat_init_label'] = iflychat_get_option('iflychat_support_chat_init_label');
		$my_settings['text_support_chat_box_header'] = iflychat_get_option('iflychat_support_chat_box_header');
		$my_settings['text_support_chat_box_company_name'] = iflychat_get_option('iflychat_support_chat_box_company_name');
		$my_settings['text_support_chat_box_company_tagline'] = iflychat_get_option('iflychat_support_chat_box_company_tagline');
		$my_settings['text_support_chat_auto_greet_enable'] = iflychat_get_option('iflychat_support_chat_auto_greet_enable');
		$my_settings['text_support_chat_auto_greet_message'] = iflychat_get_option('iflychat_support_chat_auto_greet_message');
		$my_settings['text_support_chat_auto_greet_time'] = iflychat_get_option('iflychat_support_chat_auto_greet_time', 1);
		$my_settings['text_support_chat_offline_message_label'] = iflychat_get_option('iflychat_support_chat_offline_message_label');
		$my_settings['text_support_chat_offline_message_contact'] = iflychat_get_option('iflychat_support_chat_offline_message_contact');
		$my_settings['text_support_chat_offline_message_send_button'] = iflychat_get_option('iflychat_support_chat_offline_message_send_button');
		$my_settings['text_support_chat_offline_message_desc'] = iflychat_get_option('iflychat_support_chat_offline_message_desc');
		$my_settings['text_support_chat_init_label_off'] = iflychat_get_option('iflychat_support_chat_init_label_off');
	  }
    $_iflychat_protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
    $my_settings['geturl'] = admin_url('admin-ajax.php', $_iflychat_protocol);
	  $my_settings['soffurl'] = admin_url('admin-ajax.php', $_iflychat_protocol);
    $my_settings['mobileWebUrl'] = plugin_dir_url( __FILE__ ) . 'mobile-chat.php';
	  $my_settings['chat_type'] = iflychat_get_option('iflychat_show_admin_list');
    wp_enqueue_script( 'iflychat-emotify', plugin_dir_url( __FILE__ ) . 'js/ba-emotify.js', array('jquery'));
    wp_enqueue_script( 'iflychat-titlealert', plugin_dir_url( __FILE__ ) . 'js/jquery.titlealert.min.js', array('jquery'));
    wp_enqueue_script( 'iflychat-ajax', plugin_dir_url( __FILE__ ) . 'js/script.js', array('jquery'));

    wp_localize_script('iflychat-ajax', 'iflychat', $my_settings);

  }
}

function _iflychat_get_auth($name) {
  if(iflychat_get_option('iflychat_api_key') == " ") {
	return null;
  }
  global $current_user;
  get_currentuserinfo();
  $admin_check = FALSE;
  if(iflychat_check_chat_admin()) {
    $role = "admin";
  }
  else {
    //$role = "normal";
    global $current_user;
    $role = array();
    foreach ($current_user->roles as $rkey => $rvalue) {
      $role[$rvalue] = $rvalue;
    }
  }

  $data = array(
    'uid' => iflychat_get_user_id(),
	  'uname' => iflychat_get_user_name(),
    'api_key' => iflychat_get_option('iflychat_api_key'),
	  'image_path' => plugin_dir_url( __FILE__ ) . 'themes/light/images',
	  'isLog' => TRUE,
	  'whichTheme' => 'blue',
	  'enableStatus' => TRUE,
	  'role' => $role,
	  'validState' => array('available','offline','busy','idle'),
    'rel' => '0',
  );
  if((iflychat_get_option('iflychat_enable_friends')=='2') &&  function_exists('friends_get_friend_user_ids')) {
    $data['rel'] = '1';
    $final_list = array();
    $final_list['1']['name'] = 'friend';
    $final_list['1']['plural'] = 'friends';
    $final_list['1']['valid_uids'] = friends_get_friend_user_ids(iflychat_get_user_id());
    $data['valid_uids'] = $final_list;
  }

  if(iflychat_get_option('iflychat_user_picture') == 'yes') {
    $data['up'] = iflychat_get_user_pic_url();
  }

  $data['upl'] = iflychat_get_user_profile_url();


  //$data = json_encode($data);
  $options = array(
    'method' => 'POST',
    'body' => $data,
    'timeout' => 15,
    'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
	  'sslverify' => false,
  );

  $result = wp_remote_head(DRUPALCHAT_EXTERNAL_A_HOST . ':' . DRUPALCHAT_EXTERNAL_A_PORT .  '/p/', $options);
  
  if(!is_wp_error($result) && $result['response']['code'] == 200) {
    $result = json_decode($result['body']);
    return $result;
  }
  else {
    return null;
  }
}

function iflychat_submit_uth() {
  $user_name = NULL;
  $json = NULL;
  if((iflychat_get_option('iflychat_only_loggedin') == "no") || is_user_logged_in()) {
    $tid = iflychat_get_user_id();
    if($tid) {
      $user_name = iflychat_get_user_name();
    }
    if($user_name) {
      $json = _iflychat_get_auth($user_name);
      if(isset($json->_i) && (iflychat_get_option('iflychat_ext_d_i')!=$json->_i)) {
	       if (is_multisite() && is_plugin_active_for_network(plugin_basename(__FILE__ ))) {
          update_site_option('iflychat_ext_d_i',$json->_i);
        }
        else {
          update_option('iflychat_ext_d_i',$json->_i);
        }
      }
      $json->name = $user_name;
      $json->uid = $tid;
      if(iflychat_get_option('iflychat_user_picture') == 'yes') {
        $json->up = iflychat_get_user_pic_url();
      }
      $json->upl = iflychat_get_user_profile_url();
    }
  }
  $response = json_encode($json);
  header("Content-Type: application/json");
  echo $response;
  exit;
}

function iflychat_install() {
  global $wpdb;
}

function iflychat_uninstall() {
  delete_option('iflychat_api_key');
  global $wpdb;
}

function iflychat_set_options(){

	//call custom functions if you need special data (or not so special data�)
	//$cat_data = iflychat_get_categories();


	$options = array(
		/*'post_category' => array ( //option 'slug'
			'name' => 'timeline_post_category',
			'default' => '0',
			'desc' => 'Select a post category for your timeline:',
			'input_type' => 'dropdown',
			'data' => $cat_data //data should be single dimensional assoc array
			),*/
		'api_key' => array (
			'name' => 'iflychat_api_key',
			'default' => ' ',
			'desc' => '<b>API key</b> (register at <a href="https://iflychat.com">iFlyChat.com</a> to get it)',
			'input_type' => 'text'
			),
		'show_admin_list' => array (
			'name' => 'iflychat_show_admin_list',
			'default' => '2',
			'desc' => 'Select which chat software to use',
			'input_type' => 'dropdown',
			'data' => array(
				'2' => 'Community Chat',
				'1' => 'Support Chat')
			),
		'only_loggedin' => array (
			'name' => 'iflychat_only_loggedin',
			'default' => 'no',
			'desc' => 'Allow only logged-in users to access chat',
			'input_type' => 'dropdown',
			'data' => array(
				'yes' => 'yes',
				'no' => 'no')
			),
		'theme' => array (
			'name' => 'iflychat_theme',
			'default' => 'no',
			'desc' => 'Theme to use',
			'input_type' => 'dropdown',
			'data' => array(
				'light' => 'light',
				'dark' => 'dark')
			),
		'user_picture' => array (
			'name' => 'iflychat_user_picture',
			'default' => 'yes',
			'desc' => 'Show User Avatars in chat',
			'input_type' => 'dropdown',
			'data' => array(
				'yes' => 'yes',
				'no' => 'no')
			),
    'enable_friends' => array (
      'name' => 'iflychat_enable_friends',
      'default' => '1',
      'desc' => 'Show only friends in online user list',
      'input_type' => 'dropdown',
      'data' => array(
        '1' => 'No',
        '2' => 'BuddyPress Friends')
      ),
		'notification_sound' => array (
			'name' => 'iflychat_notification_sound',
			'default' => 'yes',
			'desc' => 'Use Notification Sound',
			'input_type' => 'dropdown',
			'data' => array(
				'yes' => 'yes',
				'no' => 'no')
			),
    'enable_smileys' => array (
			'name' => 'iflychat_enable_smileys',
			'default' => 'yes',
			'desc' => 'Enable Smileys',
			'input_type' => 'dropdown',
			'data' => array(
				'yes' => 'yes',
				'no' => 'no')
			),
    'enable_mobile_browser_app' => array (
      'name' => 'iflychat_enable_mobile_browser_app',
      'default' => '1',
      'desc' => 'Enable browser based Mobile app',
      'input_type' => 'dropdown',
      'data' => array(
        '1' => 'yes',
        '2' => 'no')
      ),
		'log_chat' => array (
			'name' => 'iflychat_log_chat',
			'default' => 'yes',
			'desc' => 'Log Chat Messages',
			'input_type' => 'dropdown',
			'data' => array(
				'yes' => 'yes',
				'no' => 'no')
			),
		'anon_prefix' => array(
			'name' => 'iflychat_anon_prefix',
            'desc' => 'Prefix to be used with anonymous users (4 to 7 characters)',
            'default' => 'Guest',
			'input_type' => 'text',
			),
		'anon_use_name' => array(
			'name' => 'iflychat_anon_use_name',
			'default' => '1',
			'desc' => 'Select whether to use random generated name or number to assign to a new anonymous user',
			'input_type' => 'dropdown',
			'data' => array(
				'1' => 'Name',
                '2' => 'Number',
				)
			),
		'minimize_chat_user_list' => array (
			'name' => 'iflychat_minimize_chat_user_list',
			'default' => '2',
			'desc' => 'Select whether to minimize online user list in chat by default',
			'input_type' => 'dropdown',
			'data' => array(
				'1' => 'Yes',
				'2' => 'No',)
			),
    'enable_search_bar' => array (
			'name' => 'iflychat_enable_search_bar',
			'default' => '1',
			'desc' => 'Select whether to show search bar in online user list',
			'input_type' => 'dropdown',
			'data' => array(
				'1' => 'Yes',
				'2' => 'No',)
			),
		'public_chatroom' => array (
			'name' => 'iflychat_public_chatroom',
			'default' => 'yes',
			'desc' => 'Enable Public Chatroom',
			'input_type' => 'dropdown',
			'data' => array(
				'yes' => 'yes',
				'no' => 'no')
			),
		'allow_user_font_color' => array (
			'name' => 'iflychat_allow_user_font_color',
			'default' => '1',
			'desc' => 'Select whether to allow users to set color of their name in a room',
			'input_type' => 'dropdown',
			'data' => array(
				'1' => 'Yes',
				'2' => 'No',)
			),
		'chat_top_bar_color' => array (
			'name' => 'iflychat_chat_top_bar_color',
			'default' => '#222222',
			'desc' => 'Chat Top Bar Color',
			'input_type' => 'text'
			),
		'chat_top_bar_text_color' => array (
			'name' => 'iflychat_chat_top_bar_text_color',
			'default' => '#FFFFFF',
			'desc' => 'Chat Top Bar Text Color',
			'input_type' => 'text'
			),
		'chat_font_color' => array (
			'name' => 'iflychat_chat_font_color',
			'default' => '#222222',
			'desc' => 'Chat Font Color',
			'input_type' => 'text'
			),
		'public_chatroom_header' => array (
			'name' => 'iflychat_public_chatroom_header',
			'default' => 'Public Chatroom',
			'desc' => 'Public Chatroom Header',
			'input_type' => 'text'
			),
		'chat_list_header' => array (
			'name' => 'iflychat_chat_list_header',
			'default' => 'Chat',
			'desc' => 'Chat List Header',
			'input_type' => 'text'
			),
        'support_chat_init_label' => array (
			'name' => 'iflychat_support_chat_init_label',
			'default' => 'Chat with us',
			'desc' => 'Support Chat - Start Button Label',
			'input_type' => 'text'
			),
        'support_chat_box_header' => array (
			'name' => 'iflychat_support_chat_box_header',
			'default' => 'Support',
			'desc' => 'Support Chat - Chat Box Header Label',
			'input_type' => 'text'
			),
        'support_chat_box_company_name' => array (
			'name' => 'iflychat_support_chat_box_company_name',
			'default' => 'Support Team',
			'desc' => 'Support Chat - Team/Company Name',
			'input_type' => 'text'
			),
        'support_chat_box_company_tagline' => array (
			'name' => 'iflychat_support_chat_box_company_tagline',
			'default' => 'Ask us anything...',
			'desc' => 'Support Chat - Tagline Label',
			'input_type' => 'text'
			),
        'support_chat_auto_greet_enable' => array (
			'name' => 'iflychat_support_chat_auto_greet_enable',
			'default' => '1',
			'desc' => 'Support Chat - Enable Auto Greeting Message',
			'input_type' => 'dropdown',
			'data' => array(
				'1' => 'Yes',
				'2' => 'No',)
			),
        'support_chat_auto_greet_message' => array (
			'name' => 'iflychat_support_chat_auto_greet_message',
			'default' => 'Hi there! Welcome to our website. Let us know if you have any query!',
			'desc' => 'Support Chat - Auto Greeting Message',
			'input_type' => 'textarea'
			),
        'support_chat_auto_greet_time' => array (
			'name' => 'iflychat_support_chat_auto_greet_time',
			'default' => '1',
			'desc' => 'Support Chat - Auto Greet Message Time Delay (in seconds)',
			'input_type' => 'text'
			),
        'support_chat_init_label_off' => array (
			'name' => 'iflychat_support_chat_init_label_off',
			'default' => 'Leave Message',
			'desc' => 'Support Chat - Offline Message Button Label',
			'input_type' => 'text'
			),
        'support_chat_offline_message_desc' => array (
			'name' => 'iflychat_support_chat_offline_message_desc',
			'default' => 'Hello there. We are currently offline. Please leave us a message. Thanks.',
			'desc' => 'Support Chat - Offline Message Description',
			'input_type' => 'textarea'
			),
        'support_chat_offline_message_label' => array (
			'name' => 'iflychat_support_chat_offline_message_label',
			'default' => 'Message',
			'desc' => 'Support Chat - Offline Window - Message Label',
			'input_type' => 'text'
			),
        'support_chat_offline_message_contact' => array (
			'name' => 'iflychat_support_chat_offline_message_contact',
			'default' => 'Contact Details',
			'desc' => 'Support Chat - Offline Window - Contact Details Label',
			'input_type' => 'text'
			),
        'support_chat_offline_message_send_button' => array (
			'name' => 'iflychat_support_chat_offline_message_send_button',
			'default' => 'Send Message',
			'desc' => 'Support Chat - Offline Window - Send Button Label',
			'input_type' => 'text'
			),
        'support_chat_offline_message_email' => array (
			'name' => 'iflychat_support_chat_offline_message_email',
			'default' => iflychat_get_option('admin_email', 'support@yourwebsite.com'),
			'desc' => 'Support Chat - Email(s) to which mail offline messages should be sent (separated by comma)',
			'input_type' => 'text'
			),
		'stop_word_list' => array (
			'name' => 'iflychat_stop_word_list',
			'default' => 'asshole,assholes,bastard,beastial,beastiality,beastility,bestial,bestiality,bitch,bitcher,bitchers,bitches,bitchin,bitching,blowjob,blowjobs,bullshit,clit,cock,cocks,cocksuck,cocksucked,cocksucker,cocksucking,cocksucks,cum,cummer,cumming,cums,cumshot,cunillingus,cunnilingus,cunt,cuntlick,cuntlicker,cuntlicking,cunts,cyberfuc,cyberfuck,cyberfucked,cyberfucker,cyberfuckers,cyberfucking,damn,dildo,dildos,dick,dink,dinks,ejaculate,ejaculated,ejaculates,ejaculating,ejaculatings,ejaculation,fag,fagging,faggot,faggs,fagot,fagots,fags,fart,farted,farting,fartings,farts,farty,felatio,fellatio,fingerfuck,fingerfucked,fingerfucker,fingerfuckers,fingerfucking,fingerfucks,fistfuck,fistfucked,fistfucker,fistfuckers,fistfucking,fistfuckings,fistfucks,fuck,fucked,fucker,fuckers,fuckin,fucking,fuckings,fuckme,fucks,fuk,fuks,gangbang,gangbanged,gangbangs,gaysex,goddamn,hardcoresex,horniest,horny,hotsex,jism,jiz,jizm,kock,kondum,kondums,kum,kumer,kummer,kumming,kums,kunilingus,lust,lusting,mothafuck,mothafucka,mothafuckas,mothafuckaz,mothafucked,mothafucker,mothafuckers,mothafuckin,mothafucking,mothafuckings,mothafucks,motherfuck,motherfucked,motherfucker,motherfuckers,motherfuckin,motherfucking,motherfuckings,motherfucks,niger,nigger,niggers,orgasim,orgasims,orgasm,orgasms,phonesex,phuk,phuked,phuking,phukked,phukking,phuks,phuq,pis,piss,pisser,pissed,pisser,pissers,pises,pisses,pisin,pissin,pising,pissing,pisof,pissoff,porn,porno,pornography,pornos,prick,pricks,pussies,pusies,pussy,pusy,pussys,pusys,slut,sluts,smut,spunk',
			'desc' => 'Stop Words (separated by comma)',
			'input_type' => 'textarea'
			),
		'use_stop_word_list' => array (
			'name' => 'iflychat_use_stop_word_list',
			'default' => '1',
			'desc' => 'Use Stop Words to filter chat',
			'input_type' => 'dropdown',
			'data' => array(
			  '1' => 'Don\'t filter',
			  '2' => 'Filter in public chatroom',
			  '3' => 'Filter in private chats',
			  '4' => 'Filter in all rooms',
			  ),
			),
		'stop_links' => array (
			'name' => 'iflychat_stop_links',
			'default' => '1',
			'desc' => 'Select whether to allow/block hyperlinks posted in chats',
			'input_type' => 'dropdown',
			'data' => array(
			  '1' => 'Don\'t block',
			  '2' => 'Block in public chatroom',
			  '3' => 'Block in private chats',
			  '4' => 'Block in all rooms',
			  ),
			),
		'allow_anon_links' => array (
			'name' => 'iflychat_allow_anon_links',
			'default' => '1',
			'desc' => 'Select whether to apply above defined hyperlinks setting only to anonymous users',
			'input_type' => 'dropdown',
			'data' => array(
				'1' => 'yes',
				'2' => 'no')
			),
    'allow_render_images' => array (
			'name' => 'iflychat_allow_render_images',
			'default' => '1',
			'desc' => 'Select whether to render image and video hyperlinks inline in chat',
			'input_type' => 'dropdown',
			'data' => array(
				'1' => 'yes',
				'2' => 'no')
			),
    'enable_file_attachment' => array (
      'name' => 'iflychat_enable_file_attachment',
      'default' => '1',
      'desc' => 'Select whether to allow user to share/upload file in chat',
      'input_type' => 'dropdown',
      'data' => array(
        '1' => 'yes',
        '2' => 'no')
      ),
		'allow_single_message_delete' => array (
			'name' => 'iflychat_allow_single_message_delete',
			'default' => '1',
			'desc' => 'Select whether to allow users to delete messages selectively when in private conversation',
			'input_type' => 'dropdown',
			'data' => array(
				'1' => 'Allow all users',
				'2' => 'Allow only moderators',
				'3' => 'Disable')
			),
		'allow_clear_room_history' => array (
			'name' => 'iflychat_allow_clear_room_history',
			'default' => '1',
			'desc' => 'Select whether to allow users to clear all messages in a room',
			'input_type' => 'dropdown',
			'data' => array(
				'1' => 'Allow all users',
				'2' => 'Allow only moderators',
				'3' => 'Disable')
			),

		'path_visibility' => array (
			'name' => 'iflychat_path_visibility',
			'default' => '1',
			'desc' => 'Show chat on specific pages',
			'input_type' => 'dropdown',
			'data' => array(
				'1' => 'All pages except those listed',
				'2' => 'Only the listed pages')
			),
		'path_pages' => array (
			'name' => 'iflychat_path_pages',
			'default' => '',
			'desc' => "Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are <b>/2012/10/my-post</b> for a single post and <b>/2012/*</b> for a group of posts. The path should always start with a forward slash(/).",
			'input_type' => 'textarea'
			),
    'chat_admins_array' => array (
			'name' => 'iflychat_chat_admins_array',
			'default' => '',
			'desc' => "Specify WordPress username of users who should be chat admininstrators (separated by comma)",
			'input_type' => 'textarea'
			),
		/*'include_images' => array (
			'name' => 'timeline_include_images',
			'default' => 'no',
			'desc' => 'Do you want to include featured image thumbnails?',
			'input_type' => 'dropdown',
			'data' => array( //manual dropdown options
				'yes' => 'yes',
				'no' => 'no')
				),
		'post_order' => array (
			'name' => 'timeline_order_posts' ,
			'default' => 'DESC',
			'desc' => 'How do you want to order your posts?',
			'input_type' => 'dropdown',
			'data' => array(
				'Ascending' => 'ASC',
				'Descending' => 'DESC')
			)
			*/
	);

	return $options;

}

//create settings page
function iflychat_settings() {
  wp_enqueue_script( 'iflychat-admin', plugin_dir_url( __FILE__ ) . 'js/iflychat.admin.script.js', array('jquery'));
  ?>
		<div class="wrap">
			<h2><?php _e('iFlyChat Settings', 'iflychat_settings'); ?></h2>
		<?php
		if (isset($_GET['updated']) && $_GET['updated'] == 'true') {
			?>
			<div id="message" class="updated fade"><p><strong><?php _e('Settings Updated', 'iflychat_settings'); ?></strong></p></div>
			<?php
		}
		?>
			<form method="post" action="<?php
      if(is_multisite() && is_plugin_active_for_network(plugin_basename(__FILE__ ))) {
        echo esc_url('edit.php?action=iflychat_network_settings');
      }
      else {
        echo esc_url('options.php');
      }
      ?>">
				<div>
					<?php settings_fields('iflychat-settings'); ?>
				</div>

				<?php
					$options = iflychat_set_options();

					?>
				<table class="form-table">
				<?php foreach($options as $option){ ?>
					<?php
						//if option type is a dropdown, do this
						if ( $option['input_type'] == 'dropdown'){ ?>
							<tr valign="top">
				        		<th scope="row"><?php _e($option['desc'], 'iflychat_settings'); ?></th>
				        			<td><select id="<?php echo $option['name']; ?>" name="<?php echo $option['name']; ?>">
				        					<?php foreach($option['data'] as $opt => $value){ ?>
												<option <?php if(iflychat_get_option($option['name']) == $opt){ echo 'selected="selected"';}?> name="<?php echo $option['name']; ?>" value="<?php echo $opt; ?>"><?php echo $value ; ?></option>
												<?php } //endforeach ?>
										</select>
									</td>
					        </tr>
				    <?php
				    	//if option type is text, do this
				    	}elseif ( $option['input_type'] == 'text'){ ?>
				    		<tr valign="top">
				        		<th scope="row"><?php _e($option['desc'], 'iflychat_settings'); ?></th>
				        			<td><input id="<?php echo $option['name']; ?>" name="<?php echo $option['name']; ?>" value="<?php echo iflychat_get_option($option['name']); ?>" size="64" />
									</td>
					        </tr>
					<?php
				    	//if option type is text, do this
				    	}elseif ( $option['input_type'] == 'textarea'){ ?>
				    		<tr valign="top">
				        		<th scope="row"><?php _e($option['desc'], 'iflychat_settings'); ?></th>
				        			<td><textarea id="<?php echo $option['name']; ?>" cols="80" rows="6" name="<?php echo $option['name']; ?>"><?php echo iflychat_get_option($option['name']); ?>
									</textarea>
									</td>
					        </tr>
			     <?php

			     		}else{} //endif

			     	} //endforeach ?>

			    </table>
			    <p class="submit"><input type="submit" class="button-primary" value="<?php _e('Update', 'iflychat_settings'); ?>" /></p>
			</form>
		</div>
	<?php
    //if((isset($_GET['updated']) && $_GET['updated'] == 'true') || (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true')) {
      if(iflychat_get_option('iflychat_api_key') != " ") {
        $data = array(
          'api_key' => iflychat_get_option('iflychat_api_key'),
      	  'enable_chatroom' => (iflychat_get_option('iflychat_public_chatroom') == "yes")?'1':'2',
      	  'theme' => iflychat_get_option('iflychat_theme'),
      	  'notify_sound' => (iflychat_get_option('iflychat_notification_sound') == "yes")?'1':'2',
      	  'smileys' => (iflychat_get_option('iflychat_enable_smileys') == "yes")?'1':'2',
      	  'log_chat' => (iflychat_get_option('iflychat_log_chat') == "yes")?'1':'2',
      	  'chat_topbar_color' => iflychat_get_option('iflychat_chat_top_bar_color'),
      	  'chat_topbar_text_color' => iflychat_get_option('iflychat_chat_top_bar_text_color'),
      	  'font_color' => iflychat_get_option('iflychat_chat_font_color'),
      	  'chat_list_header' => iflychat_get_option('iflychat_chat_list_header'),
      	  'public_chatroom_header' => iflychat_get_option('iflychat_public_chatroom_header'),
      	  'version' => 'WP-2.2.0',
      	  'show_admin_list' => (iflychat_get_option('iflychat_show_admin_list') == "1")?'1':'2',
      	  'clear' => iflychat_get_option('iflychat_allow_single_message_delete'),
          'delmessage' => iflychat_get_option('iflychat_allow_clear_room_history'),
      	  'ufc' => iflychat_get_option('iflychat_allow_user_font_color'),
          'use_stop_word_list' => iflychat_get_option('iflychat_use_stop_word_list'),
          'stop_word_list' => iflychat_get_option('iflychat_stop_word_list'),
          'file_attachment' => (iflychat_get_option('iflychat_enable_file_attachment') == "1")?'1':'2',
          'mobile_browser_app' => (iflychat_get_option('iflychat_enable_mobile_browser_app') == "1")?'1':'2',
      	);
        $options = array(
          'method' => 'POST',
          'body' => $data,
          'timeout' => 15,
          'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
          'sslverify' => false,
        );
        $result = wp_remote_head(DRUPALCHAT_EXTERNAL_A_HOST . ':' . DRUPALCHAT_EXTERNAL_A_PORT .  '/z/', $options);
        if(is_wp_error($result)) {
          echo '<div id="message" class="error">Unable to connect to iFlyChat server. Error code - ' . $result->get_error_code() . '. Error message - ' . $result->get_error_message() . '</div>';
        }
        else if($result['response']['code'] != 200) {
  	      echo '<div id="message" class="error">Unable to connect to iFlyChat server. Error code - ' . $result['response']['code'] . '. Error message - ' . $result['body'] . '</div>';
  	    }
      }
    //}
}

//register settings loops through options
function iflychat_register_settings()
{
	$options = iflychat_set_options(); //get options array

	foreach($options as $option){
		register_setting('iflychat-settings', $option['name']);
    //register each setting with option's 'name'

		if (iflychat_get_option($option['name']) === false) {
			iflychat_add_option($option['name'], $option['default'], '', 'yes'); //set option defaults
		}
	}

	if (iflychat_get_option('iflychat_promote_plugin') === false) {
		iflychat_add_option('iflychat_promote_plugin', '0', '', 'yes');
	}

	if (iflychat_get_option('iflychat_ext_d_i') === false) {
		iflychat_add_option('iflychat_ext_d_i', '3', '', 'yes');
	}

}
add_action( 'admin_init', 'iflychat_register_settings' );


//add settings page
function iflychat_settings_page() {
	if(is_multisite() && is_plugin_active_for_network(plugin_basename(__FILE__ ))) {
   add_submenu_page('settings.php','iFlyChat Settings', 'iFlyChat Settings', 'manage_options', 'iflychat_settings', 'iflychat_settings');
 }
 else {
   add_options_page('iFlyChat Settings', 'iFlyChat Settings', 'manage_options', 'iflychat_settings', 'iflychat_settings');
 }
}

if(is_multisite() && is_plugin_active_for_network(plugin_basename(__FILE__ ))) {
  add_action('network_admin_menu', 'iflychat_settings_page');
}
else {
  add_action('admin_menu', 'iflychat_settings_page');
}


function iflychat_network_settings() {
  if(($_POST['option_page']=="iflychat-settings") && ($_POST['action']=="update")) {
    foreach((array)$_POST as $key => $value){
			if(substr($key, 0, 9) === "iflychat_") {
        update_site_option($key,$value);
      }
		}
  }
  // redirect to settings page in network
  wp_redirect(
    add_query_arg(
      array( 'page' => 'iflychat_settings', 'updated' => 'true' ),
        network_admin_url( 'settings.php' )
    )
  );
  exit;
}

if(is_multisite() && is_plugin_active_for_network(plugin_basename(__FILE__ ))) {
  add_action('network_admin_edit_iflychat_network_settings', 'iflychat_network_settings');
}

add_action('init', 'iflychat_init');
add_action( 'wp_ajax_nopriv_iflychat-get', 'iflychat_submit_uth' );
add_action( 'wp_ajax_iflychat-get', 'iflychat_submit_uth' );
add_action( 'wp_ajax_nopriv_iflychat-offline-msg', 'iflychat_send_offline_message' );
add_action( 'wp_ajax_iflychat-offline-msg', 'iflychat_send_offline_message' );
add_action( 'wp_login', 'iflychat_user_login' );
add_action( 'wp_logout', 'iflychat_user_logout' );
add_shortcode( 'iflychat_inbox', 'iflychat_get_inbox' );
add_shortcode( 'iflychat_message_thread', 'iflychat_get_message_thread' );
add_shortcode( 'iflychat_embed', 'iflychat_get_embed_code' );
register_activation_hook(__FILE__,'iflychat_install');
register_deactivation_hook( __FILE__, 'iflychat_uninstall');
function iflychat_match_path($path, $patterns) {
  $to_replace = array(
    '/(\r\n?|\n)/',
    '/\\\\\*/',
  );
  $replacements = array(
    '|',
    '.*',
  );
  $patterns_quoted = preg_quote($patterns, '/');
  $regexps[$patterns] = '/^(' . preg_replace($to_replace, $replacements, $patterns_quoted) . ')$/';
  return (bool) preg_match($regexps[$patterns], $path);
}
function iflychat_path_check() {
  $page_match = FALSE;
  if (trim(iflychat_get_option('iflychat_path_pages')) != '') {
    if(function_exists('mb_strtolower')) {
      $pages = mb_strtolower(iflychat_get_option('iflychat_path_pages'));
      $path = mb_strtolower($_SERVER['REQUEST_URI']);
    }
    else {
      $pages = strtolower(iflychat_get_option('iflychat_path_pages'));
      $path = strtolower($_SERVER['REQUEST_URI']);
    }
    $page_match = iflychat_match_path($path, $pages);
	$page_match = (iflychat_get_option('iflychat_path_visibility') == '1')?(!$page_match):$page_match;
  }
  else if(iflychat_get_option('iflychat_path_visibility') == '1'){
    $page_match = TRUE;
  }
  return $page_match;
}
function iflychat_mail_set_content_type(){
    return "text/html";
}
function iflychat_send_offline_message() {
  if(isset($_POST['drupalchat_m_contact_details']) && isset($_POST['drupalchat_m_message'])) {
    global $user;
    $drupalchat_offline_mail = array();
    $drupalchat_offline_mail['subject'] = 'iFlyChat: Message from Customer';
    $drupalchat_offline_mail['contact_details'] =  '<p>' . iflychat_get_option('iflychat_support_chat_offline_message_contact') . ': ' . ($_POST['drupalchat_m_contact_details']) . '</p>';
    $drupalchat_offline_mail['message'] = '<p>' . iflychat_get_option('iflychat_support_chat_offline_message_label') . ': ' . ($_POST['drupalchat_m_message']) . '</p>';
	$drupalchat_offline_mail['message'] = $drupalchat_offline_mail['contact_details'] . '<br><br>' . $drupalchat_offline_mail['message'];
	add_filter( 'wp_mail_content_type','iflychat_mail_set_content_type' );
    $result = wp_mail(iflychat_get_option('iflychat_support_chat_offline_message_email'), $drupalchat_offline_mail['subject'], $drupalchat_offline_mail['message']);
  }
  $response = json_encode($result);
  header("Content-Type: application/json");
  echo $response;
  exit;
}
function iflychat_check_chat_admin() {
  global $current_user;
  get_currentuserinfo();
  if(current_user_can('activate_plugins')) {
    return TRUE;
  }
  $a = iflychat_get_option('iflychat_chat_admins_array');
  if(!empty($a) && ($current_user->ID)) {
    $a_names = explode(",", $a);
    foreach($a_names as $an) {
      $aa = trim($an);
      if($aa == $current_user->user_login) {
        return TRUE;
        break;
      }
    }
  }
  return FALSE;
}

function iflychat_user_login() {
  setcookie("iflychat_key", "", time()-3600, "/");
  setcookie("iflychat_css", "", time()-3600, "/");
  setcookie("iflychat_time", "", time()-3600, "/");
}

function iflychat_user_logout() {
  setcookie("iflychat_key", "", time()-3600, "/");
  setcookie("iflychat_css", "", time()-3600, "/");
  setcookie("iflychat_time", "", time()-3600, "/");
}

function iflychat_get_inbox() {
  $data = array(
    'uid' => iflychat_get_user_id(),
    'api_key' => iflychat_get_option('iflychat_api_key'),
  );
  $options = array(
    'method' => 'POST',
    'body' => $data,
    'timeout' => 15,
    'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
	  'sslverify' => false,
  );
  $result = wp_remote_head(DRUPALCHAT_EXTERNAL_A_HOST . ':' . DRUPALCHAT_EXTERNAL_A_PORT .  '/r/', $options);
  $output = '';
  if(!is_wp_error($result) && $result['response']['code'] == 200) {
    $query = json_decode($result['body']);
    $timezone_offet = iflychat_get_option( 'gmt_offset' );
    $date_format = iflychat_get_option( 'date_format' );
    $time_format = iflychat_get_option( 'time_format' );
    foreach($query as $record) {
      $rt = $record->timestamp + ($timezone_offet * 3600);
      $output .= '<div style="display:block;border-bottom: 1px solid #ccc; padding: 10px;"><div style="font-size:130%; display: inline;">' . $record->name . '</div><div style="float:right;color:#AAA; font-size: 70%;">' . date( "{$date_format} {$time_format}", $rt ) . '</div><div style="display: block; padding: 10px;">' . $record->message . '</div></div>';
    }
  }
  return $output;
}

function iflychat_get_message_thread($atts) {
  extract( shortcode_atts( array(
		'id' => 'c-0',
	), $atts ) );
  $data = array(
    'uid1' => iflychat_get_user_id(),
    'uid2' => $id,
    'api_key' => iflychat_get_option('iflychat_api_key'),
  );
  $options = array(
    'method' => 'POST',
    'body' => $data,
    'timeout' => 15,
    'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
	  'sslverify' => false,
  );
  $result = wp_remote_head(DRUPALCHAT_EXTERNAL_A_HOST . ':' . DRUPALCHAT_EXTERNAL_A_PORT .  '/q/', $options);
  $output = '';
  if(!is_wp_error($result) && $result['response']['code'] == 200) {
    $query = json_decode($result['body']);
    $timezone_offet = iflychat_get_option( 'gmt_offset' );
    $date_format = iflychat_get_option( 'date_format' );
    $time_format = iflychat_get_option( 'time_format' );
    foreach($query as $record) {
      $rt = $record->timestamp + ($timezone_offet * 3600);
      $output .= '<div style="display:block;border-bottom: 1px solid #ccc; padding: 1% 0% 1% 0%;"></div><div style="display:block; padding-top: 1%; padding-bottom: 0%"><div style="font-size:100%; display: inline;"><a href="#">' . $record->from_name . '</a></div><div style="float:right;font-size: 70%;">' . date( "{$date_format} {$time_format}", $rt ) . '</div><div style="display: block; padding-top: 1%; padding-bottom: 0%">' . $record->message . '</div></div>';
    }
  }
  return $output;
}

function iflychat_get_embed_code($atts) {
  extract( shortcode_atts( array(
		'id' => 'c-0',
    'hide_user_list' => 'no',
    'hide_popup_chat' => 'no',
    'height' => '550px',
	), $atts ) );
  $output = '<style>.drupalchat-embed-chatroom-content {height: '. $height .' !important;}';
  if($hide_user_list == "yes") {
    $output .= '#drupalchat-embed-user-list {display:none !important;}.drupalchat-embed-chatroom-content {width:95% !important;}';
  }
  $output .= '</style>';
  $output .= '<script type="text/javascript">if(typeof(iflyembed) === "undefined") {iflyembed = {};iflyembed.settings = {};iflyembed.settings.ifly = {};}iflyembed.settings.ifly.embed = "1";iflyembed.settings.ifly.ur_hy = "1";iflyembed.settings.ifly.embed_msg = "Type your message here. Press Enter to send.";iflyembed.settings.ifly.embed_online_user_text = "Online Users";</script>';
  $output .= '<div id="drupalchat-embed-chatroom-' . substr($id, 2) . '" class="drupalchat-embed-chatroom-container';
  if($hide_popup_chat == "yes") {
    $output .= ' drupalchat-hide-popup-chat';
  }
  $output .= '"></div>';
  return $output;
}

function iflychat_get_user_pic_url() {
  global $current_user;
  get_currentuserinfo();
  $url = 'http://www.gravatar.com/avatar/' . (($current_user->ID)?(md5(strtolower($current_user->user_email))):('00000000000000000000000000000000')) . '?d=mm&size=24';
  if(function_exists("bp_core_fetch_avatar") && ($current_user->ID > 0)) {
    $url = bp_core_fetch_avatar(array('item_id' => iflychat_get_user_id(),'html'=>false));
  }
  else if(function_exists("user_avatar_fetch_avatar") && ($current_user->ID > 0)) {
    $local_url = user_avatar_fetch_avatar(array('html' => false, 'item_id' => $current_user->ID));
    if($local_url) {
      $url = $local_url;
    }
  }
  else if(function_exists("get_wp_user_avatar_src") && ($current_user->ID > 0)) {
    $url = get_wp_user_avatar_src(iflychat_get_user_id());
  }
  else if(function_exists("get_simple_local_avatar") && ($current_user->ID > 0)) {
    $source = get_simple_local_avatar(iflychat_get_user_id());
    $source = explode('src="', $source);
    if(isset($source[1])) {
      $source = explode('"', $source[1]);
    }
    else {
      $source = explode("src='", $source[0]);
      if(isset($source[1])) {
        $source = explode("'", $source[1]);
      }
      else {
        $source[0] = 'http://www.gravatar.com/avatar/' . (($current_user->ID)?(md5(strtolower($current_user->user_email))):('00000000000000000000000000000000')) . '?d=mm&size=24';
      }
    }
    $url = $source[0];
  }
  
  $pos = strpos($url, ':');
  if($pos !== false) {
    $url = substr($url, $pos+1);
  }
  return $url;
}

function iflychat_get_random_name() {
  $path = plugin_dir_path( __FILE__ ) . "guest_names/iflychat_guest_random_names.txt";
  $f_contents = file($path);
  $line = trim($f_contents[rand(0, count($f_contents) - 1)]);
  return $line;
}

function iflychat_get_current_guest_name() {
  if(isset($_SESSION) && isset($_SESSION['iflychat_guest_name'])) {
    //if(!isset($_COOKIE) || !isset($_COOKIE['drupalchat_guest_name'])) {
      setrawcookie('iflychat_guest_name', rawurlencode($_SESSION['iflychat_guest_name']), time()+60*60*24*365);
    //}
  }
  else if(isset($_COOKIE) && isset($_COOKIE['iflychat_guest_name']) && isset($_COOKIE['iflychat_guest_session'])&& ($_COOKIE['iflychat_guest_session']==iflychat_compute_guest_session(iflychat_get_current_guest_id()))) {
    $_SESSION['iflychat_guest_name'] = iflychat_check_plain($_COOKIE['iflychat_guest_name']);
  }
  else {
    if(iflychat_get_option('iflychat_anon_use_name')=='1') {
      $_SESSION['iflychat_guest_name'] = iflychat_check_plain(iflychat_get_option('iflychat_anon_prefix') . ' ' . iflychat_get_random_name());
    }
    else {
      $_SESSION['iflychat_guest_name'] = iflychat_check_plain(iflychat_get_option('iflychat_anon_prefix') . time());
    }
    setrawcookie('iflychat_guest_name', rawurlencode($_SESSION['iflychat_guest_name']), time()+60*60*24*365);
  }
  return $_SESSION['iflychat_guest_name'];
}

function iflychat_get_current_guest_id() {
  if(isset($_SESSION) && isset($_SESSION['iflychat_guest_id'])) {
    //if(!isset($_COOKIE) || !isset($_COOKIE['drupalchat_guest_id'])) {
      setrawcookie('iflychat_guest_id', rawurlencode($_SESSION['iflychat_guest_id']), time()+60*60*24*365);
      setrawcookie('iflychat_guest_session', rawurlencode($_SESSION['iflychat_guest_session']), time()+60*60*24*365);
    //}
  }
  else if(isset($_COOKIE) && isset($_COOKIE['iflychat_guest_id']) && isset($_COOKIE['iflychat_guest_session']) && ($_COOKIE['iflychat_guest_session']==iflychat_compute_guest_session($_COOKIE['iflychat_guest_id']))) {
    $_SESSION['iflychat_guest_id'] = iflychat_check_plain($_COOKIE['iflychat_guest_id']);
    $_SESSION['iflychat_guest_session'] = iflychat_check_plain($_COOKIE['iflychat_guest_session']);
  }
  else {
    $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $iflychatId = time();
    for ($i = 0; $i < 5; $i++) {
      $iflychatId .= $characters[rand(0, strlen($characters) - 1)];
    }
    $_SESSION['iflychat_guest_id'] = $iflychatId;
    $_SESSION['iflychat_guest_session'] = iflychat_compute_guest_session($_SESSION['iflychat_guest_id']);
    setrawcookie('iflychat_guest_id', rawurlencode($_SESSION['iflychat_guest_id']), time()+60*60*24*365);
    setrawcookie('iflychat_guest_session', rawurlencode($_SESSION['iflychat_guest_session']), time()+60*60*24*365);
  }
  return $_SESSION['iflychat_guest_id'];
}

function iflychat_compute_guest_session($id) {
  return md5(substr(iflychat_get_option('iflychat_api_key'), 0, 5) . $id);
}

function iflychat_check_plain($text) {
  return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function iflychat_get_user_profile_url() {
  global $current_user;
  get_currentuserinfo();
  $upl = 'javascript:void(0)';
  if(function_exists("bp_core_get_userlink") && ($current_user->ID > 0)) {
    $upl = bp_core_get_userlink($current_user->ID, false, true);
  }
  return $upl;
}

function iflychat_get_option($name) {
  if (is_multisite() && is_plugin_active_for_network(plugin_basename(__FILE__ ))) {
    return get_site_option($name);
  }
  else {
    return get_option($name);
  }
}

function iflychat_add_option($name, $value, $v2, $v3) {
  if (is_multisite() && is_plugin_active_for_network(plugin_basename(__FILE__ ))) {
    return add_site_option($name, $value, $v2, $v3);
  }
  else {
    return add_option($name, $value, $v2, $v3);
  }
}

?>
