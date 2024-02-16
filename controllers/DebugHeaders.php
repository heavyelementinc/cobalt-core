<?php

use Exceptions\HTTP\BadRequest;

class DebugHeaders {
    function page() {
        add_vars([
            'title' => 'Debug Headers'
        ]);

        return set_template("/debug/debug-header.html");
    }

    function response($response) {
        if(!in_array($response, [
            'status',
            'modal',
            'value',
            'style',
            'innerHTML',
            'outerHTML',
            'disabled',
        ])) throw new BadRequest("Method not allowed");
        $this->{$response}();
    }

    function status() {
        $strings = [
            "Billable",
            "Ducksworth",
            "McQuacken",
            "MacBeth"
        ];
        $tag = "";

        if($_POST['tag']) {
            $tag = "@$_POST[tag] ";
        }

        header("X-Status: $tag".$this->getRandomString($strings));
    }

    function modal() {
        
    }

    function value() {
        $field1 = [
            'Some random text',
            'A different string of text',
            'Gibberish licorice',
            'lorem5'
        ];

        update('#field1', [
            'value' => $this->getRandomString($field1)
        ]);

        update('.field2', [
            'value' => rand(0, 500),
            'innerText' => rand(0,500),
        ]);
    }

    function style() {
        $options = [
            [
                'border' => '1px solid green',
                'text-transform' => 'uppercase',
                'font-weight' => 'bold'
            ],
            [
                'border' => '3px dotted pink',
                'text-transform' => 'lowercase',
                'font-weight' => 'normal'
            ],
            [
                'border' => '2px solid yellow',
                'text-transform' => 'capitalize',
                'font-weight' => '100'
            ],
            [
                'border' => '',
                'text-transform' => '',
                'font-weight' => ''
            ]
        ];
        update("#style", ['style' => $this->getRandomString($options)]);
        
        return "";
    }

    function innerHTML() {
        $this->html("innerHTML");
    }

    function outerHTML() {
        $this->html("outerHTML");
    }

    function html($type = "innerHTML") {
        $views = [
            "/debug/colors.html",
            "/debug/modal-template.html",
            "/debug/notifications.html",
            "/debug/parallax.html"
        ];
        update("#html-target", [$type => view($this->getRandomString($views))]);
    }

    function disabled() {
        $state = json_decode($_GET['state']);

        update(".disabled-target", ['disabled' => $state]);
        update("#disable-button", ["attribute" => ['action' => "/api/v1/header-tests/disabled/?state=".json_encode(!$state)]]);
    }

    private function getRandomString(array $array_of_strings) {
        return $array_of_strings[rand(0, count($array_of_strings) - 1)];
    }
}