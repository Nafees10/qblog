<?php
include_once("qblog.php");//base of qblog
include_once("qb_users.php");//used by add_post

function qb_content_get($pid){
	$conn = qb_conn_get();
	
	$query = "SELECT heading, content FROM content WHERE id=".qb_str_process(strval($pid));
	$res = $conn->query($query);
	if ($res){
		if ($res->num_rows>0){
			return $res->fetch_assoc();
		}else{
			qb_error_set("Content not found");
			return false;
		}
	}
	return true;
}

function qb_content_list($offset, $count){
	$conn = qb_conn_get();
	$query = "SELECT id, heading FROM content LIMIT ".
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
	$query = "SELECT id, heading FROM content";
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

function qb_content_update($pid, $page){
	$conn = qb_conn_get();
	//$page = ["content","heading"]
	$pid_str = qb_str_process(strval($pid));
	$page["content"] = qb_str_process($page["content"]);
	$page["heading"] = qb_str_process(strip_tags($page["heading"]));
	//update heading & content
	
	$user = qb_user_get($_SESSION["uid"]);
	if ($user["type"]=="admin" || $user["type"]=="editor"){
		$query = "UPDATE content SET heading='".$page["heading"]."', content='".$page["content"].
			"' WHERE id=".$pid_str;
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

