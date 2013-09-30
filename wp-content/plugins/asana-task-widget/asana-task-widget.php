<?php 
/*
 * Plugin Name: Asana Task Widget
 * Version: 1.2
 * Plugin URI: http://mackerelsky.co.nz/wordpress-plugins/asana-task-widget/
 * Description: Adds a dashboard widget that displays a list of your Asana tasks. Choose from due today, due this week, overdue and tagged for today.  You will need an <a href="http://asana.com/">Asana account</a> to use this plugin.  After activating, go to <a href="profile.php">your profile page</a> to configure this plugin
 * Author: Ed Goode
 * Author URI: http://mackerelsky.co.nz/
 */
 
 //global variables
 global $api_key; 
 
 add_option('show_asana_upgrade_notice', '');


//Add extra fields to user profile and update db when profile options are saved
add_action( 'show_user_profile', 'asana_profile_info' );
add_action( 'edit_user_profile', 'asana_profile_info' );
add_action( 'personal_options_update', 'save_asana_profile_info' );
add_action( 'edit_user_profile_update', 'save_asana_profile_info' );

//load jquery ui theme (http://snippets.webaware.com.au/snippets/load-a-nice-jquery-ui-theme-in-wordpress/)
add_action('init', 'load_jquery_ui');

function load_jquery_ui() {
    global $wp_scripts;
 
    // tell WordPress to load jQuery UI datepicker
    wp_enqueue_script('jquery-ui-datepicker');
 
    // get registered script object for jquery-ui
    $ui = $wp_scripts->query('jquery-ui-core');
 
    // tell WordPress to load the Smoothness theme from Google CDN
    $url = "https://ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery.ui.all.css";
    wp_enqueue_style('jquery-ui-smoothness', $url, false, $ui->ver);
}
 
function asana_profile_info( $user ) {
 
	global $wpdb;
 
?>
	<h3><?php _e('Asana plugin information');?></h3>
	<table class="form-table">
	<tr>
	<th><label for="asana_api_key"><?php _e('Asana API key') ?></label></th>
	<td>
	<input name="asana_api_key" id="asana_api_key" value="<?php
	echo esc_attr( get_the_author_meta( 'asana_api_key', $user->ID ) );
	?>">
 
	<span class="description"><?php _e('Your Asana API key');?></span>
	</td>
	</tr>
	</table>
	<table class="form-table">
	<tr>
	<th><label for="asana_selected_workspace"><?php _e('Selected Asana Workspace') ?></label></th>
	<td>
	<?php
	if (get_the_author_meta('asana_api_key', $user->ID) != ""){
	
		
		$workspace_url = "workspaces"; 		
		$api_key = get_the_author_meta('asana_api_key', $user->ID).":"; //note API key must have colon added to it for basic auth to work
		$workspaces=get_asana_info($workspace_url, $api_key);
	?>	
		<select name='asana_selected_workspace' id='asana_selected_workspace'>";
		<?php if(get_the_author_meta('asana_selected_workspace', $user->ID)  != ""){
			echo "<option value=''>".get_the_author_meta('asana_selected_workspace', $user->ID)."</option>";
		}
		else
		{
			echo "<option value=''>Choose a workspace</option>";
		}
		foreach ($workspaces as $ws) {
		echo "<option value='".$ws->id."&".$ws->name."'>".$ws->name."</option>";
		}
		echo "</select>";
		?>
		<span class='description'><?php _e('Please select an Asana workspace to display in the dashboard widgets');?></span>
		<?php
		
	}
	else
	{
		echo "Please enter an API key and save your options, then come back to select a workspace";
	}?>
	
	</td>
	</tr>
	</table>
	
<?php			
	//display options for what's shown in the widget
	$today = get_the_author_meta('asana_show_due_today', $user->ID);
	$thisweek = get_the_author_meta('asana_show_due_this_week', $user->ID);
	$overdue = get_the_author_meta('asana_show_overdue', $user->ID);
	$tagged = get_the_author_meta('asana_show_todays_tasks', $user->ID);		
	?>
	<table>
	<tr>
		<th>Widget display options</th><th><span class="description"><?php _e('Please select which types of task to show in the display widget');?></span></th>
	</tr>
	<tr>
		<td>Show tasks due today</td>
		<td><input type='checkbox' <?php if($today == 1){echo "checked='checked'";}?>name='asana_show_due_today'id='asana_show_due_today'value='1' /></td>
	</tr>
	<tr>
		<td>Show tasks tagged to be worked on today</td>
		<td><input type='checkbox' <?php if($tagged == 1){echo "checked='checked'";}?>name='asana_show_todays_tasks'id='asana_show_todays_tasks'value='1' /></td>
	</tr>
	<tr>
		<td>Show tasks due this week</td>
		<td><input type='checkbox' <?php if($thisweek == 1){echo "checked='checked'";}?>name='asana_show_due_this_week'id='asana_show_due_this_week'value='1' /></td>	
	</tr>
	<tr>
		<td>Show overdue tasks</td>
		<td><input type='checkbox' <?php if($overdue == 1){echo "checked='checked'";}?> name='asana_show_overdue'id='asana_show_overdue'value='1' /></td>
	</tr>
	</table>
	
<?php		

}
 
