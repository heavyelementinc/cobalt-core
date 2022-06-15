<?php

namespace Cobalt\Settings\Defaults;

interface Combine {
    function combine($value, $settings, $values);
}