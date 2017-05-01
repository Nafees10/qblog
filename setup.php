<?php
include_once ("qblog.php");
include_once ("qb_users.php");
session_start();
//check if data was submitted
//check if blog is already set up, by checking if a user exists
qb_connect();
if (qb_user_count()>0){
	die ("Blog is already set up! Delete this file for security reasons");
}
if (array_key_exists("title",$_POST)){
	//data was submitted
	//check if everything was submitted
	$error = "";
	if ($_POST["title"]==""){
		$error .= 'No blog title entered<br>';
	}
	if (array_key_exists("tagline",$_POST)==false){
		$error .= 'No tagline entered<br>';
	}
	if (array_key_exists("admin_username",$_POST)==false){
		$error .= 'No administrator username entered<br>';
	}else{
		//validate username
		if (qb_username_validate($_POST["admin_username"])==false){
			$error .= qb_error_get().'<br>';
		}
	}
	if (array_key_exists("admin_password",$_POST)==false){
		$error .= 'No administrator password entered<br>';
	}else{
		//validate password
		if (qb_password_validate($_POST["admin_password"])==false){
			$error .= qb_error_get().'<br>';
		}
	}
	//now if ($error=="") {everything's fine}else{throw errors}
	if ($error==""){
		//set up the blog
		//set up qb
		if (qb_setup_db()==false){
			die ('An unexpected error occured while setting up database:<br>'.qb_error_get());
		}
		//now add admin user
		if (qb_user_add($_POST["admin_username"],$_POST["admin_password"],"admin")==false){
			die ('An unexpected error occured while adding admin user:<br>'.qb_error_get());
		}
		//now set the tagline & blog title
		if (qb_setting_add("title",$_POST["title"])==false){
			die ("An unexpected error occured while setting blog title:<br>".qb_error_get());
		}
		if (qb_setting_add("tagline",$_POST["tagline"])==false){
			die ("An unexpected error occured while setting blog tagline:<br>".qb_error_get());
		}
		//if the execution reached here, it's error free
		header("Location: ".qb_addr_get());
		qb_message_add('QBlog was set up.<br>'.
			'For security reasons, delete the setup.php file.');
		die ("QBlog was set up! Redirecting to main page");
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
