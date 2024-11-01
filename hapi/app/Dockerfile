FROM ubuntu/apache2 as builder

RUN apt update && apt install -y \
    php \
    php-curl \
    php-mbstring \
    git \
    ca-certificates
    
# PHPの設定を有効化
RUN PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;") \
    && a2enmod php$PHP_VERSION

COPY composer.json /composer.json

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
    " \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && php composer.phar install --no-dev --prefer-dist --no-progress --optimize-autoloader


# 最終イメージ
FROM ubuntu/apache2 as final

RUN apt update && apt install -y \
    # rsync \
    php \
    ca-certificates \
    libapache2-mod-php \
    \ 
    php-mysql \
    php-curl \
    php-json \
    php-xml \
    php-mbstring \
    && apt clean \
    && rm -rf /var/lib/apt/lists/*

# PHPの設定を有効化
RUN PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;") \
    && a2enmod php$PHP_VERSION \
    && a2enmod rewrite

# gitリポジトリを展開
RUN mkdir /var/hapi
COPY var/hapi/ /var/hapi/

# 設定ファイルをコピーして適用 
COPY etc/apache2/ /etc/apache2/

# 中間イメージからコピー
RUN mkdir -p /var/hapi/www/html/api/vendor/
COPY --from=builder /vendor/ /var/hapi/www/html/api/vendor/

RUN cd /var/hapi/scripts/ && chmod 744 ./testmode.sh && ./testmode.sh disable

# TODO: ファイルシステム権限関係
# ...

# Apache2 の起動スクリプト
CMD ["apachectl", "-D", "FOREGROUND"]
