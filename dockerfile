# Cobalt Engine - (c) 2021 - Heavy Element, Inc.
# To build this image, use the following command:
# cd /srv/development/
# docker build -f app/dockerfile -t heaveyelement/docker .
# Once the build process has completed, run:
# docker run -p 8080:80 -e COBALT_MONGODB=mongodb://<ip>:27017 -d <image-name>
FROM docker.io/ubuntu:20.04

# Change this to the pathname of your Cobalt App
ARG APP=app
LABEL maintainer="maintainer@heavyelement.io" developer="heavyelement.io"

# Upgrade packages and set timezone
# i.e. obnoxious boilerplate Ubuntu crap
RUN export DEBIAN_FRONTEND=noninteractive && \
    export TZ=America\New_York && \
    apt-get update && \
    #install tzdata package
    apt-get -y install tzdata && \
    # set your timezone
    ln -fs /usr/share/zoneinfo/America/New_York /etc/localtime && \
    dpkg-reconfigure --frontend noninteractive tzdata

# Might not be complete, yet?
RUN apt-get -y install apache2 imagemagick php7.4 php-bcmath php-common \
    php-curl php-gd php-gmp php-http php-igbinary php-imagick php-intl php-json \
    php-mbstring php-mongodb php-mysql php-pear php-propro php-raphf php-redis \
    php-xml php-zip php7.4-bcmath php7.4-cli php7.4-common php7.4-curl php7.4-dev \
    php7.4-gd php7.4-gmp php7.4-intl php7.4-json php7.4-mbstring php7.4-mysql \
    php7.4-opcache php7.4-readline php7.4-xml php7.4-zip

# Enable the apache rewrite module
RUN a2enmod rewrite
# Disable error reporting
RUN sed -i "s/error_reporting = .*$/error_reporting = E_ERROR | E_WARNING | E_PARSE/" /etc/php/7.4/apache2/php.ini
# @todo disable debug mode automatically

ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_LOCK_DIR /var/lock/apache2
ENV APACHE_PID_FILE /var/run/apache2.pid

# Copy the core files
COPY cobalt-core /var/www/cobalt-core/

# Copy the app-specific files
# Change "app" to your app's name
COPY $APP /var/www/cobalt-app/

# Add the Cobalt CLI to PATH
RUN ln -s /var/www/cobalt-app/cobalt.sh /bin/cobalt && \
    chmod +X /bin/cobalt

# Clear cached stuff so environment variables can be caught
RUN rm -rf /var/www/cobalt-app/cache/config
RUN rm /var/www/cobalt-app/ignored/init.json.set

# Copy the apache configuration
COPY $APP/private/config/apache/apache.conf /etc/apache2/sites-enabled/000-default.conf

# Make sure Apache can read the app files
RUN chown www-data:www-data -R /var/www

# Expose Apache's HTTP and MongoDB ports
EXPOSE 80 27017

# Run Apache
CMD apachectl -D FOREGROUND