#!/bin/bash

# Update the server and install Apache, PHP, and GIT.
yum update -y
yum install httpd24 php71 git -y

# Start apache and set it to update on file changes
service httpd start
chkconfig httpd on

# required COMPOSER_HOME dir to work because aws doesn't have a HOME var set during initialization
export COMPOSER_HOME=/root

# install composer at usr/bin/
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/bin/composer
alias composer="php /usr/local/bin/composer.phar"

# pull the okta poc repo in /var/www/html
cd /var/www/html
git clone https://github.com/trentarmstrong/php-okta-poc .
composer install
