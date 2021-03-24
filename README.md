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
Migrate all static data :
```bash
php bin/console odsstaticdata:migrate
```

Or for dev env:
```bash
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load
```

## Run Static Data Table Set Commands
### Set All 
(See `Migrate all static data` above)
```bash
php bin/console ods:bootstrap:all-static-data
```
### Set Each
To set each table apart, make sure previous table is already set :

1 `TypeSpecies`
```bash
php bin/console ods:import:typespecies
```

2 `Species`
```bash
php bin/console ods:import:species
```

3 `Events`
```bash
php bin/console ods:import:events
```

4 `EventSpecies`
- set event and species
```bash
php bin/console ods:generate:eventspecies
```
- set events periods and aberration alert periods
```bash
php bin/console ods:import:periods
```

**`periods:import` Choice Question :**\
`Please select periods types (s : stages periods, a : observations alerts periods, b : both / default to b : both)`\
_both (default):_ enter `b` or hit `enter`\
_stages periods :_ enter `s`\
_aberration alert periods :_ enter `a`
    

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
