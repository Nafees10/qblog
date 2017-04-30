<?php
include_once("qblog.php");
include_once("qb_posts.php");
include_once("qb_pages.php");
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
$echo_offset = false;
$offset_next = false;
$offset_prev = false;
$offset = 0;
if (array_key_exists("offset", $_GET)){
	$offset = intval($_GET["offset"]);
}
if ($offset > 0){
	$offset_prev = true;
}
template_var_add("%title%", qb_setting_get("title"));
template_var_add("%tagline%", qb_setting_get("tagline"));
template_var_add("%addr%", $addr);
//Now code to deal with dashboard home, post editor, and settings editor
if (array_key_exists("p",$_GET)){
	//set all the vars
	//now check whether to open post editor, settings, posts, or pages
	if ($_GET["p"]=="editor"){
		//check if has to edit
		$id = -1;
		if (array_key_exists("id",$_GET)){
			$id = intval($_GET["id"]);
		}
		//mark post and page as unchecked
		template_var_add("%post_checked%", " ");
		template_var_add("%page_checked%", " ");
		
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
						//mark page as checked
						template_var_add("%page_checked%", " checked");
					}else if ($_POST["type"]=="post"){
						$r = qb_post_add($_POST["post_heading"],$_POST["post_content"]);
						if ($r==false){
							qb_warning_add(qb_error_get());
						}else{
							qb_message_add("Post added successfully!");
						}
						//mark post as checked
						template_var_add("%post_checked%", " checked");
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
		}else{
			template_var_add("%post_checked%", " checked");
		}
		//echo the post/content editor
		//check if there was content, if yes, show that
		template_var_add("%id%", $id);
		if ($id >= 0){
			$con = qb_content_get($id);
			template_var_add("%action%", "edit");
			template_var_add("%heading%", $con["heading"]);
			template_var_add("%content%", $con["content"]);
		}else{
			template_var_add("%action%", "new");
			template_var_add("%heading%", "");
			template_var_add("%content%", "");
		}
		template_open_as_var("%content%", "dashboard_editor");
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
		//echo em
		$pages = qb_page_list($offset*20, 20);
		$count = count($pages)-1;
		$table = "";
		for ($i = 0; $i < $count; $i ++){
			template_var_add("%id%", $pages[$i]["id"]);
			template_var_add("%heading%", $pages[$i]["heading"]);
			$table .= template_open("dashboard_page");
		}
		template_var_add("%pages%", $table);
		template_open_as_var("%content%", "dashboard_pages");
		//now for the offset nav...
		if (qb_page_count() > ($offset*20) + 20){
			$offset_next = true;
			$echo_offset = true;
		}
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
		//echo em
		$posts = qb_post_list($offset*20, 20);
		$count = count($posts)-1;
		$table = "";
		for ($i = 0; $i < $count; $i ++){
			template_var_add("%id%", $posts[$i]["id"]);
			template_var_add("%heading%", $posts[$i]["heading"]);
			$table .= template_open("dashboard_post");
		}
		template_var_add("%posts%", $table);
		template_open_as_var("%content%", "dashboard_posts");
		//now for the offset nav...
		if (qb_page_count() > ($offset*20) + 20){
			$offset_next = true;
			$echo_offset = true;
		}
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
		template_open_as_var("%content%", "dashboard_settings");
	}else if ($_GET["p"]=="delete" && array_key_exists("id",$_GET)){
		$con = qb_content_get(intval($_GET["id"]));
		template_var_add("%content_type%", $con["type"].'s');
		template_var_add("%header%", $con["heading"]);
		template_var_add("%id%", $_GET["id"]);
		
		template_open_as_var("%content%", "dashboard_delete_confirm");
	}else{
		template_var_add("%content%", '<content><header>An error occurred :(</header><hr>'.
			'The GET query is invalid. Try opening another page...</content>');
	}
}else{
	//check if has to delete
		if (array_key_exists("a",$_GET) && array_key_exists("id",$_GET)){
			$r = qb_content_remove(intval($_GET["id"]));
			if ($r==false){
				qb_warning_add("Failed to remove content<br>".qb_error_get());
			}else{
				qb_message_add("Content removed successfully");
			}
		}
		//echo em
		$posts = qb_post_list($offset*20, 20);
		$count = count($posts)-1;
		$table = "";
		for ($i = 0; $i < $count; $i ++){
			template_var_add("%id%", $posts[$i]["id"]);
			template_var_add("%heading%", $posts[$i]["heading"]);
			$table .= template_open("dashboard_post");
		}
		template_var_add("%posts%", $table);
		template_open_as_var("%content%", "dashboard_posts");
		//now for the offset nav...
		if (qb_page_count() > ($offset*20) + 20){
			$offset_next = true;
			$echo_offset = true;
		}
}
//offset nav
if ($echo_offset){
	if ($offset_next){
		template_var_add("%addr_next%", $addr."/index.php?p=".$_GET["p"]."&offset=".strval($offset+1));
	}else{
		template_var_add("%addr_next%", $addr."/index.php?p=".$_GET["p"]."&offset=".strval($offset));
	}
	if ($offset_prev){
		template_var_add("%addr_prev%", $addr."/index.php?p=".$_GET["p"]."&offset=".strval($offset-1));
	}else{
		template_var_add("%addr_prev%", $addr."/index.php?p=".$_GET["p"]."&offset=".strval($offset));
	}
	template_open_var("%offset%", "dashboard_offset");
}else{
	template_var_add("%offset%", "");
}
template_echo("dashboard");

?>
