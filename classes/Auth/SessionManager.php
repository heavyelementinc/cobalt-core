<?php

namespace Auth;

use MongoDB\BSON\ObjectId;

class SessionManager extends \Drivers\Database {

    public function get_collection_name() {
        return "sessions";
    }

    public function get_schema_name($doc = []) {
        return "\\Auth\\SessionSchema";
    }

    public function destroy_expired_sessions() {
        $time = strtotime("-" . app("Auth_session_days_until_expiration") . " days");
        $ct = $this->count([]);
        $result = $this->deleteMany([
            'expires' => ['$lte' => $time]
        ]);

        return "$ct sessions, " . $result->getDeletedCount() . " deleted";
    }

    public function destroy_session_by_user_id($id) {
        if(gettype($id) === "string") $id = new ObjectId($id);
        $result = $this->deleteMany([
            'user_id' => $id
        ]);

        return $result->getDeletedCount();
    }

    public function session_manager_ui_by_user_id(null|ObjectId $id) {
        if($id === null) return "";
        $result = $this->find(['user_id' => $id]);
        
        $html = view_each("/authentication/user-management/sessions/session-item.html", ['doc' => iterator_to_array($result)]);

        return view("/authentication/user-management/sessions/session-ui.html", ['html' => $html]);
    }

    public function destroy_session_by_session_id(ObjectId $id) {
        $result = $this->deleteOne(['_id' => $id]);
        return $result->getDeletedCount();
    }
}
