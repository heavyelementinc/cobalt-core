# What is a Cron Task?
Cron Tasks are Classes that are automatically executed at specific intervals. Please note that for this to work, you must set up your host system to execute cron tasks automatically. On Ubuntu Linux, add the following to your root user's crontab.

Step one, execute this command:
`sudo crontab -e -u root`

Step two, add this line:
```sh
*/5 * * * * /bin/su www-data -s /bin/bash -c '/PATH/TO/APP/cobalt.sh cron exec'
```

> Note that the above specifies the `www-data` user. If Apache/Nginx is running as a different user, change the the above command accordingly.

> Note that you *do not want* to have your `www-data` user have their own crontab. This is a security risk as it can be used to execute arbitrary payloads. Instead, we have the root user run the cron command on behalf of the `www-data` user.

# Specify a new Cron Task
Use the `/PATH/TO/APP/private/config/cron/tasks.json` file to specify a list of tasks you wish to have executed.

Use the following format to specify a new task in the above file:
```json
    {
        "type": "DefaultType", // Use `DefaultType` for tasks that execute a class->method
        "name": "Expire Sessions", // Give the task a descriptive name
        "class": "\\Auth\\SessionManager", // Specify a class to instance
        "class_args": [], // List any class arguments here
        "method": "destroy_expired_sessions", // Specify the method to be executed
        "method_args": [], // List any method arguments here
        "interval": 3600 // Finally, the min number of seconds to wait before the next time this process is run
    }
```

If you want to run a command from the Cobalt CLI, use the following format:
```json
    // Not yet implemented
    {
        "type": "CommandType", // Use `CommandType` this to specify a Cobalt CLI command to run
        "name": "Execute a command", // Give the task a descriptive name
        "command": "somecommand command_method", // Specify a class to instance
        "interval": 3600 // Finally, the min number of seconds to wait before the next time this process is run
    }
```
> Note that `CommandType` has yet to be properly implemented.

Finally, it should be noted that the `tasks.json` file should be an **array of objects**, with each object representing a single task. Tasks are carried out from top to bottom according to their expiration.

```json
[
    {
        "type": "DefaultType",
        "name": "First task",
        //...
    },
    {
        "type": "DefaultType",
        "name": "Second task",
        //...
    }
]
```