<?php

namespace Auth;

class AdditionalUserFields {
    public function __get_additional_schema():array {
        return [];
    }

    public function __get_additional_user_tab():string { 
        return "";
    }
}
