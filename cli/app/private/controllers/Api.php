<?php

class Api {

    // An example controller method
    function example() {

        // Using the ApiFetch class to issue requests to API endpoints will allow
        // gives the server special controls over the client's browser.
        
        // For example: the X-Location header will navigate the client to the
        // specified URL.
        header("X-Location: /home");

        // The X-Next-Request header is encoded as JSON and updates the 
        // form-request with a new API endpoint action and method. Allowed
        // fields can be specified individually or all at once.
        header('X-Next-Request: {"action": "/api/v1/example","method":"put"}');

        
        // If the request was initiated by a form-request element, then the certain
        // keys will will be able to 

        // If any key matches the name of a field, that field will be updated

        // If a key begins with "#", ".", or starts and ends with [] then a
        // document query will be performed and any/all matched elements will
        // have their OUTER HTML replaced with the value paired with the key.
        // In other words, the element will be replaced with the supplied markup.
        
        // Any data you return from the controller method will be serialized as
        // JSON and sent to the client.
        return [
            "some_name" => "An updated value", // Updated the field called "some_name"
            "#some-id" => "<div id='some-id'>Some HTML</div>", // Updates the OUTER HTML of an element with the name "#some-id"
            ".some-class" => "<div class='some-class'></div>", // Updates the OUTER HTML of all elements with the class "some-class"
            "[data-id='30913']" => "<section data-id='30913'></section>" // Updates the OUTER HTML of elements with matching 'data-id' attribute
        ];
    }
}
