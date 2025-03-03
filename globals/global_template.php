<?php

use Cobalt\Maps\GenericMap;
use Cobalt\Model\GenericModel;
use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\Templates\Classes\NotAFunction;
use Cobalt\Renderer\Render;


/**
 * A shorthand way of rendering a template and getting the results. This is
 * included so you can include a template inside another template. This has the
 * potential to cause some recursive crap... so use caution!
 *
 * @param  string $template The name of the template
 * @param  mixed  $vars     Variables to include
 * @return string Processed template
 * @deprecated Use view() instead
 */
function with(string $template, $vars = []) {
    return view($template, $vars);
}

/** An error-tolerant template inclusion routine. Wraps the `with` function in a
 * try/catch block
 * 
 * @param string  $template The name of the template
 * @param mixed   $vars     Variables to include
 * @return string The processed template OR an empty string on error
 * @deprecated use maybe_view()
 */
function maybe_with($template, $vars = []) {
    return maybe_view($template, $vars);
}

/**
 * A shorthand way of rendering a template and getting the results. This is
 * included so you can include a template inside another template. This has the
 * potential to cause some recursive crap... so use caution!
 *
 * @param  string $template The name of the template
 * @param  mixed  $vars     Variables to include
 * @return string Processed template
 */
function view(string $template, array $vars = [], bool $absolute_path = false):string {
    if(__APP_SETTINGS__['Render_use_v2_engine']) {
        $render = new Render();
        $render->setVars(array_merge($GLOBALS['WEB_PROCESSOR_VARS'], $vars));
        $render->getBodyFromTemplate($template);
    } else {
        $render = new \Render\Render();
        $vars = array_merge($GLOBALS['WEB_PROCESSOR_VARS'] ?? [], $vars);
        $render->set_vars($vars);
        $render->from_template($template, $absolute_path);
    }
    return $render->execute();
}

function view_from_string(string $view, array $vars = []):string {
    $render = new \Render\Render();
    if ($vars === []) $vars = $GLOBALS['WEB_PROCESSOR_VARS'] ?? [];
    $render->set_vars($vars);
    $render->set_body($view, 'string');
    return $render->execute();
}

/** An error-tolerant template inclusion routine. Wraps the `with` function in a
 * try/catch block
 * 
 * @param string  $template The name of the template
 * @param mixed   $vars     Variables to include
 * @return string The processed template OR an empty string on error
 */
function maybe_view(string $template, array $vars = []):string {
    if (!$template) return "";
    if (!is_string($template)) return "";
    try {
        return view($template, $vars);
    } catch (Exception $e) {
        return "";
    }
}


function conditional_addition(string $template, bool $is_shown, $vars = []) {
    if (!$is_shown) return "";
    return view($template, $vars);
}

function with_each(string $template, $docs, $var_name = 'doc') {
    $rendered = "";
    foreach ($docs as $doc) {
        $rendered .= with($template, array_merge($GLOBALS['WEB_PROCESSOR_VARS'], [$var_name => $doc]));
    }
    return $rendered;
}

function view_each(string $template, Iterator|array $docs, string $var_name = 'doc', string|false $separator = "") {
    return implode($separator, view_array($template, $docs, $var_name));
}

function view_array(string $template, Iterator|array $docs, string $var_name = 'doc'){
    if(!is_array($docs) && is_iterable($docs)) $docs = iterator_to_array_recursive($docs);
    $array = [];
    $d = $docs;
    if(gettype($docs) === "array") {
        if(key_exists($var_name, $docs)) $d = $docs[$var_name];
    } else {
        $d = iterator_to_array($d);
    }
    foreach($d as $index => $doc){
        $array[$index] = view($template, array_merge(
            $d,
            [$var_name => $doc]
        ));
    }
    return $array;
}


function render($name, $posStart, $posEnd, $vars, $func_args) {
    global $WEB_PROCESSOR_VARS;
    $vars = array_merge($WEB_PROCESSOR_VARS, $vars);
    // return lookup_js_notation($name, $vars, false);
    return individual_var($name, $vars, $func_args, $posStart, $posEnd);
}

