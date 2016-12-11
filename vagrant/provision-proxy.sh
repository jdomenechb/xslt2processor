#!/bin/bash

test=$(cat /etc/apache2/envvars | grep "http_proxy")

if [ "$test" = "" ]; then
    echo -e "\n--- Adding proxy config to Apache ---\n"
    echo "export http_proxy=\"$HTTP_PROXY\"" >> /etc/apache2/envvars
    echo "export HTTP_PROXY=\"$HTTP_PROXY\"" >> /etc/apache2/envvars
    echo "export https_proxy=\"$HTTPS_PROXY\"" >> /etc/apache2/envvars
    echo "export HTTPS_PROXY=\"$HTTPS_PROXY\"" >> /etc/apache2/envvars
    echo "export no_proxy=\"$NO_PROXY\"" >> /etc/apache2/envvars
    echo "export NO_PROXY=\"$NO_PROXY\"" >> /etc/apache2/envvars

    echo -e "\n--- Restarting Apache ---\n"
    service apache2 restart
fi