function save_asana_profile_info( $user_id ) {
 
	if ( !current_user_can( 'edit_user', $user_id ) )
	return false;
	
	update_user_meta( $user_id, 'asana_api_key', $_POST['asana_api_key'] );
	//split the workspace string into ID and name, then update user meta
	$selected_workspace = $_POST['asana_selected_workspace'];
	$ws_info = explode("&", $selected_workspace);
	update_user_meta( $user_id, 'asana_selected_workspace',  $ws_info[1]);
	update_user_meta( $user_id, 'asana_workspace_id',  $ws_info[0]);
	
	if (isset($_POST[asana_show_due_today])) {
			update_user_meta($user_id, asana_show_due_today, true);
		}
		else{
			update_user_meta($user_id,asana_show_due_today, false);
		}
		if (isset($_POST[asana_show_due_this_week])) {
			update_user_meta($user_id,asana_show_due_this_week, true);
		}
		else{
			update_user_meta($user_id,asana_show_due_this_week, false);
		}
		if (isset($_POST[asana_show_overdue])) {
			update_user_meta($user_id,asana_show_overdue, true);
		}
		else{
			update_user_meta($user_id,asana_show_overdue, false);
		}
		if (isset($_POST[asana_show_todays_tasks])) {
			update_user_meta($user_id,asana_show_todays_tasks, true);
		}
		else{
			update_user_meta($user_id,asana_show_todays_tasks, false);
		}
}



