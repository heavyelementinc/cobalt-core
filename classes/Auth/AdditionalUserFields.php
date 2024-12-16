<?php

namespace Auth;

class AdditionalUserFields {
    public function __get_additional_schema():array {
        return [];
    }

    /**
     * Return an array of arrays, the key of each array will be used as an id
     * attribute for panels. The following sub-array keys are valid:
     * 
     *  * `name` - the name is used to label this function
     *  * `icon` - default is `card-bulleted-outline`
     *  * `view` - path to view used in the "User Manager"
     *  * `self_service` - If truthy, this panel will appear on the self-service panel
     *      if `true` then `view` will appear in self-service panel
     *      if this is a string, then it will be used as a path to a template
     * 
     */
    public function __get_additional_user_tabs():array { 
        return [
            // "panel-id" => [
            //     "name" => "Anoher field",
            //     "view" => "/relative/path/to/view.html",
            //     "icon" => "",
            //     "self_service" => false, 
            // ]
        ];
    }
}
