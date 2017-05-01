# qblog
A simple blog software written in PHP  
Right now, it is in early development, so expect bugs...  
## Features
1. Small and lightweight, making it run faster.
2. Open source, you can change it to your needs.
3. Easy to create new templates. Look into the `templates/` directory, everything can be easily modified.  

## Setting it up
You need a MySQL database, and PHP to run this.  
1. Change the MySQL database information to match yours in the `qblog.php` file, in function `qb_connect`, line 86.  
2. Upload it to the webserver.  
3. Go to `your-blog.com/setup.php`, and provide the information to setup the database.  
4. Delete `setup.php` file from webserver for security reasons.  
