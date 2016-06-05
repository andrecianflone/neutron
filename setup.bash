#1/bin/bash

# Setup composer in project:
echo "================================"
echo "Installing composer locally"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('SHA384', 'composer-setup.php') === '070854512ef404f16bac87071a6db9fd9721da1684cd4589b1196c3faf71b9a2682e2311b36a5079825e155ac7ce150d') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"

# Assumes composer.json exists with required dependencies
echo "Installing dependencies with composer"
php composer.phar install

# Make public publicly readeable
echo "================================"
echo "Setting permissions"
chmod 755 -R app/public
chmod 755 -R app/views
chmod 755 -R app/model
