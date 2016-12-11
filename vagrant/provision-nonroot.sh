#!/bin/bash
cd ~

echo -e "\n--- Prepare environment for running Composer executables ---\n"

hasLines=$(cat .profile | grep "composer/vendor/bin")

if [ "$hasLines" == "" ]; then
    echo "export PATH=\"$PATH:$HOME/.config/composer/vendor/bin\"" >> .profile
fi;

cd /vagrant

echo -e "\n--- Installing PHPCSFixer ---\n"
composer global require friendsofphp/php-cs-fixer  >> /vagrant/vm_build.log 2>&1

echo -e "\n--- Run Composer on website ---\n"
composer update --prefer-dist >> /vagrant/vm_build.log 2>&1
