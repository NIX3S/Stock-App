#!/bin/bash

service mariadb start

# attendre MariaDB
sleep 5

# créer DB + user si pas existant
mysql -e "CREATE DATABASE IF NOT EXISTS stock_app;"
mysql -e "CREATE USER IF NOT EXISTS 'stockuser'@'localhost' IDENTIFIED BY 'motdepasse';"
mysql -e "GRANT ALL PRIVILEGES ON stock_app.* TO 'stockuser'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# start apache
apache2-foreground