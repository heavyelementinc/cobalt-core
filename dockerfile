# Cobalt Engine - (c) 2021 - Heavy Element, Inc.
# To build this image, use the following command:
# cd /srv/development/
# docker build -f mercury/dockerfile .
FROM docker.io/ubuntu:20.04

LABEL maintainer="gardiner@heavyelement.io" developer="heavyelement.io"

# Upgrade packages and set timezone
# i.e. obnoxious boilerplate Ubuntu crap
RUN export DEBIAN_FRONTEND=noninteractive && \
    export TZ=America\New_York && \
    apt-get update && \
    # Install tzdata package
    apt-get -y install tzdata && \
    # Set our time zone
    ln -fs /usr/share/zoneinfo/America/New_York /etc/localtime && \
    dpkg-reconfigure --frontend noninteractive tzdata && \
    # Install all the dependencies for our application
    apt-get -y install apache2 imagemagick php7.4 php-bcmath php-common \
    php-curl php-gd php-gmp php-http php-igbinary php-imagick php-intl php-json \
    php-mbstring php-mongodb php-mysql php-pear php-propro php-raphf php-redis \
    php-xml php-zip php7.4-bcmath php7.4-cli php7.4-common php7.4-curl php7.4-dev \
    php7.4-gd php7.4-gmp php7.4-intl php7.4-json php7.4-mbstring php7.4-mysql \
    php7.4-opcache php7.4-readline php7.4-xml php7.4-zip && \
    # Enable the apache rewrite module
    a2enmod rewrite

# Copy the core files
COPY core /var/www/core/

# Copy the app-specific files
##### MAKE SURE YOU SPECIFY YOUR APPLICATION'S NAME HERE! #####
COPY mercury /var/www/app/

# Copy the apache configuration
COPY config/apache/app.conf /etc/apache2/sites-enabled/000-default.conf

# Give Apache read permissions for our application
RUN chown www-data:www-data -R /var/www

# Expose Apache's HTTP and MongoDB ports
EXPOSE 80 27017

# Run Apache
CMD apachectl -D FOREGROUND