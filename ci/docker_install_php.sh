#!/usr/bin/env bash

# We need to install dependencies only for Docker
[[ ! -e /.dockerenv ]] && exit 0

set -xe

# Install apt-utils, git, wget, unzip, libphp-pclzip (the php image doesn't have them) which are required by composer
apt-get update -yqq
apt-get install apt-utils -yqq
apt-get install git wget unzip libphp-pclzip -yqq

# Enable xdebug
phpversion=`php --version | head -n 1 | cut -d " " -f 2 | cut -c 1,3`
if [ $phpversion -eq 54 ]; then
    pecl install https://xdebug.org/files/xdebug-2.4.1.tgz
else
    pecl install xdebug
fi

docker-php-ext-enable xdebug

# Install composer
wget https://composer.github.io/installer.sig -O - -q | tr -d '\n' > installer.sig
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('SHA384', 'composer-setup.php') === file_get_contents('installer.sig')) { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php'); unlink('installer.sig');"
php composer.phar install

