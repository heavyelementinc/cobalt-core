<?php

/** Render\Render is our way of parsing HTML template strings, finding 
 * {{variables}}, %%variables%% or @function("calls"); and executing them.
 * 
 * Render\Render accepts accepts vars with the set_vars method. It's capable of 
 * including preset global vars. The vars array lets us expose certain variables
 * to be included in our templates
 * 
 * Variables can be referenced in a template using either syntax
 *  - [x] {{mustache}}
 *  - [x] %%tictac%%
 *  - [ ] {%non-standard%}  // UNTESTED AND NOT SUPPORTED!
 *  - [ ] %{non_standard}%  // UNTESTED AND NOT SUPPORTED!
 * 
 * We allow two different syntaxes in our templates because certain linters in 
 * varying scenarios will "correct" the {{mustache}} syntax and insert new lines
 * in between the braces. 
 * 
 * The %%tictac%% syntax allows us to reference renderer variables inside CSS or
 * JS files where linters are most likely to be applied. HOWEVER, where possible,
 * {{mustache}} references are the *preferred* reference.
 * 
 * You may enable strict {{mustache}}-style parsing before execution of the 
 * rendering process by calling $this->strict_variable_parsing(true) or by 
 * changing Render_strict_variable_parsing in your app's config/settings.json 
 * file. Note that enabling this setting will *always* enforce strict parsing 
 * unless use $this->strict_variable_parsing(false) before parsing.
 * 
 * Of note here is that if you reference a variable like so:
 * 
 *   >  <h1>{{mustache}}<h1>
 *
 * It will automatically sanitize HTML characters by escaping them. In order to
 * include the raw value of the references variable, you must prepend the 
 * variable name with an ! exclamation point:
 * 
 *   > <h1>{{!mustache}}</h1>
 * 
 * ONLY DO THIS where you're certain it's safe to insert raw variables into your
 * HTML as there will be no way for the client to distinguish what's authentic
 * HTML and what is user-submitted data. User-submitted data parsed as HTML 
 * enables XSS attacks. BE CAREFUL.
 * 
 * Additionallty, you may prepend your variable with the @ symbol to encode your
 * value as JSON and store it safely within an HTML attribute.
 * 
 *   > <a href="{{@foo}}/bar">Baz</a>
 * 
 * Finally, you can pretty-print JSON using the "$" symbol.
 * 
 *   > <pre>{{$foo}}</pre>
 *
 * ================
 *  FUNCTION CALLS 
 * ================
 *  
 * Then we have function calls. Currently FUNCTION calls (not methods or static 
 * methods) are callable.
 * 
 * Functions may be called from within a template like so:
 *  - [x] @function_name("arg",1);
 *  - [ ] @other_function(23,"args")
 * 
 * Datatypes of the arguments are preserved here, and if the parsing of the 
 * arguments fails, an exception will be thrown.
 * 
 * Note that you can call a function with or without a trailing ; semicolon. 
 * However, including the semicolon is the preferred syntax.
 * 
 * TODO: Add callable vars support
 */

namespace Render;

use BadFunctionCallException;
use Cache\Manager;
use Cobalt\Maps\GenericMap;
use Cobalt\Model\GenericModel;
use Cobalt\Model\Types\MixedType;
use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\Templates\Compiler;
use Exception;
use Exceptions\HTTP\Error;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use stdClass;
use Stringable;
use TypeError;

class Render {
    public $body = "";
    public $name = "";
    public $stock_vars = [];
    public $vars = [];
    // const VAR_STRING = "([!@#$]*[\w.\?\-\[\]$]+)?"; //\|?([\w\s]*) -- If we want to add null coalescence
    const VAR_STRING = "([!@#$]*[\w.\?\-\[\]$]+)(\(.*\))?";
    public $custom;
    public $variable = "/[%\{]{2}" . self::VAR_STRING . "[\}%]{2}/i"; // Define the regex we're using to search for variables

    public $variable_alt = "/\{\{" . self::VAR_STRING . "\}\}/i"; // Stict-mode {{mustache}}-style parsing
    public $function = "/@(\w+)\((.*?)\);?/";
    public $multiline_function = "/@(\w+)\((.*[\w\[\]\"',\r\n]*)\);/mU";
    protected $enable_strict_mustache_syntax = false; // Use use_alt_syntax(true) to swap

