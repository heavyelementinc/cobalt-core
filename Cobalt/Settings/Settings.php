<?php

namespace Cobalt\Settings;

class Settings extends SettingsAbstract {
    function default_definition_path(): string {
        return __ENV_ROOT__ . "/config/default_settings.php";
    }
    
    function definitions():array {
        return [
            __APP_ROOT__ . "/config/settings.php",
            __APP_ROOT__ . "/ignored/config/settings.php",
            __APP_ROOT__ . "/config/settings.jsonc",
            __APP_ROOT__ . "/ignored/config/settings.jsonc",
            __APP_ROOT__ . "/config/settings.json",
            __APP_ROOT__ . "/ignored/config/settings.json",
        ];
    }
    function manifests_v1():array {
        return [
            __ENV_ROOT__ . "/manifest.jsonc",
            __APP_ROOT__ . "/manifest.jsonc",
            __APP_ROOT__ . "/manifest.json",
        ];
    }
    function manifests_v2():array {
        return [
            __ENV_ROOT__ . "/config/manifest.v2.jsonc",
            __APP_ROOT__ . "/config/manifest.v2.jsonc",
            __APP_ROOT__ . "/config/manifest.v2.json",
        ];
    }
}