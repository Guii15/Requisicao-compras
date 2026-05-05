#!/bin/bash
php artisan migrate --force
php artisan queue:work --tries=3 --timeout=60 &
php artisan serve --host=0.0.0.0 --port=$PORT
