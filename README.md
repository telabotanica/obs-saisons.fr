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

## Run css/js compilation

If you have npm installed run :
```bash
npm run dev
```

## Dev server

Run integrated PHP dev server:
```bash
php bin/console server:start
```
