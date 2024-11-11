<?php

const DEFAULT_DEFINITIONS = [
    
    /*******************************************/
    /* =============== GENERAL =============== */
    /*******************************************/
    /* Provide a doman name that we expect to be listening for. This will later 
    be used to add CORS headers. */
    "domain_name" => [
        "default" => "",
        "meta" => [
            "group" => "Basic",
            "subgroup" => "General",
            "name" => "Domain Name",
            "type" => "input"
        ],
        "validate" => [
            "confirm" => "If you change this value, you may lose access to this page and will need to manually change the value to regain access.",
            "filter" => [
                "FILTER_VALIDATE_URL" => []
            ]
        ]
    ],
    /* The full name of the application. */
    "app_name" => [
        "default" => "Cobalt Engine",
        "directives" => [
            "public" => true
        ],
        "meta" => [
            "group" => "Basic",
            "subgroup" => "General",
            "name" => "Application Name",
            "type" => "input"
        ]
    ],
    /* A shortened name for the application. */
    "app_short_name" => [
        "default" => "",
        "directives" => [
            "alias" => "app_name",
            "subgroup" => "Details",
            "public" => true
        ],
        "meta" => [
            "group" => "Basic",
            "name" => "Short Name",
            "type" => "input"
        ]
    ],
    /* A bespoke name to be listed in the copyright notice */
    "app_copyright_name" => [
        "default" => "",
        "meta" => [
            "group" => "Basic",
            "subgroup" => "Details",
            "name" => "Copyright Name",
            "type" => "input"
        ],
        "directives" => [
            "alias" => "app_name"
        ]
    ],
    "Timezone" => [
        "default" => "America/New_York"
    ],
    "DB_export_directory" => [
        "default" => "/ignored/db_backups/"
    ],
    /* A bespoke name to be listed in the copyright notice */
    "logo" => [
        "default" => [
            "media" =>[
                "id" => null,
                "filename" => "\/core-content\/img\/branding\/cobalt-logo.svg",
                "meta" =>[
                    "width" => 1500,
                    "height" => 1500,
                    "mimetype" =>"image\/svg+xml"
                ]
            ],
            "thumb" =>[
                "id" => null,
                "filename" => "\/core-content\/img\/branding\/cobalt-logo.svg",
                "meta" =>[
                    "width" => 150,
                    "height" => 150,
                    "mimetype" =>"image\/svg+xml"
                ]
            ]
        ],
        "meta" => [
            // "group" => "Logo",
            // "subgroup" => "Details",
            // "name" => "Logo",
            // "view" => "/admin/settings/inputs/logo.html"
        ]
    ],
    /* Decides if the logo should be shown in the default header */
    "display_masthead" => [
        "default" => true
    ],
    "Landing_page_home_route_options" => [
        "default" => [
            "anchor" => ["name" => "Home"],
            "navigation" => ["main_navigation"]
        ]
    ],
    /* Debug routes include things like the WebComponent input tests */
    "enable_debug_routes" => [
        "default" => false,
        "directives" => [
            "config" => "enable_debug_routes"
        ],
        "meta" => [
            "group" => "Cache &amp; Debug",
            "name" => "Enable debug routes",
            "type" => "input-switch",
            "debug" => true
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    /* I don't think this does anything yet -GM */
    "route_cache_disabled" => [
        "default" => false,
        "meta" => [
            "group" => "Cache &amp; Debug",
            "name" => "Route Cache Disabled",
            "type" => "input-switch",
            "debug" => true
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    "cached_content_disabled" => [
        "default" => false,
        "meta" => [
            "group" => "Cache &amp; Debug",
            "name" => "Cached Content Disabled",
            "type" => "input-switch",
            "debug" => true
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    "settings_cache_disabled" => [
        "default" => false,
        "meta" => [
            "group" => "Cache &amp; Debug",
            "name" => "Settings Cahce Disabled",
            "type" => "input-switch",
            "debug" => true
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    "enable_benchmark_profiling" => [
        "default" => true
    ],
    /* Enabled the core-content/ route */
    "enable_core_content" => [
        "default" => true,
        "meta" => [
            "group" => "Cache &amp; Debug",
            "name" => "Core Content Disabled",
            "type" => "input-switch",
            "debug" => true
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],

    /* Fonts to be used. This can be referenced by the rendering engine for 
    email templates and more. */
    "fonts" => [
        "default" => [],
        // "definititon" => "Fonts",
        "directives" => [
            "merge" =>  [
                "head" => [
                    "family" => "'Assistant', sans-serif",
                    "import" => "Assistant:500,800"
                ],
                "body" => [
                    "family" => "'Open Sans', sans-serif",
                    "import" => "Open+Sans:400,400i,800,800i"
                ]
            ],
            "style" => true
        ]
        // "meta" => [
        //     "group" => "Look &amp; Feel",
        //     "name" => "Default Fonts",
        //     "view" => "/admin/settings/inputs/fonts.html"
        // ]
    ],
    "css-vars" => [
        "default" => [],
        "directives" => [
            "merge" => [
                
            ],
            "style" => true
        ]
    ],

    /* The name of the designer as well as their website and title text */
    "designer" => [
        "default" => [
            "prefix" => "Designed by",
            "name" =>   "Heavy Element, Inc.",
            "href" =>   "https://heavyelement.io/",
            "title" =>  "Maine's Premier New Media Production Studio"
        ],
        "meta" => [
            "group" => "Basic",
            "subgroup" => "Details",
            "name" => "Designer Credit",
            "view" => "/admin/settings/inputs/designer.html"
        ]
    ],
    
    /* The image displayed when loading a page. */
    "login-hero-sidebar" => [
        "meta" => [
            "group" => "Look &amp; Feel",
            "name" => "Sidebar Image",
            "type" => "input"
        ],
        "directives" => [
            "style" => true,
            "alias" => "logo.media.filename"
        ],
        "default" => ""
    ],

    "API_CORS_allowed_origins" => [
        "default" => [],
        // "meta" => [
        //     "group" => "API",
        //     "name" => "Allowed Origins",
        //     "type" => "input-array"
        // ],
        "directives" => [
            "push" => [
                "domain_name"
            ]
        ]
    ],
    "require_https_login_and_cookie" => [
        "default" => false
    ],
    // This was once allowed to be modified by the user in the Settings panel
    // but people could toggle it and then not be able to change it.
    "API_CORS_enable_other_origins" => [
        "default" => true
    ],
    "Validation_exclude_unregistered_keys_by_default" => [
        "default" => true
    ],
    "Validation_strict_data_submission_policy_by_default" => [
        "default" => false
    ],
    "UploadResult_default_thumbnail" => [
        "default" => [450, null]
    ],
    "Mailchimp_default_list_id" => [
        "default" => ""
    ],
    "Customizations_enabled" => [
        "default" => true,
        "directives" => [],
        "meta" => [
            "group" => "Look &amp; Feel",
            "name" => "Enable Customization Framework <help-span value='Enables customization'></help-span>",
            "subgroup" => "Customization"
        ],
        "validate" => [
            "type" => "bool"
        ]
    ],
    "error_on_missing_customization" => [
        "default" => true,
        "directives" => [],
        "meta" => [
            "group" => "Look &amp; Feel",
            "name" => "Error on missing Customzations <help-span value='When enabled, the CustomizationManager will throw an Exception if a value is missing.'></help-span>",
            "subgroup" => "Customization"
        ],
        "validate" => [
            "type" => "bool"
        ]
    ],
    "Enable_database_import_export" => [
        "default" => true
    ],
    "CobaltEvents_enabled" => [
        "default" => true,
        "directives" => [
            "public" => true
        ],
        "meta" => [
            "group" => "Look &amp; Feel",
            "subgroup" =>"Events",
            "name" => "Enable Event Banners <help-span value='Enables the Event Manager and allows you to schedule private & public pop-ups and banners.'></help-span>",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    "CobaltEvents_database_collection" => [
        // "meta" => [
        //     "group" => "Look &amp; Feel",
        //     "name" => "Database Collection",
        //     "type" => "input"
        // ],
        "default" => "CobaltEvents"
    ],

    "CobaltEvents_enable_public_index" => [
        "default" => false,
        "directives" => [
            "public" => true
        ],
        "meta" => [
            "group" => "Look &amp; Feel",
            "subgroup" =>"Events",
            "name" => "Enable web-side index of specially-marked events <help-span value='To be elligible for display on the Events page, an event must have its `Display on web-side index` flag set to true.'></help-span>",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],

    "CobaltEvents_default_h1_alignment" => [
        "default" => "space-between",
        "directives" => [
            "public" => true
        ],
        "meta" => [
            "group" => "Look &amp; Feel",
            "subgroup" =>"Events",
            "name" => "Select default text alignment",
            "type" => "radio-group"
            // "view" => "/admin/settings/inputs/default-h1-alignment.html"
        ],
        "validate" => [
            "type" => "string",
            "options" => [
                "space-between" => "<i name='format-align-left'></i>",
                "center" => "<i name='format-align-center'></i>",
                "flex-end" => "<i name='format-align-right'></i>"
            ]
        ]
    ],

    "API_contact_form_enabled" => [
        "default" => false,
        "meta" => [
            "group" => "Contact Form",
            "subgroup" =>"General",
            "name" => "Enable Contact Form",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    
    "Contact_form_interface" => [
        "default" => "panel",
        "meta" => [
            "group" => "Contact Form",
            "subgroup" =>"General",
            "name" => "Contact Form Backend",
            "type" => "select"
        ],
        "validate" => [
            "type" => "string",
            "options" => [
                "panel" => "Admin Panel",
                "SMTP" => "Email"
            ]
        ]
    ],
    "Contact_form_validation_classname" => [
        "default" => "\\Contact\\Persistance"
    ],
    "Contact_form_submission_throttle_period" => [
        "default" => "2 minutes",
        "meta" => [
            "group" => "Contact Form",
            "subgroup" =>"General",
            "name" => "Contact form submission grace period <help-span value=\"How long should the grace period last. Will be converted to a negative number and subtracted from the current time of a given submission.\"></help-span>",
            "type" => "input"
        ],
        "validate" => [
            "type" => "string"
        ]
    ],
    "Contact_form_submission_throttle_after_max_submissions" => [
        "default" => 2,
        "meta" => [
            "group" => "Contact Form",
            "subgroup" =>"General",
            "name" => "Maximum number of submissions during the grace permission",
            "type" => "input"
        ],
        "validate" => [
            "type" => "string"
        ]
    ],
    "Contact_form_success_message" => [
        "default" => "Confirmed! Your info has been saved and someone should be reaching out to you soon!",
        "meta" => [
            "group" => "Contact Form",
            "subgroup" =>"General",
            "name" => "Contact Form Success Message",
            "type" => "input"
        ],
        "validate" => [
            "type" => "string"
        ]
    ],
    "Contact_form_fail_message" => [
        "default" => "It looks like you'll need to try again later.",
        "meta" => [
            "group" => "Contact Form",
            "subgroup" =>"General",
            "name" => "Contact Form Failure Message",
            "type" => "input"
        ],
        "validate" => [
            "type" => "string"
        ]
    ],
    
    "Contact_form_notify_on_new_submission" => [
        "default" => false,
        "meta" => [
            "group" => "Contact Form",
            "subgroup" =>"General",
            "name" => "Send admins a notification when new notifications are received",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],

    "PublicContact_name" => [
        "default" => "",
        "meta" => [
            "group" => "Contact Form",
            "subgroup" => "Public Contact Info",
            "name" => "Publicly displayed contact <b>Name</b>",
            "type" => "input"
        ],
        "validate" => [
            
        ]
    ],
    "PublicContact_phone" => [
        "default" => "",
        "meta" => [
            "group" => "Contact Form",
            "subgroup" => "Public Contact Info",
            "name" => "Publicly displayed contact <b>Phone Number</b>",
            "type" => "input"
        ],
        "validate" => [
            
        ]
    ],
    "PublicContact_fax" => [
        "default" => "",
        "meta" => [
            "group" => "Contact Form",
            "subgroup" => "Public Contact Info",
            "name" => "Publicly displayed contact <b>Fax Number</b>",
            "type" => "input"
        ],
        "validate" => [
            
        ]
    ],
    "PublicContact_email" => [
        "default" => "",
        "meta" => [
            "group" => "Contact Form",
            "subgroup" => "Public Contact Info",
            "name" => "Publicly displayed contact <b>Email Address</b>",
            "type" => "input"
        ],
        "validate" => [
            
        ]
    ],
    "PublicContact_street_address1" => [
        "default" => "",
        "meta" => [
            "group" => "Contact Form",
            "subgroup" => "Public Contact Info",
            "name" => "Publicly displayed contact <b>Street Address 1</b>",
            "type" => "input"
        ],
        "validate" => [
            
        ]
    ],
    "PublicContact_street_address2" => [
        "default" => "",
        "meta" => [
            "group" => "Contact Form",
            "subgroup" => "Public Contact Info",
            "name" => "Publicly displayed contact <b>Street Address 2</b>",
            "type" => "input"
        ],
        "validate" => [
            
        ]
    ],
    "PublicContact_state" => [
        "default" => "",
        "meta" => [
            "group" => "Contact Form",
            "subgroup" => "Public Contact Info",
            "name" => "Publicly displayed contact <b>State</b>",
            "type" => "input"
        ],
        "validate" => [
            
        ]
    ],
    "PublicContact_zip" => [
        "default" => "",
        "meta" => [
            "group" => "Contact Form",
            "subgroup" => "Public Contact Info",
            "name" => "Publicly displayed contact <b>Zip Code</b>",
            "type" => "input"
        ],
        "validate" => [
            
        ]
    ],
    "PublicContact_country" => [
        "default" => "",
        "meta" => [
            "group" => "Contact Form",
            "subgroup" => "Public Contact Info",
            "name" => "Publicly displayed contact <b>Country</b>",
            "type" => "input"
        ],
        "validate" => [
            
        ]
    ],

    /* The id attribute of the body tag when errors happen in a web context. */
    "HTTP_error_body_id" => [
        "default" => "cobalt_http_error"
    ],
    "opengraph" => [
        "directives" => [
            "merge" => [
                "type" => "website",
                "image" => "/core-content/img/branding/cobalt-logo.svg",
                "image_X" => 500,
                "image_Y" => 500,
                "description" => "Cobalt engine is a fast, lightweight, and simple MVC-based framework written in PHP. Find out more at heavyelement.io"
            ]
        ]
    ],
    "keywords" => [
        "default" => "",
        "meta" => [
            "group" => "Configuration",
            "subgroup" => "SEO",
            "name" => "Keywords <help-span value=\"A comma-delimited list of keywords included in the head of your document. NOTE: This has little-to-no real-world SEO value.\"></help-span>",
            "type" => "textarea"
        ],
        "validate" => [
            "type" => "string"
        ]
    ],

    /** Mail **/

    "Notifications_system_enabled" => [
        "default" => true,
        "meta" => [
            "group" => "Features",
            "subgroup" => "Notifications",
            "name" => "Enable the notification system",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    "Notifications_enable_push_notifications" => [
        "default" => true,
        "meta" => [
            "group" => "Features",
            "subgroup" => "Notifications",
            "name" => "Enable push notifications to be dispatched",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    "Notifications_in_session_panel" => [
        "default" => true,
        "meta" => [
            "group" => "Features",
            "subgroup" => "Notifications",
            "name" => "Show notifications in the user's session panel",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    "Notifications_collection" => [
        "default" => "CobaltNotifications"
    ],
    

    "API_contact_form_recipient" => [
        "default" => "",
        "meta" => [
            "group" => "Configuration",
            "subgroup" => "Contact Form",
            "name" => "Contact Form Recipient",
            "type" => "input"
        ]
    ],

    /*******************************************/
    /* =========== EMAIL SETTINGS ============ */
    /*******************************************/
    "Mail_username" => [
        "default" => "",
        "directives" => [
            "config" => "smtp_username",
            "env" => "MAIL_USERNAME"
        ],
        "meta" => [
            "group" => "Configuration",
            "subgroup" => "Mail",
            "name" => "SMTP Username",
            "type" => "input"
        ]
    ],

    "Mail_password" => [
        "default" => "",
        "directives" => [
            "config" => "smtp_password",
            "env" => "MAIL_PASSWORD"
        ],
        "meta" => [
            "group" => "Configuration",
            "subgroup" => "Mail",
            "name" => "SMTP Password",
            "type" => "password"
        ],
        "validate" => [
            "confirm" => "Are you sure you want to update this password? Doing so will overwrite your current password!"
        ]
    ],

    "Mail_smtp_host" => [
        "default" => "",
        "directives" =>[
            "config" => "smtp_host",
            "env" => "MAIL_SMTP_HOST"
        ],
        "meta" => [
            "group" => "Configuration",
            "subgroup" =>"Mail",
            "name" => "SMTP Host",
            "type" => "input"
        ]
    ],

    "Mail_port" => [
        "default" => 587,
        "directives" =>[
            "config" => "smtp_port",
            "env" => "MAIL_PORT"
        ],
        "meta" => [
            "group" => "Configuration",
            "subgroup" =>"Mail",
            "name" => "SMTP Port",
            "type" => "number"
        ],
        "validate" => [
            "type" => "int"
        ]
    ],
    
    "Mail_smtp_auth" => [
        "default" => true,
        "directives" => [
            "config" => "smtp_auth",
            "env" => "MAIL_AUTH"
        ],
        "meta" => [
            "group" => "Configuration",
            "subgroup" =>"Mail",
            "name" => "SMTP Auth Enabled",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],

    "Mail_reply_to_address" => [
        "default" => "",
        "directives" =>[
            "alias" => "Mail_from_address"
        ],
        "meta" => [
            "group" => "Configuration",
            "subgroup" =>"Mail",
            "name" => "Reply To",
            "type" => "input"
        ],
        "validate" => [
            "filter" => [
                "FILTER_VALIDATE_EMAIL" => []
            ]
        ]
    ],
    "Mail_reply_to_name" => [
        "default" => "",
        "directives" =>[
            "alias" => "app_short_name"
        ],
        "meta" => [
            "group" => "Configuration",
            "subgroup" =>"Mail",
            "name" => "Reply To Name",
            "type" => "input"
        ]
    ],
    
    "Mail_SMTP_options" => [
        "default" => []
    ],
    
    "Mail_from_address" => [
        "default" => "",
        "directives" =>[
            "config" => "smtp_from_address",
            "alias" => "Mail_username"
        ],
        "meta" => [
            "group" => "Configuration",
            "subgroup" =>"Mail",
            "name" => "From Address",
            "type" => "input"
        ]
    ],
    "Mail_from_name" => [
        "default" => "",
        "directives" =>[
            "config" => "smtp_from_name",
            "alias" => "app_short_name"
        ],
        "meta" => [
            "group" => "Configuration",
            "subgroup" =>"Mail",
            "name" => "From Name",
            "type" => "input"
        ]
    ],


    "Cookie_consent_prompt" => [
        "default" => false,
        "meta" => [
            "group" => "Look &amp; Feel",
            "name" => "Cookie Consent Prompt",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],


    /*******************************************/
    /* ============ POST SETTINGS ============ */
    /*******************************************/

    "Posts_enable_parallax" => [
        "default" => false,
        "directives" =>[
            "public" => true
        ],
        "meta" => [
            "group" => "Features",
            "subgroup" => "Blog Posts",
            "name" => "Enable Parallax for Blog Post Headline Images",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    "Posts_date_format" => [
        "default" => "l, F jS Y"
    ],
    "Posts_date_time" => [
        "default" => "g:i a"
    ],
    "enable_default_parallax" => [
        "default" => true,
        "directives" =>[
            "public" => true
        ],
        "meta" => [
            "group" => "Look &amp; Feel",
            "name" => "Enable Parallax <help-span value='Allows you to specify [parallax-mode=\"\"] attributes on elements in your pages.'></help-span>",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],

    "PostPages_default_aside_visibility" => [
        "default" => false,
        "meta" => [
            "group" => "Features",
            "subgroup" => "Blog Posts",
            "name" => "Include a sidebar (with a table of contents) for posts by default",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    "PostPages_default_aside_flags" => [
        //PageMap::ASIDE_STICKY + PageMap::ASIDE_INCLUDE_TOC_INDEX + PageMap::ASIDE_INDEX_BEFORE_CONTENT + INCLUDE_SOCIAL_SHARE,
        "default" => 0b0001000 + 0b0010000 + 0b0100000 + 0b1000000,
    ],
    "PostMap_predefined_tags" => [
        "default" => [],
    ],
    "PageMap_predefined_tags" => [
        "default" => [],
    ],



    "Parallax_enable_debug" => [
        "default" => false,
        "directives" =>[
            "public" => true
        ],
        "meta" => [
            "group" => "Look &amp; Feel",
            "name" => "Enable Parallax Debug <help-span value='Allows the scroll manager to display debug output to help troubleshoot parallax issues.'></help-span>",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    /* If true, the settings will be cached after being processed and the cache 
    will only be updated if any of the settings files are modified. */
    "cache_settings" => [
        "default" => true
    ],
    /* The version number of our application. Used most frequently as a 
    cache break */
    "version" => [
        "default" => "0.0"
    ],
    "Schema_hydration_on_unserialize" => [
        "default" => true
    ],
    /* API Routes consist of prefixes for URI path names. These prefixes are 
    used to load the appropriate routing table and tell the engine which 
    processor to use to handle the request. */
    "context_prefixes" => [
        "default" => [],
        "directives" =>[
            "prepend" => [
                "admin" => [
                    "processor" => "Handlers\\AdminHandler",
                    "prefix" => "/admin/",
                    "exception_mode" => "web",
                    "no_session_exception" => "\\Exceptions\\HTTP\\Unauthorized",
                    "mode" => "text/html",
                    "permission" => "Admin_panel_access",
                    "session_refresh" => true,
                    "api_access" => true,
                    "router_boundry" => true,
                    "vars" => [
                        "html_class" => "admin-panel"
                    ]
                ],
                "debug" => [
                    "processor" => "Handlers\\WebHandler",
                    "prefix" => "/debug/",
                    "exception_mode" => "web",
                    "mode" => "text/html",
                    // "permission" => "Debug_access",
                    "session_refresh" => true,
                    "api_access" => true,
                    "router_boundry" => true,
                    "vars" => [
                        "html_class" => "debug-panel"
                    ]
                ],
                "init" => [
                    "processor" => "Handlers\\WebHandler",
                    "mode" => "application/json",
                    "session_refresh" => false,
                    "api_access" => false,
                    "prefix" => null
                ],
                "shared" => [
                    "processor" => "Handlers\\SharedHandler",
                    "mode" => "application/json",
                    "session_refresh" => false,
                    "api_access" => false,
                    "prefix" => "/core-content/"
                ],
                "apinotifications" => [
                    "processor" => "Handlers\\ApiHandler",
                    "mode" => "application/json",
                    "session_refresh" => false,
                    "api_access" => false,
                    "prefix" => "/api/notifications/"
                ],
                "apiv1" => [
                    "processor" => "Handlers\\ApiHandler",
                    "mode" => "application/json",
                    "session_refresh" => false,
                    "api_access" => false,
                    "prefix" => "/api/v1/"
                ],
                "webhooks" => [
                    "processor" => "Handlers\\ApiHandler",
                    "mode" => "application/json",
                    "session_refresh" => false,
                    "api_access" => false,
                    "prefix" => "/webhooks/"
                ],
                "streams" => [
                    "processor" => "Handlers\\ApiHandler",
                    "mode" => "application/json",
                    "session_refresh" => false,
                    "api_access" => false,
                    "prefix" => "/streams/"
                ],
                "websocket" => [
                    "processor" => "Handlers\\WebsSocketHandler",
                    "mode" => "application/json",
                    "session_refresh" => false,
                    "api_access" => false,
                    "prefix" => "/websocket"
                ],
                "web" => [
                    "processor" => "Handlers\\WebHandler",
                    "exception_mode" => "web",
                    "mode" => "text/html",
                    "session_refresh" => true,
                    "api_access" => true,
                    "router_boundry" => true,
                    "prefix" => "/",
                    "vars" => [
                        "html_class" => "cobalt-app"
                    ]
                ]
            ]
        ]
    ],


    /*******************************************/
    /* ============= LOOK & FEEL ============= */
    /*******************************************/
    "default_color_scheme" => [
        "default" => true
    ],
    "color_primary" => [
        "default" => "#004BA8"
    ],
    "color_background" => [
        "default" => "#efefef"
    ],
    "color_mixed_percentage" => [
        "default" => 75
    ],

    "loading_spinner" => [
        "default" => "dashes",
        "directives" => [
            "public" => true
        ]
    ],







    "pwa" => [
        "default" => [
            "display" => "standalone",
            "background_color" => "#000"
        ]
    ],




    "session_cookie_name" => [
        "default" => "token_session" // Changing this in production will log everyone out.
    ],
    "session_secure_status" => [
        "default" => true,
        "directives" => [
            "env" => "SESSION_SECURE"
        ]
    ],

    /* The CSRF seed is a secret string that is prepended to the client's 
    session cookie to form a unique "password". This password is then encrypted
    and sent to the client as the CSRF Token. */
    "csrf_seed" => [
        "default" => ""
    ],
    "Plugin_enable_plugin_support" => [
        "default" => true
    ],
    "Plugin_enabled_plugins" => [
        "default" => []
    ],
    "Plugin_blacklisted_plugins" => [
        "default" => []
    ],
    /* If a route has not specified if it needs a CSRF token, this will be the
    default value supplied for its router table entry */
    "Router_csrf_required_default" => [
        "default" => true
    ],
    "SPA" => [
        "default" => true,
        "directives" => [
            "public" => true
        ]
    ],
    "SPA_smooth_scroll_on_nav" => [
        "default" => false,
        "directives" => [
            "public" => true
        ]
    ],
    "Web_include_app_branding" => [
        "default" => true,
        "meta" => [
            "group" => "Look &amp; Feel",
            "subgroup" => "General",
            "name" => "Include logo in Web masthead?",
            "type" => "input-switch"
        ],
        "validation" => [
            "type" => "bool"
        ]
    ],
    "Web_privacy_policy" => [
        "default" => "",
        "meta" => [
            "group" => "Look &amp; Feel",
            "name" => "Path to Privacy Policy",
            "type" => "input"
        ]
    ],
    "Web_terms_of_service" => [
        "default" => "",
        "meta" => [
            "group" => "Look &amp; Feel",
            "name" => "Path to Terms of Service",
            "type" => "input"
        ]
    ],
    "Web_normally_open_pages" => [
        "default" => true
    ],
    "Web_main_content_via_api" => [
        "default" => true
    ],
    "Web_display_designer_credit" => [
        "default" => true
    ],
    "Render_strict_variable_parsing" => [
        "default" => false
    ],
    "Render_use_v2_engine" => [
        "default" => false
    ],
    "RenderV2_throw_template_exception_on_no_value" => [
        "default" => true
    ],
    /* A meta setting which will disable ALL user account settings. Anything
    that requires privileges, has to do with user accounts, or sessions should
    "$required" => ["Auth_user_accounts_enabled" => true] */
    "Auth_user_accounts_enabled" => [
        "default" => true
    ],
    "Auth_require_verified_status" => [
        "default" => true
    ],
    "Auth_allow_password_reset" => [
        "default" => true
        // Allows public password resets
    ],
    "Auth_login_via_email_token" => [
        "default" => true
    ],
    "Admin_panel_prefix" => [
        "default" => "/admin"
    ],
    "Admin_panel_access" => [
        "default" => true,
        "directives" => [   
            "required" => [
                "Auth_user_accounts_enabled" => ["is" => true]
            ]
        ]
    ],
    "Auth_min_password_length" => [
        "default" => 6
    ],
    "Auth_logins_enabled" => [
        "default" => true,
        "directives" => [   
            "required" => [
                "Auth_user_accounts_enabled" => ["is" => true]
            ]
        ]
    ],
    "Auth_enable_insecure_logins" => [
        "default" => true
    ],
    "Auth_session_panel_enabled" => [
        "default" => false,
        "directives" => [
            "required" => [
                "Auth_user_accounts_enabled" => ["is" => true]
            ]
        ]
    ],
    "Auth_user_menu_enabled" => [
        "default" => false,
        "directives" => [   
            "required" => [
                "Auth_user_accounts_enabled" => ["is" => true]
            ]
        ]
    ],
    "Auth_account_creation_enabled" => [
        "default" => false,
        "directives" => [   
            "required" => [
                "Auth_user_accounts_enabled" => ["is" => true]
            ]
        ]
    ],
    "Auth_login_page" => [
        "default" => "/login",
        "directives" => [   
            "required" => [
                "Auth_user_accounts_enabled" => ["is" => true],
                "on_fail_value" => ""
            ],
            "public" => true
        ]
    ],
    "Auth_onboading_url" => [
        "default" => "/onboarding"
    ],
    "Auth_enable_root_group" => [
        /* THIS IS DANGEROUS. ENABLING MEMBERSHIP IN THE ROOT GROUP WILL BYPASS 
        *ALL* PERMISSIONS CHECKS FOR ROOT MEMBERS!!! */
        "default" => true
    ],
    "Auth_session_days_until_expiration" => [
        "default" => 90
    ],
    "Auth_reauth_timeout" => [
        "default" => 600 // 10 minutes in seconds
    ],
    "TwoFactorAuthentication_enabled" => [
        "default" => true
    ],
    "Renderer_parse_for_multiline_functions" => [
        // When true, the trailing semicolon is REQUIRED.
        "default" => false
    ],
    "Mobile_nav_menu_closes_on_anchor_link_click" => [
        "default" => true,
        "directives" => [   
            "public" => true
        ]
    ],
    "UGC_enable_user_generated_content" => [
        "default" => false
    ],
    "UGC_retrieval_endpoint" => [
        "default" => "/ugc",
        "directives" => [   
            "public" => true
        ]
    ],
    "UCG_database_collection" => [
        "default" => "ugc"
    ],
    "UGC_directory" => [
        "default" => "/ugc"
    ],
    "Posts" => [
        "directives" => [   
            "merge" => [
                "default_enabled" => false,
                "collection_name" => "CobaltPosts",
                "default_name" => "Posts",
                "public_index" => "/posts",
                "public_index_options" => [
                    "anchor" => ["name" => "Posts"],
                    "navigation" => ["main_navigation"]
                ],
                "public_post" => "/posts/",
                "public_post_options" => []
            ]
        ],
        "meta" => [
            "group" => "Features",
            "subgroup" => "Blog Posts",
            "view" => "/admin/settings/inputs/posts.html"
        ]
    ],
    "Posts_enable_rss_feed" => [
        "default" => true,
        "meta" => [
            "group" => "Features",
            "subgroup" => "Blog Posts",
            "name" => "Enable Post RSS Feed",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    "Posts_rss_feed_name" => [
        "default" => "RSS Feed",
        "directives" => [
            "alias" => "app_name"
        ],
        "meta" => [
            "group" => "Features",
            "subgroup" => "Blog Posts",
            "name" => "Post RSS Name",
            "type" => "input"
        ],
        "validate" => [
            "type" => "string"
        ]
    ],
    "Posts_rss_feed_description" => [
        "default" => "A Cobalt Engine RSS Feed",
        "directives" => [
            "alias" => "app_name"
        ],
        "meta" => [
            "group" => "Features",
            "subgroup" => "Blog Posts",
            "name" => "Post RSS Feed Description",
            "type" => "textarea"
        ],
        "validate" => [
            "type" => "string"
        ]
    ],
    "Posts_rss_feed_path" => [
        "default" => "/posts/feed/",
        "meta" => [
            "group" => "Features",
            "subgroup" => "Blog Posts",
            "name" => "Post RSS Feed Path",
            "type" => "input"
        ],
        "validate" => [
            "type" => "string"
        ]
    ],
    "Posts_default_index_display" => [
        "default" => "default"
    ],
    "Posts_rss_feed_include_unlisted" => [
        'default' => false,
        "meta" => [
            "group" => "Features",
            "subgroup" => "Blog Posts",
            "name" => "Include \"Unlisted\" Posts in RSS Feed",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    /* 
    If debugging is FALSE, then =>
        - The WebHandler will cache a concat of all JS files in `packages`
        - The WebHandler will cache a concat of all CSS files in `css-packages`
    */
    "debug" => [
        "default" => true,
        "definition" => "Debug",
        "meta" => [
            "group" => "Developer",
            "subgroup" => "Debug",
            "name" => "Debug status",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    "Package_JS_script_content" => [
        "default" => false,
        "definition" => "Debug",
        "meta" => [
            "group" => "Developer",
            "subgroup" => "Packaging",
            "name" => "Bundle JavaScript Content <help-span value='Compiles all client-side JavaScript into one file. May moderately decrease load times.'></help-span>",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    "Package_style_content" => [
        "default" => false,
        "meta" => [
            "group" => "Developer",
            "subgroup" => "Packaging",
            "name" => "Bundle CSS Content <help-span value='Compiles all CSS files into one. May significantly decrease load times.'></help-span>",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    "Package_style_minify" => [
        "default" => true,
        "meta" => [
            "group" => "Developer",
            "subgroup" => "Packaging",
            "name" => "Minify Bundled CSS Content <help-span value='When `Package_style_content` is enabled, package.css is minified. May significantly decrease load times.'></help-span>",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    "debug_exceptions_publicly" => [
        "default" => false,
        "directives" => [
            "config" => "debug_exceptions_publicly"
        ],
        "meta" => [
            "group" => "Developer",
            "subgroup" => "Debug",
            "name" => "Output detailed exception data publicly via route context handlers. DANGEROUS!",
            "dangerous" => true,
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    "Database_fs_enabled" => [
        "default" => true
    ],
    "Database_fs_public_endpoint" => [
        "default" => "/dbfs/"
    ],
    "PaymentGateways_enabled" => [
        "default" => false
    ],
    "API_authentication_mode" => [
        "default" => "POST" // Set to "header" for legacy mode
    ],
    "API_remote_gateways_enabled" => [
        "default" => ["GoogleOAuth","Patreon","Mailchimp"],
        "meta" => [
            "group" => "Configuration",
            "subgroup" =>"Advanced",
            "name" => "Enabled APIs",
            "type" => "input-array"
            // "view" => "/admin/settings/inputs/default-h1-alignment.html"
        ],
        "validate" => [
            "type" => "array",
            "options" => [
                "AmazonPA" => "Amazon Affiliate",
                "GoogleOAuth" => "Google OAuth",
                "Mailchimp" => "Mailchimp",
                "Patreon" => "Patreon",
                "Shopify" => "Shopify",
                "Stripe" => "Stripe",
                "Twitter" => "Twitter",
                "YouTube" => "YouTube"
            ]
        ]
    ],
    "Robots_txt_config" => [
        "default" => "User-agent: *\nAllow: /\nDisallow: /admin",
        "meta" => [
            "group" => "Configuration",
            "subgroup" => "SEO",
            "name" => "Robots.txt file <help-span value=\"Each User-agent rule must be followed by distinct 'Allow: /' or 'Disallow: /' rules. One Allow or Disallow rule per route.\"></help-span>",
            "type" => "textarea"
        ],
        "validate" => [
            "type" => "string"
        ]
    ],
    "Robots_txt_block_known_ai_crawlers" => [
        "default" => false,
        "meta" => [
            "group" => "Configuration",
            "subgroup" => "SEO",
            "name" => "Request AI Web Crawlers Ignore Site <small>This will set up your robots.txt file to deny access to AI web crawlers. Note that this <strong>does not block facebookexternalhit</strong> since that would also break link previews.</small>",
            "type" => "input-switch"
        ]
    ],
    "Forbid_AI_webcrawler_access" => [
        "default" => false,
        "meta" => [
            "group" => "Configuration",
            "subgroup" => "SEO",
            "name" => "Forbid Access for AI Web Crawlers <small>This will throw a 403 Forbidden when AI bots crawl your application.<help-span value=\"This is heavy-handed and may break things.\"></help-span></small>",
            "type" => "input-switch"
        ]
    ],
    "Block_Editor_endpoints" => [
        "default" => true
    ],
    "LandingPages_enabled" => [
        "default" => true
    ],
    "LandingPage_route_prefix" => [
        "default" => "/",
        "definititon" => "LandingPage_route_prefix",
        "meta" => [
            "group" => "Landing Pages",
            "subgroup" => "General",
            "name" => "Route Prefix <help-span value=\"Your pages will live at this location. It MUST start with a slash and may contain more.&#10;&#10;Changing this setting WILL break existing links to pages.\"></help-span>",
            "type" => "input"
        ],
        "validate" => [
            "type" => "string"
        ]
    ],
    "LandingPage_table_of_contents_label" => [
        "default" => "Contents",
        "meta" => [
            "group" => "Landing Pages",
            "subgroup" => "Presentation",
            "name" => "Contents Label Headline <help-span value=\"The headline displayed over the Landing Page's Table of Contents\"></help-span>",
            "type" => "input"
        ],
        "validate" => [
            "type" => "string"
        ]
    ],
    "LandingPage_table_of_contents_by_default" => [
        "default" => true,
        "meta" => [
            "group" => "Landing Pages",
            "subgroup" => "Presentation",
            "name" => "Generate a Table Of Contents by default",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    "LandingPage_bio_by_default" => [
        "default" => true,
        "meta" => [
            "group" => "Landing Pages",
            "subgroup" => "Biography",
            "name" => "Show a biography of the author by default",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    "LandingPages_include_footer_by_default" => [
        "default" => false,
    ],
    "LandingPage_bio_default_headline" => [
        "default" => "About the Author",
        "meta" => [
            "group" => "Landing Pages",
            "subgroup" => "Biography",
            "name" => "Default headline for author biography",
            "type" => "input"
        ],
        "validate" => [
            "type" => "string"
        ]
    ],
    "LandingPage_allow_custom_css_injection" => [
        "default" => true,
        "meta" => [
            "group" => "Landing Pages",
            "subgroup" => "Presentation",
            "name" => "Allow Custom CSS Injection",
            "type" => "input-switch"
        ],
        "validate" => [
            "type" => "boolean"
        ]
    ],
    "LandingPage_related_content_title" => [
        "default" => "Related Pages",
        "meta" => [
            "group" => "Landing Pages",
            "subgroup" => "Related Content",
            "name" => "Related Content Default Headline <help-span value=\"The default headline for the 'Other Content' section.\"></help-span>",
            "type" => "input"
        ],
        "validate" => [
            "type" => "string",
            "confirm" => "Changing this value will break existing links and search engines will need to crawl your site in order to fix them. Are you sure you want to change this setting?"
        ]
    ],
    "SocialMedia_email" => [
        'default' => '',
        'meta' => [
            'group' => 'Basic',
            'subgroup' => 'Social',
            'name' => "<i name=\"email\"></i> Email Newsletter",
            "type" => "input"
        ],
        'validate' => [
            'type' => 'string'
        ]
    ],
    "SocialMedia_fediverse" => [
        'default' => '',
        'meta' => [
            'group' => 'Basic',
            'subgroup' => 'Social',
            'name' => "<i name=\"fediverse\"></i> Fediverse",
            "type" => "url"
        ],
        'validate' => [
            'type' => 'string'
        ]
    ],
    "SocialMedia_facebook" => [
        'default' => '',
        'meta' => [
            'group' => 'Basic',
            'subgroup' => 'Social',
            'name' => "<i name=\"facebook\"></i> Facebook",
            "type" => "url"
        ],
        'validate' => [
            'type' => 'string'
        ]
    ],
    'SocialMedia_instagram' => [
        'default' => '',
        'meta' => [
            'group' => 'Basic',
            'subgroup' => 'Social',
            'name' => "<i name=\"instagram\"></i> Instagram",
            "type" => "url"
        ],
        'validate' => [
            'type' => 'string'
        ]
    ],
    'SocialMedia_twitter' => [
        'default' => '',
        'meta' => [
            'group' => 'Basic',
            'subgroup' => 'Social',
            'name' => "<i name=\"twitter\"></i> Twitter",
            "type" => "url"
        ],
        'validate' => [
            'type' => 'string'
        ]
    ],
    'SocialMedia_mastodon' => [
        'default' => '',
        'meta' => [
            'group' => 'Basic',
            'subgroup' => 'Social',
            'name' => "<i name=\"mastodon\"></i> Mastodon",
            "type" => "url"
        ],
        'validate' => [
            'type' => 'string'
        ]
    ],
    // 'SocialMedia_' => [
    //     'default' => '',
    //     'meta' => [
    //         'group' => 'Basic',
    //         'subgroup' => 'Social',
    //         'name' => "<i name=\"mastodon\"></i> Mastodon",
            // "type" => "url"
    //     ],
    //     'validate' => [
    //         'type' => 'string'
    //     ]
    // ],
    'SocialMedia_shown' => [
        'default' => [],
    ],
    'Webmentions_enable_recieving' => [
        'default' => true,
    ],
    'Webmentions_enable_sending' => [
        'default' => true,
    ]
];