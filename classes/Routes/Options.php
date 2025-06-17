<?php

namespace Routes;

use Closure;
use Error;
use Exception;
use Iterator;
use JsonSerializable;
use TypeError;

class Options implements Iterator, JsonSerializable {
    const VAR_REGEX = "%\{([^/?]+)\}%";
    const KEYS = [
        'original_path',
        'real_path',
        'real_regex',
        'var_names',
        'controller',
        'handler',
        'navigation',
        'sitemap',
        'cache_control',
        'unread',
        'permission',
        'groups',
        'csrf_required',
        'require_session',
        'headers',
    ];

    private int $index = 0;

    private string $context;
    private string $context_prefix;

    private string $original_path;
    private string $real_path = "";
    private string $real_regex = "";
    private array $var_names = [];
    
    // /** @var array{controller: string, method: string} */
    private string $controller;

    private ?string $handler = null;
    /** @var array{name: string, href: string, icon: string, order: int, attributes: mixed} */
    private ?array $navigation = null;
    
    /** @var array{ignore: bool, children: callable, lastmod: callable} */
    private ?array $sitemap = null; // ['ignore' => false];
    /** @var array{disallow: bool, max-age: string, type: string} */
    private ?array $cache_control = null; //['disallow' => false,'max-age' => '604800','type' => 'private',];
    private mixed $unread = false;

    // Security stuff
    private ?string $permission = null;
    private ?string $groups = null;
    private bool $require_session = false;
    private bool $csrf_required = false;
    private ?Closure $headers = null;

    function __construct(string $path, string $controller) {
        $this->set_path($path);
        $this->set_controller($controller);
        // $this->set_context($GLOBALS['ROUTER_TABLE_ADDRESS']);
    }

    public function set_path(string $value):self {
        $this->original_path = $value;
        preg_match_all(self::VAR_REGEX, $this->original_path, $this->var_names);
        if(isset($this->context_prefix)) $this->generate_real_data();
        return $this;
    }

    public function set_context(string $context) {
        $this->context = $context;
        $this->context_prefix = __APP_SETTINGS__['context_prefixes'][$context]['prefix'];
        if(isset($this->original_path)) $this->generate_real_data();
    }
    
    public function get_context():string {
        return $this->context;
    }

    public function get_router_context():string {
        return $this->context_prefix;
    }

    private function generate_real_data() {
        $this->real_path = $this->get_context_root() . $this->original_path;
        $this->real_regex = Route::convert_path_to_regex_pattern($this->real_path);
    }

    public function get_context_root():string {
        return substr(__APP_SETTINGS__['context_prefixes'][$this->context]['prefix'] ?? "", 0, -1);
    }

    public function get_path():string {
        return $this->original_path;
    }

    public function get_real_path():string {
        return $this->real_path;
    }

    public function get_regex():string {
        return $this->real_regex;
    }

    public function get_var_names():array {
        return $this->var_names[1];
    }

    public function set_controller(string $value) {
        // $arr = explode("@",$value);
        // $this->controller['controller'] = $arr[0];
        // $this->controller['method'] = $arr[1];
        $this->controller = $value;
        return $this;
    }

    public function get_controller():string {
        return $this->controller;
    }

    public function set_handler(string $value):self {
        $files = [
            __APP_ROOT__ . "/controllers/client/$value",
            __APP_ROOT__ . "/private/controllers/client/$value",
            __ENV_ROOT__ . "/controllers/client/$value",
        ];
        if(!files_exist($files)) throw new RouteConfigError("`$value` handler does not exist!");
        $this->handler = $value;
        return $this;
    }

    public function get_handler():?string {
        return $this->handler;
    }

    /** @var array{[label: string, href: string, icon: string, order: int, attributes: mixed]} */
    public function set_navigation(array $value):self{
        $this->navigation = [];
        foreach($value as $group => $nav) {
            $this->navigation[$group] = [
                'label' => $nav['label'] ?? $nav['name'] ?? throw new RouteConfigError("$group must have a 'label' or 'name' set"),
            ];
            if($nav['order'] ?? "") $this->navigation[$group]['order'] = $nav['order'];
            if($nav['href'] ?? "") $this->navigation[$group]['href'] = $nav['href'] ?? '';
        }
        return $this;
    }