    public $allow_stock_variable_access = true; // Controls whether app, get, and post are accessible during execution

    function __construct() {
        if (app('Render_strict_variable_parsing')) $this->strict_variable_parsing(true);
        $http = (\is_secure()) ? "https" : "http";

        // Check if we need to parse for multiline function calls in scripts.
        // $this->function = (app("Renderer_parse_for_multiline_functions")) ? $this->multiline_function : $this->function;

        $this->stock_vars = [];

        // $this->custom = new CustomizationManager();
    }

    /**
     * You can call this method with a boolean argument to swap to strictly 
     * enforce {{mustache}}-style references. Use of this method will only take 
     * effect if called before $this->execute()
     */
    function strict_variable_parsing(bool $status = false) {
        // Get the current status
        $current = $this->enable_strict_mustache_syntax;
        // Check if our current state is correct
        $is_current_state = ($status === $current);

        // Abort if we don't need to do anything.
        if ($is_current_state === true) return $this->enable_strict_mustache_syntax;

        // If the current state is false, we store both values and then swap them.
        $a = $this->variable;
        $b = $this->variable_alt;
        $this->variable = $b;
        $this->variable_alt = $a;

        // Now we store the current status.
        $this->enable_strict_mustache_syntax = !$this->enable_strict_mustache_syntax;

        // Then we return the status
        return $status;
    }

    /**
     * Set the body content to be parsed by the renderer from a template.
     * 
     * @throws Exception if the specified template cannot be found
     * @param  mixed $template_path The path to the template you want to use
     * @return void
     */
    function from_template(string $template_path, bool $absolute_path = false) {
        $is_native_template = __APP_SETTINGS__["Render_all_templates_as_native"];
        // Create our template cache if it doesn't exist
        // if (!\property_exists($GLOBALS, "template_cache")) $GLOBALS['TEMPLATE_CACHE'] = [];
        $extension = pathinfo($template_path, PATHINFO_EXTENSION);
        if(strtolower($extension) === "php") {
            $is_native_template = true;
        }
        // Check if we need to replace our template path with he appropriate CORE or APP path
        if ($template_path[0] === "_") {
            $template_path = str_replace(
                ["__CORE__", "__APP__"],
                [__ENV_ROOT__ . "/templates/", __APP_ROOT__ . "/private/templates/"],
                $template_path
            );
            // If the file doesn't exist, let's throw an error
            if (!file_exists($template_path)) throw new \Exceptions\HTTP\NotFound("That template was not found");
            // Let's load our template and save it to the temporary cache
            if(!$is_native_template) {
                $GLOBALS['TEMPLATE_CACHE'][$template_path] = file_get_contents($template_path);
            }
        } else if (!key_exists($template_path, $GLOBALS['TEMPLATE_CACHE'])) { // We do not have the file saved to the template cache
            if($absolute_path) {
                $contenders = $template_path;
            } else {
                // Load our template from the specified paths
                $contenders = find_one_file($GLOBALS['TEMPLATE_PATHS'], $template_path);
                if($contenders === false) throw new Error("The template \"$template_path\" was not found", "Internal server error");
            }
            
            // Load the template
            if(!$is_native_template) {
                $GLOBALS['TEMPLATE_CACHE'][$template_path] = file_get_contents($contenders);
            } else {
                $template_path = $contenders;
            }
        }
        $this->set_body($GLOBALS['TEMPLATE_CACHE'][$template_path] ?? $is_native_template, $template_path);
    }

    /** Set the body html template. $body is the template we'll be parsing for 
     * variables and function calls. Name is the name of the template file. This
     * is really only needed for debugging purposes. */
    function set_body($body, $name = null) {
        $this->body = $body;
        $this->name = $name;
    }

    /** Set the variables that we intend to use in our template. Should be an array. */
    function set_vars($vars) {
        $this->vars = $vars;
    }

