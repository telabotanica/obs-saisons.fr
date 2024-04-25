# Use an official Ubuntu runtime as a parent image
FROM debian

RUN apt-get update && apt-get upgrade

ARG DB_USER
ARG DB_PASSWORD
ARG DB_NAME
ARG APP_ENV
ARG APP_SECRET
ARG DEFAULT_URI


# Install necessary packages and dependencies
RUN <<EOF
    apt-get -y install software-properties-common
    apt-get -y install ca-certificates curl gnupg
EOF

# PHP 7.4
RUN <<EOF
apt -y install apt-transport-https lsb-release ca-certificates wget
wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/php.list
apt-get update
apt-get -y install apache2
apt-get -y install php7.4
EOF

RUN apt-get -y install php7.4-fpm php7.4-cli php7.4-gd php7.4-pdo php7.4-xml php7.4-mbstring php7.4-zip php7.4-mysqlnd php7.4-mysql php7.4-opcache php7.4-json php7.4-intl php7.4-curl


# Nodejs & yarn
RUN <<EOF
    curl -fsSL https://deb.nodesource.com/setup_16.x | bash -
    apt-get install -y nodejs
    npm install -g yarn
EOF

# Composer
RUN <<EOF
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    php composer-setup.php
    php -r "unlink('composer-setup.php');"
    mv composer.phar /usr/local/bin/composer
EOF

# Symfony
RUN <<EOF
    curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash
    apt-get install -y symfony-cli
EOF

ENV workdir /var/www/html/obs
COPY --chown=www-data:www-data . ${workdir}
WORKDIR ${workdir}

RUN <<EOF
COMPOSER_ALLOW_SUPERUSER=1 composer install
yarn
yarn build
EOF

# Create .env.local
RUN <<EOF
echo "# Override default values for local environment
APP_ENV=${APP_ENV}
APP_SECRET=${APP_SECRET}
DEFAULT_URI=${DEFAULT_URI}
DATABASE_URL=mysql://${DB_USER}:${DB_PASSWORD}@db:3306/${DB_NAME}?charset=utf8mb4
MAILER_URL=null://localhost
MAILCHIMP_LIST_ID=your_local_mailchimp_list_id
MAILCHIMP_API_BASE_URI=your_local_mailchimp_api_base_uri
MAILCHIMP_API_KEY=your_local_mailchimp_api_key
ANALYTICS_TRACKING_ID=your_local_analytics_tracking_id" > .env.local
EOF

RUN <<EOF
mkdir /run/php-fpm
chmod a+x docker-assets/docker-services.sh
chmod a+x docker-assets/wait-for-it.sh
mv docker-assets/obs_apache.conf /etc/apache2/conf-available
ln -s /etc/apache2/conf-available/obs_apache.conf /etc/apache2/conf-enabled/obs_apache.conf
EOF

# Open HTTP and HTTPS port.
EXPOSE 80

CMD docker-assets/docker-services.sh ${workdir} ${APP_ENV}