function asana_task_widget(){
	$user_id = get_current_user_id();

	if ($_POST['update_asana']) {
		//get all the values of the checked tasks
		$tasks_to_update = $_POST['asanatask'];
		//for each task
		foreach($tasks_to_update as $task){
			$completed = array("completed" => true);
			$body = array("data" => $completed);
			$url = "tasks/".$task;
			$api_key = get_the_author_meta('asana_api_key', $user_id).":";

			//call the API
			$response = put_asana_info($url, "PUT", $body, $api_key);
			$response_r = $response["response"];
			$code = $response_r["code"];
			if($code == 200){
				echo "Task successfully updated.";
			}
			else
			{
				echo "There was a problem communicating with Asana.  The error returned was ".$response_r["message"];
			}
		}
	}
	
	if(get_the_author_meta('asana_api_key', $user_id) == "" || get_the_author_meta('asana_selected_workspace', $user_id) == ""){
				//if either no api key or no workspace, display a message
		echo "Please go to <a href='profile.php'>your profile page</a> to configure this widget";
	}
	else
	{
		//set correct timezone so that we get the right date for tasks
		$timezone = get_option('timezone_string');
		date_default_timezone_set($timezone);
		$today = date("Y-m-d");
		$endofweek = strtotime( "next Sunday" );
		$weekend = date("Y-m-d",$endofweek);

		
		//set up the form (note that it's calling PHP_SELF, which will mean that we process the output on the same page
		echo "<form action='".$_SERVER['PHP_SELF']."' method='post' enctype='multipart/form-data'>";
		
		//get the tasks
		$task_url_ending = "tasks?opt_fields=name,completed,due_on,assignee_status,projects&workspace=".get_the_author_meta('asana_workspace_id', $user_id)."&assignee=me";
		$api_key = get_the_author_meta('asana_api_key', $user_id).":"; //note API key must have colon added to it for basic auth to work
		$tasks = get_asana_info($task_url_ending, $api_key);
		
		//get the names of the projects
		$projects_url_ending = "projects";
		$api_key = get_the_author_meta('asana_api_key', $user_id).":"; //note API key must have colon added to it for basic auth to work
		$projects = get_asana_info($projects_url_ending, $api_key);
		
		//check which options are set and call function accordingly
		if (get_the_author_meta('asana_show_due_today', $user_id) == 1){
			echo "<p><strong>Tasks in the ".get_the_author_meta('asana_selected_workspace', $user_id)." workspace due today:</strong></p>";
			$todaycount = 0;
			//display incomplete tasks due today
			foreach($tasks as $t){
				if($t->completed != 1 && $t->due_on == $today)
				{
					$task_name = $t->name;
					$task_id = $t->id;
					$project_ids = $t->projects;
					$project_id = $project_ids[0]->id;
					foreach($projects as $p){
						if($project_id == $p->id)
						{
							$project_name = $p->name;
						}
					}
					echo "<p><input type='checkbox' name='asanatask[]'value='".$task_id."' />".$task_name." - ".$project_name."</p>";
					$todaycount = $todaycount+1;
				}
			}
			//if there are no tasks in the category, say so
			if($todaycount == 0){
				echo "<p>There are no tasks due today</p>";
			}
		}		
		if (get_the_author_meta('asana_show_due_this_week', $user_id) == 1){
			echo "<p><strong>Tasks in the ".get_the_author_meta('asana_selected_workspace', $user_id)." workspace due this week:</strong></p>";
			$thisweekcount = 0;
			//display incomplete tasks due for the rest of the week
			foreach($tasks as $t){
				if($t->completed != 1 && $t->due_on >= $today && $t->due_on <= $weekend)
				{
					$task_name = $t->name;
					$task_id = $t->id;
					$project_ids = $t->projects;
					$project_id = $project_ids[0]->id;
					foreach($projects as $p){
						if($project_id == $p->id)
						{
							$project_name = $p->name;
						}
					}
					echo "<p><input type='checkbox' name='asanatask[]'value='".$task_id."' />".$task_name." - ".$project_name."</p>";
					$thisweekcount = $thisweekcount+1;
				}
			}

			if($thisweekcount == 0){
				echo "<p>There are no tasks due this week</p>";
			}
		}
		if (get_the_author_meta('asana_show_todays_tasks', $user_id) == 1){
			
			echo "<p><strong>Tasks in the ".get_the_author_meta('asana_selected_workspace', $user_id)." workspace tagged to be worked on today:</strong></p>";
			$taggedcount = 0;
			
			//display incomplete tasks assigned for today
			foreach($tasks as $t){
				if($t->completed != 1 && $t->assignee_status == 'today')
				{
					$task_name = $t->name;
					$task_id = $t->id;
					$project_ids = $t->projects;
					$project_id = $project_ids[0]->id;
					foreach($projects as $p){
						if($project_id == $p->id)
						{
							$project_name = $p->name;
						}
					}
					echo "<p><input type='checkbox' name='asanatask[]'value='".$task_id."' />".$task_name." - ".$project_name."</p>";
					$taggedcount = $taggedcount + 1;
				}
			}

			if($taggedcount == 0){
				echo "<p>There are no tasks assigned for today</p>";
			}
		}
		if (get_the_author_meta('asana_show_overdue', $user_id) == 1){
			echo "<p><span style='color: red; font-weight: bold'>Overdue tasks in the ".get_the_author_meta('asana_selected_workspace', $user_id)." workspace:</span></p>";
			$overduecount = 0;
			//display incomplete tasks due today
			foreach($tasks as $t){
				if($t->completed != 1 && $t->due_on != null && $t->due_on < $today)
				{
					$task_name = $t->name;
					$task_id = $t->id;
					$project_ids = $t->projects;
					$project_id = $project_ids[0]->id;
					foreach($projects as $p){
						if($project_id == $p->id)
						{
							$project_name = $p->name;
						}
					}
					echo "<p><input type='checkbox' name='asanatask[]'value='".$task_id."' />".$task_name." - ".$project_name."</p>";
					$overduecount = $overduecount + 1;
				}
			}
			
			if($overduecount == 0){
				echo "<p>There are no overdue tasks</p>";
			}
		}
		
	//add submit button and close out the form
	echo "<input type='submit' name ='update_asana' value='Mark complete' />";
	echo "</form>";
	
	}
	
}

