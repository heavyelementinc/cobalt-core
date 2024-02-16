<?php

use Drivers\DatabaseManagement;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\Unauthorized;

class DBMgmt {
    private $forbidden = ['cron', 'sessions'];

    function __construct() {
        // if(!is_root()) array_push($this->forbidden, "CobaltTokens");
    }

    function ui() {
        $db = new DatabaseManagement;
        $html = "";
        foreach($db->collections() as $col){
            $name = $col->getName();
            if(in_array($name, $this->forbidden)) continue;
            $html .= "<label><input type='checkbox' class='select-all-target' name='collections[]' value=\"$name\" checked='checked'> $name</label>";
        }

        add_vars([
            "title" => "Database Manager",
            "html" => $html,
        ]);

        return view("/admin/settings/database-manager.html");
    }

    function download() {
        if(!has_permission("Database_database_export")) throw new Unauthorized("You do not have permission to access this resource", true, $_POST);
        if(reauthorize("You must re-authorize your account!", []) !== true) return;

        $toInclude = $_POST['collections'];
        $db = new DatabaseManagement;
        $opts = [];

        foreach($db->collections() as $col) {
            $name = $col->getName();
            if(in_array($name, $this->forbidden)) continue;
            $opts[] = $name;
        }

        $ignoredCollections = array_diff($opts, $toInclude);
        // if(count($hasInvalid) >= 1) throw new BadRequest("Request contains bad collections", true);
        $file = "/tmp/export.json";
        $db->export($file, false, true, $ignoredCollections);
        header('Content-Disposition: attachment; filename="export.json"');
        header('Content-Type: application/json');

        readfile($file);
        header("Location: /admin/database");
        exit;
    }
}