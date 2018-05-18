FROM php:7.2-apache

RUN docker-php-ext-install mysqli
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pgsql

# Memory Limit
RUN echo "memory_limit=1024M" > $PHP_INI_DIR/conf.d/memory-limit.ini

# Display Errors
RUN echo "display_errors=On" > $PHP_INI_DIR/conf.d/display-errors.ini
RUN echo "error_reporting=E_ALL" >> $PHP_INI_DIR/conf.d/display-errors.ini
