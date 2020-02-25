# obs-saisons.fr

## Install

Clone the repository, install [_composer_](https://getcomposer.org/download/) then run:
```bash
composer install
```

Copy .env to .env.local and change the settings, particulary:
- ```APP_ENV```
- ```DATABASE_URL```

If necessary, create the DB:
```bash
php bin/console doctrine:database:create
```

Create the tables:
```bash
php bin/console doctrine:migrations:migrate
```

## Build css/js

Install [_NPM_](https://www.npmjs.com/get-npm) and run:
```bash
npm install
npm run dev
```

## Normalize code

Run [_PHP-CS-Fixer_](https://github.com/FriendsOfPhp/PHP-CS-Fixer):

```
cp .php_cs.dist .php_cs
php vendor/bin/php-cs-fixer fix --diff --dry-run
```

## Dev server

Run integrated PHP dev server:
```bash
php bin/console server:start
```
