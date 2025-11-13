## How to run project 

Must be on a linux distro to install and run 

Install 

sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql

Clone Project in var/www/html/

Make sure apache is running 

sudo systemctl status apache2
sudo systemctl status mysql

mysql -u (MysqlUsername) -p -h (ServerIP) (Sql project name)



RENTAL_MANAGEMENT_APP/
├── assets/
│   └── style.css
├── includes/
│   ├── auth.php
│   └── db.php
├── roles/
│   ├── landlord/
│   │   ├── calender.php
│   │   ├── landlord_dashboard.php
│   │   ├── payments.php
│   │   └── register_property.php
│   ├── staff/
│   └── tenant/
│       └── requests.php
├── dashboard.php
├── fix_hashes.php
├── index.php
├── README.md
├── register.php
└── test_db.php