    /** @return array{[label: string, href: string, icon: string, order: int, attributes: mixed]} */
    public function get_navigation():array {
        return $this->navigation ?? [];
    }
    
    /**
     * @deprecated 
     * @return never */
    public function set_anchor(array $anchor):never {
        throw new RouteConfigError("Anchors are no longer supported");
    }

    /**
     * @param array{ignore: bool, children: callable, lastmod: callable} $value
     * @return Options 
     */
    public function set_sitemap(array $value):self {
        if(key_exists('ignore', $value) && gettype($value['ignore']) !== "boolean") throw new RouteConfigError("Missing `ignore` key");
        if(key_exists('children', $value) && !is_callable($value['children'])) throw new RouteConfigError("`children` must be callable");
        if(key_exists('lastmod', $value) && !is_callable($value['lastmod'])) throw new RouteConfigError("`lastmod` must be callable");
        $this->sitemap = $value;
        return $this;
    }

    /**
     * @return array{ignore: bool, children: callable, lastmod: callable}
     */
    public function get_sitemap():array {
        return $this->sitemap ?? [];
    }

    /**
     * @param array{disallow: bool, max-age: string, type: string} $value
     * @return Options 
     */
    public function set_cache_control(array $value):self {
        $this->cache_control = $value;
        return $this;
    }

    /**
     * @return array{disallow: bool, max-age: string, type: string}
     */
    public function get_cache_control():array {
        return $this->cache_control ?? [];
    }

    /**
     * @param array{disallow: bool, max-age: string, type: string} $value
     * @return Options 
     */
    public function set_unread($value):self {
        // if()
        $this->unread = $value;
        return $this;
    }

    /**
     * @return array{disallow: bool, max-age: string, type: string}
     */
    public function get_unread():array|false {
        return $this->unread;
    }

    public function set_permission(string $value):self{
        $this->permission = $value;
        return $this;
    }

    public function get_permission():?string {
        return $this->permission;
    }

    public function set_groups(string $value):self{
        $this->groups = $value;
        return $this;
    }

    public function get_groups():?string {
        return $this->groups;
    }


    public function set_csrf_required(bool $value):self {
        $this->csrf_required = $value;
        return $this;
    }
    
    public function get_csrf_required():bool {
        return $this->csrf_required;
    }

    public function set_require_session(bool $session):self {
        $this->require_session = $session;
        return $this;
    }

    public function get_require_session():bool {
        return $this->require_session;
    }

    public function set_headers(Closure $funct):self {
        $this->headers = $funct;
        return $this;
    }

    public function get_headers(): Closure {
        if(is_null($this->headers)) return function () {};
        return $this->headers;
    }

    // public function __get($name) {
    //     if(in_array($name, self::KEYS)) return $this->{"get_".$name};
    //     return null;
    // }

    public function __isset($name) {
        if(!in_array($name, self::KEYS)) return false;
        return isset($this->{$name});
    }

    /************INTERFACE*************/
    public function jsonSerialize(): mixed {
        $array = [];
        foreach($this as $key => $value) {
            if($value === null) continue;
            $array[$key] = $value;
        }
        return $array;
    }

    public function current(): mixed {
        $method = "get_" . $this->key();
        if(!method_exists($this, $method)) throw new Error("Method `$method` is invalid");
        return $this->{$method}();
    }

    public function next(): void {
        $this->index += 1;
    }

    public function key(): mixed {
        return self::KEYS[$this->index];
    }

    public function valid(): bool {
        if($this->index < 0) return false;
        if($this->index >= count(self::KEYS) - 1) return false;
        return true;
    }

    public function rewind(): void {
        $this->index = 0;
    }
}