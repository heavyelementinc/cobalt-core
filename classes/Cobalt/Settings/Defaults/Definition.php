<?php

namespace Cobalt\Settings;

abstract class DefaultDefinition {

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

    abstract function get_value($value, $defaults);
}
