<?php
include_once("qblog.php");//base of qblog
include_once("qb_users.php");//used by add_post

class Content{
	private $content_id, $content_heading, $content_content, $content_type;
	private $modified = false;
	private $modified_id = false;
	
	/// loads a content from database using the id
	/// $id is the id of the content
	/// returns true on success, false on error
	public function load($id){
		$conn = qb_conn_get();
		
		$query = "SELECT heading, content, type FROM content WHERE id=".qb_str_process(strval($id));
		$res = $conn->query($query);
		
		$modified = false;
		if ($res){
			if ($res->num_rows>0){
				$c = $res->fetch_assoc;
				$content_id = $id;
				$content_heading = $c["heading"];
				$content_content = $c["content"];
				$content_type = $c["type"];
			}else{
				qb_error_set("Content not found");
				return false;
			}
		}
		return true;
	}
	
	/// updates the content in database, which has the id same as this one, with this one's content, heading, & type
	/// this will fail if none of the variables were changed.
	/// returns true on success & false on error
	public function update(){
		if ($modified){
			$heading = qb_str_process($content_heading);
			$content = qb_str_process($content_content);
			$type = qb_str_process($content_type);
			$id = qb_str_process(strval($content_id));
			$query = "UPDATE content SET heading='".$heading."', content='".$content."', type='".$type."' WHERE id=".$id;
			
			if ($conn->query($query)==false){
				$error = "Failed to edit content";
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
	
	/// inserts this Content to the database, and changes the id to the id in the database
	/// the id set before inserting is not considered
	/// returns true if sucessful, false if not
	public function insert(){
		$conn = qb_conn_get();
		$heading = qb_str_process($content_heading);
		$content = qb_str_process($content_content);
		$type = qb_str_process($content_type);
		$query = "INSERT INTO content(heading, content, type) VALUES('".$heading."','".$content."','".$type."')";
		
		if ($conn->query($query)==false){
			$error = "Failed to add content";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}else{
			return true;
		}
	}
	
	/// removes a content from database using the id
	public static function remove($id){
		$conn = qb_conn_get();
		$query = "DELETE FROM content WHERE id=".qb_str_process(strval($pid));
		if (!$conn->query($query)){
			$error = "Failed to remove content";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}else{
			return true;
		}
	}
	
	/// returns an array of Content
	/// $type: if "all", all types of content are returned; if "post" or "page", then only that type
	/// $offset is used to specify the offset (obviously). must not be a negative number
	/// $count is used to specify the number of Contents to return at max, if it is zero, all contents are returned, and offset it not applied
	/// returns false if fails, if successful, returns `Content[]`
	public static function content_list($type = "all", $offset = 0, $count = 0){
		$conn = qb_conn_get();
		// generate query
		$query = "SELECT * FROM content WHERE ";
		// check type
		if ($type == "post"){
			$query .= "type='post' ";
		}else if ($type == "page"){
			$query .= "type='page' ";
		}
		// check offset & count
		if ($count > 0){
			$query .= "LIMIT ".qb_str_process(strval($count))." OFFSET ".qb_str_process(strval($offset));
		}
		/// push result in array
		$res = $conn->query($query);
		$r = false;
		if ($res && $res->num_rows>0){
			$i = 0;
			$r = array_fill(0,$res->num_rows, null);
			while ($content = $res->fetch_assoc()){
				$r[$i] = new Content();
				$r[$i]->content = $content["content"];
				$r[$i]->heading = $content["heading"];
				$r[$i]->type = $content["type"];
				$r[$i]->id = $content["id"];
				$i ++;
			}
		}
		return $r;
	}
	
	/// Returns number of content
	/// $type, if "all": all type of content is counted, otherwise, if "post" or "page", only that type will be counted
	public static function count($type = "all"){
		$conn = qb_conn_get();
		// generate query
		if ($type == "page"){
			$res = $conn->query("SELECT count(*) FROM content WHERE type='page'");
		}else if ($type == "post"){
			$res = $conn->query("SELECT count(*) FROM content WHERE type='post'");
		}
		// get result
		if ($res){
			$nRows = $res->fetch_assoc()["count(*)"];
		}else{
			$nRows = 0;
		}
		return $nRows;
	}
	
	public function __set($var, $val){
		$modified = true;
		if ($var == "id"){
			$modified_id = true;
			$content_id = $val;
		}else if ($var == "heading"){
			$content_heading = $val;
		}else if ($var == "content"){
			$content_content = $val;
		}else if ($var == "type"){
			$content_type = $val;
			// make sure val is either post or page
			if ($content_type != "post" && $content_type != "page"){
				$content_type = "post";
			}
		}else{
			die('variable "'.$var.'" does not exist');
		}
	}
	
	public function __get($var){
		if ($var == "id"){
			return $content_id;
		}else if ($var == "heading"){
			return $content_heading;
		}else if ($var == "content"){
			return $content_content;
		}else if ($var == "type"){
			return $content_type;
		}else{
			die('variable "'.$var.'" does not exist');
		}
	}
}

function qb_content_list($offset, $count){
	$conn = qb_conn_get();
	$query = "SELECT id, heading, type FROM content LIMIT ".
		qb_str_process(strval($count))." OFFSET ".qb_str_process(strval($offset));
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

function qb_content_list_all(){
	$conn = qb_conn_get();
	$query = "SELECT id, heading, type FROM content";
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

function qb_content_update($pid, $content){
	$conn = qb_conn_get();
	//$content = ["content","heading"]
	$pid_str = qb_str_process(strval($pid));
	$content["content"] = qb_str_process($content["content"]);
	$content["heading"] = qb_str_process(strip_tags($content["heading"]));
	$content["type"] = qb_str_process($content["type"]);
	if ($content["type"]  != "post" && $content["type"] != "page"){
		$content["type"] = "post";
	}
	//update heading & content
	
	$user = qb_user_get($_SESSION["uid"]);
	if ($user["type"]=="admin"){
		$query = "UPDATE content SET heading='".$content["heading"].
			"', content='".$content["content"]."', type='".$content["type"]."' WHERE id=".$pid_str;
		if ($conn->query($query)==false){
			$error = "Failed to edit content";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}
	}
	return true;
}

function qb_content_remove($pid){
	$conn = qb_conn_get();
	
	$user = qb_user_get($_SESSION["uid"]);
	if ($user["type"]=="admin"/* || $user["type"]=="editor"*/){
		$query = "DELETE FROM content WHERE id=".qb_str_process(strval($pid));
		if (!$conn->query($query)){
			$error = "Failed to remove content";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}
	}
	return true;
}

function qb_content_count(){
	$conn = qb_conn_get();
	
	$res = $conn->query("SELECT count(*) FROM content");
	if ($res){
		$nRows = $res->fetch_assoc()["count(*)"];
	}else{
		$nRows = 0;
	}
	return $nRows;
}

?>

