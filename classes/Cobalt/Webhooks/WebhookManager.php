<?php

namespace Cobalt\Webhooks;

class WebhookManager extends \Drivers\Database {

    public function get_collection_name() {
        return "CobaltWebhooks";
    }

    

}
