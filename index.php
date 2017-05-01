<?
include_once("qblog.php");
include_once("qb_users.php");
include_once("qb_content.php");
include_once("qb_templates.php");
/*include_once("qb_pages.php");
include_once("qb_posts.php");*/
session_start();
qb_connect();
$addr = qb_addr_get();
$current_user = false;
//check if logged in
if (array_key_exists("uid",$_SESSION)){
	$current_user = qb_user_get($_SESSION["uid"]);
}
//check if login was attempted
if (array_key_exists("login_username",$_POST) && array_key_exists("login_password",$_POST)){
	//login was attempted
	//check if there was an previous attempt
	if (array_key_exists("login_attempts_rem",$_SESSION)==false){
		$_SESSION["login_attempts_rem"] = 3;
	}
	if ($_SESSION["login_attempts_rem"]>0){
		$_SESSION["login_attempts_rem"] -= 1;
		//verify username & password
		$username = $_POST["login_username"];
		$password = $_POST["login_password"];
		$uid = qb_login_verify($username, $password);
		if ($uid===false || $uid === 0){
			qb_message_add("Login failed");
		}else{
			$_SESSION["uid"] = $uid;
			qb_message_add("Login successful");
		}
	}else{
		qb_warning_add("You have used all login attempts...");
	}
	header("Location: ".$addr);
	die("Redirecting to index page");
}
//check if log out was requested
if (array_key_exists("a",$_GET)){
	if ($_GET["a"]=="logout"){
		session_unset();
		header("Location: ".$addr);
		die("Redirecting to index page");
	}
}//set all the vars
template_var_add("%title%", qb_setting_get("title"));
template_var_add("%tagline%", qb_setting_get("tagline"));
template_var_add("%content%", qb_setting_get(""));
template_var_add("%offset%", qb_setting_get(""));
template_var_add("%aside%", qb_setting_get("aside_content"));
template_var_add("%addr%", $addr);
if ($current_user === false){
	template_open_as_var("%members_area%","login_form");
}else{
	template_open_as_var("%members_area%", "members_area");
}
//get the nav pages
$nav = "";
$pages = qb_page_list_all();
$count = count($pages)-1;
for ($i = 0; $i < $count; $i ++){
	template_var_add("%heading%", $pages[$i]["heading"]);
	template_var_add("%id%", $pages[$i]["id"]);
	$nav .= template_open("index_nav_page");
}
template_var_add("%nav_pages%", $nav);
//echo page contents:
if (array_key_exists("con",$_GET)){
	//echo a specific page/post
	$con = qb_content_get(intval($_GET["con"]));
	template_var_add("%content%", $con["content"]);
	template_var_add("%heading%", $con["heading"]);
	template_open_as_var("%content%", "index_content");
}else{
	//echo the blog's home (i.e show the posts)
	if (qb_post_count() == 0){
		qb_message_add("No posts found.");
	}else{
		$echo_offset = false;
		$offset_next = false;
		$offset_prev = false;
		$offset = 0;
		if (array_key_exists("offset", $_GET)){
			$offset = intval($_GET["offset"]);
		}
		if ($offset > 0){
			$offset_prev = true;
			$echo_offset = true;
		}
		//echo posts
		$posts = qb_post_list($offset*10, 10, true);
		$content = "";
		//echo them all!
		$count = count($posts)-1;
		for ($i = 0; $i < $count; $i++){
			template_var_add("%heading%",$posts[$i]["heading"]);
			template_var_add("%content%",$posts[$i]["content"]);
			template_var_add("%id%", $posts[$i]["id"]);
			$content .= template_open("index_post");
		}
		//put content in var
		template_var_add("%content%", $content);
		$content = "";//free memory?
		
		//check if has to echo the "offset navigator" or whatever it is
		if (qb_post_count() > ($offset*10) + 10){
			$offset_next = true;
			$echo_offset = true;
		}
		
		//check if has to echo the offset-navigator
		if ($echo_offset){
			if ($offset_next){
				template_var_add("%addr_next%", $addr."/index.php?offset=".strval($offset+1));
			}else{
				template_var_add("%addr_next%", $addr."/index.php?offset=".strval($offset));
			}
			if ($offset_prev){
				template_var_add("%addr_prev%", $addr."/index.php?offset=".strval($offset-1));
			}else{
				template_var_add("%addr_prev%", $addr."/index.php?offset=".strval($offset));
			}
			template_open_as_var("%offset%", "index_offset");
		}else{
			template_var_add("%offset%", "");
		}
	}
	
}
//finally echo it!
template_echo("index");

?>
