#!/bin/bash
# Web Development non-interactive installation script for Ubuntu 14.04
# Christine Kozin

# Change passwords, username, database and directory name
MYSQL_ROOT_PW="ENTER PASSWORD" # Set MySQL root password
WP_USER="username"; # Set WordPress username
WP_USER_PW="########"; # Set WordPress user password 
WP_DB_NAME="dbname"; # Set WordPress database name
PHPMYADMIN_DIR="ENTER NEW DIRECTORY ALIAS" # Set alias for phpMyAdmin directory

# Update repositories
sudo apt-get update;

# Install required applications for PHP MySQL Apache 
sudo apt-get -y install byobu libmysqld-dev apache2 php5 libapache2-mod-php5 php5-mcrypt pwgen;

# Change web group to www-data which user is attached
sudo chown "$USER" /var/www/;
sudo chgrp www-data /var/www/;
sudo chmod 775 -R /var/www/;

echo "==========================================="
echo "INSTALLING MYSQL"
echo "==========================================="
#The following commands set the MySQL root password to MYSQL_ROOT_PW when you install the mysql-server package.
echo "mysql-server mysql-server/root_password password $MYSQL_ROOT_PW" | sudo debconf-set-selections
echo "mysql-server mysql-server/root_password_again password $MYSQL_ROOT_PW" | sudo debconf-set-selections
sudo apt-get -y install mysql-server

# Restart Apache and MySQL 
echo -e "\n"
sudo service apache2 restart && service mysql restart > /dev/null
echo -e "\n"

# Check for proper installation and display message
if [ $? -ne 0 ]; then
   echo "Please Check the Install Services, There is some $(tput bold)$(tput setaf 1)Problem$(tput sgr0)"
else
   echo "Installed Services run $(tput bold)$(tput setaf 2)Sucessfully$(tput sgr0)"
fi
echo -e "\n"

echo "==========================================="
echo "INSTALLING PHPMYADMIN"
echo "==========================================="
# The following sets the phpMyAdmin passwords 

# Generate 20 character long password with at least one capital
AUTOGENERATED_PASS=`pwgen -c -1 20` 
echo "phpmyadmin phpmyadmin/dbconfig-install boolean true" | sudo debconf-set-selections
echo "phpmyadmin phpmyadmin/mysql/admin-user string root" | sudo debconf-set-selections 
echo "phpmyadmin phpmyadmin/mysql/admin-pass password $MYSQL_ROOT_PW" | sudo debconf-set-selections 
echo "phpmyadmin phpmyadmin/mysql/app-pass password $AUTOGENERATED_PASS" | sudo debconf-set-selections 
echo "phpmyadmin phpmyadmin/app-password-confirm password $AUTOGENERATED_PASS" | sudo debconf-set-selections 
echo "phpmyadmin phpmyadmin/reconfigure-webserver multiselect apache2" | sudo debconf-set-selections

# Install phpMyAdmin
sudo apt-get -y install phpmyadmin

#  Set the alias for the directory
sed -i -r "s:(Alias /).*(/usr/share/phpmyadmin):\1$PHPMYADMIN_DIR \2:" /etc/phpmyadmin/apache.conf 

# Install mcrypt extension for 14.04 
php5enmod mcrypt 

# Restart Apache and MySQL
sudo service apache2 restart && service mysql restart > /dev/null
echo -e "\n"

# Check for errors in the installation and display messages
if [ $? -ne 0 ]; then
   echo "Please Check the Install Services, There is some $(tput bold)$(tput setaf 1)Problem$(tput sgr0)"
else
   echo "Installed Services run $(tput bold)$(tput setaf 2)Sucessfully$(tput sgr0)"
fi
echo -e "\n"

echo "=========================================="
echo "WORDPRESS DATABASE CREATION"
echo "=========================================="
# Change directory to HTML for WordPress
cd /var/www/html || exit

# Log into MySQL, create database and set permissions
mysql -u root -p"$MYSQL_ROOT_PW" -e "CREATE USER '$WP_USER'@'localhost' IDENTIFIED BY '$WP_USER_PW';" 
echo "WP user created"
mysql -u root -p"$MYSQL_ROOT_PW" -e "CREATE DATABASE $WP_DB_NAME;"

# Check for successful database creation and print messages
if [ -d /var/lib/mysql/$WP_DB_NAME ]; then
    echo "$(tput setaf 2)New MySQL database ($WP_DB_NAME) was successfully created$(tput sgr0)"
else
    echo "$(tput setaf 1)New MySQL database ($WP_DB_NAME) failed to be created$(tput sgr0)"
fi

# Set privileges for user and flush
mysql -u root -p"$MYSQL_ROOT_PW" -e "GRANT ALL PRIVILEGES ON $WP_DB_NAME.* TO $WP_USER;"
echo "Privileges set"
mysql -u root -p"$MYSQL_ROOT_PW" -e "FLUSH PRIVILEGES;" 
echo "Flush complete"
echo "=========================================="
echo "WORDPRESS DB COMPLETE"
echo "=========================================="

# Enter into web directory
cd /var/www/html || exit

echo "==========================================="
echo "WORDPRESS INSTALLATION"
echo "==========================================="
# Download latest WordPress
echo "Downloading WP latest version"
sudo wget http://wordpress.org/latest.tar.gz

# Unzip WordPress
echo "Unpacking latest WordPress file"
sudo tar -xzvf latest.tar.gz

# Move contents of WP directory up a level
echo "Moving WP to main web directory"
sudo mv wordpress/* .
echo "Setting up config file"

# Create random 8 char string for WP table prefix
WP_RDM_STRING=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 8 | head -n 1)

# Table prefix should start with wp_ for best practices then add generated random string
WP_TABLE_PREFIX="wp_${WP_RDM_STRING}_"

# Create WP config file from sample
sudo cp wp-config-sample.php wp-config.php

# Set database details with perl find and replace
sudo perl -pi -e "s/database_name_here/$WP_DB_NAME/g" wp-config.php
sudo perl -pi -e "s/username_here/$WP_USER/g" wp-config.php
sudo perl -pi -e "s/password_here/$WP_USER_PW/g" wp-config.php
sudo perl -pi -e "s/wp_/$WP_TABLE_PREFIX/g" wp-config.php

# Create salt value from WP API
SALT=$(curl -L https://api.wordpress.org/secret-key/1.1/salt/)
STRING='put your unique phrase here' # Do not change string value 

# Search for string and replace with salt value into config file
sudo printf '%s\n' "g/$STRING/d" a "$SALT" . w | ed -s wp-config.php

# Remove index.html file
echo "Removing index file"
sudo rm index.html

# Remove empty WP directory
sudo rmdir wordpress

# Remove zip file
sudo rm latest.tar.gz
echo "=========================================="
echo "WORDPRESS INSTALLATION COMPLETE"
echo "=========================================="