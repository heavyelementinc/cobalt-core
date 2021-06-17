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
```php
<?php
namespace Plugins;
class Sample extends CobaltPlugin{
  /* Add any bespoke logic here */
}
```

#### config.json
```json
{
  "name": "Sample", // This must match the name of the class
  "repo": "https://github.com/heavyelementinc/Sample", // The git repo
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
  }
}
```

#### routes/web.php
```php
<?php
use Routes\Route;
Route::get("/sample","SamplePlugin@index");
Route::get("/sample/protected","SamplePlugin@protected_area", [
  'permission' => 'Sample_plugin_permission' // Protect area using plugin's permissions
]);
```

#### controllers/SamplePlugin.php
```php
<?php
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