    /** Start the template parsing process. Will return the finished template. */
    function execute() {
        if($this->body === true) {
            return $this->execute_advanced_template();
        }
        $this->add_stock_vars(); // Add stock variables so they're accessible
        
        $matched_functions = $this->parse_for_functions();
        $mutant = $this->replace_functs($this->body, $matched_functions);

        $matched_variables = $this->parse_for_vars();
        $mutant = $this->replace_vars($mutant, $matched_variables);

        return $mutant;
    }
    private string $compiled_cache_name;
    function execute_advanced_template() {
        $this->construct_file_name();
        $cache = new Manager($this->compiled_cache_name);
        if(!$cache->cache_exists()) {
            $this->compile_to_cache($cache);
        }
        if($cache->outdated($this->name)) {
            $this->compile_to_cache($cache);
        }
        ob_start();
        // $cache = __APP_ROOT__ . "/cache/" . $this->compiled_cache_name;
        $cache_file = $cache->file_path;
        $vars = $this->vars;
        extract($vars);
        require $cache_file;
        $result = ob_get_clean();
        return $result;
    }

    function construct_file_name() {
        // $md5 = substr(md5($this->name), 0, 5);
        $filename = obfuscate_path_name($this->name);
        $name = preg_replace("/\/{2,}/", "/", $filename);
        $name = str_replace("/","_",$name);
        $this->compiled_cache_name = "compiled/$name.php";
    }

    function compile_to_cache(Manager $cache){
        $comp = new Compiler();
        $comp->set_template($this->name);
        $compiled = $comp->compile();
        $cache->set($compiled, false);
    }

    /** Merge the stock variables */
    function add_stock_vars() {
        if (!$this->allow_stock_variable_access) return;
        $this->vars = array_merge(
            $this->stock_vars,
            $this->vars
        );
    }

    function parse_for_vars() {
        $match = []; // Store our standard syntax's matches
        \preg_match_all($this->variable, $this->body, $match); // Scan for mustache or tictac syntax
        return $match;

        // 03/13/21 Updated the regex to support {{mustache}} or %%tictac%% syntax in a single scan
        // making the following code unnecessary.
        // $match_alt = []; // Store our alt syntax's matches
        // \preg_match_all($this->variable_alt,$this->body,$match_alt); // Scan for alt syntax
        // /** Return the match and match_alt groups in a structure $this->replace_vars() expects */
        // return [array_merge($match[0],$match_alt[0]),array_merge($match[1],$match_alt[1])];
    }

    function replace_vars($subject, $replacements) {
        $search = [];
        $replace = [];
        foreach ($replacements[0] as $i => $replacement) {
            // Reset everything to the default safe state.
            $search[$i] = $replacement;
            $name = $replacements[1][$i];
            $is_inline_html = false;
            $is_inline_json = false;
            $is_pretty_print = 0;
            $operator = $name[0];
            $options = ENT_QUOTES;
            $process_vars = true;
            $ex = null;
            $function = null;
            $arguments = null;

            /** Check if this variable is supposed to be inline HTML (as denoted by the "!")
             * if it is, we need to remove the exclamation point from the name */

            switch ($operator) {
                case "!":
                    $name = substr($name, 1); // Remove the !
                    $is_inline_html = true; // Set our inline flag
                    $process_vars = false;
                    break;
                case "#":
                    $name = substr($name, 1);
                    $is_pretty_print = JSON_PRETTY_PRINT;
                    $is_inline_json = true;
                    $options = ENT_NOQUOTES;
                    $process_vars = false;
                    break;
                case "$":
                    $name = substr($name, 1);
                    if($name[0] === "!") {
                        $is_pretty_print = JSON_PRETTY_PRINT;
                        $name = substr($name, 1);
                    }
                    $is_inline_json = true;
                    $options = ENT_NOQUOTES;
                    $process_vars = false;
                    break;
                case "@":
                    $name = substr($name, 1); // Remove the @
                    $is_inline_json = true;
                    $process_vars = false;
                    break;
            }
            $arguments = [];
            // Let's decide if we have a function call and strip that call from the lookup name
            if($args = $replacements[2][$i]) {
                $ex = explode(".",$name);
                $function = array_pop($ex);
                $arguments = $this->parse_funct_args(substr($args, 1,-1), $function, $replacements[2][$i]);//json_decode("[".substr($args, 1, -1)."]");
                $name = implode(".",$ex);
            }

            $replace[$i] = $this->lookup_value($name, $process_vars);

            // At this point, we should have our value. If it's a model, then we should see about converting it to a function
            if($replace[$i] instanceof GenericModel) {
                $is_inline_html = true;
                $replace[$i] = $this->call_prototype_function($replace[$i], $function, $arguments, $replacements, $i, $subject);
                if($replace[$i] instanceof GenericModel) $replace[$i] = "[object GenericModel]";
            } else if(gettype($replace[$i]) === "object" && method_exists($replace[$i], "htmlSafe")) {
                $replace[$i]->htmlSafe($is_inline_html);
                $is_inline_html = true;
                $replace[$i] = $this->call_prototype_function($replace[$i], $function, $arguments, $replacements, $i, $subject);
            }

            if($replace[$i] instanceof GenericMap) {
                $this->debug_template($replacements[0][1], "", $subject);
                throw new Exception("PersistanceMap shouldn't get to this point");
                // user_error("Schemas shouldn't make it to this point!", E_USER_WARNING);
            }

            if ($is_inline_json) $replace[$i] = json_encode($replace[$i], $is_pretty_print); // Convert to JSON
            if (!$is_inline_html) $replace[$i] = htmlspecialchars($replace[$i] ?? '', $options); // < = &lt;
            // if (gettype($replace[$i]) === "object") $replace[$i] = "[object]";
        }

        if(__APP_SETTINGS__['Renderer_debug_process'] ?? false) {
            $result = "";
            foreach($search as $i => $var) {
                $result .= str_replace($var, $replace[$i], $subject);
            }
            return $result;
        }

        return str_replace($search, $replace, $subject);
    }