function asana_create_task_widget(){
	
	$user_id = get_current_user_id();

	//call task creation when form is submitted
	if ($_POST['asana_create_task']) {
		//get variables and set up data
		$name = $_POST[asana_new_task_name];
		$notes = $_POST[asana_new_task_notes];
		$project = $_POST[asana_project];
		$due_date = $_POST[asana_due_date];
		$url = "tasks";
		$method = "POST";
		$bodydata = array("name" => $name, "workspace" => get_the_author_meta('asana_workspace_id', $user_id), "assignee" => "me");
		if ($notes != ""){
			$bodydata["notes"] = $notes;
		}
		if ($due_date != ""){
			$bodydata["due_on"] = $due_date;
		}
		$body = array("data" => $bodydata);
		$api_key = get_the_author_meta('asana_api_key', $user_id).":";
		
		//call task creation
		$response = put_asana_info($url, $method, $body, $api_key);
		if ( is_wp_error($response) ) {
				echo "Error communicating with Asana: ".$response->get_error_message();
		}
		else
		{		
			$response_r = $response["response"];
			$code = $response_r["code"];
			if($code == 201){
				echo "Task successfully created.";
			}
			else
			{
				echo "There was a problem communicating with Asana.  The error returned was ".$response_r["message"];
			}
		

			//call the api again to associate it with the project
				$response_body = json_decode($response['body']);
				$data = $response_body->data;
				$id = $data->id;
				$url = "tasks/".$id."/addProject";
				$bodydata = array("project" => $project);
				$body = array("data" => $bodydata);
				$api_key = get_the_author_meta('asana_api_key', $user_id).":";
				$response = put_asana_info($url, "POST", $body, $api_key);
				if ( is_wp_error($response) ) {
					echo "Error communicating with Asana: ".$response->get_error_message();
				}
				else
				{			
					$response_r = $response["response"];
					$code = $response_r["code"];
					if($code == 200){
						echo "  Task added to project.";
					}
					else
					{
						echo "  There was a problem adding the task to the project.  The error returned was ".$response_r["message"];
					}
				}
		}
	}
	
	//can't create projects until the plugin has been configured
	if(get_the_author_meta('asana_api_key', $user_id) == "" || get_the_author_meta('asana_selected_workspace', $user_id) == ""){
		//if either no api key or no workspace, display a message
		echo "Please go to <a href='profile.php'>your profile page</a> to configure this widget";
	}
	else
	{
		//create form
		$projects_url = "workspaces/".get_the_author_meta('asana_workspace_id', $user_id)."/projects"; 		
		$api_key = get_the_author_meta('asana_api_key', $user_id).":"; //note API key must have colon added to it for basic auth to work
		$projects=get_asana_info($projects_url, $api_key);

	
	echo "<form action='".$_SERVER['PHP_SELF']."' method='post' enctype='multipart/form-data'>";?>
	<table>
	<tr>
	<td>Task name</td>
	<td><input type='text' size='50'name='asana_new_task_name' ";"id='asana_new_task_name' value='' /></td>
	</tr>
	<tr>
	<td>Notes</td>
	<td><input type='text' size='50'name='asana_new_task_notes' ";"id='asana_new_task_notes' value='' /></td>
	</tr>
	<tr>
	<td>Project</td>
	<td><?php
		echo "<select name='".asana_project."' id='".asana_project."'>";
		echo "<option value=''>Choose a project</option>";
		foreach ($projects as $p) {
			echo "<option value='".$p->id."'>".$p->name."</option>";
		}
	?></td>
	</tr>
	<tr>
	<td>Due Date</td>
	<td>
	<input class='asana_date_picker' type='text' size='25'name='asana_due_date' ";"id='asana_due_date' value='' />
	</td>
	</tr>
	</table>
	<input type='submit' name ='asana_create_task' value='Create Task' />
	</form>
	<?php
	} 
	
	
}

