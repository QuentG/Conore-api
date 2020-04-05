# API Documentation

<img src="/.github/images/conore.svg" align="right" />

[Full API documentation](https://app.gitbook.com/@axel-paris/s/woder/)

# Installation

## Prerequisites

Install the docker stack as defined in the [tools repository](https://gitlab.com/conore/api/-/tree/master/docker/README.md)

## Install

Connect to the apache container and follow steps :

```bash
# Connect to container
docker exec -it api_conore-api-php_1 bash

cd api/

# Composer
composer install --no-interaction

# Install a fresh & empty database
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:schema:update --force
```

## Test

http://api.local.conore.com

## More

Mysql container available here : 

```bash
# Connect to container
docker exec -it api_conore-api-php_1 bash
```
