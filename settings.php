<?php
function biginsights_config(){
  $currenturl = 'http';
   if (isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == "on") {$currenturl .= "s";}
   $currenturl .= "://";
   if ($_SERVER["SERVER_PORT"] != "80") {
	$currenturl .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
   } else {
	$currenturl .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
  }
  $htmlfile = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'biginsightsconfig'.DIRECTORY_SEPARATOR.'biginsightspage.html';
  ?>
  
  <div class="wrap">
  <h2><?php _e('Put a Big Insights feedback button on every page!', 'biginsights'); ?></h2>
  <h3><?php _e('Paste your Big Insights code here. It will be inserted into the &lt;head&gt; tag on every page.', 'biginsights'); ?></h3>
 
  <?php
  if(!file_exists($htmlfile)){
	  if($htmlcreatehandle = fopen($htmlfile, 'x')){
		  fwrite($htmlcreatehandle, "");
		  fclose($htmlcreatehandle);
	  }else{
		  printf(__("Error creating %s! Is the underlying folder writable?", 'biginsights'), $htmlfile);
	  }
  }
  if($htmlhandle = fopen($htmlfile, 'r')){
	  if(filesize($htmlfile) > 0){
		$html = fread($htmlhandle, filesize($htmlfile));
	  }else{
		  $html = "";
	  }
	  fclose($htmlhandle);
	  
  }else{printf(__("Error reading HTML from file %s. Is this file readable?", 'biginsights'), $htmlfile);}
  if(isset($_POST['html']) && current_user_can('add-to-head')){
	  $newhtml = stripslashes_deep($_POST['html']);
	  if($newhtml == $html){
		  _e('HTML not updated', 'biginsights');
	  }elseif($htmlwritehandle = fopen($htmlfile, 'w')){
		  $html = $newhtml;
		  fwrite($htmlwritehandle, $html);
		  fclose($htmlwritehandle);
		  printf(__("Succesfully edited %s!", 'biginsights'), $htmlfile);
	  }else{printf(__("Error writing HTML to %s. Is this file writable?", 'biginsights'), $htmlfile);}
  }
  global $wp_roles;
  $rolesallowednow = array();
  foreach($wp_roles->get_names() as $role_slug => $role_name){
		  $roleobject = $wp_roles->get_role($role_slug);
		  if(isset($roleobject->capabilities['add-to-head']) && $roleobject->capabilities['add-to-head']==1){
			  $rolesallowednow[] = $role_slug;
		  }
  }
  if(!in_array('administrator',$rolesallowednow)){
		$roleobject = $wp_roles->get_role('administrator');
		$roleobject->add_cap('add-to-head');
		$rolesallowednow[]='administrator';
  }
  $posttypesallowednow = get_option('insights_types_allowed', array('post', 'page'));
   
  if(isset($_POST['submit'])){
	  //Role management
	  //Check if all values are really roles
	  $optionroles = array('administrator');
	  if(isset($_POST['rolesallowed'])&& is_array($_POST['rolesallowed'])){
		  foreach($_POST['rolesallowed'] as $roleallowed){
			  if($wp_roles->is_role($roleallowed)){
				  $optionroles[]=$roleallowed;
			  }
		  }
	  }
	  //If changed, update roles
	  if($optionroles != $rolesallowednow){
		 if(current_user_can('manage_options')){
			foreach($optionroles as $role){
				$roleobject = $wp_roles->get_role($role);
				$roleobject->add_cap('add-to-head');
			}
			$revokedroles = array_diff($rolesallowednow, $optionroles);
			if(!empty($revokedroles)){
				foreach($revokedroles as $role){
					$roleobject = $wp_roles->get_role($role);
					$roleobject->remove_cap('add-to-head');
				}
			}
			$rolesallowednow = $optionroles;
			$rolesupdated = true;
		 }
	  }else{
		  $rolesupdated = false;
	  }
	 //Post type management
	 $newposttypes = array();
	 if(isset($_POST['typeallowed'])&& is_array($_POST['typeallowed'])){
		 foreach($_POST['typeallowed'] as $newposttype){
			 if(post_type_exists($newposttype)){
				 $newposttypes[]= $newposttype;
			 }
		 }
	 }
	 if($newposttypes != $posttypesallowednow && current_user_can('add-to-head')){
		 update_option('insights_types_allowed', $newposttypes);
		 $posttypesallowednow = $newposttypes;
		 $typesupdated = true;
	 }else{
		 $typesupdated = false;
	 } 
  }
  ?><form method="post" action="<?php echo $currenturl; ?>"><?php
  if(current_user_can('add-to-head')|| current_user_can('manage_options')):
  if(isset($html)){
  	?><textarea style="white-space:pre; width:80%; min-width:600px; height:300px;" name="html"><?php echo $html; ?></textarea><?php
  }
  ?><br />
  <h3><?php _e('Manage which users can access the plugin', 'biginsights');?></h3>
  <?php
  	if(isset($rolesupdated)){
		if($rolesupdated){
			_e('Succesfully updated user role support', 'biginsights');
		}else{
			_e('User role support not updated', 'biginsights');
		}
	}
  ?>
  <table>
  	<?php
		foreach($wp_roles->get_names() as $role_slug => $role_name){
			?><tr><td><?php echo translate_user_role($role_name);?></td><td><input type="checkbox" name="rolesallowed[]" value="<?php echo $role_slug; ?>" <?php if(in_array($role_slug, $rolesallowednow)){ echo ' checked="checked"';} if($role_slug == 'administrator'){echo ' disabled="disabled"';}?> /></td></tr>
            <?php	
		}
	?>
  </table>
  <br />
  <h3><?php _e('Manage post types that have the Big Insights box', 'biginsights');?></h3>
  <p><?php _e('Note: only post types which have their own page (like posts or pages, but not navigation menu items) are supported' , 'biginsights'); ?></p>
  <?php
  	if(isset($typesupdated)){
		if($typesupdated){
			_e('Succesfully updated post type support', 'biginsights');
		}else{
			_e('Post type support not updated', 'biginsights');
		}
	}
  ?>
  <table>
  	<?php
		$post_types = get_post_types('', 'names');
		foreach($post_types as $post_type){
			$post_type_object = get_post_type_object( $post_type );
			?><tr><td><?php echo $post_type_object->labels->name;?></td><td><input type="checkbox" name="typeallowed[]" value="<?php echo $post_type; ?>" <?php if(in_array($post_type, $posttypesallowednow)){ echo ' checked="checked"';} ?> /></td></tr>
            <?php	
		}
	?>
  </table>
  <?php
  submit_button();
  endif;
  echo "</form></div>";
}
?>