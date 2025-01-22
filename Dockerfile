#Dockerfile
FROM php:8.2-fpm

# Atualiza os pacotes do sistema
RUN apt-get update

# Instala as dependências necessárias para o PHP e o Memcached
RUN apt-get install -y libpq-dev libmemcached-dev zlib1g-dev

# Instala a extensão PDO para PostgreSQL
RUN docker-php-ext-install pdo_pgsql

# Instala e habilita a extensão Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Instala e habilita a extensão Memcached
RUN pecl install memcached && docker-php-ext-enable memcached

# Limpa os arquivos temporários do APT para reduzir o tamanho da imagem
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html
COPY ./php/php.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
WORKDIR /var/www/html