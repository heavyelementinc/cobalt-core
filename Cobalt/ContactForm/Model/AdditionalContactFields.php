<?php

namespace Cobalt\ContactForm\Model;

use Cobalt\Model\Types\StringType;

class AdditionalContactFields {
    /**
     * Define some extra schema entries for the contact form
     * to accept. This must be in the same format as other
     * Model schemas.
     * @return array 
     */
    public function __get_additional_schema():array {
        return [];
    }

    /**
     * Specify some action for the application to take after
     * a contact form has been submitted.
     * @return void 
     */
    public function onSubmit():void {

    }

    function __get_additional_fields():string {
        return "";
    }
}