<?php
/** Render\Render is our way of parsing HTML template strings, finding {{variables}}, %%variables%%
 * or @function("calls"); and executing them.
 * 
 * Render\Render accepts accepts vars with the set_vars method. It's capable of including preset
 * global vars. The vars array lets us expose certain variables to be included in our templates
 * 
 * Variables can be referenced in a template using either syntax
 *  - [x] {{mustache}}
 *  - [x] %%tictac%%
 *  - [ ] {%non-standard%}  // UNTESTED AND NOT SUPPORTED!
 *  - [ ] %{non_standard}%  // UNTESTED AND NOT SUPPORTED!
 * 
 * We allow two different syntaxes in our templates because certain linters in varying scenarios
 * will "correct" the {{mustache}} syntax and insert new lines in between the braces. 
 * 
 * The %%tictac%% syntax allows us to reference renderer variables inside CSS or JS files where
 * linters are most likely to be applied. HOWEVER, where possible, {{mustache}} references are
 * the *preferred* reference.
 * 
 * You may enable strict {{mustache}}-style parsing before execution of the rendering process
 * by calling $this->strict_variable_parsing(true) or by changing Render_strict_variable_parsing
 * in your app's config/settings.json file. Note that enabling this setting will *always* enforce
 * strict parsing unless use $this->strict_variable_parsing(false) before parsing.
 * 
 * Of note here is that if you reference a variable like so:
 * 
 *   >  <h1>{{mustache}}<h1>
 * 
 * It will automatically sanitize HTML characters by escaping them. In order to include the raw
 * value of the references variable, you must prepend the variable name with an ! exclamation point:
 * 
 *   > <h1>{{!mustache}}</h1>
 * 
 * ONLY DO THIS where you're certain it's safe to insert raw variables into your HTML as there
 * will be no way for the client to distinguish what's authentic HTML and what is user-submitted
 * data. User-submitted data parsed as HTML enables XSS attacks. BE CAREFUL.
 *
 * ================
 *  FUNCTION CALLS 
 * ================
 *  
 * Then we have function calls. Currently FUNCTION calls (not methods or static methods) are
 * callable.
 * 
 * Functions may be called from within a template like so:
 *  - [x] @function_name("arg",1);
 *  - [ ] @other_function(23,"args")
 * 
 * Datatypes of the arguments are preserved here, and if the parsing of the arguments fails, an
 * exception will be thrown.
 * 
 * Note that you can call a function with or without a trailing ; semicolon. However, including the
 * semicolon is the preferred syntax.
 * 
 * TODO: Add callable vars support
 */
namespace Render;
class Render{
    public $body = "";
    public $vars = [];

    public $variable = "/[%\{]{2}(\!?[\w.\-\[\]$]+)[\}%]{2}/i"; // Define the regex we're using to search for variables
    public $variable_alt = "/\{\{(\!?[\w.\-\[\]$]+)\}\}/i"; // Stict-mode {{mustache}}-style parsing
    public $function = "/@(\w+)\((.*?)\);?/";
    protected $enable_strict_mustache_syntax = false; // Use use_alt_syntax(true) to swap

    public $allow_stock_variable_access = true; // Controls whether app, get, and post are accessible during execution

    function __construct(){
        if(app('Render_strict_variable_parsing')) $this->strict_variable_parsing(true);
        $this->stock_vars = ['app' => app(),'get' => $_GET,'post' => $_POST,'session' => session()];
    }

