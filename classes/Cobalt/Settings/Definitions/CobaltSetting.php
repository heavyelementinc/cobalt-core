<?php

namespace Cobalt\Settings\Definitions;

use \Cobalt\Settings\Exceptions\TypeError;

abstract class CobaltSetting {

    /**
     * The property where we store the setting.
     * @var mixed
     */
    private $value = null;
    
    function __construct($value, &$settings) {
        $this->value = $value;
        $this->settings = $settings;
    }

    /**
     * The default value of this setting
     * @var string
     */
    public $default = "";
    private $type = null;

    /**
     * A list of other settings that this one depends on.
     * @var array
     */
    public $depends_on = [];

    abstract function get_value($value);

    final public function value_callback() {
        $value = $this->get_value($this->value);
        
        return $value ?? $this->default;
    }

    final public function validate_setting($value = null) {
        if($value == null) $value = $this->value;
        $type = gettype($value);

        if($type !== null && $type !== $this->type) throw new TypeError("The type property for " . $this::class . " mismatches the specified value.");
    }

}
