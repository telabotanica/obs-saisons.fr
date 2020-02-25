# obs-saisons.fr

## Install

Clone the repository, install [composer](https://getcomposer.org/download/) then run:
```bash
composer install
```

Copy .env to .env.local and change the settings, particulary:
- `APP_ENV`
- `DATABASE_URL`

If necessary, create the DB:
```bash
php bin/console doctrine:database:create
```

Create the tables:
```bash
php bin/console doctrine:migrations:migrate
```
Or for dev env:
```bash
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load
```

## Build css/js

Install [NPM](https://www.npmjs.com/get-npm) and run:
```bash
npm install
npm run dev
```

## Normalize code

Run [PHP-CS-Fixer](https://github.com/FriendsOfPhp/PHP-CS-Fixer):

```bash
cp .php_cs.dist .php_cs
php vendor/bin/php-cs-fixer fix --diff --dry-run
```

## Dev server

Install [symfony binary](https://symfony.com/download), check [its doc](https://symfony.com/doc/current/setup/symfony_server.html) and run:
```bash
symfony server:start
```

Or run integrated PHP dev server:
```bash
php -S localhost:8042 -t public/
```
