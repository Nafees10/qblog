# qblog
A simple blog software written in PHP  
Right now, it is in early development, although it's tested and it works, expect bugs...  
## Features  
  
1. Small and lightweight, making it run fast.
2. Open source, you can change it to your needs.
3. Easy to create new templates. Look into the `templates/` directory, everything can be easily modified.  
4. Features a "dashboard" from where new content can be added.  
5. Easy to setup, read below:  

## Setting it up
You need a MySQL database, and PHP to run this.  
1. Change the MySQL database information to match yours in the `qblog.php` file, in function `qb_connect`, line 86.  
2. Upload it to the webserver.  
3. Go to `your-blog.com/setup.php`, and provide the information to setup the database.  
4. Delete `setup.php` file from webserver for security reasons.  
  
## What still needs to be added  
qblog is not yet very "usable".  

1. The post editor needs some real work done on it - I think I'll get this done in the new version
2. There is no plugin support - This feature wont be coming very soon
3. There's only one default template, need to add more - This is something that requires me to learn some "real" frontend development (I'm not even a web dev BTW)
4. To change a template, all the files need to be moved, need to add support for changing templates without hardcoding them - This should be easy?
5. And lot of more work
