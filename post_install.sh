#!/bin/bash

set -x

date

php -l loggly.php
php -l loggly_error.php

chmod 755 ./start_web.sh

date