    function call_prototype_function(&$replace, $function, $arguments, $replacements, $i, $subject) {
        if($function) {
            // if(!$args) $args = [];
            try {
                // if(__APP_SETTINGS__['Renderer_debug_process']) $replace[$i]->setDebugTarget();
                $replace = $replace->{$function}(...$arguments);
            } catch(BadFunctionCallException $e) {
                $this->debug_template($replacements[0][$i], $e->getMessage(), $subject);
            } catch(TypeError $e) {
                $this->debug_template($replacements[0][$i], $e->getMessage(), $subject);
            }
        } else {
            $replace = $replace;//->getValue();
        }
        return $replace;
    }

    function lookup_value($name, $process = true) {
        // $custom = "custom.";
        // if($name === "custom") $this->custom->{str_replace("custom.","",$name)};
        $lookup = \lookup_js_notation($name, $this->vars);
        // $lookup = \lookup($name, $this->vars, false);
        if ($process) return $this->process_vars($lookup);
        return $lookup;
    }

    function process_vars($val) {
        switch (\gettype($val)) {
            case "boolean":
                // case "NULL":
            case "array":
                $value = \json_encode($val); // Is this what we want?
                break;
            case "object":
                if($val instanceof MixedType) return $val;
                if($val instanceof GenericModel) return $val;
                if($val instanceof SchemaResult) return $val;
                if($val instanceof ObjectId) return (string)$val;
                if (method_exists($val, "__toString")) {
                    $value = (string)$val;
                    break;
                } else {
                    $value = "[object Object]";
                }
                break;
            case "resource":
            case "resource (closed)":
                $value = "[resource]";
                break;
            default:
                $value = $val;
                break;
                // $value = ($val) ? "`true`" : "`false`";
                // break;
                // case "null": 
                //     $value = "`null`";
                // break;
                // case "array":
                //     $value = \implode(", ",$val);
                // break;
                // case "object":
                //     $value = "[Object]";
                // break;

        }
        return $value;
    }

    function parse_for_functions() {
        $match = [];
        \preg_match_all($this->function, $this->body, $match);
        return $match;
    }

    function replace_functs($subject, $functions) {
        $mutant = $subject;
        foreach ($functions[1] as $i => $funct) {
            if (!is_callable($funct)) $this->debug_template($funct, $functions[2][$i], $functions[0][$i], "@$funct() is not callable", $i, $subject);
            // try{
            //     $args = \json_decode("[" . $functions[2][$i] . "]", true, 512, JSON_THROW_ON_ERROR);
            // } catch (\Exception $e) {
            //     $this->debug_template($functions[0][$i], "@$funct() was supplied malformed parameters", $subject);
            // }
            // $mutant_vars = $this->functs_get_vars($args);
            $mutant_vars = $this->parse_funct_args($functions[2][$i], $funct, $functions[0][$i]);
            
            // We want to include the current context's variables when @view is called
            // from inside a template, so we add a special case. Fun.
            if (in_array($funct, ['maybe_with', 'with', 'view', 'maybe_view']) && !isset($mutant_vars[1])) $mutant_vars[1] = $this->vars;
            try{
                $result = $funct(...$mutant_vars);
            } catch (\Exception $e) {
                $this->debug_template($functions[0][$i], $e->getMessage(), $subject);
            } catch (\Error $e) {
                $this->debug_template($functions[0][$i], $e->getMessage(), $subject);
            }
            // If we run the 'set' callable, then we want to update our current vars with 
            // the values set just set.
            if($funct === "set") $this->vars = array_merge($this->vars,$GLOBALS['WEB_PROCESSOR_VARS']);
            $mutant = \str_replace($functions[0][$i], $result, $mutant);
        }
        return $mutant;
    }

