<?php

use Cache\Manager;
use Cobalt\Settings\Settings;

/**
 * @todo Do not display help items that require environment context if in pre-env
 */
class App {
    public $help_documentation = [
        'rebuild' => [
            'description' => '[bool: $delete_settings = false] Rebuild the settings from scratch',
            'context_required' => true,
        ],
        'mode' => [
            'description' => '[dev|prod] Set the application\'s mode to dev/prod',
            'context_required' => true
        ],
        'info' => [
            'description' => 'Display version information',
            'context_required' => true,
        ]
    ];

    public function rebuild($delete = false) {
        $settings = new Settings();
        if($delete === "true") $delete = true;
        $result = $settings->bootstrap($delete);
        $records = $result->getModifiedCount() || $result->getUpsertedCount();
        $recordExplainer = "";
        if($records == 0) {
            $recordExplainer = fmt(" (this means no settings have been changed)","w");
        }
        
        $cache = new Manager("");
        $empty = $cache->empty();
        $cleared = (is_array($empty)) ? "Cache emptied: ".fmt("$empty[dirs] director".plural($empty['dirs'],'ies', 'y'),"s")." and ".fmt("$empty[files] file".plural($empty['files']),"s") : fmt("Failed to empty cache: ", "e").$empty;
        return "Boostrap updated ".fmt((($records == 0) ? "0" : $records)." record".plural($records),'i')."$recordExplainer\n$cleared";
    }

    private string $production_path = "/ignored/DEVELOPMENT";

    public function mode(string $mode) {
        $prod = __APP_ROOT__ . $this->production_path;
        if($mode == "prod" || $mode == "production") {
            return $this->set_production($prod);
        }
        return $this->set_development($prod);
    }

    private function set_production($prod) {
        if(file_exists(__APP_ROOT__ . $prod)) {
            unlink($prod);
            return "Application is now in PRODUCTION MODE";
        }
        return "App was already in PRODUCTION MODE.";
    }

    private function set_development($prod) {
        if(file_exists($prod)) {
            return "App was already in DEVELOPMENT MODE.";
        }
        touch($prod);
        return "Application is now in DEVELOPMENT MODE";
    }

    public function version() {
        versions();
    }

    public function info() {
        versions();
    }
}