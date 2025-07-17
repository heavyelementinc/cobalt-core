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

    public function mode(string $mode) {
        if($mode == "prod" || $mode == "production") {
            return $this->set_production();
        }
        return $this->set_development();
    }

    private string $production_path = __APP_ROOT__ . "/ignored/DEVELOPMENT";

    private function set_production() {
        if(file_exists($this->production_path)) {
            unlink($this->production_path);
            return "Removed $this->production_path, application should now be in PRODUCTION MODE";
        }
        return "$this->production_path did not exist, so there was nothing to do. App is now in PRODUCTION MODE.";
    }

    private function set_development() {
        if(file_exists($this->production_path)) {
            return "$this->production_path already existed, so there was nothing to do. App is now in DEVELOPMENT MODE.";
        }
        touch($this->production_path);
        return "Created $this->production_path, application is now in DEVELOPMENT MODE";
    }
}