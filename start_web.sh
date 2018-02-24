#!/bin/bash

set -x

export TZ=JST-9

httpd -V
httpd -M
php --version
whereis php
cat /proc/version
curl --version
printenv

if [ ! -v LOGGLY_TOKEN ]; then
  echo "Error : LOGGLY_TOKEN not defined."
  exit
fi

if [ ! -v LOG_LEVEL ]; then
  export LOG_LEVEL="warn"
fi

if [ ! -v BASIC_USER ]; then
  echo "Error : BASIC_USER not defined."
  exit
fi

if [ ! -v BASIC_PASSWORD ]; then
  echo "Error : BASIC_PASSWORD not defined."
  exit
fi

if [ ! -v HOME_FQDN ]; then
  echo "Error : HOME_FQDN not defined."
  exit
fi

if [ ! -v HEROKU_APP_NAME ]; then
  echo "Error : HEROKU_APP_NAME not defined."
  exit
fi

htpasswd -c -b .htpasswd ${BASIC_USER} ${BASIC_PASSWORD}

nslookup ${HOME_FQDN} 8.8.8.8

export HOME_IP_ADDRESS=$(nslookup ${HOME_FQDN} 8.8.8.8 \
  | grep ^Address \
  | grep -v 8.8.8.8 \
  | awk '{print $2}')
    
vendor/bin/heroku-php-apache2 -C apache.conf www
