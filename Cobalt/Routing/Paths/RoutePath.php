<?php

namespace Cobalt\Routing\Paths;

use Cobalt\Routing\Route;
use Exception;
use Override;
use Stringable;

class RoutePath implements Stringable {
    public private(set) ?string $context;
    public private(set) ?string $context_root = null;
    public private(set) ?string $original_path = null;
    public private(set) ?string $real_path = null;
    public private(set) ?string $real_regex = null;
    public private(set) array $arguments = [];

    function __construct(public Route $route) {
        
    }

    #[Override]
    public function __toString(): string {
        return $this->real_path;
    }

    function matches(string $uri):bool {
        $matches = [];
        $bool = preg_match($this->real_regex, $uri, $this->route->uri_vars);
        $this->arguments = $matches;
        return $bool === 1;
    }

    const VAR_REGEX = "%\{([^/?]+)\}%";

    public function setPath(string $path, ?string $context = null):self {
        $this->original_path = $path;
        return $this;
    }

    public function setContext(string $context, array $details):self {
        $this->context = $context;
        $this->real_path = substr($details['prefix'] ?? "", 0, -1) . $this->original_path;
        $this->real_regex = self::convert_path_to_regex_pattern($this->real_path);
        return $this;
    }

    public function getPath() {
        // if(isset($this->path)) return $this->path;
        $path = [
            'original_path' => $this->original_path,
            'real_path'     => $this->real_path,
            'real_regex'    => $this->real_regex,
            'var_names'     => [],
        ];
        preg_match_all(static::VAR_REGEX, $path['original_path'], $path['var_names']);
        return $path;
    }
    
    const PREG_QUOTE = "[^/?]+";
    static function convert_path_to_regex_pattern(string $route) {
        $preg_quote = self::PREG_QUOTE;
        $regex_search = "%\{$preg_quote\}%";
        $regex_replace = "($preg_quote)";
        $new_route = preg_replace($regex_search, $regex_replace, $route);

        // Make routes with optional parameters tolerant to a missing trailing slash
        $new_route = str_replace("/$regex_replace?", "/?$regex_replace?", $new_route);

        // Finally, we create our regex pattern
        $new_route = "%^" . str_replace(["/", "..."], ["\/", "(.*)"], $new_route);

        // Make the route tolerant of trailing slashes
        if (substr($new_route, -2) === "\/") {
            $new_route .= "?";
        } else {
            $new_route .= "\/?";
        }
        return "$new_route$%";
    }

}
