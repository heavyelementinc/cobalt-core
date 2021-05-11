<?php

/**
 * Routing Table
 * =============
 * 
 * Here, you'll specify the routes that make up the application you're building.
 * In traditional web frameworks, the request URI represents a physical file at
 * a pathname 
 * 
 *   >   For example: /webroot/subdirectory/document.html
 * 
 * In Cobalt Engine, things are a little different. If a physical file exists in 
 * your webroot (project/public/), then the server will send that file to the
 * client without issue.
 * 
 * However, if the URI does not point to a file in your webroot, Cobalt will
 * search one of these tables for instructions to execute, based on the current
 * context (if you're accessing a web page, an API, a webook, etc).
 * 
 * In Cobalt, you can map URIs (essentially path names) to logic on your
 * server like this:
 * 
 *   >    Routes\Route::get("/uri", "Pages@logic");
 * 
 * The first argument is the URI you want to map and the second argument
 * specifies a custom controller (found in your `project/private/controllers/`
 * directory), and the method in that controller you wish to execute.
 * 
 * So "Pages@logic" would invoke `$page = new Pages()` from controllers/Pages.php 
 * and then call the `$page->logic()` method in Pages. From there, the method
 * is able to do pretty much anything. For more info, check out the Controllers
 * documentation.
 * 
 * Variables in your routes
 * ========================
 * 
 * Also of note is that certain symbols mean certain things:
 * 
 *   >   Routes\Route::get("/user/{username}", "Api@logic");
 *   >   Routes\Route::put("/user/...", "Api@logic");
 * 
 * The "{username}" is a special token which represents a variable. This
 * variable will be parsed out of the URI and can contain any character
 * except a "?" or a "/".
 * 
 * Similarly, the "..." token represents an unlimited number of variables
 * after the root prefix. This must come at the end of your route.
 * 
 * If you have two URIs you want to match, make sure that the more complex
 * patterns come before the simpler ones. For example, the second pattern
 * in the following arrangement will never be detected as the current URI,
 * whereas if they swapped positions, the first one would be detected if there
 * were a single argument and the second would be detected for all other
 * queries to "/user/etc/etc/"
 * 
 *   >   Routes\Route::get("/user/...", "Api@logic");
 *   >   Routes\Route::get("/user/{username}", "Api@logic");
 * 
 * Also of note in this arrangement a request to simply "/user/" would NOT be
 * found. Even if the two were to swap positions. To rememdy this you could use
 * the "?" zero or one operator.
 * 
 *   >   Routes\Route::get("/user/{username}?,"Api@logic");
 * 
 * This will tell the router that the previous token (along with the previous /)
 * are optional.
 * 
 * Accessing URI Variables
 * =======================
 * 
 * Consider the following:
 * 
 *   > Routes\Route::get("/delete/{user}/{token}","Api@logic")
 * 
 * The route above will provide {user} and {token} as the first and second arguments
 * for new Api->logic($user,$token);
 * 
 * Additionally, those variables will be stored in $_GET['uri'] as:
 *   [
 *      'user' => 'value',
 *      'token' => 'value'
 *   ]
 * 
 * This means that name collisions in the specified route are possible and later
 * instances will overwrite former instances in $_GET['uri']
 */

use Routes\Route;

// Route::get("/","Pages@index");