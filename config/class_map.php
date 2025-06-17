<?php

$CLASS_MAP = [
    "SettingsManager" => [
        "path" => __ENV_ROOT__."/classes/SettingsManager/SettingsManager.php"
    ],
    "SettingsManagerException" => [
        "path" => __ENV_ROOT__."/classes/SettingsManager/SettingsManagerException.php"
    ],
    "ApiHandler" => [
        "path" => __ENV_ROOT__."/classes/Api/ApiHandler.php"
    ],
    'Cobalt\Integrations\Patreon\PatreonConfig' => [
        "path" => __ENV_ROOT__."/Cobalt/Integrations/Final/Patreon/PatreonConfig.php"
    ],
    'Cobalt\Integrations\Ghost\GhostConfig' => [
        "path" => __ENV_ROOT__."/Cobalt/Integrations/Final/Ghost/GhostConfig.php"
    ],
    'Cobalt\Integrations\Facebook\FBConfig' => [
        "path" => __ENV_ROOT__."/Cobalt/Integrations/Final/Facebook/FBConfig.php"
    ],
    'Cobalt\Integrations\MailChimp\MailChimpConfig' => [
        "path" => __ENV_ROOT__."/Cobalt/Integrations/Final/MailChimp/MailChimpConfig.php"
    ],
    'Cobalt\Integrations\YouTube\Config' => [
        "path" => __ENV_ROOT__."/Cobalt/Integrations/Final/YouTube/Config.php"
    ]
];

$CLASS_NAMESPACES = [
    "Exceptions\\HTTP" => [
        'path' => __ENV_ROOT__."/Exceptions/HTTP/"
    ]
];