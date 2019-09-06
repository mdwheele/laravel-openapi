FROM php:7-cli

# Avoid warnings by switching to noninteractive
ENV DEBIAN_FRONTEND=noninteractive
ENV PYTHONUNBUFFERED 1

# Update
RUN apt-get update

# This Dockerfile adds a non-root 'developer' user with sudo access. However, for Linux,
# this user's GID/UID must match your local user UID/GID to avoid permission issues
# with bind mounts. Update USER_UID / USER_GID if yours is not 1000.
ARG USER_USERNAME=developer
ARG USER_UID=1000
ARG USER_GID=$USER_UID

# Create a non-root user to use if preferred - see https://aka.ms/vscode-remote/containers/non-root-user.
RUN groupadd --gid $USER_GID $USER_USERNAME \
    && useradd -s /bin/bash --uid $USER_UID --gid $USER_GID -m $USER_USERNAME \
    && apt-get install -y sudo \
    && echo $USER_USERNAME ALL=\(root\) NOPASSWD:ALL > /etc/sudoers.d/$USER_USERNAME\
    && chmod 0440 /etc/sudoers.d/$USER_USERNAME

# Configure apt and install packages
RUN apt-get update \
    && apt-get -y install --no-install-recommends apt-utils dialog 2>&1 \
    #
    # Verify git, process tools, lsb-release (common in install instructions for CLIs) installed
    && apt-get -y install git iproute2 procps lsb-release

# Install PHP dependencies and extensions
RUN apt-get install -y --no-install-recommends \
    bash-completion \
    mariadb-client \
    less \
    sudo \
    ssh \
    curl \
    git \
    vim \
    nano \
    wget \
    unzip \
    libzip-dev \
    libmemcached-dev \
    libjpeg-dev \
    libz-dev \
    libpq-dev \
    libssl-dev \
    libmcrypt-dev \
    libldap2-dev \
    pcscd \
    scdaemon \
    gnupg2 \
    pcsc-tools

RUN docker-php-ext-install pdo_mysql \
  && docker-php-ext-install zip \
  && docker-php-ext-install ldap

# Configure xDebug
RUN yes | pecl install xdebug \
    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.default_enable=off" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.ide_key=DEBUG" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_port=9000" >> /usr/local/etc/php/conf.d/xdebug.ini

ARG ENABLE_XDEBUG
RUN if [ "x$ENABLE_XDEBUG" = "x1" ] ; then docker-php-ext-enable xdebug; else echo Skipping xdebug activation!; fi

# Install Composer
RUN curl --silent --show-error https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

# Install NodeJS
RUN curl -sL https://deb.nodesource.com/setup_12.x | bash - \
  && apt-get install -y nodejs

# Clean up
RUN apt-get autoremove -y \
    && apt-get clean -y \
    && rm -rf /var/lib/apt/lists/*

# Switch back to dialog for any ad-hoc use of apt-get
ENV DEBIAN_FRONTEND=

WORKDIR /package
