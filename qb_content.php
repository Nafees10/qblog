<?php
include_once("qblog.php");//base of qblog
include_once("qb_users.php");//used by add_post

class Content{
	private $content_id, $content_heading, $content_content, $content_type;
	
	/// loads a content from database using the id
	/// $id is the id of the content
	/// returns true on success, false on error
	public function load($id){
		$conn = qb_conn_get();
		
		$query = "SELECT heading, content, type FROM content WHERE id=".qb_str_process(strval($id));
		$res = $conn->query($query);
		
		if ($res){
			if ($res->num_rows>0){
				$c = $res->fetch_assoc;
				$this->content_id = $id;
				$this->content_heading = $c["heading"];
				$this->content_content = $c["content"];
				$this->content_type = $c["type"];
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
		$heading = qb_str_process($this->content_heading);
		$content = qb_str_process($this->content_content);
		$type = qb_str_process($this->content_type);
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
	
	/// inserts this Content to the database, and changes the stored id to the id in the database
	/// the id set before inserting is not considered
	/// returns true if sucessful, false if not
	public function insert(){
		$conn = qb_conn_get();
		$heading = qb_str_process($this->content_heading);
		$content = qb_str_process($this->content_content);
		$type = qb_str_process($this->content_type);
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
	/// returns true on success, false if not
	public static function remove($id){
		$conn = qb_conn_get();
		$query = "DELETE FROM content WHERE id=".qb_str_process(strval($id));
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
				$r[$i] = new Content;
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
		if ($var == "id"){
			$this->content_id = $val;
		}else if ($var == "heading"){
			$this->content_heading = $val;
		}else if ($var == "content"){
			$this->content_content = $val;
		}else if ($var == "type"){
			$this->content_type = $val;
			// make sure val is either post or page
			if ($this->content_type != "post" && $this->content_type != "page"){
				$this->content_type = "post";
			}
		}else{
			die('variable "'.$var.'" does not exist');
		}
	}
	
	public function __get($var){
		if ($var == "id"){
			return $this->content_id;
		}else if ($var == "heading"){
			return $this->content_heading;
		}else if ($var == "content"){
			return $this->content_content;
		}else if ($var == "type"){
			return $this->content_type;
		}else{
			die('variable "'.$var.'" does not exist');
		}
	}
}

?>

