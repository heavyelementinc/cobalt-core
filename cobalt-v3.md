# Cobalt 3.0 Planning

## Overview
Cobalt 3.0 is a near-complete server-side rewrite of our engine aimed at 
massively improving developer ergonomics, runtime execution, architecture
consistency, testability, and code footprint.

## Bootstrapping Lifecycle
Bootstrapping lifecycle is as follows:
 0. Request to `https://cobalt.app/about`
    * Any files that live in the `public` directory of the Cobalt app are
      are served normally without Cobalt's intervention
    * If no file exists matching the incoming request, the webserver (Apache, 
      Nginx) forwards the request to `public/index.php`
    * In the case of this request, the webserver must be configured to forward
      the request to `public/index.php`
 1. `public/index.php`
    * Defines __APP_ROOT__ constant as __DIR__
    * Searches for Cobalt Core filest 
       * It will either be at `__APP_ROOT__/core` or `../../cobalt_core`)
         * In the event the Cobalt Core cannot be located, an error is thrown
       * This script defines the __ENV_ROOT__ constant
       * Finally, the script executes `requires __ENV_ROOT__ . "/env.php"`
 2. `__ENV_ROOT__/env.php`
    * Loads `__APP_ROOT__/config/config.php`
    * Defines autoloader routines
    * `requires` globals, constants, and helper functions
    * Establishes database connection
    * Instantiates `Cobalt\Core\Settings`
    * Instantiates `Cobalt\Core\Router`
 3. `Cobalt\Core\Router`
    * This class loads the router tables from `/routes/*.php`
    * Router examines the request and determines which `Cobalt\Core\Kernel`
      to use to handle the current request
    * The router then executes the Kernel routine:
    ```php
        $kernel = new WebKernel();
        $kernel->configuration($this)
        /** @var PreflightResponse $preflight */
        $preflight = $kernel->preflight($discoveredRoute);
        /** @var Response $response */
        $response = $kernel->execute($discoveredRoute);
        $kernel->response($response, $discoveredRoute);
    ```
 4. `Cobalt\Core\Kernel`
    * Kernel creates a valid `Cobalt\Core\HTTP\Request`
    * Kernel insantiates 

## Config & Settings
### Modes
The mode of the engine can be defined by the value of:
    1. The COBALT_<APP_FOLDER_NAME>_STATE environment variable
    2. Or the contents of `ignored/mode` (if no file exists, it defaults to production)

`0` indicates production, `1` indicates development


### Configuration
Configuration is limited to basic types (`string`s, `int`s, `float`s, `bool`s, 
etc.) Config values are defined in `config/config.development.php` and 
`config/config.php`.

The engine will load the appropriate file depending on the app's mode.


### Settings
Settings must be defined using an instance of the `Cobalt\Core\Settings\Setting`
class. Each Setting is defined as an entry in the `config\definitions.php` file.

```php
# config/config.php

$definitions = [
    (new Setting('hostname'))
        ->setType('select')
        ->setValid(['0' => 'One'])
];
```

At runtime, the definitions file's `mtime` is checked against the cached settings
values. If the definition file's `mtime` is greater than the cached setting, then
the cache is marked as stale and it's rebuilt.

Same goes for the actual `config/settings.php` file's `mtime`.

:::info
The settings must be able to be arbitrarially marked as stale and refreshed via
the CLI! As with all other caches!
:::


## Router
Routes are defined using <Controller>::route() (or one of its aliases such as 
`get`, `post`, `delete`, or `cli`) <Controller>::route() returns an instance of 
`\Cobalt\Router\Route` and each of its `set` functions returns `$this`. 

Therefore, we have an object representation of each route which will provide 
type hinting and improve overall ergonomics.

```php
    use Path\To\Namespace\SomeController;

    # routes/web.php
    SomeController::route("/about/{var}", "methodToCall", HTTPMethod::GET)
        ->setPermissions(SomePermission::ID);
```

`route` calls are static but return an instanced version of the 
`\Cobalt\Core\Router\Route` object.

Route instances should be serializable as JSON so route tables can be cached, 
though this is a down-the-road consideration that we should plan for.



## Kernels
Kernels provide a way of segmenting the route table into smaller groups. For example
the `/admin` segment, the `/api/v1` segment, the `/` segment, and even a `cli`
segment.

They're also a means of segmenting distinct behavior and responses. For example,
the CLI Kernel writes to stdout, the API Kernel formats responses in JSON, while
the Web Kernel outputs in HTML.

These kernels are responsible for configuring the Cobalt environment, executing
the route method, and handling any response data.


