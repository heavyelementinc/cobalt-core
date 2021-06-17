<?php

/**  */

namespace Auth;

class UserAccount {

    public $account = null;

    function __construct() {
        $this->collection = \db_cursor('users');
    }

    function get_user_by_id($id) {
        try {
            $this->user_id = new \MongoDB\BSON\ObjectId($id);
        } catch (\Exception $e) {
            $this->account = null;
        }
        $this->account = $this->collection->findOne(['_id' => $this->user_id]);
        return $this->account;
    }

    function get_user_by_uname_or_email($uname_or_email) {
        $this->account = $this->collection->findOne([
            '$or' => [
                ['uname' => $uname_or_email],
                ['email' => $uname_or_email]
            ]
        ]);
        return $this->account;
    }
}
