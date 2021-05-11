<?php

class Pages {

    // An example controller method
    function index() {

        /**
         * Use the add_vars function to provide variables that you wish to have
         * rendered into the final HTML
         */
        add_vars([
            'title' => "Hello World"
        ]);

        /** Use the add_template function to specify which template you want the
         * renderer to load and parse.
         */
        add_template("index.html");
    }
}
