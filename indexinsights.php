<?php

/*
Plugin Name: Big Insights
Plugin URI: http://biginsights.io/wordpres-plugin
Description: Easily lets you to add your Big Insights code into the &lt;head&gt; section of a specific page or every page on your website. Go to Settings -> Big Insights
Version: 1.0.1
Author: Big Insights
Author URI: http://biginsights.io/
License: GPLv2
Text Domain: biginsights
*/
load_plugin_textdomain('biginsights', false, basename( dirname( __FILE__ ) ) . '/languages' );

//Add the meta box
function biginsightsbox() {
	if(current_user_can('add-to-head')||current_user_can('manage_options')){
		$addtohead = __('Add to head', 'biginsights');
		$screens = get_option('insights_types_allowed', array('post', 'page'));
		foreach ( $screens as $screen ) {
			add_meta_box( 'biginsights', $addtohead, 'biginsightscallback', $screen, 'normal',
			 'default', null );
		}
	}
}

add_action( 'add_meta_boxes', 'biginsightsbox' );

function biginsightscallback($post){
	
  // Add an nonce field so we can check for it later.
  wp_nonce_field( 'biginsightscallback', 'biginsightscontent' );

  /*
   * Use get_post_meta() to retrieve an existing value
   * from the database and use the value for the form.
   */
  $value = get_post_meta( $post->ID, 'biginsights-content', true );

  echo '<label for="biginsights">';
       _e( "Put your head html here", 'biginsights' );
  echo '</label><br/> ';
  echo '<textarea id="biginsightstextbox" style="width:100%; min-height:120px; white-space: pre-wrap;" name="biginsights">'.str_replace('%BREAK%', "\n",stripslashes_deep(esc_attr($value))).'</textarea>';
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function biginsights_save_postdata( $post_id ) {

  /*
   * We need to verify this came from the our screen and with proper authorization,
   * because save_post can be triggered at other times.
   */

  // Check if our nonce is set.
  if ( ! isset( $_POST['biginsightscontent'] ) )
    return $post_id;

  $nonce = $_POST['biginsightscontent'];

  // Verify that the nonce is valid.
  if ( ! wp_verify_nonce( $nonce, 'biginsightscallback' ) )
      return $post_id;

  // If this is an autosave, our form has not been submitted, so we don't want to do anything.
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
      return $post_id;

  // Check the user's permissions.
  if ( 'page' == $_POST['post_type'] ) {

    if ( ! current_user_can( 'edit_page', $post_id ) )
        return $post_id;
  
  } else {

    if ( ! current_user_can( 'edit_post', $post_id ) )
        return $post_id;
  }

  /* OK, its safe for us to save the data now. */

  // Sanitize user input.
  $mydata = esc_sql( str_replace(array("\r\n", "\r", "\n"), '%BREAK%',$_POST['biginsights']) );

  // Update the meta field in the database.
  update_post_meta( $post_id, 'biginsights-content', $mydata );
}
add_action( 'save_post', 'biginsights_save_postdata' );
//Now that's done. Let's add the meta field to the head

function biginsights_display(){
	$pageid = get_queried_object_id();	
	$htmlfile = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'biginsightsconfig'.DIRECTORY_SEPARATOR.'biginsightspage.html';
	if(file_exists($htmlfile)&& filesize($htmlfile) > 0){
		if($htmlhandle = fopen($htmlfile, 'r')){
			$html = fread($htmlhandle, filesize($htmlfile));
		  	fclose($htmlhandle);
			echo $html;
		 }else{
			 echo "<!-- ".printf( __( 'Error reading config file %s! Is this file readable by the webserver?', 'biginsights' ), $htmlfile )." -->";
		 }
	}
	$addtoheadcontent = get_post_meta( $pageid, 'biginsights-content', true );
	if(!empty($addtoheadcontent)){
		echo str_replace('%BREAK%', "\n", stripslashes_deep($addtoheadcontent));
	}

}
add_action('wp_head', 'biginsights_display', 1000);

//Create a menu
//Load in the option page
function BigInsightsCreateMenu() {
	$menuname = __('Big Insights', 'biginsights');
	add_options_page( $menuname, $menuname, 'manage_options', 'biginsights-every-page', 'biginsightsSettings' );
}

function biginsightsSettings(){
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'settings.php');
	biginsights_config();
}

//Create the option menu, and load admin CSS to it
add_action( 'admin_menu', 'BigInsightsCreateMenu' );

//Installation
function biginsightsInstallStep1(){
	include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'installdeinstall.php');
	biginsightsInstallStep2();	
}
register_activation_hook( __FILE__, 'biginsightsInstallStep1');
//Deinstallation
function biginsightsDeinstallStep1(){
	include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'installdeinstall.php');
	biginsightsDeinstallStep2();	
}
register_uninstall_hook( __FILE__, 'biginsightsDeinstallStep1');
?>