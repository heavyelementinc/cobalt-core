![Cobalt Engine](shared/img/branding/cobalt-logo.svg)
# Cobalt Engine
## By Heavy Element
This engine is meant to provide developers with a simple and intuitive router-based web development frontend. It's compatible with Docker and easily deployed.

Cobalt provides an all-in-one framework which includes
 * An expressive HTML template engine
 * A hybrid backend/frontend routing system
 * Route contexts for both HTML content and RESTful APIs
 * Frontend WebComponents for easily interacting with the API
 * User account management and extendable permission system

# Getting Started
To get started with Cobalt, you'll need at least PHP version 7.4 and MongoDB on your system. 

## On Ubuntu:
```
  sudo apt install apache2 php7.4 php-mongodb
  # Depending on your setup, you'll also want MongoDB set up on your system
  sudo a2enmod rewrite
```

## MongoDB
If you have enabled MongoDB authentication, you'll want to provide your username and password in `app_directory/ignored/settings.json`. You *can* add these to your `app_directory/private/config/settings.json` but this is **not** recommended.

If you're running Cobalt in a Docker container, you can specify your MongoDB connection information as an environment variable.

```
COBALT_MONGODB=mongodb:\\localhost:27017

--- OR ---

COBALT_MONGODB=mongodb:\\username:password@localhost:27017
```

## Cloning this repo
If you plan on cloning this repository, you must run composer as the `www-data` user in the cobalt-core directory.

## Creating your first project
Copy `core/cli/app/` to be a sibling directory of this project. Rename it to whatever you'd like. Point your webserver's webroot to `[app-name]/public` and you should be good to go!

