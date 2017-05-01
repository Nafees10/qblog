<?php
include_once("qblog.php");//base of qblog
include_once("qb_users.php");//used by add_post

function qb_page_get($pid){
	$conn = qb_conn_get();
	
	$query = "SELECT heading, content FROM content WHERE type='page' AND id=".
		qb_str_process(strval($pid));
	$res = $conn->query($query);
	if ($res){
		if ($res->num_rows>0){
			$r = $res->fetch_assoc();
			$r["content"] = ($r["content"]);
			return $r;
		}else{
			qb_error_set("Page not found");
			return false;
		}
	}
	return true;
}

/*function qb_page_get_homepage(){
	$conn = qb_conn_get();
	
	$query = "SELECT id, heading, content FROM content WHERE type='page' AND heading='Home'";
	$res = $conn->query($query);
	if ($res){
		if ($res->num_rows>0){
			return $res->fetch_assoc();
		}else{
			qb_error_set("No homepage set");
			return false;
		}
	}
	return true;
}*/

function qb_page_add($heading, $content){
	$conn = qb_conn_get();
	
	$heeading = qb_str_process(strip_tags($heading));
	$content = qb_str_process($content);
	
	$user = qb_user_get($_SESSION["uid"]);
	if ($user["type"]=="admin" || $user["type"]=="editor"){
		//post it!
		$query = "INSERT INTO content(heading, content, type) VALUES('".$heading."',".
			"'".$content."','page')";
		if ($conn->query($query)==false){
			$error = "Failed to add page";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}
	}else{
		$error = "This account is not authorized for creating pages.";
		qb_error_set($error);
		return false;
	}
	return true;
}

function qb_page_list($offset, $count){
	$conn = qb_conn_get();
	$query = "SELECT id, heading FROM content WHERE type='page' LIMIT ".
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

function qb_page_list_all(){
	$conn = qb_conn_get();
	$query = "SELECT id, heading FROM content WHERE type='page'";
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

function qb_page_update($pid, $page){
	$conn = qb_conn_get();
	//$page = ["content","heading"]
	$pid_str = qb_str_process(strval($pid));
	$page["content"] = qb_str_process($page["content"]);
	$page["heading"] = qb_str_process(strip_tags($page["heading"]));
	//update heading & content
	
	$user = qb_user_get($_SESSION["uid"]);
	if ($user["type"]=="admin" || $user["type"]=="editor"){
		$query = "UPDATE content SET heading='".$page["heading"]."', content='".$page["content"].
			"' WHERE type='page' AND id=".$pid_str;
		if ($conn->query($query)==false){
			$error = "Failed to edit page";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}
	}
	return true;
}

function qb_page_remove($pid){
	$conn = qb_conn_get();
	
	$user = qb_user_get($_SESSION["uid"]);
	if ($user["type"]=="admin"/* || $user["type"]=="editor"*/){
		$query = "DELETE FROM content WHERE type='page' AND id=".qb_str_process(strval($pid));
		if (!$conn->query($query)){
			$error = "Failed to remove page";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}
	}
	return true;
}

function qb_page_count(){
	$conn = qb_conn_get();
	
	$res = $conn->query("SELECT count(*) FROM content WHERE type='page'");
	if ($res){
		$nRows = $res->fetch_assoc()["count(*)"];
	}else{
		$nRows = 0;
	}
	return $nRows;
}

?>