    /**
     * You can call this method with a boolean argument to swap to strictly enforce {{mustache}}-style 
     * references.  Use of this method will only take effect if called before $this->execute()
     */
    function strict_variable_parsing(Bool $status = false){
        // Get the current status
        $current = $this->enable_strict_mustache_syntax;
        // Check if our current state is correct
        $is_current_state = ($status === $current);

        // Abort if we don't need to do anything.
        if($is_current_state === true) return $this->enable_strict_mustache_syntax;
        
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
    function from_template(string $template_path){
        if(!\property_exists($this,"template_cache")) $this->template_cache = [];
        if(!key_exists($template_path,$this->template_cache)){
            $contenders = files_exist([
                __APP_ROOT__ . "/private/templates/$template_path",
                __ENV_ROOT__ . "/templates/$template_path"
            ]);
            $this->template_cache[$template_path] = file_get_contents($contenders[0]);
        }
        $this->set_body($this->template_cache[$template_path],$template_path);
    }

    /** Set the body html template. $body is the template we'll be parsing for 
     * variables and function calls. Name is the name of the template file. This
     * is really only needed for debugging purposes. */
    function set_body($body,$name = null){
        $this->body = $body;
        $this->name = $name;
    }

    /** Set the variables that we intend to use in our template. Should be an array. */
    function set_vars($vars){
        $this->vars = $vars;
    }

    /** Start the template parsing process. Will return the finished template. */
    function execute(){
        $this->add_stock_vars(); // Add stock variables so they're accessible
        $matched_variables = $this->parse_for_vars();
        $mutant = $this->replace_vars($this->body,$matched_variables);

        $matched_functions = $this->parse_for_functions();
        $mutant = $this->replace_functs($mutant,$matched_functions);

        return $mutant;
    }

    /** Merge the stock variables */
    function add_stock_vars(){
        if(!$this->allow_stock_variable_access) return;
        $this->vars = array_merge(
            $this->stock_vars,
            $this->vars
        );
    }

    function parse_for_vars(){
        $match = []; // Store our standard syntax's matches
        \preg_match_all($this->variable,$this->body,$match); // Scan for mustache or tictac syntax
        return $match;

        // 03/13/21 Updated the regex to support {{mustache}} or %%tictac%% syntax in a single scan
        // making the following code unnecessary.
        // $match_alt = []; // Store our alt syntax's matches
        // \preg_match_all($this->variable_alt,$this->body,$match_alt); // Scan for alt syntax
        // /** Return the match and match_alt groups in a structure $this->replace_vars() expects */
        // return [array_merge($match[0],$match_alt[0]),array_merge($match[1],$match_alt[1])];
    }

    function replace_vars($subject,$replacements){
        $search = [];
        $replace = [];
        foreach($replacements[0] as $i => $replacement){
            $search[$i] = $replacement;
            $name = $replacements[1][$i];
            $is_inline_html = false;
            /** Check if this variable is supposed to be inline HTML (as denoted by the "!")
             * if it is, we need to remove the exclamation point from the name */
            if($name[0] === "!") {
                $name = substr($name,1); // Remove the !
                $is_inline_html = true; // Set our inline flag
            }
            $replace[$i] = $this->lookup_value($name);
            if(!$is_inline_html) $replace[$i] = htmlspecialchars($replace[$i]);
        }
        return str_replace($search,$replace,$subject);
    }

    function lookup_value($name){
        $lookup = \lookup_js_notation($name,$this->vars);
        return $this->process_vars($lookup);
    }

    function process_vars($val){
        switch(\gettype($val)){
            case "boolean":
            // case "NULL":
            case "array":
            case "object":
                $value = \json_encode($val); // Is this what we want?
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

    function parse_for_functions(){
        $match = [];
        \preg_match_all($this->function,$this->body,$match);
        return $match;
    }

    function replace_functs($subject,$functions){
        $mutant = $subject;
        foreach($functions[1] as $i => $funct){
            if(!is_callable($funct)) $this->debug_template($function[0][$i],"$funct is not callable");
            $args = \json_decode("[".$functions[2][$i]."]",true,512,JSON_THROW_ON_ERROR);
            $mutant_vars = $this->functs_get_vars($args);
            // We want to include the current context's variables when @with is called
            // from inside a template, so we add a special case. Fun.
            if($funct === "with" && !isset($mutant_vars[1])) $mutant_vars[1] = $mutant_vars;
            $result = $funct(...$mutant_vars);
            $mutant = \str_replace($functions[0][$i],$result,$mutant);
        }
        return $mutant;
    }

    function functs_get_vars($vars){
        $mutant = [];
        foreach($vars as $value){
            if($value[0] === "$") array_push($mutant,$this->lookup_value(substr($value,1)));
            else array_push($mutant,$value);
        }
        return $mutant;
    }

    function debug_template($funct){
        $strpos = \strpos($this->body,$funct);
        throw new Exception($message . "; at position " . $strpos);
    }

    function strposall($needle,$haystack,$up_to){
        $hs = explode($needle,$haystack);
        $strpos = [];
        foreach($hs as $i => $n){
            
        }
    }
}
