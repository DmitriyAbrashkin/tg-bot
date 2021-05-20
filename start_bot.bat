@echo off

:start

php artisan get:updates
TIMEOUT /T 1

goto start
