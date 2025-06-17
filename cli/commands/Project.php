<?php

use Handlers\WebHandler;

require_once __CLI_ROOT__ . "/dependencies/new_project.php";

class Project {
    public $help_documentation = [
        'init' => [
            'description' => "Initializes a new project.",
            'context_required' => false
        ],
        'rebuild' => [
            'description' => "Schedule a rebuild of cached settings on next request.",
            'context_required' => true
        ],
    ];

    private NewProject $np;

    function init() {
        $this->np = new NewProject();
        $this->np->__collect_new_project_settings(func_get_args());
    }

    function rebuild($clearCSS = "true", $clearJS = "true", $clearCompiledTemplates = "true", $clearTemplatePrecomp = "true") {
        function removeDir(string $dir): bool {
            $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($it,
                         RecursiveIteratorIterator::CHILD_FIRST);
            foreach($files as $file) {
                if ($file->isDir()){
                    rmdir($file->getPathname());
                } else {
                    unlink($file->getPathname());
                }
            }
            return rmdir($dir);
        }

        $mod_string = [];
        if($clearCSS === "true") {
            $file = __APP_ROOT__.'/cache/css-precomp';
            $result = removeDir($file);
            if($result) $mod_string[] = fmt("CSS", "i");
            else say("Failed to delete 'CSS'","e");
        }
        if($clearJS === "true") {
            $result = removeDir(__APP_ROOT__.'/cache/js-precomp');
            if($result) $mod_string[] = fmt("JS","i");
            else say("Failed to delete 'JS'","e");
        }
        if($clearCompiledTemplates === "true") {
            $result = removeDir(__APP_ROOT__.'/cache/compiled');
            if($result) $mod_string[] = fmt("Templates","i");
            else say("Failed to delete 'Templates'","e");
        }
        if($clearTemplatePrecomp === "true") {
            $result = removeDir(__APP_ROOT__.'/cache/template-precomp');
            if($result) $mod_string[] = fmt("Misc Precomp", "i");
            else say("Failed to delete 'Misc Precomp'","e");
        }
        return "Deleted " . join(", ", $mod_string) . " caches. Caches will be rebuilt on-demand.";
        // $file = __APP_ROOT__ . "/ignored/config/settings.json";
        // if (!file_exists($file)) {
        //     if (!mkdir(pathinfo($file, PATHINFO_DIRNAME), true)) throw new Exception("Unable to create APP_ROOT/ignored/config path");
        // }
        // touch($file);
        // return "Next web request will regenerate settings cache.";
    }

    function get_deps() {
    }

}
