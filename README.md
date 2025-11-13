## How to run project 

Must be on a linux distro to install and run 

Install 

sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql

Clone Project in var/www/html/

Make sure apache is running 

sudo systemctl status apache2
sudo systemctl status mysql

mysql -u (MysqlUsername) -p -h (ServerIP) (Sql project name)

