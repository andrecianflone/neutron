#!/bin/bash

# Some tput colours
red=`tput setaf 1`
green=`tput setaf 2`
yellow=`tput setaf 3`
reset=`tput sgr0`

# Setup composer in project:
echo "================================"
echo "Downloading composer locally"
EXPECTED_SIGNATURE=$(wget https://composer.github.io/installer.sig -O - -q)
php-cli -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_SIGNATURE=$(php-cli -r "echo hash_file('SHA384', 'composer-setup.php');")

if [ "$EXPECTED_SIGNATURE" = "$ACTUAL_SIGNATURE" ]
then
    /usr/php/56/bin/php56s composer-setup.php --quiet
    RESULT=$?
    rm composer-setup.php
    #exit $RESULT
    echo 'Successfully downloaded composer'
else
    >&2 echo 'ERROR: Invalid installer signature'
    rm composer-setup.php
    exit 1
fi

# Assumes composer.json exists with required dependencies
echo "================================"
echo "Installing dependencies with composer"
DIRECTORY="vendor"
rm -fR $DIRECTORY
php-cli composer.phar install

# If dir not successfully created, try alternative method
if [ ! -d "$DIRECTORY" ]; then
  echo "**********"
  echo "It seems composer.phar failed, trying alternative"
  echo "**********"
  /usr/php/56/bin/php56s -d register_argc_argv=1 "./composer.phar" install
fi

# Make public publicly readeable
echo "================================"
echo "Setting permissions"
chmod -R 755 app/public
chmod -R 755 app/views
chmod -R 755 app/Model

# Config file
echo "================================"
echo "${yellow}Created config file 'site_config.ini', you'll need to update this with your settings${reset}"
rm -f site_config.ini
cat <<EOF > site_config.ini
; Database configuration
[db]
host   = "localhost"
user   = "root"
pass   = "root"
dbname = "your_database"
EOF
echo "================================"

# Htaccess file
echo "================================"
echo "${yellow}I've created a suggested .htaccess file, check it out and move it to the right location${reset}"
cat <<EOF > .htaccess
# Forward scripts and css
RewriteRule css/(.*)$ neutron/app/public/css/$1 [L]
RewriteRule scripts/(.*)$ neutron/app/public/scripts/$1 [L]

# Forward everything else to index
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
#RewriteCond %{REQUEST_URI} !^/public/

RewriteRule ^ neutron/app/public/index.php [QSA,L]
EOF
echo "================================"
