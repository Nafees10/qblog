<?
include_once("qblog.php");
include_once("qb_users.php");
include_once("qb_content.php");
include_once("qb_templates.php");
include_once("parsedown/Parsedown.php");
session_start();
qb_connect();
$addr = qb_addr_get();
$current_user = null;
//check if logged in
if (array_key_exists("uid",$_SESSION)){
	$current_user = new User;
	if ($current_user->load($_SESSION["uid"]) == false){
		qb_warning_add("Your login was invalid, you have been logged out");
		unset($_SESSION["uid"]);
		unset($current_user);
		$current_user = null;
	}
}
//check if login was attempted
if (array_key_exists("login_username",$_POST) && array_key_exists("login_password",$_POST)){
	//login was attempted
	//check if there was an previous attempt
	if (array_key_exists("login_attempts_rem",$_SESSION)==false){
		$_SESSION["login_attempts_rem"] = 4;
	}
	if ($_SESSION["login_attempts_rem"]>0){
		$_SESSION["login_attempts_rem"] -= 1;
		//verify username & password
		$username = $_POST["login_username"];
		$password = $_POST["login_password"];
		$uid = qb_login_verify($username, $password);
		if ($uid===false){
			qb_message_add(qb_error_get());
		}else{
			$_SESSION["uid"] = $uid;
			qb_message_add("Login successful");
			unset($_SESSION["login_attempts_rem"]);
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
}
//template init
template_init();
//set all the vars
template_var_add("%title%", htmlentities(qb_setting_get("title"), ENT_QUOTES));
template_var_add("%tagline%", htmlentities(qb_setting_get("tagline"), ENT_QUOTES));
template_var_add("%content%", "");
template_var_add("%offset%", "");
template_var_add("%aside%", "");
template_var_add("%addr%", $addr);
// open login form if not logged in, else, show links to dashboard and logout
if ($current_user === null){
	template_open_as_var("%members_area%", "login_form");
}else{
	template_open_as_var("%members_area%", "members_area");
}
//get the nav pages
$nav = "";
$pages = Content::content_list("page");
$count = count($pages);
for ($i = 0; $i < $count; $i ++){
	template_var_add("%heading%", htmlentities($pages[$i]->heading, ENT_QUOTES));
	template_var_add("%id%", $pages[$i]->id);
	unset($pages[$i]);
	$nav .= template_open("index_nav_page");
}
template_var_add("%nav_pages%", $nav);
//echo page contents:
if (array_key_exists("con",$_GET)){
	//echo a specific page/post
	$con = new Content();
	$con->load(intval($_GET["con"]));
	$parsedown = new Parsedown;
	template_var_add("%content%", $parsedown->text(htmlentities($con->content, ENT_QUOTES)));
	unset($parsedown);
	template_var_add("%heading%", htmlentities($con->heading, ENT_QUOTES));
	unset($con);
	template_open_as_var("%content%", "index_content");
}else{
	//echo the blog's home (i.e show the posts)
	$post_count = Content::count("post");
	if ($post_count == 0){
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
		$posts = Content::content_list("post", $offset*10, 10);
		// check if an error occured
		if ($posts === false){
			qb_warning_add(qb_error_get());
		}else{
			$content = "";
			//echo them all!
			$count = count($posts);
			$parsedown = new Parsedown;
			for ($i = 0; $i < $count; $i++){
				template_var_add("%heading%", htmlentities($posts[$i]->heading, ENT_QUOTES));
				template_var_add("%content%", $parsedown->text(htmlentities($posts[$i]->content, ENT_QUOTES)));
				template_var_add("%id%", $posts[$i]->id);
				//unset($posts[$i]);
				$content .= template_open("index_post");
			}
			unset($parsedown);
			//put content in var
			template_var_add("%content%", $content);
			$content = "";//free memory?
			
			//check if has to echo the "offset navigator" or whatever it is
			if ($post_count > ($offset*10) + 10){
				$offset_next = true;
				$echo_offset = true;
			}
		}
		
		//check if has to echo the offset-navigator
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
			template_open_as_var("%offset%", "index_offset");
		}else{
			template_var_add("%offset%", "");
		}
	}
	
}
//finally echo it!
template_echo("index");

?>
