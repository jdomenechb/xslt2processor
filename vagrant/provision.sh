#!/bin/bash

# Script inspired by https://gist.github.com/rrosiek/8190550

# Variables
DBHOST=localhost
DBNAME=wenalyzer
DBUSER=root
DBPASSWD=root

echo -e "\n--- Setting timezone ---\n"
timedatectl set-timezone Europe/Madrid

echo -e "\n--- Reseting log ---\n"
echo "" > /vagrant/vm_build.log

echo -e "\n--- Updating packages list ---\n"
apt-get -qq update

echo -e "\n--- Install base packages ---\n"
apt-get -y install curl build-essential subversion zip unzip nano htop tree cifs-utils >> /vagrant/vm_build.log 2>&1

echo -e "\n--- Add Node 6.x rather than 4 ---\n"
curl -sL https://deb.nodesource.com/setup_6.x | sudo -E bash - >> /vagrant/vm_build.log 2>&1

echo -e "\n--- Updating packages list ---\n"
apt-get -qq update

echo -e "\n--- Installing PHP-specific packages ---\n"
apt-get -y install php apache2 libapache2-mod-php php-mbstring php-curl php-gd php-mysql php-gettext php-xdebug php-zip php-memcached php-intl php-pear php-dev php-soap >> /vagrant/vm_build.log 2>&1

echo -e "\n--- Enabling mod-rewrite ---\n"
a2enmod rewrite >> /vagrant/vm_build.log 2>&1

echo -e "\n--- Allowing Apache override to all ---\n"
sed -i "s/AllowOverride None/AllowOverride All/g" /etc/apache2/apache2.conf

echo -e "\n--- Changing Apache default user & group ---\n"
sed -i "s/export APACHE_RUN_USER=.*/export APACHE_RUN_USER=vagrant/g" /etc/apache2/envvars
sed -i "s/export APACHE_RUN_GROUP=.*/export APACHE_RUN_GROUP=vagrant/g" /etc/apache2/envvars

echo -e "\n--- Fixing mod_alias issue ---\n"
sed -i "s/\(\s*Alias \/icons\/ \"\/usr\/share\/apache2\/icons\/\"\s*\)/#\1/g" /etc/apache2/mods-available/alias.conf

echo -e "\n--- We definitely need to see the PHP errors, turning them on ---\n"
sed -i "s/error_reporting = .*/error_reporting = E_ALL/" /etc/php/7.0/apache2/php.ini
sed -i "s/display_errors = .*/display_errors = On/" /etc/php/7.0/apache2/php.ini

echo -e "\n--- Configuring xDebug for remote debug ---\n"
cp /vagrant/vagrant/etc/php/7.0/mods-available/xdebug.ini /etc/php/7.0/mods-available/xdebug.ini

#echo -e "\n--- Configuring PHP to be more permisive ---\n"
#sed -i "s/memory_limit = 128M/memory_limit = 512M/g" /etc/php/7.0/apache2/php.ini
#sed -i "s/display_startup_errors = Off/display_startup_errors = On/g" /etc/php/7.0/apache2/php.ini
#sed -i "s/post_max_size = 8M/post_max_size = 50M/g" /etc/php/7.0/apache2/php.ini
#sed -i "s/upload_max_filesize = 2M/upload_max_filesize = 50M/g" /etc/php/7.0/apache2/php.ini

echo -e "\n--- Setting PHP timezone ---\n"
sed -i "s/;date.timezone =/date.timezone = Europe\/Madrid/g" /etc/php/7.0/apache2/php.ini

echo -e "\n--- Installing Composer for PHP package management ---\n"
curl --silent https://getcomposer.org/installer | php >> /vagrant/vm_build.log 2>&1
mv composer.phar /usr/local/bin/composer

echo -e "\n--- Installing and configuring dependencies for phpDocumentor ---\n"
apt-get -y install graphviz >> /vagrant/vm_build.log 2>&1

echo -e "\n--- Installing phpDocumentor ---\n"
curl -L --silent https://github.com/phpDocumentor/phpDocumentor2/releases/download/v2.9.0/phpDocumentor.phar -o phpdoc.phar >> /vagrant/vm_build.log 2>&1
mv phpdoc.phar /usr/local/bin/phpdoc
chmod a+x /usr/local/bin/phpdoc

echo -e "\n--- Installing NodeJS and NPM ---\n"
apt-get -y install nodejs >> /vagrant/vm_build.log 2>&1

echo -e "\n--- Prepare websites ---\n"
cp /vagrant/vagrant/etc/apache2/sites-available/*.conf /etc/apache2/sites-available

a2ensite *wenalyzer* >> /vagrant/vm_build.log 2>&1

echo -e "\n--- Updating hosts ---\n"
cp /vagrant/vagrant/etc/hosts /etc >> /vagrant/vm_build.log 2>&1

echo -e "\n--- Restarting Apache ---\n"
service apache2 restart >> /vagrant/vm_build.log 2>&1