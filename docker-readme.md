# Docker Deployment
## Container configuration
  * Install the latest MongoDB Docker image
    * `docker pull mongo`
    * `docker run --name cobalt-mongo -d mongo`
  * Create a new Docker network
    * `docker network create cobalt-network`
    * `docker network connect cobalt-network cobalt-mongo`
  * Get the IP of the mongo container
    * `docker network inspect cobalt-network`
    * Look for the "Containers" section, find cobalt-mongo's IP (usually **172.19.0.2**)
  * Install the site's Docker image
    * `docker run --name cobalt-site -p 8081:80 -e COBALT_MONGODB=mongodb://[ip]:27017 -d [image]`
    * Replacing [ip] and [image] with the correct values.
      * If you're using database authentication or a different default port for your Mongo instance, you'll need to tell your Cobalt image:
      * `-e COBALT_MONGODB=mongodb://[user]:[password]@[ip]:[port]`
  * Add the site's container to the Docker network
    * `docker network connect cobalt-network cobalt-site`
  
## Host configuration
  * Configure your Apache/NGINX reverse proxy to Cobalt's Docker image can tell which HTTP mode we're in (HTTP/S)
    * In your HTTP configuration add `proxy_set_header X-Forward-Proto http;`
    * In your HTTPS configuration add `proxy_set_header X-Forward-Proto https;`
  * Find a sample below:

```xml
<VirtualHost *:80>
        ServerName cobaltsite.com
        ServerAdmin admin@cobaltsite.com
        
        ProxyRequests Off
        <proxy *>
                AddDefaultCharset off
                Order Allow,Deny
                Allow from all
        </proxy>
        ProxyPass / http://127.0.0.1:8081/
        ProxyPassReverse / http://127.0.0.1:8081/

        proxy_set_header   X-Forwarded-Proto http;    # In your HTTPS config, change this to "https"

        ErrorLog ${APACHE_LOG_DIR}/cobalt.log
</VirtualHost>
```

### Install SSL
```
    # On your host machine, run
    sudo apt install certbot python3-certbot-apache
    sudo certbot --apache
```
Follow the prompts.

## Site configuration
  * Finally, using your browser, log into your site and create a user account using the on-screen prompts.

## Accessing Cobalt's CLI
  * To access the Cobalt CLI, run `docker exec -i -t -u www-data <image-name> /bin/bash`
  * Once in the container's shell, run `cobalt help all` for a list of options
    * Commands will be listed in this format:

> [ help ]
>   all            List ALL supported commands and subcommands.
>   <command>      List every subcommand of given command where <command> is the name of any valid command.
>   flags          List known flags.
>
>[ user ]
>   create         [firstname [username [password [email]]]] - Create new user.
>   list           limit [skip] - List registered user accounts
>   password       username|email - Change a user's password
>   promote        username|email grant the user root privileges

If we wanted to find every operand for the "user" command, we could run `cobalt help user`

If we wanted to create a new user we'd run `cobalt user create` and we can provide optional arguments to the create command.

By combining the parent `[ command ]` with its child commands, we can perform any of the listed actions.