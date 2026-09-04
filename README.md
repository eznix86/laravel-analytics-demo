# Laravel Analytics Integration Tests

Exercises `eznix86/laravel-analytics` against real PostgreSQL, MySQL, and SQLite servers.

The package is installed as a symlinked path repository from `../laravel-analytics`, so edits there
are picked up with no reinstall. Clone both repositories side by side.

To check the published release instead, drop the repository and require it from Packagist:

```bash
composer config --unset repositories.0
composer require eznix86/laravel-analytics
```

## Setup

Create the databases:

```bash
createdb -h 127.0.0.1 -U postgres analytics_tests
mysql -h 127.0.0.1 -u root -e "create database if not exists analytics_tests"
mysql -h 127.0.0.1 -u root -e "create database if not exists analytics_other"
touch database/database.sqlite database/warehouse.sqlite
```

Install and prepare:

```bash
composer install
php artisan key:generate
php artisan migrate --force
php artisan migrate --force --database=warehouse --path=database/migrations/warehouse
```

## Running the Tests

Every model in one dependency chain shares a connection, so `ANALYTICS_CONNECTION` has to be set
alongside `DB_CONNECTION`. Setting only `DB_CONNECTION` leaves the analytics models on the connection
in `.env` and produces a confusing driver error.

PostgreSQL:

```bash
DB_CONNECTION=pgsql DB_PORT=5432 DB_USERNAME=postgres DB_PASSWORD= \
ANALYTICS_CONNECTION=pgsql \
php artisan test
```

MySQL:

```bash
DB_CONNECTION=mysql DB_PORT=3306 DB_USERNAME=root DB_PASSWORD= DB_DATABASE=analytics_tests \
ANALYTICS_CONNECTION=mysql \
php artisan test
```

SQLite:

```bash
DB_CONNECTION=sqlite DB_DATABASE="$(pwd)/database/database.sqlite" \
ANALYTICS_CONNECTION=sqlite \
php artisan test
```

A single file:

```bash
DB_CONNECTION=pgsql ANALYTICS_CONNECTION=pgsql php artisan test tests/Feature/ImportTest.php
```

The second connection, `warehouse`, is always SQLite at `database/warehouse.sqlite`. It stays SQLite
on every run, which is what the cross-connection and import tests exercise.

## Building the Models by Hand

```bash
php artisan analytics:graph
php artisan analytics:compile TrialBalance
php artisan analytics:sync
php artisan analytics:sync EventCounts --only
php artisan analytics:sync --full-refresh
php artisan analytics:test
```

## Checking the Package

Run the package's own suite from a local checkout:

```bash
cd ../laravel-analytics && composer test
```

## Troubleshooting

If a run dies partway through, `ParallelSyncTest` can leave generated models behind:

```bash
rm -rf app/Analytics/Broken app/Analytics/Late
```

They join the real model graph and make every later `analytics:sync` fail with an unrelated error.
