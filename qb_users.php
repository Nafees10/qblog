<?php
include_once("qblog.php");//this is the base

class User{
	private $user_username, $user_id, $user_type, $user_passhash;
	
	/// Loads the user with $id from database to this class
	/// returns true on succcess, false on error
	public function load($id){
		$conn = qb_conn_get();
		
		$query = "SELECT * FROM users WHERE id=".qb_str_process(strval($id));
		$res = $conn->query($query);
		if ($res){
			if ($res->num_rows > 0){
				$r = $res->fetch_assoc();
				$this->user_username = $r["username"];
				$this->user_passhash = $r["password"];
				$this->user_type = $r["type"];
				$this->user_id = $r["id"];
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
		
		// check if username is already used
		if (User::get_user_id($this->user_username) >= 0){
			qb_error_set("Username is already in use");
			return false;
		}else{
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
	}
	
	/// inserts this user into the database
	/// the id set before inserting is not considered
	/// the id is changed to the actual id that was stored in database
	public function insert(){
		// check if username is already used
		if (User::get_user_id($this->user_username) !== false){
			qb_error_set("Username is already in use");
			return false;
		}else{
			$conn = qb_conn_get();
			
			$username = qb_str_process($this->user_username);
			$passhash = qb_str_process($this->user_passhash);
			$type = qb_str_process($this->user_type);
			$query = "INSERT INTO users(username, password, type) VALUES('".$username."','".$passhash."','".$type."')";
			
			if ($conn->query($query)){
				// set id
				$this->user_id = $conn->insert_id;
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
	public static function user_list($type = "all", $offset = 0, $count = 0){
		$conn = qb_conn_get();
		// generate query
		$query = "SELECT * FROM users ";
		// check type
		if ($type != "all"){
			$query .= "WHERE type='".qb_str_process($type)."' ";
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
			$nRows = $res->num_rows;
			$r = array_pad([], $nRows, null);
			if ($nRows>0){
				$i = 0;
				while ($i < $nRows){
					$r[$i] = new User;
					$content = $res->fetch_assoc();
					$r[$i]->username = $content["username"];
					$r[$i]->passhash = $content["password"];
					$r[$i]->type = $content["type"];
					$r[$i]->id = $content["id"];
					$i ++;
				}
			}
			return $r;
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
		if ($type != "all"){
			$res = $conn->query("SELECT count(*) FROM users WHERE type='".qb_str_process($type)."'");
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
	
	/// Returns the user ID for a user using the username
	/// on failure, returns -1
	public static function get_user_id($username){
		$conn = qb_conn_get();
		$username = qb_str_process($username);
		
		$query = "SELECT id FROM users WHERE username='".$username."'";
		$res = $conn->query($query);
		if ($res){
			if ($res->num_rows > 0){
				return $res->fetch_assoc()["id"];
			}else{
				qb_error_set("User not found");
				return false;
			}
		}else{
			$error = "Failed to get user_id";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}
	}
	
	public function __set($var, $val){
		if ($var == "username"){
			// check if validation is successful
			if (qb_username_validate($val)){
				$this->user_username = $val;
			}
		}else if ($var == "passhash"){
			$this->user_passhash = $val;
		}else if ($var == "password"){
			// check if validation is successful
			if (qb_password_validate($val)){
				$this->user_passhash = password_hash($val, PASSWORD_DEFAULT);
			}
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

?>
