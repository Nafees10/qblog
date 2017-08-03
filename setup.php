<?php
include_once ("qblog.php");
include_once ("qb_users.php");
session_start();
//check if blog is already set up, by checking if a user exists
qb_connect();
if (User::count("all")>0){
	die ("Blog is already set up! Delete this file for security reasons");
}
/// function to drop all tables, called when an error occurs during setup, and all progress should be reversed
function drop_db(){
	$conn = qb_conn_get();
	$query = "DROP TABLE users; DROP TABLE content; DROP TABLE settings";
	if ($conn->query($query)){
		return true;
	}else{
		qb_error_set($conn->error);
		return false;
	}
}
//check if data was submitted
if (array_key_exists("title",$_POST)){
	//data was submitted
	//check if everything was submitted
	$error = "";
	if (array_key_exists("tagline",$_POST)==false){
		$error .= 'No tagline entered<br>';
	}
	if (array_key_exists("admin_username",$_POST)==false || $_POST["admin_username"] == ""){
		$error .= 'No administrator username entered<br>';
	}
	if (array_key_exists("admin_password",$_POST)==false || $_POST["admin_password"] == ""){
		$error .= 'No administrator password entered<br>';
	}
	//now if ($error=="") {everything's fine}else{throw errors}
	if ($error==""){
		// first set the user's username etc, coz setting them validate-checks them
		$user = new User;
		$user->username = $_POST["admin_username"];
		if ($user->username != $_POST["admin_username"]){
			$error .= qb_error_get()."<br>";
		}
		$prev_hash = $user->passhash;
		$user->password = $_POST["admin_password"];
		if ($user->passhash == $prev_hash){
			$error .= qb_error_get();
		}
		if ($error == ""){
			//set up the blog
			//set up database
			if (qb_setup_db()===false){
				die ('Unexpected error occured while setting up database:<br>'.qb_error_get());
				drop_db();
			}
			//now add admin user
			$user->type = "admin";
			if ($user->insert()===false){
				die ('Unexpected error occured while adding admin user:<br>'.qb_error_get());
				drop_db();
			}
			//now set the tagline & blog title
			if (qb_setting_add("title",$_POST["title"])===false){
				die ("Unexpected error occured while setting blog title:<br>".qb_error_get());
				drop_db();
			}
			if (qb_setting_add("tagline",$_POST["tagline"])===false){
				die ("Unexpected error occured while setting blog tagline:<br>".qb_error_get());
				drop_db();
			}
			// default template
			if (qb_setting_add("template", "default") === false){
				die ("Unexpected error occured while setting default template<br>".qb_error_get());
				drop_db();
			}
			//if the execution reached here, it's error free
			header("Location: ".qb_addr_get());
			qb_message_add('QBlog was set up.<br>'.
				'For security reasons, delete the setup.php file.');
			die ("QBlog was set up! Redirecting to main page");
		}else{
			qb_warning_add($error);
		}
	}else{
		//there's error!
		qb_warning_add($error);
		//echo $error;
	}
}
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<html>
	<head>
		<title>QBlog - Setup</title>
		<link rel="stylesheet" href="qs.css">
	</head>
	<body>
		<header style="text-align: center;">
			QBlog Setup
		</header>
		<article style="width: auto; margin: auto; max-width: 480px; padding: 10px;">
			<?php
			//display a warning if any
			if (array_key_exists("warning",$_SESSION)){
				echo '<div class="warning">'.$_SESSION["warning"].'</div>';
				unset($_SESSION["warning"]);
			}
			//and a message, if any
			if (array_key_exists("message",$_SESSION)){
				echo '<div class="message">'.$_SESSION["message"].'</div>';
				unset($_SESSION["message"]);
			}
			?>
			<form action="setup.php" method="POST">
				<fieldset>
					<legend>Blog info:</legend>
					<label>Blog title</label>
					<input type=text name=title placeholder="blog title">
					<label>Tagline</label>
					<input type=text name=tagline placeholder="tagline">
				</fieldset>
				<fieldset>
					<legend>Administrator info:</legend>
					<label>Administrator username</label>
					<input type=text name="admin_username" placeholder="admin">
					<label> Administrator password</label>
					<input type=password name="admin_password" placeholder="password">
				</fieldset>
				<input type=submit value="Finish">
			</form>
		</article>
	</body>
</html>
