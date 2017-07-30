<?php
include_once("qblog.php");//this is the base

class User{
	private $user_username, $user_id, $user_type, $user_passhash;
	
	/// Loads the user with $id from database to this class
	/// returns true on succcess, false on error
	public function load(){
		$conn = qb_conn_get();
		
		$query = "SELECT * FROM users WHERE id=".qb_str_process(strval($id));
		$res = $conn->query($query);
		if ($res){
			if ($res->num_rows > 0){
				$r = $res->fetch_assoc();
				$user_username = $r["username"];
				$user_passhash = $r["password"];
				$user_type = $r["type"];
				$user_id = $r["id"];
				return true;
			}else{
				qb_error_set("User not found");
				return false;
			}
		}else{
			$error = "Failed to load user";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}
	}
	
	/// updates the user in database, which has the id same as this one, with this one's propertes
	/// returns true on success & false on error
	public function update(){
		$conn = qb_conn_get();
		
		$username = qb_str_process($this->user_username);
		$passhash = qb_str_process($this->user_passhash);
		$type = qb_str_process($this->user_type);
		$id = qb_str_process(strval($this->user_id));
		
		//TODO : implement a check to make sure the same username is not used
		
		$query = "UPDATE users SET username='".$username."', password='".$passhash."', type='".$type."' WHERE id=".$id;
		
		if ($conn->query($query)==false){
			$error = "Failed to update user";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}else{
			return true;
		}
	}
	
	/// inserts this user into the database
	/// the id set before inserting is not considered
	/// the id is changed to the actual id that was stored in database
	public function insert(){
		$conn = qb_conn_get();
		
		$username = qb_str_process($this->user_username);
		$passhash = qb_str_process($this->user_passhash);
		$type = qb_str_process($this->user_type);
		
		$query = "INSERT INTO users(username, password, type) VALUES('".$username."','".$passhash."','".$type."')";
		if ($conn->query($query)){
			// set id
			$user_id = $conn->lastInsertId();
			return true;
		}else{
			$error = "Failed to insert user";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}
	}
	
	/// removes a user from database using the id
	/// returns true on success, false if not
	public static function remove($id){
		$conn = qb_conn_get();
		$query = "DELETE FROM users WHERE id=".qb_str_process(strval($id));
		if (!$conn->query($query)){
			$error = "Failed to remove user";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}else{
			return true;
		}
	}
	
	/// returns an array of User
	/// $type: if "all", all types of content are returned; if "admin" or "user", then only that type
	/// $offset is used to specify the offset (obviously). must not be a negative number
	/// $count is used to specify the number of users to return at max, if it is zero, all contents are returned, and offset it not applied
	/// returns false if fails, if successful, returns `User[]`
	public static function content_list($type = "all", $offset = 0, $count = 0){
		$conn = qb_conn_get();
		// generate query
		$query = "SELECT * FROM users WHERE ";
		// check type
		if ($type == "admin"){
			$query .= "type='admin' ";
		}else if ($type == "user"){
			$query .= "type='user' ";
		}
		// orderby
		$query .= "ORDER BY id DESC";
		// check offset & count
		if ($count > 0){
			$query .= " LIMIT ".qb_str_process(strval($count))." OFFSET ".qb_str_process(strval($offset));
		}
		/// push result in array
		$res = $conn->query($query);
		$r = false;
		if ($res){
			if ($res->num_rows>0){
				$nRows = $res->num_rows;
				$i = 0;
				$r = array_pad([], $nRows, null);
				while ($i < $nRows){
					$r[$i] = new User;
					$content = $res->fetch_assoc();
					$r[$i]->username = $content["username"];
					$r[$i]->passhash = $content["password"];
					$r[$i]->type = $content["type"];
					$r[$i]->id = $content["id"];
					$i ++;
				}
			}else{
				qb_error_set("User not found");
				return false;
			}
		}else{
			$error = "Failed to fetch users";
			if (qb_debug_get() == true){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}
		return $r;
	}
	
	/// Returns number of users in database
	/// $type, if "all": all type of content is counted, otherwise, if "admin" or "user", only that type will be counted
	/// returns number of users, otherwise; if fails, returns false
	public static function count($type = "all"){
		$conn = qb_conn_get();
		// generate query
		if ($type == "admin"){
			$res = $conn->query("SELECT count(*) FROM users WHERE type='admin'");
		}else if ($type == "user"){
			$res = $conn->query("SELECT count(*) FROM users WHERE type='user'");
		}else{
			$res = $conn->query("SELECT count(*) FROM users");
		}
		// get result
		if ($res){
			$nRows = $res->fetch_assoc()["count(*)"];
		}else{
			$nRows = 0;
			$error = "Failed to count users";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}
		return $nRows;
	}
	
	public function __set($var, $val){
		if ($var == "username"){
			$this->user_username = $val;
		}else if ($var == "passhash"){
			$this->user_passhash = $val;
		}else if ($var == "type"){
			$this->user_type == $val;
			if ($this->user_type != "admin" && $this->user_type != "user"){
				$this->user_type = "user";
			}
		}else if ($var == "id"){
			$this->user_id = $val;
		}else{
			die('variable "'.$var.'" does not exist in class User');
		}
	}
	
	public function __get($var){
		if ($var == "username"){
			return $this->user_username;
		}else if ($var == "passhash"){
			return $this->user_passhash;
		}else if ($var == "type"){
			return $this->user_type;
		}else if ($var == "id"){
			return $this->user_id;
		}
	}
}

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