## Controllers
Controllers are instantiated by the Kernel and 

```php
# Controller abstract skeleton
abstract class Controller {
    
    function __construct(protected Request $request) {
        // Do some instantiation
    }

    final static function route(string $pattern, string $controller, HTTPMethod $method):Route {
        // Handle route initialization
        return new Route($pattern, $controller, $method);
    }

    final static function get(string $pattern, string $controller):Route {
        return $this->route($pattern, $controller, HTTPMethod::GET);
    }
    
    /** Define other route aliases */
    
    final function setPermission(Permission $permission):Controller {
        $this->permission = $permission;
        return $this;
    }
}

class SomeController extends Controller {
    function methodToCall($var):Response {
        $this->request->getParam['someParam'];
        $this->request->postParam['someParam'];
        $this->request->getHeader("X-Some-Header");
        if($this->request->hasHeader("X-Some-Header")) // Do something
        
    }
}

class Request {
    public readonly array $getParams;
    public readonly array $postParams;
    public readonly array $requestHeaders;
    
    final function getHeader(string $key) {
        // 
    }

    final function setHeaders(array $headers) {
        
    }

    final function response(string $response):Response;
}
```

The Kernel must instantiate the controller with a `Cobalt\Core\HTTP\Request`
argument. It's up to the Kernel to properly parse and form the `Request` object.

All controller methods must return a `Cobalt\Core\HTTP\Response` object. It's
up to the Kernel to handle that `Response` object and write it to the output
buffer/stdout.


## CLI-first design
Previous iterations of our CLI were hacked together and were quite fragile. 
Given the importance of a CLI, however, the CLI is a crucial component of 
server-side management. Thus, our CLI is now a priority feature. In fact, we're
starting our version 3.0 development with the CLI as our focus.

First, CLI commands are now defined as routes, just like any other. 
The `routes/cli.php` file will be where routes are declared which will give.

The Cobalt CLI should be installed by copying `__APP_ROOT__/cobalt` to your PATH.

The CLI will do the following:

  1. Look for `public/index.php` in the current directory
  2. Look for a `core` directory in the current directory
    * Failing to find `core` it will check for the existence of `../cobalt-core` directory
    * Fail with exit status `1` if it cannot locate a `core` or `../cobalt-core` directory
  3. Load `public/index.php` with PHP environment and pass in any args

CLI Kernel is tasked with handling all everything from here including argv parsing,
flag parsing, etc.


## Error Handling
Cobalt 3.0 will significantly re-work our error handling code. Any `400` to `499`
errors will automatically display the error message to the client.

```php
namespace Cobalt\Core\HTTP\Errors;

class HTTPError extends Error {
    const CODE = 500;
    protected string $message = "Error";
    
}
```


## Templates
All templates must be loaded and handled via the `Cobalt\Core\Templates\Template`
class. The `view()` function will remain as an abstraction for this function but
it will soon be deprecated.

```php
class Template {
    function __construct(private string $relativePath, private array $variables = [], private ?Controller $controller = null) {}

    final function __toString() {
        return $this->render();
    }

    final public function getPath():string;

    final public function getAbsolutePath():string;
}
```

## Cobalt Protoypes & MixedTypes
Cobalt Engine implements a JS-esque interface for manipulating data. For example,
a `StringType` might have a `trim()` method or `toUppercase()` method. 

In order to handle this, our data model implements the MongoDB Persistance interface
and all documents stored return our prototypical Model (itself, a Prototype).




## Models
`Cobalt\Core\Models\Model` are a MixedType. 

```php
class SomeModel extends Model {
    #[DirectiveFilter('filterName')]
    public readonly StringType $name {
        get => $this->name;
    }

    #[DirectivePrivate]
    public readonly NumberType $number {
        get => $this->number
    }
}
```


## Style Guide
Every file that is part of Cobalt Engine must start with the `<?php` tag when 
only containing PHP. Similarly, the `?>` closing tag must *always* be omitted.

Every Cobalt Engine file must start with a file-level DocBlock in this format:
```php
/**
 * @copyright <year> <author> <vendor>
 * @link <link to Git repo>
 * @package <Package name (e.g. Cobalt Engine Core 3.0)>
 */
```

Dunderscore prefixes indicate `__` a interface method wrapper. That is to say, 
if a class might implement an interface with the method `read`, then the `__read`
method should be called to determine if the the current method implements said 
interface and, if it does, execute the `read` function indirectly. Underscores 
have no other intrinsic meaning and should not be used to indicate private or 
protected properties/methods.