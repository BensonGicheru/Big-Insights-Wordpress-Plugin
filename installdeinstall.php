<?php
function biginsightsInstallStep2(){
	$configdir = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'biginsightsconfig';
	$htmlfile = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'biginsightsconfig'.DIRECTORY_SEPARATOR.'biginsightspage.html';
	if(!is_dir($configdir)){
		mkdir($configdir, 0775);
	}  
	if(!file_exists($htmlfile)){
	  if($htmlcreatehandle = fopen($htmlfile, 'x')){
		  fwrite($htmlcreatehandle, "");
		  fclose($htmlcreatehandle);
	  }
  }
  global $wp_roles;
  $roleobject = $wp_roles->get_role('administrator');
  $roleobject->add_cap('add-to-head');
  add_option('insights_types_allowed', array('post', 'page'), '', 'no');
}
function biginsightsDeinstallStep2(){
	$configdir = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'biginsightsconfig';
	$htmlfile = $configdir.DIRECTORY_SEPARATOR.'biginsightspage.html';
	biginsightsConfigMgrDeleteConfigfile($htmlfile);
	$scanned_directory = array_diff(scandir($configdir), array('..', '.'));
	if(empty($scanned_directory)){
		rmdir($configdir);
	}
	delete_option('insights_roles_allowed');
	delete_option('insights_types_allowed');
}
function biginsightsConfigMgrDeleteConfigfile($filename){
	$configdir = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'biginsightsconfig';
	$keepfileindex = $configdir.DIRECTORY_SEPARATOR.'keepfiles.txt';
	$neverdelete = $configdir.DIRECTORY_SEPARATOR.'neverdelete.txt';
	if(is_file($configdir .DIRECTORY_SEPARATOR. basename($filename))){
		//File exists and is inside the config dir
	  if(!is_file($neverdelete)){
		  //never delete is not set
		if(is_file($keepfileindex)){
			//There is a keep file index
			$tokeep = file($keepfileindex, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
			if(!in_array($basename($filename),$tokeep)){
				//but the file to delete isn't in it
			   return unlink($filename);
			}else{
				//The file is in the keep file index, do not delete
				return false;
			}
		}else{
			//There is no keep file index, delete
			return unlink($filename);
		}
	  }else{
		  //Never delete is set, do not delete
		  return false;
	  }
	}else{
		//The file is invalid, do not delete (but can't delete anyway)
		return false;
	}
}
?>