-- Allow root login without password from any host
ALTER USER 'root'@'%' IDENTIFIED WITH mysql_native_password BY '';
FLUSH PRIVILEGES;
