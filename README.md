# commercetools-sunrise-php

:warning: **The Sunrise php shopfront implementation is now deprecated and will be decommissioned in 2020.**
If you would nevertheless like to use it for your own developments you should fork this repository under the given license and continue developing your version, but please be aware that there will be no more updates and no support provided to this framework. 

SUNRISE PHP is a template shopfront implementation that implements a complete online shop on the commercetools eCommerce platform using the following components:

 * the [commercetools PHP SDK](https://github.com/commercetools/commercetools-php-sdk) and its corresponding [commons library](https://github.com/commercetools/commercetools-php-commons)
 * the [commercetools SUNRISE](https://github.com/commercetools/sphere-sunrise-design) responsive HTML templates (handlebars syntax)
 * the [symfony framework](http://symfony.com/) (using the MicrokernelTrait)

It aims to focus on being slim, easy to understand and fast, delegating reusabilty into the commons and symfony-components libraries.

Please read the [Changelog](CHANGELOG.md) before updating in any case.

# Prerequisites

 * PHP (see the SDK for detailed requirements)
 * composer

# Run

To run, you need a project on the commercetools platform that contains some minimum necessary content (e.g. the sample data). Then get your project key, client ID and client secret from the merchant center and provide them in one of the following ways:

 1. create a file `app/config/parameters.yml` with the following format:

```yml
parameters:
    env(SECRET_TOKEN): Secr3tT0ken
    env(CTP_CLIENT_ID): client-id
    env(CTP_CLIENT_SECRET): client-secret
    env(CTP_PROJECT): project-key

```

For local development purposes you can run the application in the php built-in web server. The `web` folder contains static assets and index.php as the _only_ PHP file.

Production Site:
```php
php bin/console server:run --env prod
```

Development Site:
```php
php bin/console server:run
```

Open [http://localhost:8000/](http://localhost:8000/)

# Develop

To improve the template, we are happily receiving pull requests with improvements and bug fixes. If you'd like to change bigger things or add features it's a good idea to discuss the idea in a github issue first.

IMPORTANT: The composer configuration clones the PHP commons library and the sunrise designs inside the `vendor` directory. So pay attention when doing git operations with changes in these files.

## Using the command line

### Available console commands
```
php bin/console list
```

### Cache warm up

```
php bin/console cache:warmup
```

## Deployment

### Heroku

For an easy and fast deployment of your application we recommend [heroku](https://www.heroku.com):

<a href="https://heroku.com/deploy?template=https://github.com/commercetools/commercetools-sunrise-php/tree/develop"><img src="https://www.herokucdn.com/deploy/button.png" alt="Deploy"></a>

### Docker

The Sunrise app is delivered with a docker compose configuration.

```sh
docker run -v $PWD:/var/www/symfony -w /var/www/symfony --rm=true jaysde/symfony-php-fpm composer install --prefer-dist
docker-compose up
```

This starts a nginx and a php-fpm docker container serving the application.

# Create your own shop

Fork and forge your own eCommerce solution from it.

More specifically:

 * fork the project or just copy the code (the MIT license of this code and the SUNRISE design allow unrestricted commercial use and modification)
 * you may want to change the namespace of some things to your own project's name (TODO the template could use a generic namespace from the beginning)
 * EITHER override and change the SUNRISE design by creating a `templates` directory in the project folder and overriding the `*.hbs` filenames you find in `vendor/commercetools/sunrise-design/templates`
 * OR create an own HTML and templates structure from scratch using your preferred tools and template language
 * add and change stuff as you like
 * Be nice and contribute useful generic helpers back to the OSS commons library.
