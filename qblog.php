<?php
include_once ("qb_pages.php");
include_once ("qb_users.php");
include_once ("qb_posts.php");

$qb_conn = null;
$qb_debug = false;
$qb_error = null;

if ($_SERVER["REQUEST_URI"][strlen($_SERVER["REQUEST_URI"])-1] == '/'){
	$qb_site_addr = "http://".$_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
	$qb_site_addr = substr($qb_site_addr, 0, strlen($qb_site_addr)-1);
}else{
	$qb_site_addr = "http://".$_SERVER["HTTP_HOST"] . dirname($_SERVER["REQUEST_URI"]);
}

function qb_addr_get(){
	global $qb_site_addr;
	return $qb_site_addr;
}

function qb_error_get(){
	global $qb_error;
	return $qb_error;
}

function qb_error_set($er){
	global $qb_error;
	$qb_error = $er;
	return $er;
}

function qb_debug_set($onOff){
	global $qb_debug;
	$qb_debug = $onOff;
	return $onOff;
}

function qb_debug_get(){
	global $qb_debug;
	return $qb_debug;
}

function qb_message_add($msg){
	if (array_key_exists("message", $_SESSION)){
		array_push($_SESSION["message"], $msg);
	}else{
		$_SESSION["message"] = array($msg);
	}
}

function qb_warning_add($msg){
	if (array_key_exists("warning", $_SESSION)){
		array_push($_SESSION["warning"], $msg);
	}else{
		$_SESSION["warning"] = array($msg);
	}
}

function qb_message_get(){
	if (array_key_exists("message", $_SESSION)){
		$r = $_SESSION["message"];
		unset($_SESSION["message"]);
		return $r;
	}else{
		return false;
	}
}

function qb_warning_get(){
	if (array_key_exists("warning", $_SESSION)){
		$r = $_SESSION["warning"];
		unset($_SESSION["warning"]);
		return $r;
	}else{
		return false;
	}
}

function qb_conn_get(){
	global $qb_conn;
	return $qb_conn;
}

function qb_connect(){
	global $qb_conn, $qb_debug;
	$servername = "localhost";
	$username = "qsahab";
	$password = "12245589";
	$db_name = "qblog_db";
	$qb_conn = new mysqli($servername, $username, $password, $db_name);
	//Check it
	if ($qb_conn->connect_error){
		$qb_error = "Connection to database failed.";
		if ($qb_debug){
			$qb_error .= $qb_conn->connect_error;
		}
		return false;
	}
	return true;
}

function qb_setup_db(){
	global $qb_conn, $qb_debug, $qb_error;
	
	//set it up
	//posts/pages table
	$query = "CREATE TABLE content (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,".
		"heading TEXT NOT NULL,".
		"content TEXT,".
		"type ENUM('post','page') DEFAULT 'post')";
	if ($qb_conn->query($query)!=true){
		$qb_error = "Failed to setup database; ";
		if ($qb_debug){
			$qb_error .= $qb_conn->error;
		}
		return false;
	}
	//users table
	$query = "CREATE TABLE users (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,".
		"username TEXT NOT NULL,".
		"password TEXT NOT NULL,".
		"type ENUM('admin','user') DEFAULT 'user')";
	if ($qb_conn->query($query)!=true){
		$qb_error = "Failed to setup database; ";
		if ($qb_debug){
			$qb_error .= $qb_conn->error;
		}
		return false;
	}
	//qblog settings
	$query = "CREATE TABLE settings (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,".
		"name TEXT NOT NULL,".
		"value TEXT)";
	if ($qb_conn->query($query)==false){
		$qb_error = "Failed to setup database; ";
		if ($qb_debug){
			$qb_error .= $qb_conn->error;
		}
		return false;
	}
	return true;
}

function qb_str_process($str){
	$search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
	$replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");

	return str_replace($search, $replace, $str);
}

//QBlog settings
function qb_setting_get($setting){
	global $qb_conn, $qb_error, $qb_debug;
	
	$r = false;
	$query = "SELECT value FROM settings WHERE name='".qb_str_process($setting)."'";
	$res = $qb_conn->query($query);
	if ($res && $res->num_rows==1){
		$r = $res->fetch_assoc()["value"];
	}else{
		$qb_error = "Failed to retreive setting";
		if ($qb_debug){
			$qb_error .= "; \n".$qb_conn->error;
		}
		return false;
	}
	return $r;
}

function qb_setting_add($setting, $value){
	global $qb_conn, $qb_error, $qb_debug;
	
	$query = "INSERT INTO settings (name, value) VALUES('".qb_str_process($setting)."','".
		qb_str_process($value)."')";
	if (!$qb_conn->query($query)){
		$qb_error = "Failed to add new setting";
		if ($qb_debug){
			$qb_error .= "; \n".$qb_conn->error;
		}
		return false;
	}
	return true;
}

function qb_setting_modify($setting, $value){
	global $qb_conn, $qb_error, $qb_debug;
	
	$query = "UPDATE settings SET value='".qb_str_process($value)."' WHERE name='".
		qb_str_process($setting)."'";
	if (!$qb_conn->query($query)){
		$qb_error = "Failed to modify new setting";
		if ($qb_debug){
			$qb_error .= "; \n".$qb_conn->error;
		}
		return false;
	}
	return true;
}


//For login verification & validation
function qb_username_validate($username){
	global $qb_error;
	$len = strlen($username);
	if ($len<4){
		$qb_error = "username must be at least 4 characters long.";
		return false;
	}
	if ($len>16){
		$qb_error = "username cannot be more than 16 characters.";
		return false;
	}
	//now scan it
	for ($i = 0;$i<$len;$i++){
		if (stripos("qwertyuiopasdfghjklzxcvbnm1234567890_",$username[$i])==false){
			$qb_error = "username can only contain alphabets, numbers, and underscore.";
			return false;
		}
	}
	//no errors found till now, so:
	return true;
}

function qb_password_validate($password){
	global $qb_error;
	$len = strlen($password);
	if ($len<8){
		$qb_error = "password must be at least 8 characters.";
		return false;
	}
	if ($len>40){
		$qb_error = "password cannot contain more than 40 characters.";
		return false;
	}
	return true;
}

function qb_login_verify($username, $password){
	global $qb_conn, $qb_debug, $qb_error;
	$username = qb_str_process($username);
	$query = "SELECT id, password FROM users WHERE username='".$username."'";
	$res = $qb_conn->query($query);
	if ($res && $res->num_rows==1){
		$row = $res->fetch_assoc();
		$hash = $row["password"];
		if (password_verify($password, $hash)){
			return $row["id"];
		}else{
			return false;
		}
	}
	return 0;
}

?>
