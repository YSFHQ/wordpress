<?php

define ('IFLYCHAT_WP_ROOT_DIR', dirname(dirname(dirname(dirname(__FILE__)))));

require_once(IFLYCHAT_WP_ROOT_DIR . '/wp-load.php');

$data = array('settings' => array());
$data['settings']['authUrl'] = admin_url('admin-ajax.php', $_iflychat_protocol);
$data['settings']['host'] = ((is_ssl())?(DRUPALCHAT_EXTERNAL_A_HOST):(DRUPALCHAT_EXTERNAL_HOST));
$data['settings']['port'] = ((is_ssl())?(DRUPALCHAT_EXTERNAL_A_PORT):(DRUPALCHAT_EXTERNAL_PORT));
//$data = json_encode($data);

$result = wp_remote_head(DRUPALCHAT_EXTERNAL_A_HOST . ':' . DRUPALCHAT_EXTERNAL_A_PORT .  '/m/v1/app/', array(
  'method' => 'POST',
  'body' => $data,
  'timeout' => 15,
  'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
));
if((!is_wp_error($result)) && ($result['response']['code'] == 200)) {
	$o = $result['body'];
}
else {
	print $result['response']['code'];
}

print $o;
exit;



?>