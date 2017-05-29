FROM registry.gitlab.com/thecodeine-docker/php:7.0-fpm

ARG SYMFONY_ENV
ENV SYMFONY_ENV $SYMFONY_ENV

RUN echo -e "always_populate_raw_post_data = -1" >> $CUSTOM_PHP_INI_PATH \
	&& echo -e "cgi.fix_pathinfo = 1" >> $CUSTOM_PHP_INI_PATH

RUN echo -e "" >> $CUSTOM_PHP_FPM_PATH \
    && echo -e "[www]" >> $CUSTOM_PHP_FPM_PATH \
    && echo -e "user = root" >> $CUSTOM_PHP_FPM_PATH \
    && echo -e "group = root" >> $CUSTOM_PHP_FPM_PATH

RUN mkdir -p /usr/src/app
WORKDIR /usr/src/app

COPY docker-php-entrypoint.sh /
COPY mautic.crontab /var/spool/cron/crontabs/www-data

ENTRYPOINT ["/docker-php-entrypoint.sh"]

CMD ["php-fpm", "-R"]
