<?php
include_once("qblog.php");
/*include_once("qb_posts.php");
include_once("qb_pages.php");*/
include_once("qb_users.php");
include_once("qb_templates.php");
qb_connect();
session_start();
$addr = qb_addr_get();
//is logged in?
if (array_key_exists("uid",$_SESSION)==false){
	header("Location: ".$addr);
	die ("You must be logged in to access post editor.<br>Redirecting to index page...");
}
$current_user = qb_user_get($_SESSION["uid"]);
if ($current_user == false){
	header("Location: ".$addr);
	$_SESSION["message"] = 'Your login was invalid. You\'ve been logged out.';
	unset($_SESSION["uid"]);
	die ("Your login was invalid. Redirecting...");
}
if ($current_user["type"]!="admin"){
	header("Location: ".$addr);
	$_SESSION["message"] = 'Your account cannot access post editor.';
	die ("Only admins can access post editor.");
}
/*$_GET arguments:
 * p - to specify what to open, post 'editor', 'pages'_list, 'posts'_list, or 'settings'
 * a - used to delete content
 * Post editor arguments:
 * id - if specified, then you're editing, else, creating new content
 */
//Now code to deal with dashboard home, post editor, and settings editor
if (array_key_exists("p",$_GET)){
	//now check whether to open post editor, settings, posts, or pages
	if ($_GET["p"]=="editor"){
		//check if ID was specified
		$id = -1;
		if (array_key_exists("id",$_GET)){
			$id = intval($_GET["id"]);
		}
		//echo post editor
		echo_dashboard_editor($id);
	}else if ($_GET["p"]=="pages"){
		echo_dashboard_pages();
	}else if ($_GET["p"]=="posts"){
		echo_dashboard_posts();
	}else if ($_GET["p"]=="settings"){
		echo_dashboard_settings();
	}else{
		$title = qb_setting_get("title");
		$content = '<content><header>An error occurred :(</header><hr>'.
			'The GET query is invalid, please try again</content>';
		echo_dashboard($title, $content);
	}
}else{
	//echo dashboard home
	echo_dashboard_index();
}
//do actions specified in $_GET

?>
