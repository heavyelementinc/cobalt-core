<?php

/**
 * Keywords
 * 
 * {{previous}} - Used as method argument will reference the results of the last
 *                method executed
 * 
 * return       - Possible values are an integer, 'last', or 'all'
 */

namespace Drivers;

use Exception;

class Instructions {

    private $keywords = [
        'prev' => "{{previous}}",
        'return' => 'return'
    ];

    function __construct($instructions = null) {
        if ($instructions) $this->set_instructions($instructions);
    }

    public function set_instructions($instructions) {
        $this->instructions = $instructions;
    }

    public function execute() {
        $this->results = [];
        foreach ($this->instructions as $index => $instructions) {
            $this->index = $index;
            if (in_array("instance", $instructions)) {
                $this->results[$index] = $this->instance($instructions);
            }
        }
        return $this->results;
    }

    final private function instance($ins) {
        $callable = $ins["instance"];
        if (!is_callable($callable)) throw new Exception("$callable is not a callable");
        $args = [];
        if (in_array("argument", $ins)) $args = array_values($ins["argument"]);

        $return = "last";
        if (in_array($this->keywords['return'], $ins)) $return = $ins[$this->keywords['return']];

        unset($ins["instance"], $ins["argument"], $ins[$this->keywords['return']]);

        // Replace any previous reference to "{{prevous}}" in the constructor
        // with the previous results
        $args = $this->previous($args, $this->results, $this->index);

        $instance = $callable(...$args);

        $results = [];
        $iteration = 0;

        foreach ($ins as $type => $val) {
            if (!$type === "method") continue;
            if (!method_exists($instance, $val[0])) throw new Exception("$val[0] is not a method!");
            if (!is_array($val[1])) throw new Exception("Invalid list of arguments");

            $var[1] = $this->previous($val[1], $results, $iteration);

            $results[$iteration] = $instance->{$val[0]}(...array_values($val[1]));

            $iteration++;
        }

        switch ($return) {
            case gettype($return) === "int":
                if (!in_array($return, $results)) {
                    $return = "last";
                    break;
                }
                return $results[$return];
                break;
            case "all":
                return $results;
                break;
            case "last":
            default:
                return $results[count($results) - 1];
                break;
        }
    }

    final private function previous($args, $prev_results, $iteration) {
        // Replace any references to "{{previous}}" with 
        $refIndex = null;
        if (in_array($this->keywords['prev'], $args)) $refIndex = array_search($this->keyword['prev'], $args[1]);
        if ($refIndex !== null && in_array($iteration - 1, $prev_results)) $args[$refIndex] = $prev_results[$iteration - 1];
        return $args;
    }
}
