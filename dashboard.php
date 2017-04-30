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
	die ("You must be logged in to access dashboard.<br>Redirecting to index page...");
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
	$_SESSION["message"] = 'Your account cannot access dashboard.';
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
		//check if has to edit
		$id = -1;
		if (array_key_exists("id",$_GET)){
			$id = intval($_GET["id"]);
		}
		
		if (array_key_exists("a",$_GET)){
			//check if it was a form submission
			if (array_key_exists("post_heading",$_POST) && 
			array_key_exists("post_content",$_POST) && array_key_exists("type",$_POST)){
				//edit/post it
				if ($_GET["a"]=="new"){
					if ($_POST["type"]=="page"){
						$r = qb_page_add($_POST["post_heading"],$_POST["post_content"]);
						if ($r==false){
							qb_warning_add(qb_error_get());
						}else{
							qb_message_add("Page added successfully!");
						}
					}else if ($_POST["type"]=="post"){
						$r = qb_post_add($_POST["post_heading"],$_POST["post_content"]);
						if ($r==false){
							qb_warning_add(qb_error_get());
						}else{
							qb_message_add("Post added successfully!");
						}
					}else{
						qb_warning_add("Failed to add content.");
					}
				}else if ($_GET["a"]=="edit"){
					if ($id > -1){
						$new_content = array();
						$new_content["heading"] = $_POST["post_heading"];
						$new_content["content"] = $_POST["post_content"];
						//update it
						$r = qb_content_update($id,$new_content);
						if ($r==false){
							qb_warning_add(qb_error_get());
						}else{
							qb_message_add("Content updated successfully!");
						}
					}
				}
			}
		}
		//echo post editor
		echo_dashboard_editor($id);
	}else if ($_GET["p"]=="pages"){
		//check if has to delete
		if (array_key_exists("a",$_GET) && array_key_exists("id",$_GET)){
			$r = qb_content_remove(intval($_GET["id"]));
			if ($r==false){
				qb_warning_add("Failed to remove content<br>".qb_error_get());
			}else{
				qb_message_add("Content removed successfully");
			}
		}
		echo_dashboard_pages();
	}else if ($_GET["p"]=="posts"){
		//check if has to delete
		if (array_key_exists("a",$_GET) && array_key_exists("id",$_GET)){
			$r = qb_content_remove(intval($_GET["id"]));
			if ($r==false){
				qb_warning_add("Failed to remove content<br>".qb_error_get());
			}else{
				qb_message_add("Content removed successfully");
			}
		}
		echo_dashboard_posts();
	}else if ($_GET["p"]=="settings"){
		//check if has to update
		if (array_key_exists("new_title",$_POST) && array_key_exists("new_tagline",$_POST)){
			//has to update settings
			if (qb_setting_modify("title",$_POST["new_title"]) == false){
				qb_warning_add("Failed to modify settings<br>".qb_error_get());
			}else if (qb_setting_modify("tagline",$_POST["new_tagline"]) == false){
				qb_warning_add("Failed to modify settings<br>".qb_error_get());
			}else{
				qb_message_add("Settings updated successfully");
			}
		}
		echo_dashboard_settings();
	}else if ($_GET["p"]=="delete" && array_key_exists("id",$_GET)){
		echo_dashboard_delete(intval($_GET["id"]));
	}else{
		$title = qb_setting_get("title");
		$content = '<content><header>An error occurred :(</header><hr>'.
			'The GET query is invalid, please try again</content>';
		echo_dashboard($title, $content);
	}
}else{
	//echo dashboard home (AKA posts)
	echo_dashboard_posts();
}
//do actions specified in $_GET
//page/post delete

?>
