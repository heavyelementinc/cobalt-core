<?php

namespace Contact;

use MongoDB\BSON\ObjectId;

class ContactManager extends \Drivers\Database {
    function get_schema_name($doc = []) {
        return "\Contact\ContactFormSchema";
    }

    function get_collection_name() {
        return "CobaltContactForm";
    }

    function get_unread_count_for_user($user) {
        return $this->count([]) - $this->count(['read' => $user['_id']]);
    }

    function read_for_user($message_id, $user) {
        $id = $this->get_ids($message_id);
        $this->updateMany(
            ['_id' => $id],
            [
                '$addToSet' => [
                    'read' => $user->_id,
                ]
            ]
        );
        return true;
    }

    function unread_for_user($message_id, $user) {
        $id = $this->get_ids($message_id);
        $this->updateMany(['_id' => $id],
            ['$pull' => ['read' => $user->_id]]
        );
        return false;
    }

    function delete_submission($id) {
        $_id = new ObjectId($id);
        $conMan = $this->deleteOne(['_id' => $_id]);
        return $conMan->getDeletedCount();
    }

    function get_ids($ids) {
        $mutant_id = ['$in' => []];
        switch(gettype($ids)) {
            // case ($ids implements "Iterable"):
            case "array":
                foreach($ids as $id) {
                    array_push($mutant_id['$in'], $this->__id($id));
                }
                break;
            default:
                $mutant_id = $this->__id($ids);
                break;
        }
        return $mutant_id;
    }
}