    function parse_funct_args($args, $funct_name, $originalName, $errorMessage = "was supplied malformed parameters", ):array {
        try{
            $args = \json_decode("[" . ($args ?? "") . "]", true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            $this->debug_template($originalName, "$funct_name() $errorMessage", $this->body);
        }
        return $this->functs_get_vars($args);
    }

    function functs_get_vars($vars):array {
        $mutant = [];
        foreach ($vars as $value) {
            if (is_string($value) && $value[0] === "$") array_push($mutant, $this->lookup_value(substr($value, 1), false));
            else array_push($mutant, $value);
        }
        return $mutant;
    }

    function debug_template($errorToHighlight, $message, $body) {
        // $strpos = \strpos($GLOBALS['TEMPLATE_CACHE'][$this->name], $funct);
        // $template = $GLOBALS['TEMPLATE_CACHE'][$this->name];
        // $errorToHighlight = preg_quote($errorToHighlight);
        $strpos = \strpos($body, $errorToHighlight);
        $template = $body;
        $substr = substr($template, 0, $strpos);
        $explosion = explode("\n",$substr);
        $lineNum = count($explosion);
        $linePos = strlen($explosion[$lineNum - 1]);
        // $substr = substr();
        // $message = "";
        if(app("debug_exceptions_publicly")) $this->render_template_error($errorToHighlight, $message, $lineNum, $linePos, $template);
        $errorMessage = "$message in \"$this->name\" on line $lineNum, column $linePos";
        try{
            throw new \Exception($errorMessage);
        } catch (\Exception $e){}
        kill("A template error occurred. Please contact your IT team.");
    }

    function render_template_error($funct, $message, $lineNum, $strpos, $template) {
        header("HTTP/1.1 500 Internal Server Error");
        header("Content-Type: text/html");
        // $template = $GLOBALS['TEMPLATE_CACHE'][$this->name];
        // $safe = htmlspecialchars($template);
        $safe = str_replace($funct,"<code class='error'>$funct</code>",$template);
        echo "<h1>Cobalt Template Debugger</h1>";
        echo "<h2 style='font-family: monospace;'>Filename: " . $this->name . "</h2>";
        echo "<code>".$message . " in \"$this->name\" on line $lineNum, column " . $strpos."</code>";
        // echo "<pre class='template-debugger' language='html'>";
        $code = "";
        // foreach(explode("\n",$safe) as $number => $line) {
        //     $code .= "" . $line . "\n";
        // }
        $highlighted = syntax_highlighter($template, "error", "html", false);
        $error_highlighted = str_replace($funct,"<code class='error'>$funct</code>",$highlighted);
        echo $error_highlighted;
        // echo "</pre>";
        echo <<<HTML
        <style>
        .code-block-wrapper {
            max-width: calc(100vw - 4rem);
            overflow: scroll;
            max-height: calc(100vh - 4rem);
            box-sizing: border-box;
        }
        body {
            display: flex;
            flex-direction: column !important;
        }
        h1 {
            display:pre;
            font-family: monospace;
        }
        code.error {
            // color: red;
            text-decoration-line: spelling-error;
        }
        pre{
            background:#212124;
            color:white;
            white-space:pre-wrap
            
            width: 110ch;
        }
        pre span {
            counter-increment: line;
            display: pre;
            color: inherit;
            white-space: pre-wrap;
        }
        pre span:before{
            content: counter(line);
            display: inline-block;
            border-right: 1px solid #ddd;
            width: 3ch;
            text-align:right;
            padding: 0 .5em;
        }
        pre code.error{
            color:red;
            font-weight:bold;
        }</style>
        HTML;
        kill();
    }

    function strposall($needle, $haystack, $up_to) {
        $hs = explode($needle, $haystack);
        $strpos = [];
        foreach ($hs as $i => $n) {
        }
    }
}
