# Local installation for development

## Prerequisites

Install Docker

Install docker-compose

### Mac

Install Docker for Mac : https://store.docker.com/editions/community/docker-ce-desktop-mac

## Source code

Get the source code of the other projets that you need:

```bash
# Api (Symfony 4.3)
git clone https://gitlab.com/conore/api.git
```

## Stack

Add in your hosts file:

```
127.0.0.1 api.local.conore.com
```

Run the docker stack:

```bash
cd api/
docker-compose up
```

## Applications

Check this URL:

http://api.local.conore.com

If you want to install one of the apps:

- [Api](https://gitlab.com/conore/api)

If you need to open a bash on one of the containers:

```bash
# Apache server
docker exec -it api_conore-api-php_1 bash
```
