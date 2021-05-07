<?php
/**
 * The Router
 * 
 * This class handles mapping URL paths to the corresponding functions/methods
 * in the router table.
 * 
 * @todo When the router parses the values, it should check for name collisions
 * between existing $_GET parameters and save them as $_GET["<name>"] or 
 * $_GET["uri_<name>"] if a collision exists. The "..." should be saved as a 
 * string called $_GET['uri_misc']
 * 
 * @todo Add typing so that digit:{varname} would be typecast to a digit or,
 * throws an BadRequest error if its no a digit
 * 
 */
namespace Routes;

class Router{

  public $current_route = null;
  private $route_cache_name = "config/routes.json";

  /** Let's establish our $route_context and our method  */
  function __construct($route_context = "web",$method = null){
    if($method === null) $method = $_SERVER['REQUEST_METHOD'];
    $this->route_context = $route_context;
    $this->method = strtolower($method);
  }

  function init_route_table(){
    /** Export our route table to the global space, we use this to specify where
     * we should look for our routes.
     */
    $GLOBALS['route_table_address'] = $this->route_context . "_routes";
    if(!isset($GLOBALS[$GLOBALS['route_table_address']])) $GLOBALS[$GLOBALS['route_table_address']] = [];
  }

  function get_routes(){
    /** Check if we're supposed to cache our routes
     *  @todo Complete the table_from_cache functionality */
    if(app('route_cache_enabled') && $this->table_from_cache()){
      return;
    }

    try{
      /** Get a list of route tables that exist */
      $route_tables = files_exist([
        __ENV_ROOT__ . "/routes/core/".$this->route_context.".php", // "/"
        __ENV_ROOT__ . "/routes/".$this->route_context.".php",
        __APP_ROOT__ . "/private/routes/".$this->route_context.".php", // "/"
      ]);
    } catch(\Exception $e){
      /** If there are no routes available, die with a nice message */
      die("Could not load route for context $GLOBALS[route_context]");
    }
    
    /** Execute each router table we found */
    foreach($route_tables as $table){
      require_once $table;
    }

    $this->routes = $GLOBALS[$GLOBALS['route_table_address']];

  }

  /** @todo complete this */
  function table_from_cache(){
    $this->cache_resource = new \Cache\Manager($this->route_cache_name);
    return false;
  }

  
  function discover_route($route = null,$query = null){
    if($route === null) $route = $_SERVER['REQUEST_URI'];
    if($query === null) $query = $_SERVER['QUERY_STRING'];
    /** Let's remove the query string from the incoming request URI and decode 
     * any special characters in our URI.
     */
    $this->uri = urldecode(str_replace(["?".$query],"",$route));
    if($this->route_context !== "web"){
      $this->context_prefix = app("context_prefixes")[$this->route_context]['prefix'];
      $this->uri = substr($this->uri,strlen($this->context_prefix) - 1);
    }
    
    $route = null;
    /** Search through our current routes and look for a match */
    foreach($this->routes[$this->method] as $route => $directives){
      $match = [];
      /** Regular Expression against our uri, store any matches in $match */
      if(preg_match($route,$this->uri,$match) === 1) {
        if($match !== null) $this->set_uri_vars($directives,$match,$route);

        $this->current_route = $route;
        $GLOBALS['current_route_meta'] = $directives;
        break;
      }
    }

    if($this->current_route === null) throw new \Exceptions\HTTP\NotFound("No route discovered.");
  }

  function set_uri_vars($directives,$match,$route){
    array_shift($match);
    $_GET['uri'] = \array_fill_keys($directives['uri_var_names'],$match);
    foreach($directives['uri_var_names'] as $i => $name){
      if(key_exists($name,$_GET)) $name = "uri_$name";
      if(key_exists($i,$match)) $_GET['uri'][$name] = $match[$i];
    }
    $this->routes[$this->method][$route]['matches'] = $match;
    // array_shift($match);
    // if($match === null) $match = [];
    // /** Store the $match with the route data */
    // $this->routes[$this->method][$route]['matches'] = $match;
    // $this->current_route = $route;
  }

  function execute_route(){
    /** Store our route data for easy access */
    $exe = $this->routes[$this->method][$this->current_route];
    if(isset($exe['permission'])) {
      $permission = true;
      try{
        $permission = $GLOBALS['auth']->has_permission($exe['permission'],$exe['group']);
      } catch (\Exceptions\HTTP\Unauthorized $e) {
        $permission = false;
      }
      if(!$permission) throw new \Exceptions\HTTP\Unauthorized('You do not have the required privileges.');
    }
    /** Check if we're a callable or a string and execute as necessary */
    if(is_callable($exe['controller'])) throw new Exception("Anonymous functions are no longer supported as controllers.");//return $this->controller_callable($exe);
    
    if(is_string($exe['controller'])) return $this->controller_string($exe);
  }

  function controller_callable($exe){
      return $ctrl = $exe['controller'](...$exe['matches']);
  }

  function controller_string($exe){
    // Split our Pages@method string
    $explode = explode("@",$exe['controller']);
    $controller_name = $explode[0];
    $controller_method = $explode[1];
    
    try{
      // We are doing these in reverse order because we want our app's 
      // controllers to override the core's controllers.
      $controller_file = files_exist([
        __APP_ROOT__ . "/private/controllers/$controller_name.php",
        __ENV_ROOT__ . "/controllers/$controller_name.php"
      ]);
    } catch(\Exception $e){
      die("Controller $controller_name not found.");
    }
    
    // We need to require this because the controllers folder is outside of our 
    // classes path and the developer is going to be able to create new controllers
    require_once $controller_file[0];

    // Instantiate our controller and then execute it
    $ctrl = new $controller_name();
    if(!method_exists($ctrl,$controller_method)) throw new \Exceptions\HTTP\NotFound("Specified method was not found.");
    $test = new \ReflectionMethod($controller_name,$controller_method);
    /** We check to make sure that we're not going to have callable exception 
     * where we aren't supplying the correct number of arguments to a callable. 
     * So we check how many arguments are required for the callable and then 
     * count the number of matches we found to ensure that there will always be 
     * enough matches.
     * 
     * Otherwise, we'll throw a 400 Bad Request.
     */
    if($test->getNumberOfRequiredParameters() > count($exe['matches'])) throw new \Exceptions\HTTP\BadRequest("Method supplied too few arguments.");
    if(gettype($exe['matches']) !== "array") $exe['matches'] = [$exe['matches']];
    /** Execute our method */
    return $ctrl->{$controller_method}(...$exe['matches']);
  }

  /** @todo Fix terrible nested loops/logic */
  function get_js_route_table(){
    $table = [];
    foreach($this->routes as $method => $routes){
      foreach($routes as $path => $route){
        $handler = $route['handler'];
        if($handler === null) continue;
        $files = \files_exist([
            __APP_ROOT__ . "/private/controllers/client/$handler",
            __ENV_ROOT__ . "/controllers/client/$handler",
        ]);
        array_push($table,"\n'$path': ".file_get_contents($files[0]));
      }
    }
    return "\nconst router_table = {\n" . implode(",\n",array_unique($table)) . "\n}\n";
  }

}