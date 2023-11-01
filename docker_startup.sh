#!/bin/sh

php /var/www/html/pay_less_cms/artisan migrate
php /var/www/html/pay_less_cms/artisan db:seed
php /var/www/html/pay_less_cms/artisan serve --host=0.0.0.0 --port=80
