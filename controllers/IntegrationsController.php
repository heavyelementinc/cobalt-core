<?php

use Cobalt\Integrations\Base;
use Cobalt\Integrations\OauthBase;
use Controllers\Controller;
use Exceptions\HTTP\NotFound;

class IntegrationsController extends Controller{
    public function index() {
        $integrations = $this->load_classes(__APP_ROOT__ . "/Cobalt/Integrations/Final");
        $integrations = array_merge($integrations, $this->load_classes(__ENV_ROOT__ . "/Cobalt/Integrations/Final"));

        $html = "";
        foreach($integrations as $integration) {
            $namespaced = "\\Cobalt\\Integrations\\Final\\$integration\\$integration";
            $i = new $namespaced();
            if($i instanceof Base === false) throw new Exception("Namespaced class $namespaced is not an instance of the Base Integration");
            $html .= $i->html_index_button();
        }

        return view("/Cobalt/Integrations/templates/index.html", ['html' => $html]);
    }

    public function getOauthIntegrations() {
        $integrations = $this->load_classes(__APP_ROOT__ . "/Cobalt/Integrations/Final");
        $integrations = array_merge($integrations, $this->load_classes(__ENV_ROOT__ . "/Cobalt/Integrations/Final"));
        $html = "";
        foreach($integrations as $integration) {
            $namespaced = "\\Cobalt\\Integrations\\Final\\$integration\\$integration";
            $i = new $namespaced();
            if($i instanceof OauthBase === false) continue;//throw new Exception("Namespaced class $namespaced is not an instance of the Base Integration");
            $html .= $i->html_oauth_button();
        }
        return $html;
    }

    private function load_classes($dir) {
        $scandir = @scandir($dir);
        if(!$scandir) return [];
        $candidates = [];
        foreach($scandir as $file) {
            if($file === "." || $file === "..") continue;
            if($file[0] === ".") continue;
            if(is_dir($dir ."/". $file)) {
                if(file_exists("$dir/$file/$file.php")) $candidates[] = $file;
            }
        }
        return $candidates;
    }

    private function namespaced($name) {
        $namespaced = "\\Cobalt\\Integrations\\Final\\$name\\$name";
        try {
            $i = new $namespaced();
        } catch (Exception $e) {
            throw new NotFound("No valid integration found", true);
        }
        return $i;
    }

    public function token_editor($index) {
        $i = $this->namespaced($index);

        add_vars([
            'title' => $i->config->publicName,
            'receiveEndpoint' => str_replace("http://", "https://", server_name()) . route("IntegrationsController@oauth_receive", [$index]),
            'name' => $i->config->name,
            'publicName' => $i->config->publicName,
            'tokenName' => $i->config->tokenName,
            'icon' => $i->config->icon,
            'config' => $i->config,
        ]);

        return $i->html_token_editor();
    }

    public function update($name) {
        $i = $this->namespaced($name);
        if(!$i->configured) return $this->insert($i);
        $persistance = $i->config;
        $persistance->__validate($_POST);
        $ops = $persistance->__operators();
        $result = $i->updateOne(
            ['_id' => $persistance->_id],
            $ops,
        );
        return $result->getModifiedCount();
    }

    public function insert($instance) {
        $persistance = $instance->config;
        $persistance->__validate($_POST);
        $persistance->__token_name = $persistance->tokenName;
        $result = $instance->insertOne($persistance);
        $created = $result->getInsertedCount();
        header("X-Status: Created $created document".plural($created));
        return $created;
    }

    public function delete($instance) {
        $i = $this->namespaced($instance);
        $query = ['__token_name' => $i->config->tokenName];
        $count = $i->count($query);
        if($count === 0) {
            header("X-Status: No documents to delete");
            return;
        }
        if($count >= 1) confirm("There's $count document".plural($count)." that will be deleted. Continue?", $_POST);
        $result = $i->deleteMany($query);
        header("X-Status: Deleted ".$result->getDeletedCount()." document".plural($count));
    }

    public function oauth_receive($instance) {
        $i = $this->namespaced($instance);

        $result = $i->oauth_receive($_GET);

        $state = $_GET['state'];
        if(!$state) $state = "/me";

        header("Location: $state");
        exit;
    }

    public function oauth_deauthorize(){ 
        return view("index.html");
    }
}