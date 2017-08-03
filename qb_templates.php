<?php
include_once("qblog.php");
include_once("qb_users.php");
include_once("qb_content.php");
//templates

$template_vars = array();
$template_name = "default";

function template_var_add($var, $val){
	global $template_vars;
	$template_vars[$var] = $val;
}

function template_vars_add($vars){
	global $template_vars_names;
	$template_vars = array_merge($template_vars, $vars);
}

function template_vars_reset(){
	global $template_vars;
	$template_vars = array();
}

function template_vars_set($new_vars){
	global $template_vars;
	$template_vars = $new_vars;
}

function template_open($fname){
	global $template_vars;
	$tmpl = file_get_contents("templates".DIRECTORY_SEPARATOR.$template_name.DIRECTORY_SEPARATOR.$fname.".html");
	return strtr($tmpl, $template_vars);
}

function template_open_as_var($var,$fname){
	global $template_vars;
	$template_vars[$var] = template_open($fname);
}

function template_echo($fname){
	global $template_vars;
	//check if there's a warning
	if (array_key_exists("warning", $_SESSION)){
		$warnings = qb_warning_get();
		if ($warnings !== false){
			//insert warnings
			$count = count($warnings);
			$warn_text = "";
			for ($i = 0; $i < $count; $i ++){
				template_var_add("%warning%",$warnings[$i]);
				if ($fname == "dashboard"){
					$warn_text .= template_open("dashboard_warning");
				}else{
					$warn_text .= template_open("index_warning");
				}
			}
			template_var_add("%warning%", $warn_text);
		}
	}else{
		$template_vars["%warning%"] = "";
	}
	//check if there's a message
	if (array_key_exists("message", $_SESSION)){
		$messages = qb_message_get();
		if ($messages !== false){
			//insert warnings
			$count = count($messages);
			$message_text = "";
			for ($i = 0; $i < $count; $i ++){
				template_var_add("%message%",$messages[$i]);
				if ($fname == "dashboard"){
					$message_text .= template_open("dashboard_message");
				}else{
					$message_text .= template_open("index_message");
				}
			}
			template_var_add("%message%", $message_text);
		}
	}else{
		$template_vars["%message%"] = "";
	}
	echo template_open($fname);
}
?>
