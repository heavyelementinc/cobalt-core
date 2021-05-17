# Plugins
## About
Plugins are a simple way of providing portable, reusable functionality in Cobalt Engine. Plugins are independent of the `__APP_ROOT__` they're installed in.

---

## Plugins Structure

* A directory in `__APP_ROOT__/plugins/` which matches the name of the plugin.
* A PHP file in the plugin's directory which matches the name of the plugin followed by `.php`
  * This file must `implements Plugins\CobaltPlugin`
* A file named `config.json` which specifies
  * The `name` of the plugin
  * A git `repo` where it can be downloaded

---

## Example
### `Sample` Plugin Directory structure
In this example, consider a plugin named `Sample` which defines a web route and a controller named `SamplePlugin`

#### Directory Structure
```
__APP_ROOT__/
  ∟ Sample/
    ⊢ routes/
      ∟ web.php
    ⊢ controllers
      ∟ SamplePlugin.php
    ⊢ templates/
      ∟ SampleIndex.html
    ⊢ config.json
    ∟ Sample.php
```

#### Sample.php
```php
<?php
```

#### routes/web.php
```php
<?php
use Routes\Route;
Route::get("/sample","SamplePlugin@index");
```

#### controllers/SamplePlugin.php
```php
<?php
class SamplePlugin{
    function index(){
        add_vars(['title' => "Hello World"]);
        add_template('templates/SampleIndex.php')
    }
}
```
