<?php

use \Cobalt\CLI\Migration;
use Cobalt\Integrations\Final\Facebook\FBConfig;
use Cobalt\Integrations\Final\Ghost\GhostConfig;
use Cobalt\Integrations\Final\MailChimp\MailChimpConfig;
use Cobalt\Integrations\Final\Patreon\Patreon;
use Cobalt\Integrations\Final\Patreon\PatreonConfig;
use Cobalt\Integrations\Final\YouTube\Config;
use Cobalt\Integrations\Final\YouTube\YouTubeConfig;
use Cobalt\Pages\Models\PageMap;

class tokens extends Migration {

    function config():void {
        $this->__run_one = true;
    }

    function runAll() {
        return null;
    }

    public function get_persistance() {
        return new PageMap();
    }

    public function get_collection_name() {
        return "IntegrationTokens";
    }

    public function beforeOneExecute(): ?\MongoDB\Driver\Cursor {
        return $this->find([], ['limit' => $this->count([]), 'projection' => ['__pclass' => 0]]);
    }

    public function runOne($document) {
        $id = $document['_id'];
        unset($document['_id']);
        // $persistance = $this->get_persistance();
        $persistance = null;
        switch($document->__token_name) {
            case "facebook":
                $persistance = new FBConfig();
                break;
            case "ghost":
                $persistance = new GhostConfig();
                break;
            case "mailchimp":
                $persistance = new MailChimpConfig();
                break;
            case "patreon":
                $persistance = new PatreonConfig();
                break;
            case "YouTubeToken":
                $persistance = new YouTubeConfig();
                break;
            default:
                throw new Exception("Unknown token name. Aborting.");
        }
        $doc = [
            '__pclass' => new \MongoDB\BSON\Binary($persistance::class, \MongoDB\BSON\Binary::TYPE_USER_DEFINED),
            '__version' => "1.0"
        ];

        $result = $this->updateOne(['_id' => $id], ['$set' => $doc]);
        return $result;
    }
}