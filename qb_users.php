<?php
include_once("qblog.php");//this is the base

function qb_user_get($uid){
	$conn = qb_conn_get();
	
	$query = "SELECT username, type FROM users WHERE id=".qb_str_process(strval($uid));
	$res = $conn->query($query);
	if ($res){
		if ($res->num_rows>0){
			return $res->fetch_assoc();
		}else{
			qb_error_set("User does not exist");
			return false;
		}
	}
}

function qb_user_add($username, $password, $type){
	$username = qb_str_process(strip_tags($username));
	$name = qb_str_process(strip_tags($name));
	$password = qb_str_process(password_hash($password, PASSWORD_DEFAULT));
	$type = qb_str_process($type);
	
	$conn = qb_conn_get();
	//First check if username is available
	$ret = null;
	$query = "SELECT id FROM users WHERE username='".$username."'";
	$res = $conn->query($query);
	if ($res && $res->num_rows>0){
		qb_error_set("username not available");
		return false;
	}else{
		$query = "INSERT INTO users(username, password, type) VALUES('".$username."','".
			$password."','".$type."')";
		$res = $conn->query($query);
		if ($res==false){
			$error = "Failed to add new user";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}
	}
	return true;
}

function qb_users_get_list($offset, $count){
	$conn = qb_conn_get();
	
	$query = "SELECT id, username, type FROM users LIMIT ".qb_str_process(strval($count)).
		" OFFSET ".qb_str_process(strval($offset));
	$res = $conn->query($query);
	$r = false;
	if ($res && $res->num_rows>0){
		$i = 0;
		$r = array_fill(0,$res->num_rows, null);
		while ($r[$i] = $res->fetch_assoc()){
			$i++;
		}
	}
	return $r;
}

//this is not for password
function qb_user_update($uid, $user){//only intended for admins
	$conn = qb_conn_get();
	//$user is assoc_array with 'type' & 'username'. 
	$uid_str = qb_str_process(strval($uid));
	$current_user = qb_user_get($_SESSION["uid"]);
	
	//if user is admin, let 'em update the username and type
	if ($current_user["type"]=="admin" && array_key_exists("username",$user)){
		$user["username"] = qb_str_process($user["username"]);
		$user["type"] = qb_str_process($user["type"]);
		$query = "UPDATE users SET username='".$user["username"]."', type='".$user["type"].
			"' WHERE id=".$uid_str;
		if ($conn->query($query)==false){
			$error = "Failed to edit user";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}
	}else{
		qb_error_set("usernames can only be changed by admins.");
	}
	return true;
}

function qb_user_update_password($uid, $password){
	$conn = qb_conn_get();
	
	$password = qb_str_process(password_hash($password, PASSWORD_DEFAULT));
	$uid_str = qb_str_process(strval($uid));
	$query = "UPDATE users SET password='".$password."' WHERE id=".$uid_str;
	
	$current_user = qb_user_get($_SESSION["uid"]);
	//only account holder & admin can chang passwd
	if ($uid == $_SESSION["uid"] || $current_user["type"] == "admin"){
		if ($conn->query($query)==false){
			$error = "Failed to update password";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}
	}else{
		$error = "This account's password cannot be modified";
		qb_error_set($error);
		return false;
	}
	return true;
}

function qb_user_remove($uid){
	$conn = qb_conn_get();
	
	$user = qb_user_get($_SESSION["uid"]);
	if ($user["type"]=="admin"){
		$query = "DELETE FROM users WHERE id=".qb_str_process(strval($uid));
		if (!$conn->query($query)){
			$error = "Failed to remove user";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}
	}
	return true;
}

function qb_user_count(){
	$conn = qb_conn_get();
	
	$res = $conn->query("SELECT count(*) FROM users");
	if ($res){
		$nRows = $res->fetch_assoc()["count(*)"];
	}else{
		$nRows = 0;
	}
	return $nRows;
}

?>
