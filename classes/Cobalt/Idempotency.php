<?php

namespace Cobalt;

use Drivers\Database;

class Idempotency extends Database {

    public function get_collection_name() {
        return "CobaltIdempotency";
    }

    function record($unique_identifier) {
        return $this->findOne(['identifier' => $unique_identifier]);
    }

    function status($unique_identifier) {
        $result = $this->record($unique_identifier);
        return $result->status;
    }

    function complete($unique_identifier) {
        $result = $this->updateOne(
            ['identifier' => $unique_identifier],
            ['$set' => [
                'status' => true
                ]
            ],
            ['upsert' => true]
        );
        return $result;
    }

    function incomplete($unique_identifier) {
        $result = $this->updateOne(
            ['identifier' => $unique_identifier],
            ['$set' => [
                'status' => false
                ]
            ],
            ['upsert' => true]
        );
        return $result;
    }
}
