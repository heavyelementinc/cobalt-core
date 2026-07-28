<?php

namespace Cobalt\Routing\Paths;

use Override;
use Stringable;

class RoutePath implements Stringable {
    protected ?string $context_root = null;
    protected ?string $original_path = null;
    protected ?string $real_path = null;
    protected ?string $real_regex = null;
    protected array $arguments = [];

    #[Override]
    public function __toString(): string {
        
    }

    function matches(string $uri):bool {
        $matches = [];
        $bool = preg_match($this->real_regex, $uri, $uri_var, $matches);
        $this->arguments = $matches;
        return $bool >= 0;
    }

    const VAR_REGEX = "%\{([^/?]+)\}%";

    public function setPath(string $path, ?string $context = null):self {
        $this->original_path = $path;
        if(!$context) $context = $GLOBALS['ROUTE_TABLE_ADDRESS'];
        if(!$context) throw new Exception("Route for ".htmlspecialchars($path)." is being instanced without explicit prefix outside of declarative window.");
        
        $this->context = $context;
        $this->context_prefix = __APP_SETTINGS__['context_prefixes'][$context]['prefix'];

        $this->real_path = substr(__APP_SETTINGS__['context_prefixes'][$this->context]['prefix'] ?? "", 0, -1);
        $this->real_regex = self::convert_path_to_regex_pattern($this->real_path);

        return $this;
    }

    public function getPath() {
        if(isset($this->path)) return $this->path;
        $path = [
            'original_path' => $this->original_path,
            'real_path'     => $this->context_prefix . $this->original_path,
            'real_regex'    => $this->real_regex,
            'var_names'     => [],

        ];
        preg_match_all(static::VAR_REGEX, $path['original_path'], $path['var_names']);
        $this->path = $path;
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
