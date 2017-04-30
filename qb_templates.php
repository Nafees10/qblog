<?php
include_once("qblog.php");
include_once("qb_users.php");
include_once("qb_pages.php");
include_once("qb_posts.php");
include_once("qb_content.php");
//templates

$template_vars = array();

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
	$tmpl = file_get_contents("templates".DIRECTORY_SEPARATOR.$fname.".html");
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
		$template_vars["%warning%"] = nl2br($_SESSION["warning"]);
		if ($fname == "dashboard"){
			$template_vars["%warning%"] = template_open("dashboard_warning");
		}else{
			$template_vars["%warning%"] = template_open("index_warning");
		}
		unset($_SESSION["warning"]);
	}else{
		$template_vars["%warning%"] = "";
	}
	//check if there's a message
	if (array_key_exists("message", $_SESSION)){
		$template_vars["%message%"] = nl2br($_SESSION["message"]);
		if ($fname == "dashboard"){
			$template_vars["%message%"] = template_open("dashboard_message");
		}else{
			$template_vars["%message%"] = template_open("index_message");
		}
		unset($_SESSION["message"]);
	}else{
		$template_vars["%message%"] = "";
	}
	echo template_open($fname);
}
?>
