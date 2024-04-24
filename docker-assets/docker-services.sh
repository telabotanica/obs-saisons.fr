#!/bin/sh

# This script contains all command to launch by Dockerfile.

# Start php-fpm.
#/usr/sbin/php-fpm

# Wait asynchronously that database is ready to insert data.
nohup $1/docker-assets/wait-for-it.sh db:3306 -- bin/console make:migration #> /dev/null 2> /dev/null &r
nohup $1/docker-assets/wait-for-it.sh db:3306 -- bin/console doctrine:migrations:migrate #> /dev/null 2> /dev/null &r
nohup $1/docker-assets/wait-for-it.sh db:3306 -- bin/console doctrine:schema:create #> /dev/null 2> /dev/null &
nohup $1/docker-assets/wait-for-it.sh db:3306 -- -- yes yes | bin/console doctrine:fixtures:load #> /dev/null 2> /dev/null &


# Wait asynchronously that var directory has been created to change his rights.
nohup sh -c 'until [ -d "$1/var" ]; do sleep 1; done && chown -R www-data:www-data $1/var' > /dev/null 2> /dev/null &

# Start Apache server
/usr/sbin/apachectl -D FOREGROUND
