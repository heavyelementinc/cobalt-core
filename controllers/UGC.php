<?php

use \Files\UGC as UserContent;

class UGC extends \Controllers\Pages {
    function __construct() {
        $this->UGC = new UserContent;
    }

    // Expects $submission in an individual
    function submit(array $submission) {
        if(!app("UGC_enable_user_generated_content")) throw new \Exceptions\HTTP\ServiceUnavailable("This app does not accept uploads.");

        return $this->UGC->submit($submission);
    }

    

    // Expects database ID
    function retrieve($file_id){
        if(!app("UGC_enable_user_generated_content")) throw new \Exceptions\HTTP\ServiceUnavailable("Service unavailable.");
        
    }
}