# Cobalt Engine

<img align="right" src="shared/img/branding/cobalt-logo.svg" alt="Heavy Element" height="200" width="auto">
Cobalt Engine is meant to provide developers with a simple and intuitive router-based web development frontend. It's compatible with Docker and easily deployed.

Cobalt aims to provide an all-in-one framework which includes
 * An expressive HTML template engine
 * A hybrid backend/frontend routing system
 * Route contexts for both HTML content and RESTful APIs
 * Frontend WebComponents for easily interacting with the API
 * User account management and extendable permission system

It was created by [Heavy Element, Inc](https://heavyelement.io).

> **NOTE** *Cobalt Engine is still in an ALPHA STATE. That means it is currently **not considered stable**. We are constantly adding new features, removing broken ones, and tweaking thing. If you use Cobalt in production, you do so at **your own risk**.*

# Getting Started

## Preparation
To get started with Cobalt, you'll need at least PHP version 7.4 and MongoDB on your host system. 

### On Ubuntu
```shell
  sudo apt install apache2 php7.4 php-mongodb
  # Depending on your setup, you might also want MongoDB set up on your system
  sudo a2enmod rewrite
```

## Get the Code
Decide where you want to have your files live. For the purposes of this tutorial, we'll choose `/var/www/`

> Hint: it might be helpful for you to `su` into your webserver's user account to perform these operations. On Ubuntu, that would probably be `www-data`

Let's clone this repository and install our dependencies.

```shell
cd /var/www/
git clone https://github.com/heavyelementinc/cobalt-core.git
cd cobalt-core
composer install
```
If you do not have `composer` installed in your PATH, you can get it [here](https://getcomposer.org/).

## Creating your first project
1. In your terminal, `cd` into your `./cobalt-core` directory which you cloned in the last step.
2. Type `./core.sh project init` and hit enter
   * On Windows, you will need to run `php.exe ".\cli\cobalt.php" project init` (untested)
3. Answer the prompts

Your new project will be created based on the answers to the CLI's questions. For this tutorial, we'll assume you named your project `my-project`.

## Creating your first user account
1. In your terminal, `cd` into your project's main directory. (In this tutorial, that would be `my-project`)
2. Type `./core.sh user create` and hit enter
    * On Windows you will need to run `php.exe ".\cli\cobalt.php" user create` (untested)
3. Answer the prompts
4. Alternatively, you may specify a username, password, and email address in your command:
   * `./core.sh user create username p!sswOrd123 user@example.com`
     * Passwords specified in this manner **cannot include spaces**
   * **NOTE:** if you do not specify one or more of the above items, you will be prompted for them

## MongoDB
If you have enabled MongoDB authentication you'll want to provide your username and password in `app_directory/ignored/settings.json`. You *can* add these to your `app_directory/private/config/settings.json` but this is **not** recommended.

If you're running Cobalt in a Docker container, you can specify your MongoDB connection information as an environment variable.

```shell
COBALT_MONGODB=mongodb:\\localhost:27017
# OR
COBALT_MONGODB=mongodb:\\username:password@localhost:27017
```