function individual_var($name, $vars, $arguments, $posStart, $posEnd) {
    // Reset everything to the default safe state.
    // $search[$i] = $replacement;
    // $name = $replacements[1][$i];
    $operator = $name[0];
    $is_inline_html = false;
    $is_inline_json = false;
    $is_inline_debug = false;
    $is_pretty_print = 0;
    $options = ENT_QUOTES;
    $explode = null;
    $function = null;

    /** Check if this variable is supposed to be inline HTML (as denoted by the "!")
     * if it is, we need to remove the exclamation point from the name */

    switch ($operator) {
        case "!":
            $operator_name = "Inline HTML Operator";
            $name = substr($name, 1); // Remove the !
            $is_inline_html = true; // Set our inline flag
            break;
        case "#":
            // $operator_name = "JSON Pretty Print Operator";
            // $name = substr($name, 1);
            // $is_pretty_print = JSON_PRETTY_PRINT;
            // $is_inline_json = true;
            // $options = ENT_NOQUOTES;
            $operator_name = "Debug Operator";
            $name = substr($name, 1);
            $is_inline_debug = config()['mode'] === COBALT_MODE_DEVELOPMENT;
            break;
        case "$":
            $operator_name = "JSON Print Operator";
            $name = substr($name, 1);
            if($name[0] === "!") {
                $operator_name .= " + Inline Operator = Pretty Print";
                $is_pretty_print = JSON_PRETTY_PRINT;
                $name = substr($name, 1);
            }
            $is_inline_json = true;
            $options = ENT_NOQUOTES;
            break;
        case "@":
            $operator_name = "Inline JSON";
            $name = substr($name, 1); // Remove the @
            $is_inline_json = true;
            break;
    }

    // Let's decide if we have a function call and strip that call from the lookup name
    if(is_array($arguments)) {
        $explode = explode(".",$name);
        $function = array_pop($explode);
        process_arguments_as_vars($arguments, $vars, $posStart, $posEnd);//$this->parse_funct_args(substr($args, 1,-1), $function, $replacements[2][$i]);//json_decode("[".substr($args, 1, -1)."]");
        $name = implode(".",$explode);
    }

    $literal_value = lookup_js_notation($name, $vars, false);
    
    $type = gettype($literal_value);
    if($type === "object") {
        $type = "REFERENCE: &lt;$name&gt,".(isset($literal_value->name) ? " INTERNAL_NAME: &lt;$literal_value->name&gt;," : "");
        $type .= " TYPE: &lt;". get_class($literal_value) . "&gt;,";
    }

    // At this point, we should have our value. If it's a model, then we should see about converting it to a function
    if($literal_value instanceof GenericModel) {
        $is_inline_html = true;
        $literal_value = call_prototype_function($literal_value, $function, $arguments);//, $replacements, $i, $subject);
        if($literal_value instanceof GenericModel) $literal_value = "[object GenericModel]";
    } else if(gettype($literal_value) === "object" && $function) {
        if(method_exists($literal_value, "htmlSafe")) $literal_value->htmlSafe($is_inline_html);
        $is_inline_html = true;
        $literal_value = call_prototype_function($literal_value, $function, $arguments);//, $replacements, $i, $subject);
    }

    if($literal_value instanceof GenericMap) {
        // debug_template($, "", $subject);
        throw new Exception("PersistanceMap shouldn't get to this point");
        // user_error("Schemas shouldn't make it to this point!", E_USER_WARNING);
    }

    $final_value = "";
    $closing_tag = "";
    if ($is_inline_debug || __APP_SETTINGS__['Template_debug_state'] !== 0) {
        $type .= ($function) ? " FUNCTION: $function(".htmlspecialchars(json_encode($arguments)).")," : "";
        $type .= ($operator_name) ? " OPERATOR: $operator_name," : "";
        $type .= " IS_HTML: " .json_encode($is_inline_html) . ",";
        $type .= " IS_JSON: " .json_encode($is_inline_json) . "";
    }

    if (__APP_SETTINGS__['Template_debug_state'] & TEMPLATE_DEBUG_SHOW_TYPES) {
        $final_value = "<cobalt-var title=\"$type\">";
        $closing_tag = "</cobalt-var>";
    }
    if ($is_inline_debug || __APP_SETTINGS__['Template_debug_state'] & TEMPLATE_DEBUG_RENDER_TYPES) {
        $final_value = "";
        $closing_tag = "<small class='cobalt-var-debug'>$type</small>";
    }


    if ($is_inline_json) $literal_value = json_encode($literal_value, $is_pretty_print); // Convert to JSON
    
    switch(gettype($literal_value)) {
        case "boolean":
            $literal_value = ($literal_value) ? "true" : "false";
            break;
        case "null":
            $literal_value = "null";
            break;
    }

    if($literal_value instanceof SchemaResult) $literal_value->htmlSafe($is_inline_html);
    // if(gettype($literal_value) === "object" && method_exists($literal_value, '__toString')) $literal_value = $literal_value->__toString();
    else if (!$is_inline_html) $literal_value = htmlspecialchars((string)$literal_value ?? '', $options); // < = &lt;

    return $final_value . $literal_value . $closing_tag;
}

