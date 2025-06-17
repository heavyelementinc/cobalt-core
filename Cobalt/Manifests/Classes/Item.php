<?php
declare(strict_types=1);

namespace Cobalt\Manifests\Classes;

use Cobalt\Manifests\Enums\ValidTypes;
use Error;
use MongoDB\BSON\Document;
use MongoDB\BSON\Persistable;
use MongoDB\Model\BSONArray;
use stdClass;
use TypeError;



class Item implements Persistable{
    private string $href = "";
    private string $known_file = "";
    private array $contexts = [];
    private bool $append = false; // Appends this item to the end of the final list of items
    private int $version = 1;

    private bool $module = false;
    private bool $registered = false; 
    private bool $deferred = false;
    private bool $inline = false;
    private bool $required = false;
    
    private string $package = "package"; // Defines which package this item belongs to when cached
    // private array $package_attrs = []; // Defines what attributes should be applied to the tag

    private ValidTypes $type;

    const FILE_LOCATIONS = [
        'js' => [
            __APP_ROOT__."/src/",
            __ENV_ROOT__."/src/",
        ],
        'css' => [
            __APP_ROOT__."/shared/css_v2/",
            __ENV_ROOT__."/shared/css_v2/",
            __APP_ROOT__."/shared/css/",
            __ENV_ROOT__."/shared/css/",
        ]
    ];

    const COMMON_SHORTHAND_REFERENCE = ["web", "admin", "debug"];

    function __construct() {
    }

    public function bsonSerialize(): array|stdClass|Document {
        return [
            'href' => $this->href,
            'package' => $this->package,
            // 'known_file' => $this->known_file,
            'contexts' => $this->contexts,
            'module' => $this->module,
            'registered' => $this->registered,
            'append' => $this->append,
            'version' => $this->version,
            'inline' => $this->inline,
            'deferred' => $this->deferred,
        ];
    }

    public function bsonUnserialize(array $data): void {
        $this->ingest($data);
    }

    function ingest(array $data) {
        $this->set_href($data['href'], $data['path'] ?? null);
        $this->set_package($data['package'] ?? 'package');
        $this->set_contexts($data['contexts'] ?? []);
        $this->set_module($data['module'] ?? false);
        $this->set_registered($data['registered'] ?? false);
        $this->set_append($data['append'] ?? false);
        $this->set_version($data['version'] ?? 1);
        $this->set_inline($data['inline'] ?? false);
        $this->set_deferred($data['deferred'] ?? false);
    }

    public function set_href(string $value, ?string $path = null) {
        $type = null;
        switch (strtolower(pathinfo($value, PATHINFO_EXTENSION))) {
            case "js":
            case "mjs":
                $type = ValidTypes::js;
                break;
            case "css":
                $type = ValidTypes::css;
                break;
        }
        $this->set_type($type);

        if($path) {
            if(!file_exists($path)) throw new Error("set_href was passed a path, but it does not exist!");
            $this->known_file = $path;
        } else {
            $existing_file = find_one_file(self::FILE_LOCATIONS[$this->type->name], $value);
            if(!$existing_file) {
                if($this->required === true) throw new Error("Manifest file description could not be found: $value");
                $existing_file = "";
            }
            $this->known_file = $existing_file;
        }
        $this->href = $value;

    }
    public function set_contexts(array|BSONArray $value) {
        if($value instanceof BSONArray) $value = $value->getArrayCopy();
        // if(in_array("common", $value)) {
        //     unset($value[array_search("commomn", $value)]);
        //     $value = self::COMMON_SHORTHAND_REFERENCE;
        // }
        $this->contexts = $value;
    }

    public function set_module(bool $value) {
        $this->module = $value;
    }

    public function set_type(ValidTypes $value) {
        $this->type = $value;
    }

    public function set_append(bool $value) {
        $this->append = $value;
    }

    public function set_version(int $version) {
        $this->version = $version;
    }

    public function set_package(string $package) {
        $this->package = $package;
    }

    public function set_registered(bool $value) {
        $this->registered = $value;
    }

    public function set_deferred(bool $value) {
        $this->deferred = $value;
    }
    public function set_inline(bool $value) {
        $this->inline = $value;
    }

    public function get_href():string {
        return $this->href;
    }

    public function get_package():string {
        return $this->package;
    }

    public function get_contexts():array {
        return $this->contexts;
    }

    public function get_module():bool {
        return $this->module;
    }

    public function get_type():ValidTypes {
        return $this->type;
    }

    public function get_append():bool {
        return $this->append;
    }

    public function get_version():int {
        return $this->version;
    }

    public function is_inline_content():bool {
        return $this->inline;
    }
    public function is_deferred_content():bool {
        return $this->deferred;
    }

    public function belongs_to_context($context):bool {
        if(in_array($context, $this->contexts)) return true;
        if(in_array("common", $this->contexts)) return true;
        return false;
    }

    public function inflate(&$data) {
        $type = $this->type->name;
        if(!key_exists($type, $data)) $data[$type] = [];
        foreach($this->contexts as $ctx) {
            if(!key_exists($ctx, $data[$type])) $data[$type][$ctx] = [];
            $data[$type][$ctx][] = $this;
        }
    }

    public function get_html_tag(array &$packages) {
        switch($this->type) {
            case ValidTypes::js:
                return $this->get_script_tag($packages);
                break;
            case ValidTypes::css:
                return $this->get_css_tag($packages);
            default:
                throw new TypeError("Unknown type. Cannot generate HTML for manifest entry");
        }
    }

    public function get_package_meta() {
        $meta = [];
        if($this->module) $meta[] = "type=\"module\"";
        if($this->registered) $meta[] = "onload=\"window.asyncScripts.push(new Promise(resolve=>resolve(this)))\"";
        if($this->deferred) $meta[] = "deferred=\"deferred\"";
        return $meta;
    }

    public function get_script_tag(&$packages) {
        $module = "";
        if($this->module) $module = " type=\"module\"";
        $registered = "";
        if($this->registered) $registered = " onload=\"window.asyncScripts.push(new Promise(resolve=>resolve(this)))\"";

        $version = "";
        if($this->version > 1) $version = "v$this->version/";
        $pkg = "/core-content/js/$version"."$this->href";
        // header("Link: <$pkg?".__APP_SETTINGS__['version'].">; rel=preload; as=script", false);
        return "<script src=\"".to_base_url("$pkg?{{versionHash}}")."\"$module"."$registered></script>";
    }

    public function get_css_tag(&$packages) {
        $version = "";
        if($this->version > 1) $version = "v$this->version/";
        $pkg = "/core-content/css/$version"."$this->href";
        // header("Link: <$pkg?".__APP_SETTINGS__['version'].">; rel=style; as=script", false);
        return "<link rel=\"stylesheet\" href=\"".to_base_url("$pkg?{{versionHash}}")."\">";
    }

    public function read_content() {
        switch($this->type) {
            case ValidTypes::js:
                return $this->read_script();
                break;
            case ValidTypes::css:
                return $this->read_css();
        }
    }

    public function read_script() {
        // $handle = find_one_file(self::FILE_LOCATIONS['js'],$this->href);
        $handle = $this->known_file;
        $details = view($handle, [], true);
        return (__APP_SETTINGS__['manifest_v2_include_filenames']) ? "\n\n// $this->href\n$details" : $details;
    }

    public function read_css() {
        // $handle = find_one_file(self::FILE_LOCATIONS['css'],$this->href);
        $handle = $this->known_file;
        $details = view($handle, [], true);
        return (__APP_SETTINGS__['manifest_v2_include_filenames']) ? "\n\n/** $this->href */\n$details" : $details;
    }
}