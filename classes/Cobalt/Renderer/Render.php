<?php

namespace Cobalt\Renderer;

use Cobalt\Renderer\Exceptions\TemplateException;
use Exceptions\HTTP\NotFound;
use SebastianBergmann\Type\VoidType;

class Render {
    protected string $body = "";
    protected string $filename = "";
    public array $stock_vars = [];
    protected array $vars = [];
    protected bool $templateMayAccessStockVariables = true;
    const VAR_STRING = "([!@#$]*[\w.\?\-\[\]$]+)(\(.*\))?";
    const VARIABLE = "/[%\{]{2}" . self::VAR_STRING . "[\}%]{2}/i";
    const FUNCTION = "/@(\w+)\((.*?)\);?/";
    const OPERATORS = [
        "!",
        "#",
        "$",
        "@"
    ];

    function __construct() {
        // $http = (\is_secure()) ? "https" : "http";

        // Check if we need to parse for multiline function calls in scripts.
        // $this->function = (app("Renderer_parse_for_multiline_functions")) ? $this->multiline_function : $this->function;

        $query_string = ($_SERVER['QUERY_STRING']) ? "?$_SERVER[QUERY_STRING]" : "";
        $this->stock_vars = [
            'app'  => __APP_SETTINGS__,
            'versionHash' => VERSION_HASH,
            'get'  => $_GET,
            'post' => $_POST,
            // '$main_id' => 'main-content',
            'session' => session(),
            'request' => [
                'url' => server_name() . "$_SERVER[REQUEST_URI]$query_string",
                'referrer' => $_SERVER['HTTP_REFERRER'] ?? "",
            ],
            'context' => __APP_SETTINGS__['context_prefixes'][$GLOBALS['route_context']]['vars'] ?? [],
            'og_template' => "/parts/opengraph/default.html",
            // 'custom' => new CustomizationManager(),
        ];
    }

    /**
     * @deprecated - This function is for debugging purposes. DO NOT USE!
     * @param string $body 
     * @return void 
     */
    public function set_body(string $body): void{
        $this->setBody($body);
    }

    public function setBody(string $body):void {
        $this->body = $body;
    }

    public function getBodyFromTemplate(string $templatePath) {
        $file = find_one_file($GLOBALS['TEMPLATE_PATHS'], $templatePath);
        if(!$file) throw new NotFound("Specified template does not exist.");
        $this->filename = $file;
        $this->setBody(file_get_contents($file));
    }

    public function getBody():string {
        return $this->body;
    }

    /**
     * @deprecated - This function is for debugging purposes. DO NOT USE!
     * @param mixed $arr 
     * @return void 
     */
    public function set_vars($arr):void{ 
        $this->setVars($arr);
    }

    public function setVars($arr):void {
        $this->vars = $arr;
    }

    public function addVars($arr):void {
        $this->vars = array_merge($this->vars, $arr);
    }

    public function getVars($all = true):array {
        if($all) return array_merge($this->stock_vars, $this->vars);
        return $this->vars;
    }

    public function execute() {
        $variables = $this->getVars($this->templateMayAccessStockVariables);
        $processed_body = $this->body;

        $matched_functions = $this->parseFor(self::FUNCTION);
        foreach($matched_functions[0] as $i => $fn) {

        }

        $matched_variables = $this->parseFor(self::VARIABLE);
        $translationTable = [];
        foreach($matched_variables[0] as $i => $var) {
            if(key_exists($var, $translationTable)) continue;
            // 
            // 
            $parser = new Parser(
                $var, 
                $matched_variables[1][$i], 
                $matched_variables[2][$i] ?? "",
                $this->filename,
                $variables
            );
            $lookup = $parser->lookup($variables) ?? "";
            try {
                $processed_body = str_replace($var, $lookup, $processed_body);
            } catch (\TypeError $e) {
                throw new TemplateException($e->getMessage(), $this->filename, "__toString", $var, !!$this->filename);
            }
        }

        return $processed_body;
    }

    private function parseFor($regex) {
        $matches = [];
        \preg_match_all($regex, $this->body, $matches);
        return $matches;
    }

    
}