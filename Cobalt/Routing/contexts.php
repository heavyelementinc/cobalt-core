<?php

use Cobalt\Routing\Kernels\Enums\Modes;
use Cobalt\Routing\Kernels\WebKernel;

/**
 * @return array<string,array{
 *   processor:string,
 *   prefix:string,
 *   no_session_exception:string,
 *   mode:Modes,
 *   permission:array<string>,
 *   session_refresh:bool,
 *   api_access:bool,
 *   router_boundary:true,
 *   active:bool,
 *   vars:array<string,string>
 * }>
 */
return [
    // "init" => [
    //     "processor" => WebKernel::class,
    //     "mode" => Modes::TEXT_HTML,
    //     "session_refresh" => false,
    //     "api_access" => false,
    //     "prefix" => "/",
    //     "active" => file_exists(__APP_ROOT__ . "/ignored/init.set")
    // ],
    "admin" => [
        "processor" => "Handlers\\AdminHandler",
        "prefix" => "/admin/",
        "no_session_exception" => "\\Exceptions\\HTTP\\Unauthorized",
        "mode" => Modes::TEXT_HTML,
        "permission" => ["Admin_panel_access"],
        "session_refresh" => true,
        "api_access" => true,
        "router_boundry" => true,
        "active" => __APP_SETTINGS__['Admin_panel_access'],
        "vars" => [
            "html_class" => "admin-panel"
        ]
    ],
    "documentation" => [
        "processor" => "Handlers\\DocumentationHandler",
        "prefix" => "/documentation/",
        "mode" => Modes::TEXT_HTML,
        "session_refresh" => true,
        "api_access" => true,
        "router_boundry" => true,
        "vars" => [
            "html_class" => "documentation"
        ]
    ],
    "debug" => [
        "processor" => "Handlers\\WebHandler",
        "prefix" => "/debug/",
        "mode" => Modes::TEXT_HTML,
        "session_refresh" => true,
        "api_access" => true,
        "router_boundry" => true,
        "active" => __APP_SETTINGS__['enable_debug_routes'],
        "vars" => [
            "html_class" => "debug-panel"
        ]
    ],
    "shared" => [
        "processor" => "Handlers\\SharedHandler",
        "mode" => Modes::APPLICATION_JSON,
        "session_refresh" => false,
        "api_access" => false,
        "prefix" => "/core-content/"
    ],
    "res" => [
        'processor' => "Handlers\\SharedHandler",
        'mode' => Modes::APPLICATION_JSON,
        "session_refresh" => false,
        "api_access" => false,
        "prefix" => "/res/"
    ],
    "apinotifications" => [
        "processor" => "Handlers\\ApiHandler",
        "mode" => Modes::APPLICATION_JSON,
        "session_refresh" => false,
        "api_access" => false,
        "prefix" => "/api/notifications/"
    ],
    "apiv1" => [
        "processor" => "Handlers\\ApiHandler",
        "mode" => Modes::APPLICATION_JSON,
        "session_refresh" => false,
        "api_access" => false,
        "prefix" => "/api/v1/"
    ],
    "webhooks" => [
        "processor" => "Handlers\\ApiHandler",
        "mode" => Modes::APPLICATION_JSON,
        "session_refresh" => false,
        "api_access" => false,
        "prefix" => "/webhooks/"
    ],
    "web" => [
        "kernel" => WebKernel::class,
        "mode" => Modes::TEXT_HTML,
        "session_refresh" => true,
        "api_access" => true,
        "router_boundry" => true,
        "prefix" => "/",
        "vars" => [
            "html_class" => "cobalt-app"
        ]
    ]
];
