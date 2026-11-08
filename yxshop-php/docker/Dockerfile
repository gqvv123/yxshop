# FROM php:7.4.33-cli-alpine
#FROM php:8.1.28-cli-alpine
FROM php:8.2.18-cli-alpine
# FROM php:8.3.4-cli-alpine

# Container package  : mirrors.163.com、mirrors.aliyun.com、mirrors.ustc.edu.cn
RUN sed -i "s/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g" /etc/apk/repositories
# RUN sed -i "s/dl-cdn.alpinelinux.org/mirrors.ustc.edu.cn/g" /etc/apk/repositories

RUN cat /etc/issue

# [php74] Add basics first
RUN apk update && apk add bash curl ca-certificates openssl openssh git libxml2-dev tzdata icu-dev openntpd libedit-dev libzip-dev libjpeg-turbo-dev libpng-dev freetype-dev autoconf dpkg-dev dpkg file g++ gcc libc-dev make pkgconf re2c pcre-dev libffi-dev libressl-dev libevent-dev zlib-dev libtool automake supervisor

# [php8] Add basics first
# RUN apt-get update && apt-get upgrade && apt-get install bash curl ca-certificates openssl openssh git nano libxml2-dev tzdata icu-dev openntpd libedit-dev libzip-dev libjpeg62-turbo-dev libpng12-dev libfreetype6-dev autoconf dpkg-dev dpkg file g++ gcc libc-dev make pkgconf re2c pcre-dev openssl-dev libffi-dev libressl-dev libevent-dev zlib-dev libtool automake supervisor

COPY ./extension /tmp/extension
WORKDIR /tmp/extension
RUN chmod +x install.sh \
    && sh install.sh \
    && rm -rf /tmp/extension    

# 安装 Redis 扩展 (关键添加)
RUN pecl install redis && \
docker-php-ext-enable redis

RUN php -m

# Add Composer
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

# Configure PHP
COPY config/php.ini /usr/local/etc/php/conf.d/zzz_custom.ini

# Configure supervisord
COPY config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Make sure files/folders needed by the processes are accessable when they run under the nobody user
RUN chown -R nobody.nobody /run && chown -R nobody.nobody /app

# Setup document root
RUN mkdir -p /app

# Make the document root a volume
VOLUME /app

#echo " > /usr/local/etc/php/conf.d/phalcon.ini
# Switch to use a non-root user from here on
USER nobody

# Add application
WORKDIR /app

# Expose the port nginx is reachable on
EXPOSE 8777

# Let supervisord start nginx & php
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]