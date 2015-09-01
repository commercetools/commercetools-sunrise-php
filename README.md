# commercetools-sunrise-php

SUNRISE PHP is a template shopfront implementation that implements a complete online shop on the commercetools eCommerce platform using the following components:

 * the [commercetools PHP SDK](https://github.com/sphereio/commerctools-php-sdk) and its corresponding [commons library](https://github.com/sphereio/commerctools-php-commons)
 * the [commercetools SUNRISE](https://github.com/sphereio/sphere-sunrise-design) responsive HTML templates (handlebars syntax)
 * the [silex microframework](http://silex.sensiolabs.org/) (part of the Symfony ecosystem and using lots of Symfony components)

It aims to focus on being slim, easy to understand and fast, delegating reusabilty into the commons and symfony-components libraries. 
 
# Prerequisites

 * PHP (see the SDK for detailed requirements)
 * composer
 * (for now) node.js & npm installed (for building the templates)

# Run

To run, you need a project on the commercetools platform that contains some minimum necessary content (e.g. the sample data). Then get your project key, client ID and client secret from the merchant center and provide them in one of the following ways:

 1. TODO how to get stuff into the $_SERVER vars ? environment variables? CGI? 
 2. create a file `app/myapp.ini` with the following format:
 
```ini
[commercetools]
project = foo
client_id = bar
client_secret = baz
```

For local development purposes you can run the application in the php built-in web server. The `web` folder contains static assets and index.php as the _only_ PHP file.

```php
php -S localhost:8000 -t web/ web/index.php
```
Open [http://localhost:8000/](http://localhost:8000/)

# Develop

To improve the template, we are happily receiving pull requests with improvements and bug fixes. If you'd like to change bigger things or add features it's a good idea to discuss the idea in a github issue first. 

IMPORTANT: The composer configuration clones the PHP commons library and the sunrise designs inside the `vendor` directory. So pay attention when doing git operations with changes in these files. 

## Using the command line

TODO

# Create your own shop

Fork and forge your own eCommerce solution from it. 

More specifically:

 * fork the project or just copy the code (the MIT license of this code and the SUNRISE design allow unrestricted commercial use and modification)
 * you may want to change the namespace of some things to your own project's name (TODO the template could use a generic namespace from the beginning)
 * EITHER override and change the SUNRISE design by creating a `templates` directory in the project folder and overriding the `*.hbs` filenames you find in `/vendor/commercetools/sunrise-design/input/templates`
 * OR create an own HTML and templates structure from scratch using your preferred tools and template language
 * add and change stuff as you like
 * Be nice and contribute useful generic helpers back to the OSS commons library.  
