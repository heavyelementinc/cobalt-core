<?php

namespace Cobalt\Settings;

abstract class DefaultDefinition {
    
    function __construct($value,$settings) {
        $this->value = $value;
        $this->settings = $settings;
    }

    /**
     * The default value of this setting
     * @var string
     */
    public $default = "";

    /**
     * A list of other settings that this one depends on.
     * @var array
     */
    public $depends_on = [];

    abstract function determine_value();

}
