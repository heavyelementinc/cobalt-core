<?php

namespace Cobalt\Settings;

class SettingsPanel {
    private $definitions;
    function __construct(array $settings) {
        $this->setDefinitions($settings);
    }

    function setDefinitions($settings) {
        $this->definitions = $settings;
    }

    function getPanel() {
        $panel = "";
        // for()
    }
}