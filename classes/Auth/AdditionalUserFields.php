<?php

namespace Auth;

class AdditionalUserFields {
    public function __get_additional_schema():array {
        return [];
    }

    public function __get_additional_user_tabs():array { 
        return [
            // "panel-id" => [
            //     "name" => "Anoher field",
            //     "view" => "/relative/path/to/view.html",
            // ]
        ];
    }
}