function get_asana_info($url_ending, $api_key){
	$asana_url = "https://app.asana.com/api/1.0/".$url_ending; 
			
	//encode key and set header arguments (note sslverify must be false)
	$args = array('headers' => array('Authorization' => 'Basic ' .base64_encode($api_key)), 'sslverify' => false);

	//call API
	$results = wp_remote_get($asana_url, $args);
	if(!is_null($results)){
	
		//get results
		$resultsJson = json_decode($results['body']);
		$data = $resultsJson->data;
	}	
	return $data;
}


function put_asana_info($url_ending, $method, $data, $api_key){
	
	$url = "https://app.asana.com/api/1.0/".$url_ending;
	$body = json_encode($data);
	
	//method is POST for new tasks/projects, PUT for updating existing stuff
	//note that content type has been set to application/json.  That's the only way to get data out of this sucker
	$args = array('method' =>  $method, 'body' => $body, 'headers' => array('Content-Type'=> 'application/json', 'Authorization' => 'Basic ' .base64_encode($api_key)),'sslverify' => false);
		
	//call it
	$response = wp_remote_request( $url, $args);
	return $response;
}

// display notice about change to plugin
add_action('admin_notices', 'asana_settings_notice');
function asana_settings_notice() {
	if (current_user_can('manage_options')) {
		if (get_option('show_asana_upgrade_notice') != 'Version 1.0' ) {
			echo '<div class="updated"><p>';
			printf(__('The Asana Task Widget plugin now stores its information in usermeta.  Please go to your profile page to set it up. | <a href="%1$s">Hide Notice</a>'), '?ignore_asana_settings_notice=0');
			echo "</p></div>";
		}
    }
}

add_action('admin_init', 'ignore_asana_settings_notice');
function ignore_asana_settings_notice() {

    // If user clicks to ignore the notice, update the option
    if ( isset($_GET['ignore_asana_settings_notice']) && '0' == $_GET['ignore_asana_settings_notice'] ) {
		update_option('show_asana_upgrade_notice', 'Version 1.0');
	}
}

//function for calling jquery datepicker 
function asana_admin_footer() {
	?>
	<script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('.asana_date_picker').datepicker({
			dateFormat : 'yy-mm-dd'
		});
	});
	</script>
	<?php
}
add_action('admin_footer', 'asana_admin_footer');

//register widgets
function register_asana_task_widget(){
     wp_add_dashboard_widget( 'asana-tasks', 'Asana Tasks', 'asana_task_widget');
	 wp_add_dashboard_widget( 'create-asana-tasks', 'Create Asana Tasks', 'asana_create_task_widget');
}

add_action('wp_dashboard_setup', 'register_asana_task_widget');

?>