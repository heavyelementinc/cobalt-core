<?php
$dbg_options = "Debug";
$dbg_options__subgroup = "Public Debugging";
$settings = [
    /** BASIC */
    "debug_exceptions_publicly" => [
        "default" => "",
        "meta" => [
            "group" => $dbg_options,
            "subgroup" => $dbg_options__subgroup,
            "name" => "Public stack traces",
            "description" =>  "If an error occurs, display a public stack trace for the error.",
            "dangerous" => true,
            "type" => "input-switch"
        ],
        "validate" => [
            "confirm" => "This is dangerous. Are you sure you want to continue?",
        ]
    ],
];