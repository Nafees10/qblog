<?php
include_once("qblog.php");
include_once("qb_users.php");

function qb_post_get($pid){
	$conn = qb_conn_get();
	
	$query = "SELECT heading, content FROM content WHERE type='post' AND id=".
		qb_str_process(strval($pid));
	$res = $conn->query($query);
	if ($res){
		if ($res->num_rows>0){
			$r = $res->fetch_assoc();
			$r["content"] = ($r["content"]);
			return $r;
		}else{
			qb_error_set("Post not found");
			return false;
		}
	}
	return true;
}

function qb_post_add($heading, $content){
	$conn = qb_conn_get();
	
	$heeading = qb_str_process(strip_tags($heading));
	$content = qb_str_process($content);
	
	$user = qb_user_get($_SESSION["uid"]);
	if ($user["type"]=="admin" || $user["type"]=="editor"){
		//post it!
		$query = "INSERT INTO content(heading, content, type) VALUES('".$heading."',".
			"'".$content."','post')";
		if ($conn->query($query)==false){
			$error = "Failed to add post";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}
	}else{
		$error = "This account is not authorized for creating posts.";
		qb_error_set($error);
		return false;
	}
	return true;
}

function qb_post_list($offset, $count, $content=false){
	$conn = qb_conn_get();
	if ($content){
		$query = "SELECT id, heading, content FROM content WHERE type='post' ORDER BY id DESC".
			" LIMIT ".qb_str_process(strval($count))." OFFSET ".
			qb_str_process(strval($offset));
	}else{
		$query = "SELECT id, heading FROM content WHERE type='post' ORDER BY id DESC LIMIT ".
			qb_str_process(strval($count))." OFFSET ".qb_str_process(strval($offset));
	}
	
	$res = $conn->query($query);
	$r = false;
	if ($res && $res->num_rows>0){
		$i = 0;
		$r = array_fill(0,$res->num_rows, null);
		while ($r[$i] = $res->fetch_assoc()){
			if ($content){
				$r[$i]["content"] = ($r[$i]["content"]);
			}
			$i++;
		}
	}
	return $r;
}

function qb_post_list_all($content=false){
	$conn = qb_conn_get();
	if ($content){
		$query = "SELECT id, heading, content FROM content WHERE type='post' ORDER BY id DESC";
	}else{
		$query = "SELECT id, heading FROM content WHERE type='post' ORDER BY id DESC";
	}
	
	$res = $conn->query($query);
	$r = false;
	if ($res && $res->num_rows>0){
		$i = 0;
		$r = array_fill(0,$res->num_rows, null);
		while ($r[$i] = $res->fetch_assoc()){
			if ($content){
				$r[$i]["content"] = ($r[$i]["content"]);
			}
			$i++;
		}
	}
	return $r;
}


function qb_post_update($pid, $post){
	$conn = qb_conn_get();
	//$post = ["content","heading"]
	$pid_str = qb_str_process(strval($pid));
	$post["content"] = qb_str_process($post["content"]);
	$post["heading"] = qb_str_process(strip_tags($post["heading"]));
	//update heading & content
	
	$user = qb_user_get($_SESSION["uid"]);
	if ($user["type"]=="admin" || $user["type"]=="editor"){
		$query = "UPDATE content SET heading='".$post["heading"]."', content='".$post["content"].
			"' WHERE type='post' AND id=".$pid_str;
		if ($conn->query($query)==false){
			$error = "Failed to edit post";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}
	}
	return true;
}

function qb_post_remove($pid){
	$conn = qb_conn_get();
	
	$user = qb_user_get($_SESSION["uid"]);
	if ($user["type"]=="admin"/* || $user["type"]=="editor"*/){
		$query = "DELETE FROM content WHERE type='post' AND id=".qb_str_process(strval($pid));
		if (!$conn->query($query)){
			$error = "Failed to remove post";
			if (qb_debug_get()){
				$error .= "; \n".$conn->error;
			}
			qb_error_set($error);
			return false;
		}
	}
	return true;
}

function qb_post_count(){
	$conn = qb_conn_get();
	
	$res = $conn->query("SELECT count(*) FROM content WHERE type='post'");
	if ($res){
		$nRows = $res->fetch_assoc()["count(*)"];
	}else{
		$nRows = 0;
	}
	return $nRows;
}

?>
