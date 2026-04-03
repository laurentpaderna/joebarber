FROM ubuntu:22.04

ARG DEBIAN_FRONTEND=newt

#install git
#RUN apt-get update && \
#apt-get install -y git

#install development tools
RUN apt-get update && \
apt-get install -y build-essential

#install tool for checking repository
RUN apt-get install -y software-properties-common

#set repository for php
RUN apt-add-repository ppa:ondrej/php && \
apt-get update

#install php and extensions
RUN apt-get -y update && \
apt-get install -y \
php8.2 \
php8.2-dev \
php8.2-cli \
php8.2-bcmath \
php8.2-gd \
php8.2-opcache \
php8.2-memcached \
php8.2-common \
php8.2-curl \
php8.2-xml \
php8.2-xmlrpc \
php8.2-imap \
php8.2-mbstring \
php8.2-zip \
php8.2-imagick \
php8.2-soap \
php8.2-intl \
php8.2-fpm \
libapache2-mod-php8.2 \
php8.2-mysql
#php8.2-mcrypt \
#php8.2-sqlite \

# set extra php.ini settings for php
RUN { \
echo 'date.timezone = Europe/Paris'; \
echo 'upload_max_filesize = 1024M'; \
echo 'post_max_size = 1024M'; \
echo 'memory_limit = 1024M'; \
echo 'extension=pdo.so'; \
echo 'extension=pdo_mysql.so'; \
echo 'extension=mcrypt.so'; \
echo 'SMTP mail.server.com'; \
echo 'smtp_port 1025'; \
} > /etc/php/8.2/apache2/php.in
COPY php.ini /etc/php/8.2/cli/php.ini

#switch to preferred version of php
RUN update-alternatives --set php /usr/bin/php8.2

#install apache
RUN apt-get -y update && \
apt-get install -y apache2

#install editing tool
RUN apt-get -y update && \
apt-get install -y \
sudo \
mcrypt \
nano \
rsync \
vim \
wget \
curl \
ca-certificates \
gpg

RUN apt-get install locales
RUN locale-gen fr_FR.UTF-8
RUN locale-gen en_US.UTF-8
RUN locale-gen de_DE.UTF-8

# to be able to use "nano" with shell on "docker exec -it [CONTAINER ID] bash"
#ENV TERM xterm

# allow .htaccess with RewriteEngine
RUN a2enmod rewrite

# autorize .htaccess files
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

#RUN chown -R www-data:www-data /var/www
#RUN chmod 755 -R /var/www
RUN chgrp -R www-data /var/www
RUN find /var/www -type d -exec chmod 775 {} +
RUN find /var/www -type f -exec chmod 664 {} +

# add mysql & mysqlpackage
RUN apt-get update \
	&& apt-get -y install mysql-server
RUN apt-get update \
	&& apt-get -y install mysql-client

# add composer
RUN cd ~ \
	&& php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
	&& php composer-setup.php \
	&& php -r "unlink('composer-setup.php');" \
	&& mv composer.phar /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER 1

# Install sendmail and required tools
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    sendmail \
    mailutils \
    && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Configure sendmail to use bytemark/smtp
#RUN echo "sendmail_path = /usr/sbin/sendmail -t -i" > /usr/local/etc/php/conf.d/sendmail.ini

#set local mail account
RUN apt-get update && apt-get install -y msmtp mailutils

RUN rm -rf /etc/apache2/sites-enabled/*

#add current host to apache
COPY vhost.conf /etc/apache2/sites-available/joebarber.local.com.conf

#backup original Virtual Host conf
RUN cp /etc/apache2/sites-available/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf.bak

#set custom SSL Virtual Host && https certificate
COPY vhost.conf /etc/apache2/sites-available/vhost.conf
COPY joebarber.local.com.crt /etc/ssl/certs/joebarber.local.com.crt
COPY joebarber.local.com.key /etc/ssl/private/joebarber.local.com.key

#set custom SSL Virtual Host
COPY vhost.conf /etc/apache2/sites-available/vhost.conf

#enable module for https
RUN a2enmod headers \
  && a2enmod expires \
  && a2enmod ssl

#enable custom site configuration
RUN a2ensite joebarber.local.com

#USER lhci
#WORKDIR /home/lhci/reports
RUN chown root:users /var/log/apache2

VOLUME /var/www/html

WORKDIR /var/www/html

#expose ports for normal host & http host
EXPOSE 80
EXPOSE 443

#expose port for mail through smtp
EXPOSE 25
EXPOSE 2525

# start Apache2 on image start
CMD ["/usr/sbin/apache2ctl","-DFOREGROUND"]