function debug_template() {

}

function call_prototype_function(&$replace, $function, $arguments) {
    if($function) {
        $replace = $replace->{$function}(...$arguments);
        // try {
        //     // if(__APP_SETTINGS__['Renderer_debug_process']) $replace[$i]->setDebugTarget();
        // } catch(BadFunctionCallException $e) {
        //     // $this->debug_template($replacements[0][$i], $e->getMessage(), $subject);
        // } catch(TypeError $e) {
        //     // $this->debug_template($replacements[0][$i], $e->getMessage(), $subject);
        // }
    } else {
        $replace = $replace;//->getValue();
    }
    return $replace;
}

function call_template_func($name, $posStart, $posEnd, $vars) {
    global $WEB_PROCESSOR_VARS;
    $vars = array_merge($WEB_PROCESSOR_VARS, $vars);
    $args = array_slice(func_get_args(), 4);
    process_arguments_as_vars($args, $vars, $posStart, $posEnd);
    return call_user_func($name, ...$args);
}

function process_arguments_as_vars(&$arguments, $vars, $posStart, $posEnd) {
    foreach($arguments as $i => $arg) {
        if($arg[0] !== "$") continue;
        $arguments[$i] = individual_var(substr($arg,1), $vars, [], $posStart, $posEnd);
    }
}

function fonts_tag() {
    switch(__APP_SETTINGS__['fonts']['version']){
        case 2:
            return font_tag_v2();
        default:
            // $head = __APP_SETTINGS__['fonts']['head']['import'];
            // $body = __APP_SETTINGS__['fonts']['body']['import'];
            // $headFam = __APP_SETTINGS__['fonts']['head']['family'];
            // $bodyFam = __APP_SETTINGS__['fonts']['body']['family'];
            $links = [];
            $root = "";
            foreach(__APP_SETTINGS__['fonts'] as $link => $details) {
                $links[] = $details['import'];
                $root .= "--project-$link-family: $details[family];\n";
            }
            $links = implode("|", $links);
            return <<<HTML
            <link href="https://fonts.googleapis.com/" rel="preconnect">
            <link href="https://fonts.googleapis.com/css?family=$links&display=swap" rel="stylesheet">
            <style>
                :root{
                    $root
                }
            </style>
            HTML;
    }
}

function font_tag_v2() {
    $google = "https://fonts.googleapis.com";
    $href = "<link rel=\"preconnect\" href=\"$google\">\n<link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>\n";
    $tag = "";
    $def = "<style>\n:root{\n";
    foreach(__APP_SETTINGS__['fonts'] as $type => $font) {
        if($type === "version") continue;
        $tag .= "family=" . str_replace(" ", "+", $font['name']) . (($font['imports']) ? ":$font[imports]" : "") . "&";
        $family = $font['name'];
        $fallback = $font['fallback'];
        $def .= "--project-$type"."-family: \"$family\", $fallback;\n";
    }
    return $href . "<link href=\"$google/css2?$tag"."display=swap\" rel=\"stylesheet\">\n$def}</style>";
}