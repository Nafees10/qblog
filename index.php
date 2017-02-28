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
if (array_key_exists("uid",$_SESSION)==true){
	$currect_user = qb_user_get($_SESSION["uid"]);
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
		if ($uid==0){
			$_SESSION["message"] = 'Login failed';
		}else{
			$_SESSION["uid"] = $uid;
			$_SESSION["message"] = 'Login successful';
		}
	}else{
		$_SESSION["warning"] = 'You\'ve used all your login attempts.';
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
//echo page contents:
if (array_key_exists("con",$_GET)){
	//echo a specific page/post
	echo_index_content($_GET["con"]);
}else{
	//echo the blog
	if (qb_post_count() == 0){
		$_SESSION["message"] = "No posts found";
	}
	$off = 0;
	if (array_key_exists("off",$_GET)){
		$off = intval($_GET["off"]);
	}
	echo_index_posts($off);
}

?>
