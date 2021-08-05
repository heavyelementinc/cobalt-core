<?php

namespace Auth;

class SessionManager extends \Drivers\Database {

    public function get_collection_name() {
        return "sessions";
    }

    public function destroy_expired_sessions() {
        $time = strtotime("-" . app("Auth_session_days_until_expiration") . " days");
        $ct = $this->count([]);
        $result = $this->deleteMany([
            'expires' => ['$lte' => $time]
        ]);

        return "$ct sessions, " . $result->getDeletedCount() . " deleted";
    }
}
