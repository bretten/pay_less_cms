FROM ubuntu:22.04

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND noninteractive
ENV TZ=UTC

COPY . ./pay_less_cms
COPY .env.example ./pay_less_cms/.env
COPY docker_startup.sh ./pay_less_cms/.docker_startup.sh

RUN apt-get update \
    && apt-get install -y php8.1 php8.1-pgsql php8.1-xml php8.1-curl \
    && apt-get install -y vim \
    && apt-get install -y git \
    && apt-get install -y postgresql-client \
    && apt-get install -y php-zip \
    && php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer \
    && composer install --ignore-platform-reqs --working-dir=/var/www/html/pay_less_cms \
    && php pay_less_cms/artisan key:generate \
    #&& php pay_less_cms/artisan migrate \ # Running this will NOT use env vars in docker compose since it is the build phase
    && chmod +x /var/www/html/pay_less_cms/docker_startup.sh

COPY php.ini /etc/php/8.1/cli/conf.d/99-custom.ini

EXPOSE 8001

CMD ["/var/www/html/pay_less_cms/docker_startup.sh"]
#CMD ["/usr/bin/php", "/var/www/html/pay_less_cms/artisan", "serve", "--host=0.0.0.0", "--port=80"]

# Run the following to override for specific app:
# docker run -it -dp 8000:80 -v "C:\path\to\repo\on\host\:/var/www/html/[app_name]" --name [container_name] [image_name] "/var/www/html/[app_name]/artisan" "serve" "--host=0.0.0.0" "--port=80"
