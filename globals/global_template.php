<?php

use Cobalt\Maps\GenericMap;
use Cobalt\Model\GenericModel;
use Cobalt\Templates\Classes\NotAFunction;

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
    $is_pretty_print = 0;
    $options = ENT_QUOTES;
    $explode = null;
    $function = null;

    /** Check if this variable is supposed to be inline HTML (as denoted by the "!")
     * if it is, we need to remove the exclamation point from the name */

    switch ($operator) {
        case "!":
            $name = substr($name, 1); // Remove the !
            $is_inline_html = true; // Set our inline flag
            break;
        case "#":
            $name = substr($name, 1);
            $is_pretty_print = JSON_PRETTY_PRINT;
            $is_inline_json = true;
            $options = ENT_NOQUOTES;
            break;
        case "$":
            $name = substr($name, 1);
            if($name[0] === "!") {
                $is_pretty_print = JSON_PRETTY_PRINT;
                $name = substr($name, 1);
            }
            $is_inline_json = true;
            $options = ENT_NOQUOTES;
            break;
        case "@":
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

    // At this point, we should have our value. If it's a model, then we should see about converting it to a function
    if($literal_value instanceof GenericModel) {
        $is_inline_html = true;
        $literal_value = call_prototype_function($literal_value, $function, $arguments);//, $replacements, $i, $subject);
        if($literal_value instanceof GenericModel) $literal_value = "[object GenericModel]";
    } else if(gettype($literal_value) === "object" && method_exists($literal_value, "htmlSafe")) {
        $literal_value->htmlSafe($is_inline_html);
        $is_inline_html = true;
        $literal_value = call_prototype_function($literal_value, $function, $arguments);//, $replacements, $i, $subject);
    }

    if($literal_value instanceof GenericMap) {
        // debug_template($, "", $subject);
        throw new Exception("PersistanceMap shouldn't get to this point");
        // user_error("Schemas shouldn't make it to this point!", E_USER_WARNING);
    }

    if ($is_inline_json) $literal_value = json_encode($literal_value, $is_pretty_print); // Convert to JSON
    if (!$is_inline_html) $literal_value = htmlspecialchars((string)$literal_value ?? '', $options); // < = &lt;
        // if (gettype($replace[$i]) === "object") $replace[$i] = "[object]";
    // }

    return $literal_value;
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