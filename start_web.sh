#!/bin/bash

set -x

export TZ=JST-9

export USER_AGENT=$(curl https://raw.githubusercontent.com/tshr20171114/getrss02/master/useragent.txt)

vendor/bin/heroku-php-apache2 -C apache.conf www
