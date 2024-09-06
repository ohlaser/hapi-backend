FROM ubuntu/apache2

RUN apt update && apt install -y \
    php \
    libapache2-mod-php \
    && apt clean \
    && rm -rf /var/lib/apt/lists/*

# 必要な拡張モジュールをインストール
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
RUN rm -r /var/www
COPY var/ /var/

# Apache2 の起動スクリプト
CMD ["apachectl", "-D", "FOREGROUND"]
