<?php

namespace Cobalt\Settings;

class Preferences extends SettingsAbstract {
    function default_definition_path(): string {
        return __ENV_ROOT__ . "/config/default_preferences.php";
    }
    
    function definitions():array {
        return [
            __APP_ROOT__ . "/config/preferences.php",
            __APP_ROOT__ . "/ignored/config/preferences.php",
        ];
    }
    function manifests_v1():array {
        return [];
    }
    function manifests_v2():array {
        return [];
    }
}