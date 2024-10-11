FROM ubuntu/apache2

RUN apt update && apt install -y \
    rsync \
    php \
    libapache2-mod-php \
    && apt clean \
    && rm -rf /var/lib/apt/lists/*

# 必要なPHP拡張モジュールをインストール
RUN apt update && apt install -y \
    php-mysql \
    php-curl \
    php-json \
    php-xml \
    php-mbstring \
    && apt clean \
    && rm -rf /var/lib/apt/lists/*

# PHPの設定を有効化
RUN PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;") && a2enmod php$PHP_VERSION
RUN a2enmod rewrite

# gitリポジトリを展開
RUN mkdir /var/hapi
COPY app/var/ /var/hapi

# php composer の準備、実行
RUN php -r \
    "copy('https://getcomposer.org/installer', 'composer-setup.php'); \
    if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { \
        echo 'Installer verified'; \
    } else { \
        echo 'Installer corrupt'; \
        unlink('composer-setup.php'); \
    } \
     echo PHP_EOL; \
     "
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"

RUN cd app/ && composer install --no-dev --prefer-dist --no-progress --no-suggest --optimize-autoloader
COPY app/vendor/* /var/hapi/www/html/api/vendor/

# 設定ファイルをコピーして適用 
COPY apache2.conf /etc/apache2/apache2.conf
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf

# TODO: ファイルシステム権限関係
# ...

# Apache2 の起動スクリプト
CMD ["apachectl", "-D", "FOREGROUND"]
