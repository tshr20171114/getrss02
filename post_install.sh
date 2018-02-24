#!/bin/bash

set -x

date

mv phpPgAdmin-5.1 www/phppgadmin

rm -f phpPgAdmin-5.1.tar.bz2

cp config.inc.php www/phppgadmin/conf/config.inc.php

chmod 755 ./start_web.sh

date
