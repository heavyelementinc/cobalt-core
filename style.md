Code Quality Guidelines
=======================
This document is meant to suggest a particular and consistent style across this project's codebase. These guidelines are generally meant to provide a baseline of how we expect code to be written.

Tools
=====

 * [VS Code](https://code.visualstudio.com/) - Or an FOSS derivative like VS Codium or code-server
 * [Intelephense](https://intelephense.com/) - Intelephense is a language server extension for VS Code which facilitates PHP development. It implements reliable PHPDoc Intellisense and debugging.
 * Composer - Composer is a prerequisite.
 * XDebug - Makes your life so much easier.


Whitespace
==========
Indentation should universally be four spaces for all files in Cobalt Engine. This is non-negotaible.

No single line of code should exceed 120 characters (with 80 begin a soft and preferred limit). Add the following setting to VS Code's `settings.json` to help follow this rule.
```json
{
    "editor.rulers": [ 80, 120 ]
}
```
> This column width rule does not apply to markdown documents.

White space should be included between an if expression and its paratheses & it's opening curly brace.

```php
# Do this
if ($option) {
    // ...
}
# Not this
if($option){
    // ...
}
```

Boolean Values
==============
Boolean values should be provided as lowercase and should be explicitly `true` or `false` and not `1` or `0`.

Single vs. Double Quotes
========================
We prefer the use of single quotes for array keys and double quotes for strings. This is a preference for our coding style and not a rule that's strongly enforced as there are occasions where it makes sense to use doublequotes as your keyname or single quotes to declare a string. However since we use MongoDB as our database by default, using single quotes for keys by convention makes it simpler to write complex queries with top level operators. See [Braces](#braces).


```php
# Array literal definition
$array = [
    'key1' => "Value",
    'key2' => "Value"
];

# Accessing array values
$array['key1'];

# String definition
$string = "This is a string with $array[key1]";
```

Braces
======
## Arrays and Parameters
Arrays are **NEVER** to be declared using the `array()` function. Instead they must use PHP's short syntax.

```php
# This is the only acceptable way to declare an array
$array  = ['key1' => 'value1', 'key2' => 'value2'];

# This syntax is never acceptable
$array = array('key1' => 'value1', 'key2' => 'value2');
```
This syntax was introduced in PHP 5.4. It's been a long time. Let's start using it.

## Curly Braces
Curly braces should always occur on the same line as the expression in all instances. This goes for `if`s, `for`s, `function`s, `class`es, and anywhere else. This is known as the K&R convention. Anything else is unacceptable.

## Parameters
When calling a function, parameters may be declared on separate lines where necessary. This is especially the case when querying using MongoDB.

```php
$result = $collection->updateMany(
    [
        'field' => [
            '$exists' => true
        ]
    ],
    [
        '$set' => [
            'field' => [
                '$sum' => 10
            ]
        ]
    ]
);
```
> **NOTE** that this is the only exception to the [nested indentation rule](#closure-nesting-and-blocking-logic).

Comments & PHPDoc
=================
Code comments are an important part of any program. We recommend the following usage for comments.

## Flavor text for the preceeding line
```php
$bar = "Define another string"; // Some flavor text
```

## Descriptor for the following line
```php
# A comment which describes the next line
$foo = "here's the string";
```
Why use a different comment syntax? Firstly because the `#` looks more like a 'headline' indicator in markdown.

Secondly consider for a moment if you were to use the `//` token. This style of token is used to toggle the state of a line of code when you press `CTRL + /` in VS Code. If you do this selecting the line containing `$foo`, you'd end up with two lines as follows:

```php
// A comment which describes the next line
// $foo = "here's the string";
```

If you were to later select both lines and press the hotkey, you would unintentionally remove the `//` from the comment line. Using a different syntax eliminates the chance of this happening.

## Multi-line comments
```php
/* Multiline comments are sometimes required. Doloremque tenetur sed modi 
voluptatum ea iure sit exercitationem. Odio saepe dicta maiores in et sunt. 
Laborum saepe dolorum blanditiis a quia optio accusamus. */
```

## Docblocks
```php
/** A brief description of foo's functionality
 * 
 * A longer description that allows you to expand on what this function does, 
 * when and where you might want to use it, etc.
 * 
 * @param string $bar A string to be mutated by foo
 * @return string A mutated version of $bar
 */
abstract function foo(string $bar);
```

> **NOTE** that comments are strictly bound to [80 columns](#whitespace)

## Header Docblock
Header comments contributed by a user should include a header comment resembling the following:

```php
/**
 * ExampleFileOrClass.php - Brief description of this file or class
 * 
 * An expanded description of this file or class. Describe what it does, how to
 * use it, and your rationale for certain decisions.
 * 
 * @license cobalt-core/contributing.md
 * @license cobalt-core/license
 * @author Your Name <your-email@heavyelement.io>
 * @copyright (year) your name
 */
```

> **NOTE** By including `@license cobalt-core/contributing.md` tag in your docblock, **you're agreeing to the terms of the Contributor License Agreement**.

# Natrual Flow vs. Branching Logic
Where feasible, we prefer our code to use natural flow vs branching logic:

## Natural Flow
```php
$list = [
    'option 1' => "Bar",
    'option 2' => "Baz",
    'option 3' => "BarBaz"
];

$result = "Foo"; // Sets our default value
if (isset($list[$option])) $result = $list[$option];
```

## Branching Logic
```php
switch ($option) {
    case "option 1":
        $result = "Bar";
        break;
    case "option 2":
        $result = "Baz";
        break;
    case "option 3":
        $result = "BarBaz";
        break;
    default:
        $result = "Foo";
        break;
}
```

In the first instance there is one logical check. In the second, there are three. The second option is quite inefficient, especially as the number of items scale. Additionally, the amount of code duplication grows. Finally, the first option is considerably more readable.

## Equality Operations
We prefer strong (`===`) over weak (`==`) equality checks, however it's also acceptable where appropriate to use shorthand checks.

```php
# Acceptable
$value = "";
if (empty($value) === true) {}

# Acceptable
if (empty($value)) {}

# Not acceptable
if (empty($value) == true) {}
```

> Though it may seem trivial to distinguish between examples 2 and 3 above, we believe that it's easier to *notice a difference* between examples 1 and 2 versus 1 and 3.



# Closure Nesting and Blocking Logic
## Blocking Logic
Blocking logic will `return` from a function/method, will `continue` or `break` from a loop, or do other things when conditions are met.

```php
function foo($some_array) {
    if (empty($some_array)) return false;
    
    if (key_exists($some_array['bar'])) return $some_array['bar'];
    
    return true;
}
```

We very much discourage the use of else

## Nesting in a callable
Within a method or function we discourage using more than one level of nested closure (or more than one tab/indentation). There may be times where it is necessary to do so but these are the exception.

If there is a loop that you're performing over a dataset and you need to use anything more than blocking logic, we recommend creating a function and calling that function from with the loop if you need nested logic.

```php
function foo($some_array) {
    foreach ($some_array as $value) {
        // First level of indentation. (This is fine.)
        if (!$value['foo']) continue;
        
        if ($value['foo']['bar'] === true) {
            // Second level of indentation. (Don't do this.)
            $value['foo']['bar'] = "baz";
        }
    }
}
```

# Naming
Naming is something we take seriously. First, we need to talk about our conventions.

## Files & URLs
Filenames should use *underscores between words*. This is sometimes referred to as "**Snake Case**." We will use that term going forward.

```php
require "path/to/file_name.php";
```

URLs/routes should use *dashes between words*. This is sometimes referred to as "**Kebab Case**"

```php
Route::get("/api/v1/user-account/{user_id}", "Users@account_by_id");
```
```javascript
const result = fetch("/api/v1/user-account/087317de87ad81fab");
```

## Variables
We belive that variables and associative array keys should be descriptive of their use. We also believe that the names of variables should be as long as necessary to describe its contents or use.

```php
$is_descriptive_of_its_content = true;
```

We also strongly discourage the use of abbreviations.

We use **Snake Case** when naming our variables, functions, and methods in PHP. For example:

```php
$foo_bar = "A string";
```

## Functions & Methods
Like variables, we believe that functions and methods should follow the "Snake Case" naming convention. However with functions and methods with believe its name should start with a verb:

 * *get* - returns values (typically a string)
 * *set* - sets values
 * *gather* - when returning an array or iterable
 * *add* - when adding to an array
 * *is* or *has* - when returning a boolean value
 * *render* - when returning HTML as a string

We will leave naming up to you, but these are a few ideas

```php
function parse_foo($bar_baz){
    // ..
}
```

## Globals and Constants
Globals and constants should be UPPERCASED in all instances. Any constant which we provide will be dunderscored on either side (for example, `__APP_ROOT__`)

## Classes
Classes and namespaces always *capitalize the first letter of every word*. This is often called "Pascal Case".

```php
namespace SomeNamespace;

abstract class FooBarBaz {

}
```

Code Quality
============
We believe that by following these common-sense formatting standards, we can ensure that our codebase is easily read, easily understood, and easily re-used.