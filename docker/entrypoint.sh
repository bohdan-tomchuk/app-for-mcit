#!/bin/sh
set -e

php -r '
$dsn = sprintf("pgsql:host=%s;port=%s;dbname=%s", getenv("DB_HOST"), getenv("DB_PORT") ?: "5432", getenv("DB_DATABASE"));
for ($i = 0; $i < 60; $i++) {
    try { new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0); } catch (Throwable $e) { sleep(1); }
}
fwrite(STDERR, "Postgres недоступний\n");
exit(1);
'

php artisan migrate --force

if [ "$(php artisan tinker --execute='echo App\Models\User::count();')" = "0" ]; then
    php artisan db:seed --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec docker-php-entrypoint "$@"
