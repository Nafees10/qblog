<?php
include_once("qblog.php");
include_once("qb_users.php");
include_once("qb_content.php");
include_once("qb_templates.php");
qb_connect();
session_start();
$addr = qb_addr_get();
//is logged in?
if (array_key_exists("uid",$_SESSION)==false){
	header("Location: ".$addr);
	qb_message_add("You must be logged in to access dashboard.<br>Redirecting to index page...");
	die ("You must be logged in to access dashboard.<br>Redirecting to index page...");
}
$current_user = new User;
if ($current_user->load($_SESSION["uid"]) === false){
	qb_message_add("Your login was invalid. You have been logged out.");
	header("Location: ".$addr);
	unset($_SESSION["uid"]);
	die ("Your login was invalid. Redirecting...");
}
// check if is admin
if ($current_user->type!="admin"){
	qb_warning_add('Your account is not authorized to access dashboard.');
	header("Location: ".$addr);
	die ("Your account is not authorized to access dashboard.");
}
/*$_GET arguments:
 * p - to specify what to open, post 'editor', 'pages'_list, 'posts'_list, or 'settings'
 * a - used to specify action, like a=delete, a=new...
 * Post editor arguments:
 * id - if specified or greater than -1, then you're editing, else, creating new content
 * type - type of content, only considered if new content is being written, not for editing
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
	//check whether to open post editor, settings, posts, or pages
	if ($_GET["p"]=="editor"){
		//check if has to edit
		$id = -1;
		if (array_key_exists("id",$_GET)){
			$id = intval($_GET["id"]);
		}
		//mark post and page as unchecked
		template_var_add("%post_checked%", " ");
		template_var_add("%page_checked%", " ");
		
		template_var_add("%heading%", "");
		template_var_add("%content%", "");
		
		if (array_key_exists("a",$_GET) && ($_GET["a"] == "new" || $_GET["a"] == "edit")){
			//check if it was a form submission
			if (array_key_exists("post_heading",$_POST) && array_key_exists("post_content",$_POST) && array_key_exists("post_type",$_POST)){
				$content = new Content();
				//edit/post it
				if ($_GET["a"]=="new"){
					$content->heading = $_POST["post_heading"];
					$content->content = $_POST["post_content"];
					$content->type = $_POST["post_type"];
					// insert, and check if successful
					if ($content->insert()){
						qb_message_add("Content added successfully!");
					}else{
						qb_warning_add(qb_error_get());
					}
				}else if ($_GET["a"]=="edit"){
					if ($id > -1){
						$content->heading = $_POST["post_heading"];
						$content->content = $_POST["post_content"];
						$content->type = $_POST["type"];
						if ($content->update()){
							qb_message_add("Content updated successfully!");
						}else{
							qb_warning_add(qb_error_get());
						}
					}
				}
				template_var_add("%heading%", $content->heading);
				template_var_add("%content%", $content->content);
				if ($content->type == "post"){
					template_var_add("%post_checked%", " checked");
				}else{
					template_var_add("%page_checked%", " checked");
				}
				unset($content);
			}
			template_var_add("%action%", "edit");
		}else{
			template_var_add("%action%", "new");
			template_var_add("%heading%", "");
			template_var_add("%content%", "");
			// if there content type was specified in $_GET, use it
			if (array_key_exists("type", $_GET)){
				if ($_GET["type"] == "page"){
					template_var_add("%page_checked%", " checked");
				}else if ($_GET["type"] == "post"){
					template_var_add("%post_checked%", " checked");
				}
			}else{
				// by default, post is the content type
				template_var_add("%post_checked%", " checked");
			}
		}
		template_var_add("%id%", $id);
		template_open_as_var("%content%", "dashboard_editor");
	}else if ($_GET["p"]=="pages" || $_GET["p"] == "posts"){
		//check if has to delete
		if (array_key_exists("a",$_GET) && $_GET["a"] == "delete" && array_key_exists("id", $_GET) && intval($_GET["id"]) >= 0){
			if (Content::remove(intval($_GET["id"]))){
				qb_message_add("Content removed successfully");
			}else{
				qb_warning_add("Failed to remove content<br>".qb_error_get());
			}
		}
		//echo em
		$type = "post";
		if ($_GET["p"] == "pages"){
			$type = "page";
		}
		$contents = Content::content_list($type, $offset*10, 10);
		$count = count($contents)-1;
		$table = "";
		for ($i = 0; $i < $count; $i ++){
			template_var_add("%id%", $contents[$i]->id);
			template_var_add("%heading%", $contents[$i]->heading);
			unset($contents[$i]);
			$table .= template_open("dashboard_".$type);
		}
		template_var_add("%".$type."s%", $table);
		template_open_as_var("%content%", "dashboard_".$type."s");
		//now for the offset nav...
		if (Content::count($type) > ($offset*10) + 10){
			$offset_next = true;
			$echo_offset = true;
		}else if ($offset_prev){
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
		$con = new Content;
		$con->load(intval($_GET["id"]));
		template_var_add("%content_type%", $con->type.'s');
		template_var_add("%header%", $con->heading);
		template_var_add("%id%", strval($con->id));
		
		template_open_as_var("%content%", "dashboard_delete_confirm");
	}else{
		template_var_add("%content%", '<content><header>An error occurred :(</header><hr>'.
			'The GET query is invalid. Try opening another page...</content>');
	}
}else{
	//echo em
	$posts = Content::content_list("post", $offset*10, 10);
	$count = count($posts)-1;
	$table = "";
	for ($i = 0; $i < $count; $i ++){
		template_var_add("%id%", $posts[$i]->id);
		template_var_add("%heading%", $posts[$i]->heading);
		unset($posts[$i]);
		$table .= template_open("dashboard_post");
	}
	template_var_add("%posts%", $table);
	template_open_as_var("%content%", "dashboard_posts");
	//now for the offset nav...
	if (Content::count("post") > ($offset*10) + 10){
		$offset_next = true;
		$echo_offset = true;
	}else if ($offset_prev){
		$echo_offset = true;
	}
}
//offset nav
if ($echo_offset){
	if ($offset_next){
		$_GET["offset"] = $offset+1;
		template_var_add("%addr_next%", $addr."/dashboard.php?".http_build_query($_GET));
	}else{
		$_GET["offset"] = $offset;
		template_var_add("%addr_next%", $addr."/dashboard.php?".http_build_query($_GET));
	}
	if ($offset_prev){
		$_GET["offset"] = $offset-1;
		template_var_add("%addr_prev%", $addr."/dashboard.php?".http_build_query($_GET));
	}else{
		$_GET["offset"] = $offset;
		template_var_add("%addr_prev%", $addr."/dashboard.php?".http_build_query($_GET));
	}
	template_open_as_var("%offset%", "dashboard_offset");
}else{
	template_var_add("%offset%", "");
}
template_echo("dashboard");

?>
