# Plugins
## About
Plugins are a simple way of providing portable, reusable functionality in Cobalt Engine. Plugins are independent of the `__APP_ROOT__` they're installed in.

---

## Plugins Structure

* A directory in `__APP_ROOT__/plugins/` which matches the name of the plugin.
* A PHP file in the plugin's directory which matches the name of the plugin followed by `.php`
  * This class must be in the `Plugins` namespace
  * This class must `extends CobaltPlugin`
* A file named `config.json` which specifies
  * The `name` of the plugin
  * A git `repo` where it can be downloaded

---

## Example
### `Sample` Plugin Directory structure
In this example, consider a plugin named `Sample` which defines a web route and a controller named `SamplePlugin`

#### Directory Structure
```
__APP_ROOT__/plugins/
  ∟ Sample/
    ⊢ routes/
      ∟ web.php
    ⊢ controllers/
      ∟ SamplePlugin.php
    ⊢ templates/
      ∟ SampleIndex.html
    ⊢ config.json
    ∟ Sample.php
```

#### Sample.php
Your plugin should extend CobaltPlugin which does most of the heavy lifting for you. You don't need to add much here unless you want or need to.
```php
<?php
namespace Plugins;
class Sample extends CobaltPlugin{
  /* Add any bespoke logic here */
}
```

#### config.json
Config.json configuration data for your plugin.
```json
{
  "name": "Sample", // This must match the name of the class
  "repo": "https://github.com/heavyelementinc/Sample", // The git repo
  // JS and CSS packages must be stored in your plugin's /public directory under the /js or /css directory, respectively
  "js-packages": ["somePackage.js"], // You must only specify the filename relative to its parent directory here.
  "css-packages": ["somePackage.css"],
  "settings": {
    "some_setting": {
      "default": true,
    }
  },
  "permissions": {
    "Sample_plugin_permission": {
      "groups": "Sample",
      "label": "Some flavor text to explain this permission",
      "dangerous": true|false,
      "default": false // Permissions may only be boolean true or false
    }
  },
  // The following entries may be specified to override the default values (shown here with the default values):
  "class_dir": "/classes/",
  "routes_dir": "/routes/",
  "controllers_dir": "/controllers/",
  "template_dir": "/templates/",
  "shared_dir": "/shared/",
  "cli_dir": "/cli/commands",
  "classes_dir": "/classes/",
  "public_dir": "/public/"
}
```

## JavaScript and Stylesheets
Any file added to the `js-packages` or `css-packages` arrays in `config.json` will be automatically imported into Cobalt at runtime. This means the content of files will live on every page of the application.

If you do not want certain JS or CSS automatically included, you can manually link to the file using the following path format:

```html
<script src="/core-content/plugins/{PluginName}/js/{filename}.js">
<link href="/core-content/plugins/{PluginName}/css/{filename}.css" rel="stylesheet">
```

### Shared content
If your app makes other content available for public use, it should be placed in the `<plugin-root>/shared/` directory and accessed via the `core-content/{filename}` path. Keep in mind that `/core-content/js/`, `/core-content/css/`, and `/core-content/plugins/` are **reserved** by Cobalt Engine and any files in your plugin's /shared directory with a relative pathname starting with `js/`, `css/`, or `plugins/` will be unreachable via public routing.

> NOTE: App developers are able to override the shared content of plugins from within their own apps. This is done by the app providing a shared file of the same name from within the app's `private/shared` directory. *THIS IS BY DESIGN* and plugin developers should keep this in mind when providing shared assets.

## Controllers and Routes
Controllers and routes work identically to how they behave in any Cobalt app. Simply specify the method (below you'll see `get` as the method), and then the `uri` parameter followed by the `ControllerName`@`controller_method` you want to execute with that route.

Also of note is that the second `Route` definition utilizes the optional **Route Directives** array. In it, we're specifying a permission required to access that route using the `permission` keyword.

#### routes/web.php
```php
use Routes\Route;
Route::get("/sample","SamplePlugin@index");
Route::get("/sample/protected","SamplePlugin@protected_area", [
  'permission' => 'Sample_plugin_permission' // Protect area using plugin's permissions
]);
```

#### controllers/SamplePlugin.php
```php
class SamplePlugin{
    function index() {
      add_vars(['title' => "Hello World"]);
      set_template('index.html')
    }
    function protected_area() {
      add_vars(['title' => "Protected Area"]);
      set_template('protected.html');
    }
}
```
