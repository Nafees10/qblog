<?php
include_once("qblog.php");
include_once("qb_users.php");
include_once("qb_pages.php");
include_once("qb_posts.php");
include_once("qb_content.php");

function echo_dashboard($title,$content){
	
	$message = "";
	$warning = "";
	if (array_key_exists("warning",$_SESSION)){
		$warning = $_SESSION["warning"];
		unset($_SESSION["warning"]);
		$format = file_get_contents("templates/index_warning.html");
		$warning = str_replace("%warning%",$warning,$format);
	}
	if (array_key_exists("message",$_SESSION)){
		$message = $_SESSION["message"];
		unset($_SESSION["message"]);
		$format = file_get_contents("templates/index_message.html");
		$message = str_replace("%message%",$message,$format);
	}
	
	$dash = file_get_contents("templates/dashboard.html");
	$search = array("%title%","%addr%","%content%","%message%","%warning%");
	$replace = array($title, qb_addr_get(), $content, $message, $warning);
	echo str_replace($search, $replace, $dash);
}

function echo_dashboard_index(){
	$title = qb_setting_get("title");
	$content = file_get_contents("templates/dashboard_index.html");
	//replace stuff
	echo_dashboard($title,$content);
}

function echo_dashboard_pages(){
	$title = qb_setting_get("title");
	$pages_list = "";
	$pages_dat = qb_page_list_all();
	$pages_format = file_get_contents("templates/dashboard_page.html");
	$search = array("%id%","%heading%","%addr%");
	$count = count($pages_dat)-1;
	for ($i = 0; $i < $count; $i++){
		$pages_list .= str_replace($search, array($pages_dat[$i]["id"],$pages_dat[$i]["heading"],
			qb_addr_get()),$pages_format);
	}
	//put pages list in pages table
	$pages_table = file_get_contents("templates/dashboard_pages.html");
	$search = array("%addr%","%pages%");
	$replace = array(qb_addr_get(),$pages_list);
	//echo it
	echo_dashboard($title, str_replace($search, $replace, $pages_table));
}

function echo_dashboard_posts(){
	$title = qb_setting_get("title");
	$posts_list = "";
	$posts_dat = qb_post_list_all();
	$posts_format = file_get_contents("templates/dashboard_post.html");
	$search = array("%id%","%heading%","%addr%");
	$count = count($posts_dat)-1;
	for ($i = 0; $i < $count; $i++){
		$posts_list .= str_replace($search, array($posts_dat[$i]["id"],$posts_dat[$i]["heading"],
			qb_addr_get()),$posts_format);
	}
	//put posts list in posts table
	$posts_table = file_get_contents("templates/dashboard_posts.html");
	$search = array("%addr%","%posts%");
	$replace = array(qb_addr_get(),$posts_list);
	//echo it
	echo_dashboard($title, str_replace($search, $replace, $posts_table));
}

function echo_dashboard_editor($id){//$id = -1 to create new content
	$title = qb_setting_get("title");
	$content = "";
	$heading = "";
	$action = "new";
	if ($id>=0){
		$con = qb_content_get($id);
		$heading = $con["heading"];
		$content = $con["content"];
		$action = "edit";
	}
	$id = strval($id);
	
	
	$format = file_get_contents("templates/dashboard_editor.html");
	$search = array("%id%","%action%","%heading%","%content%","%addr%");
	$replace = array($id, $action, $heading, $content, qb_addr_get());
	echo_dashboard($title, str_replace($search, $replace, $format));
}

function echo_dashboard_settings(){
	$title = qb_setting_get("title");
	$tagline = qb_setting_get("tagline");
	
	$format = file_get_contents("templates/dashboard_settings.html");
	$search = array("%title%","%tagline%");
	$replace = array($title, $tagline);
	echo_dashboard($title,str_replace($search, $replace, $format));
}

//now for main index page
function echo_index_base($addr,$content,$off=""){
	$title = qb_setting_get("title");
	$tagline = qb_setting_get("tagline");
	$aside = qb_setting_get("aside_content");
	$message = "";
	$warning = "";
	if (array_key_exists("warning",$_SESSION)){
		$warning = $_SESSION["warning"];
		unset($_SESSION["warning"]);
		$format = file_get_contents("templates/index_warning.html");
		$warning = str_replace("%warning%",$warning,$format);
	}
	if (array_key_exists("message",$_SESSION)){
		$message = $_SESSION["message"];
		unset($_SESSION["message"]);
		$format = file_get_contents("templates/index_message.html");
		$message = str_replace("%message%",$message,$format);
	}
	//now to get $mem_area and $nav
	//if logged in, as admin/editor, then echo members_area.html, else login_form.html
	$mem_area = file_get_contents("templates/login_form.html");
	if (array_key_exists("uid",$_SESSION)){
		$cur_user = qb_user_get($_SESSION["uid"]);
		if ($cur_user["type"]=="admin"){
			$mem_area = file_get_contents("templates/members_area.html");
			//replace vars
			$mem_area = str_replace("%addr%",$addr,$mem_area);
		}
	}
	//now get $nav
	$nav_format = file_get_contents("templates/nav_page.html");
	$nav = "";
	$pages = qb_page_list_all();
	$count = count($pages)-1;
	$search = array("%addr%","%id%","%heading%");
	for ($i = 0; $i < $count; $i++){
		$nav .= str_replace($search,array($addr,$pages[$i]["id"],$pages[$i]["heading"]),
			$nav_format);
	}
	
	$format = file_get_contents("templates/index.html");
	$search = array("%title%","%tagline%","%addr%","%nav_pages%","%content%","%aside%",
		"%members_area%","%message%","%warning%","%offset%");
	$replace = array($title,$tagline,$addr,$nav,$content,$aside,$mem_area,$message,$warning,$off);
	echo str_replace($search, $replace, $format);
}

function echo_index_content($id){
	$con = qb_content_get($id);
	$content = $con["content"];
	$heading = $con["heading"];
	$addr = qb_addr_get();
	$id = strval($id);
	
	$format = file_get_contents("templates/index_content.html");
	$search = array("%addr%","%id%","%heading%","%content%");
	$replace = array($addr,$id,$heading,$content);
	$content = str_replace($search,$replace,$format);
	echo_index_base($addr,$content);
}

function echo_index_posts($offset){
	$new_offset = $offset+1;
	$addr = qb_addr_get();
	$post_format = file_get_contents("templates/index_post.html");
	$posts = qb_post_list($offset*20,20,true);
	$count = count($posts)-1;
	$search = array("%addr%","%id%","%heading%","%content%");
	$content = "";
	for ($i = 0; $i < $count; $i++){
		$content .= str_replace($search,array($addr,$posts[$i]["id"],$posts[$i]["heading"],
			$posts[$i]["content"]),$post_format);
	}
	//check if there are more posts, if yes, echo the offset_previous button too
	$off = "";
	if (qb_post_count() > $new_offset*20){
		$off = file_get_contents("templates/index_offset_prev.html");
		$search = array("%addr%","%new_offset%");
		$replace = array($addr,strval($new_offset));
		$off = str_replace($search,$replace,$off);
	}
	if ($offset > 0){
		$format = file_get_contents("templates/index_offset_next.html");
		$search = array("%addr%","%new_offset%");
		$new_offset = $new_offset - 1;
		if ($new_offset < 0){
			$new_offset = 0;
		}
		$replace = array($addr,strval($new_offset));
		$off .= str_replace($search,$replace,$format);
	}
	echo_index_base($addr,$content,$off);
}

?>
