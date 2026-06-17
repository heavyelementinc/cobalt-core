<?php

use Cobalt\Auth\Users\UserCRUD;
use Cobalt\EventListings\Models\Event;
use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\BinaryType;
use Cobalt\Model\Types\BooleanType;
use Cobalt\Model\Types\EmailAddressType;
use Cobalt\Model\Types\EnumType;
use Cobalt\Model\Types\ImageType;
use Cobalt\Model\Types\MarkdownType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\PasswordHashType;
use Cobalt\Model\Types\RadioType;
use Cobalt\Model\Types\StringType;
use Cobalt\Model\Types\TextType;
use Cobalt\Model\Types\URLType;
use PHPMailer\PHPMailer\PHPMailer;

const TEMPLATE_DEBUG_SHOW_TYPES   = 0b0001;
const TEMPLATE_DEBUG_RENDER_TYPES = 0b0010;
const GROUP_BASIC = "<i name='cog'></i> Basic";
const SUBGROUP_BASIC_GENERAL = "General";
const SUBGROUP_BASIC_DETAILS = "Details";
const GROUP_CONFIGURATION = "<i name='wrench'></i> Configuration";
const GROUP_CACHE_DEBUG = "<i name='bug'></i> Cache &amp; Debug";
const GROUP_LOOK_FEEL = "<i name='palette'></i> Look &amp; Feel";
const GROUP_USERBAR = "<i name='button-cursor'></i> Userbar";
const GROUP_CONTACT = "<i name='form-dropdown'></i> Contact Form";
const GROUP_FEATURES = "<i name='star-circle-outline'></i> Features";
const GROUP_SEO = "<i name='search-web'></i> Search &amp; SEO";
const SUBGROUP_SEO_ROBOTS = "Search Engine";
const GROUP_SMTP = "<i name='email'></i> Mail";
const SUBGROUP_SMTP_BASIC = "Basic";
const GROUP_PAGES = "<i name='page-layout-body'></i> Pages";
const SUBGROUP_PAGES_RSS = "RSS Settings";
const GROUP_POSTS = "<i name='post-outline'></i> Posts";
const SUBGROUP_PAGES_POSTS_GENERAL = "General";
const SUBGROUP_PAGES = "Pages";
const GROUP_DEV = "<i name='developer-board'></i> Developer";
const SUBGROUP_DEV_JS_PACKAGE = "JavaScript Packaging";
const SUBGROUP_DEV_CSS_PACKAGE = "CSS Packaging";

const FONT_BACKEND_GOOGLE = 0;
const FONT_BACKEND_FONTSOURCE = 1;

const COBALT_LOGIN_TYPE_LEGACY = 0;
const COBALT_LOGIN_TYPE_STAGES = 1;

const POSTS_INDEX_MODE_GRID = "0";
const POSTS_INDEX_MODE_FEED = "1";
const POSTS_INDEX_MODE_BODY = "2";
const POSTS_INDEX_MODE_LATEST = "3";

const CONTACT_SUCCESS_SYSTEM  = 0b000001;
const CONTACT_SUCCESS_NOTIFY  = 0b000010;
const CONTACT_SUCCESS_PUSH    = 0b000100;
const CONTACT_SUCCESS_EMAIL   = 0b001000;
// const CONTACT_SUCCESS_MESSAGE = 0b010000;

const CONTACT_CLIENT_SUCCESS_REDIRECT = 0b0001;
const CONTACT_CLIENT_SUCCESS_STATUS   = 0b0010;
const CONTACT_CLIENT_SUCCESS_STAGE    = 0b0100;

return [
    /** BASIC */
        /* Provide a doman name that we expect to be listening for. This will later 
        be used to add CORS headers. */
        "domain_name" => [
            "default" => "",
            "meta" => [
                "type" => new StringType(),
                "label" => "Domain Name",
                "description" =>  "The domain name you use to access your Cobalt application.",
                "group" => GROUP_BASIC,
                "subgroup" => SUBGROUP_BASIC_GENERAL,
                "confirm" => "If you change this value, you may lose access to this page and will need to manually change the value to regain access.",
            ]
        ],
        "canonical_name" => [
            "default" => "",
            "meta" => [
                "type" => new StringType(),
                "description" =>  "This is the host name that will be provided by the server_name() function. If it's not set, it will fall back to the `domain_name` value.",
                "label" => "Canonical Name",
                "group" => GROUP_BASIC,
                "subgroup" => SUBGROUP_BASIC_GENERAL,
                "alias" => "domain_name",
            ]
        ],
        "cobalt_base_path" => [
            "default" => "",
            "meta" => [
                "type" => new StringType(),
            ]
        ],
        "app_name" => [
            // The full name of the application.
            "default" => "Cobalt Engine",
            "directives" => [
                "public" => true
            ],
            "meta" => [
                "type" => new StringType(),
                "label" => "Application Name",
                "description" => "The full name of your application. This is used in various places around your app.",
                "group" => GROUP_BASIC,
                "subgroup" => SUBGROUP_BASIC_GENERAL,
            ]
        ],
        /* A shortened name for the application. */
        "app_short_name" => [
            "default" => "",
            "directives" => [
                "alias" => "app_name",
                "subgroup" => SUBGROUP_BASIC_DETAILS,
                "public" => true
            ],
            "meta" => [
                "type" => new StringType(),
                "label" => "Short Name",
                "description" => "This is a short name that is used in various space-limited places. It inherits from your App Name.",
                "group" => GROUP_BASIC,
            ]
        ],
        /* A bespoke name to be listed in the copyright notice */
        "app_copyright_name" => [
            "default" => "",
            // "meta" => [
            //     "group" => GROUP_BASIC,
            //     "subgroup" => SUBGROUP_BASIC_DETAILS,
            //     "label" => "Copyright Name",
            //     "description" => "This is the name of your application",
            //     "type" => new StringType()
            // ],
            "directives" => [
                "alias" => "app_name"
            ],
            "meta" => [
                "type" => new StringType(),
            ]
        ],
        "copyright_notice" => [
            "default" => "All Rights Reserved",
            "meta" => [
                "type" => new MarkdownType(),
                "label" => "Copyright Notice",
                "description" => "The copyright notice to used in the footer of your app.",
                "help" => "The copyright notice is parsed as markdown. Be cautious.",
                "group" => GROUP_BASIC,
                "subgroup" => SUBGROUP_BASIC_DETAILS,
            ],
        ],
        /* The version number of our application. Used most frequently as a 
        cache break */
        "version" => [
            "default" => "0.0",
            "meta" => [
                "type" => new StringType(),
            ]
        ],
        "Timezone" => [
            "default" => "America/New_York",
            "meta" => [
                "type" => new StringType(),
                'valid' => function() {
                    $timezones = DateTimeZone::listIdentifiers();
                    $tz = array_fill_keys($timezones, $timezones);
                    return $tz;
                }
            ]
        ],
        "DB_export_directory" => [
            "default" => "/ignored/db_backups/",
            "meta" => [
                "type" => new StringType(),
            ]
        ],

    /** API ACCESS CONTROL */
        "API_CORS_allowed_origins" => [
            "default" => [],
            // "meta" => [
            //     "group" => "API",
            //     "label" => "Allowed Origins",
            //     "type" => new ArrayType()
            // ],
            "directives" => [
                "push" => [
                    "domain_name"
                ]
            ],
            "meta" => [
                "type" => new ArrayType(),
            ]
        ],
        // When set to `true`, non-web routes will check if the referer is valid. 
        // If not, it will throw a 400 Unauthorized
        "CORS_restrictive_referer_policy" => [
            'default' => true,
            "meta" => [
                "type" => new BooleanType(),
            ]
        ],
        "API_authentication_mode" => [
            "default" => "POST", // Set to "header" for legacy mode,
            "meta" => [
                "type" => new StringType(),
            ]
        ],
        "API_remote_gateways_enabled" => [
            "default" => ["GoogleOAuth","Patreon","Mailchimp"],
            "meta" => [
                "type" => new ArrayType(),
                "label" => "Enabled APIs",
                "description" => "Use the field to control which remote APIs are enabled or disabled.",
                "group" => GROUP_CONFIGURATION,
                "subgroup" =>"Advanced",
                // "view" => "/admin/settings/inputs/default-h1-alignment.html"
                "valid" => [
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
        "API_CORS_enable_other_origins" => [
            "default" => true,
            "meta" => [
                "type" => new BooleanType(),
            ]
        ],
        
        /* The CSRF seed is a secret string that is prepended to the client's 
        session cookie to form a unique "password". This password is then encrypted
        and sent to the client as the CSRF Token. */
        "csrf_seed" => [
            "default" => "",
            "meta" => [
                "type" => new StringType(),
            ]
        ],
        /* If a route has not specified if it needs a CSRF token, this will be the
        default value supplied for its router table entry */
        "Router_csrf_required_default" => [
            "default" => true
        ],
        "require_https_login_and_cookie" => [
            "default" => false
        ],
        

        "Mailchimp_default_list_id" => [
            "default" => ""
        ],
    /** FEATURES */
        "Notifications_system_enabled" => [
            "default" => true,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Enable Cobalt Notifications",
                "description" => "This allows users to send and receive notifications within Cobalt Engine.",
                "group" => GROUP_FEATURES,
                "subgroup" => "Notifications",
            ]
        ],
        "Notifications_in_session_panel" => [
            "default" => true,
            "meta" => [
                "type" => new BooleanType(),
                "group" => GROUP_FEATURES,
                "subgroup" => "Notifications",
                "label" => "Show notifications in the user's session panel",
                "description" => "Display a notification button and the notification panel when a user exists.",
            ]
        ],
        "Notifications_enable_push_notifications" => [
            "default" => true,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Enable push notifications",
                "description" => "This allows registered users to enroll their devices or browsers in push notifications.",
                "group" => GROUP_FEATURES,
                "subgroup" => "Notifications",
            ]
        ],
        "Notifications_collection" => [
            "default" => "CobaltNotifications"
        ],
        "Notifications_process_queue_notes_newer_than" => [
            "default" => "-30 days"
        ],
        "Enable_database_import_export" => [
            "default" => true
        ],
        "CobaltEvents_enabled" => [
            "default" => false,
            "directives" => [
                "public" => true
            ],
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Enable Event Banners",
                "description" => "Enables the Event Manager and allows you to schedule private & public popups.",
                "group" => GROUP_FEATURES,
                "subgroup" =>"Events",
            ]
        ],
        "CobaltEvents_database_collection" => [
            // "meta" => [
            //     "group" => GROUP_LOOK_FEEL,
            //     "label" => "Database Collection",
            //     "type" => new StringType()
            // ],
            "default" => "CobaltEvents"
        ],

        "CobaltEvents_enable_public_index" => [
            "default" => false,
            "directives" => [
                "public" => true
            ],
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Enable Public Event Index",
                "description" => "Enable web-side index of specially-marked events",
                "help" => "To be elligible for display on the Events page, an event must have its `Display on web-side index` flag set to true.",
                "group" => GROUP_FEATURES,
                "subgroup" =>"Events",
            ]
        ],

        "CobaltEvents_default_public_listing_status" => [
            'default' => Event::INDEX_IFPUBLIC,
            "meta" => [
                "type" => new EnumType(),
                "label" => "Default Public Index Behavior",
                "description" => "Decide how new events will display themselves on the public index (if enabled)",
                "group" => GROUP_FEATURES,
                "subgroup" =>"Events",
                "valid" => [
                    // "notification" => "Notification",
                    Event::INDEX_UNLISTED => "Do not display",
                    Event::INDEX_IFPUBLIC => "Display, if Public",
                    Event::INDEX_ALWAYS => "Display, even if not public"
                ]
            ]
        ],

        "CobaltEvents_default_h1_alignment" => [
            "default" => "space-between",
            "directives" => [
                "public" => true
            ],
            "meta" => [
                "type" => new RadioType(),
                "label" => "Default text alignment",
                "description" => "Cobalt Events will default to the selected text alignment.",
                "group" => GROUP_FEATURES,
                "subgroup" =>"Events",
                "valid" => Event::POPUP_TXT_JUSTIFICATION

            ]
        ],

    /** CONTACT */
        "API_contact_form_enabled" => [
            "default" => false,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Enable Contact Form",
                "description" => "Enable the public-facing contact form.",
                "group" => GROUP_CONTACT,
                "subgroup" =>"General",
            ]
        ],
        "Contact_form_on_success_modes" => [
            "default" => CONTACT_SUCCESS_SYSTEM + CONTACT_SUCCESS_NOTIFY + CONTACT_SUCCESS_PUSH,
            "directives" => [
                "public" => true
            ],
            "meta" => [
                "type" => new BinaryType(),
                "label" => "Contact Form Success Behavior",
                "description" => "Cobalt Events will default to the selected text alignment.",
                "group" => GROUP_CONTACT,
                "subgroup" =>"General",
                // "view" => "/admin/settings/inputs/default-h1-alignment.html"
                "valid" => [
                    CONTACT_SUCCESS_SYSTEM => "<i name='book-open-blank-variant'></i> System<br><small>Store the details in the built-in contact system.</small>",
                    CONTACT_SUCCESS_EMAIL  => "<i name='email-fast'></i> Email<br><small>Send the details of the submission to an email address (specified below).</small>",
                    CONTACT_SUCCESS_NOTIFY => "<i name='bell-badge'></i> Cobalt Notification<br><small>Admins get a notification in the Cobalt Notification system.</small>",
                    CONTACT_SUCCESS_PUSH   => "<i name='tablet-cellphone'></i> Device/Browser Notification<br><small>Admins get notified via a push notification.</small>",
                ]
            ]
        ],
        "Contact_form_public_routes_enabled" => [
            "default" => false,
        ],
        "Contact_form_navigation_options" => [
            "default" => [
                'main_navigation' => [
                    "label" => "Contact",
                    'attrs' => ['classes' => 'contact-form-link'],
                    'order' => 999,
                ]
            ]
        ],
        "Contact_form_container_view" => [
            "default" => "Cobalt/ContactForm/templates/web/stage-1--contact-page.php"
        ],
        "API_contact_form_recipients" => [
            "default" => ["Contact_form_submissions_access"],
            "meta" => [
                "type" => new ArrayType(),
                "label" => "Contact Form Recipients",
                "description" => "Specify a permission level that will receive a message when the contact form is submitted.",
                "group" => GROUP_CONTACT,
                "subgroup" => "Confirmation",
                'valid' => function () {
                    return auth()->getPermissionSingleton()->getValidPermissions();
                }
            ]
        ],
        "Contact_form_interface" => [
            "default" => "panel",
            // "default" => "notification",
            "meta" => [
                "type" => new EnumType(),
                "label" => "Contact Form Backend",
                "description" => "Select a backend to handle contact form submissions. <em>Admin Panel</em> will store submissions in your database while <em>Email</em> will send a specified user an email.",
                "group" => GROUP_CONTACT,
                "subgroup" =>"General",
                "valid" => [
                    // "notification" => "Notification",
                    "panel" => "Admin Panel",
                    "SMTP" => "Email"
                ]
            ]
        ],
        "Contact_form_validation_classname" => [
            "default" => "\\Cobalt\\ContactForm\\Model\\FormSubmission"
        ],
        "Contact_form_anti_spam_technique" => [
            "default" => "captcha",
            "meta" => [
                "type" => new RadioType(),
                "label" => "Anti-Spam Technique",
                "description" => "Specify a technique that will be used to mitigate spam submissions.",
                "group" => GROUP_CONTACT,
                "subgroup" =>"Anti-spam",
                "valid" => [
                    "none" => "<i name='border-none-variant'></i> None (not recommended)<br><small>Do not enforce a spam mitigation technique.</small>",
                    "stepped-click" => "<i name='check-outline'></i> Status Message<br><small>A simple check to ensure that the user sees and clicks the extra step required to submit their request.</small>",
                    "captcha" => "<i name='alpha-c-box'></i> Captcha<br><small>A deeper check that requires the user to read and submit a traditional captcha.</small>",
                ]
            ]
        ],
        "Contact_form_submission_throttle_period" => [
            "default" => "2 minutes",
            "meta" => [
                "type" => new StringType(),
                "label" => "Submission Throttling",
                "description" => "Contact form submissions will be rate limited over the specified window.",
                "help" => "How long should the grace period last. Will be converted to a negative number and subtracted from the current time of a given submission.",
                "group" => GROUP_CONTACT,
                "subgroup" =>"Anti-spam",
            ]
        ],
        "Contact_form_submission_throttle_after_max_submissions" => [
            "default" => 900,
            "meta" => [
                "type" => new StringType(),
                "label" => "Max Limit",
                "description" => "Maximum number of submissions during the throttle period.",
                "group" => GROUP_CONTACT,
                "subgroup" =>"Anti-spam",
            ]
        ],

        "Contact_form_client_success" => [
            "default" => CONTACT_CLIENT_SUCCESS_REDIRECT,
            "directives" => [
                "public" => true
            ],
            "meta" => [
                "type" => new RadioType(),
                "label" => "Contact Client Success",
                "description" => "When the client submits a contact form, what should happen?",
                "group" => GROUP_CONTACT,
                "subgroup" =>"Confirmation",
                "valid" => [
                    CONTACT_CLIENT_SUCCESS_REDIRECT => "<i name='arrow-right-bold'></i> Redirect<br><small>The client is navigated to a new page (specified below).</small>",
                    CONTACT_CLIENT_SUCCESS_STATUS   => "<i name='message-alert'></i> Status Message<br><small>The client receives a status message.</small>",
                    CONTACT_CLIENT_SUCCESS_STAGE    => "<i name='page-next-outline'></i> Step<br><small></small>",
                ]
            ],
        ],
        
        "Contact_form_success_message" => [
            "default" => "Confirmed! Your info has been saved and someone should be reaching out to you soon!",
            "meta" => [
                "type" => new StringType(),
                "label" => "Contact Form Success Message",
                "group" => GROUP_CONTACT,
                "subgroup" =>"Confirmation",
            ]
        ],
        "Contact_form_fail_message" => [
            "default" => "It looks like you'll need to try again later.",
            "meta" => [
                "type" => new StringType(),
                "label" => "Contact Form Failure Message",
                "group" => GROUP_CONTACT,
                "subgroup" =>"Confirmation",
            ]
        ],
        "Contact_form_redirect" => [
            "default" => "/contact/success",
            "meta" => [
                "type" => new StringType(),
                "label" => "Contact Form Redirect Location",
                "description" => "When a user submits the public contact form, specify a location they will be redirected to upon success.",
                "group" => GROUP_CONTACT,
                "subgroup" =>"Confirmation",
            ]
        ],
        "Contact_form_user_permissions_to_notify" => [
            'default' => ["Contact_form_submissions_access"],
        ],
        
        "Contact_form_notify_on_new_submission" => [
            "default" => false,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Notifications",
                "description" => "Send admins a notification when new contact submissions are received",
                "group" => GROUP_CONTACT,
                "subgroup" =>"General",
            ]
        ],

        "PublicContact_name" => [
            "default" => "",
            "meta" => [
                "type" => new StringType(),
                "subgroup" => "Public Contact Info",
                "label" => "Publicly displayed contact <b>Name</b>",
                "group" => GROUP_CONTACT,
            ]
        ],
        "PublicContact_phone" => [
            "default" => "",
            "meta" => [
                "type" => new StringType(),
                "label" => "Publicly displayed contact <b>Phone Number</b>",
                "group" => GROUP_CONTACT,
                "subgroup" => "Public Contact Info",
            ]
        ],
        "PublicContact_fax" => [
            "default" => "",
            "meta" => [
                "type" => new StringType(),
                "label" => "Publicly displayed contact <b>Fax Number</b>",
                "group" => GROUP_CONTACT,
                "subgroup" => "Public Contact Info",
            ]
        ],
        "PublicContact_email" => [
            "default" => "",
            "meta" => [
                "type" => new StringType(),
                "label" => "Publicly displayed contact <b>Email Address</b>",
                "group" => GROUP_CONTACT,
                "subgroup" => "Public Contact Info",
            ],
        ],
        "PublicContact_street_address1" => [
            "default" => "",
            "meta" => [
                "type" => new StringType(),
                "label" => "Publicly displayed contact <b>Street Address 1</b>",
                "group" => GROUP_CONTACT,
                "subgroup" => "Public Contact Info",
            ]
        ],
        "PublicContact_street_address2" => [
            "default" => "",
            "meta" => [
                "type" => new StringType(),
                "label" => "Publicly displayed contact <b>Street Address 2</b>",
                "group" => GROUP_CONTACT,
                "subgroup" => "Public Contact Info",
            ]
        ],
        "PublicContact_city" => [
            "default" => "",
            "meta" => [
                "type" => new StringType(),
                "label" => "Publicly displayed contact <b>City</b>",
                "group" => GROUP_CONTACT,
                "subgroup" => "Public Contact Info",
            ]
        ],
        "PublicContact_state" => [
            "default" => "",
            "meta" => [
                "type" => new StringType(),
                "label" => "Publicly displayed contact <b>State</b>",
                "group" => GROUP_CONTACT,
                "subgroup" => "Public Contact Info",
            ]
        ],
        "PublicContact_zip" => [
            "default" => "",
            "meta" => [
                "type" => new StringType(),
                "label" => "Publicly displayed contact <b>Zip Code</b>",
                "group" => GROUP_CONTACT,
                "subgroup" => "Public Contact Info",
            ]
        ],
        "PublicContact_country" => [
            "default" => "",
            "meta" => [
                "type" => new StringType(),
                "label" => "Publicly displayed contact <b>Country</b>",
                "group" => GROUP_CONTACT,
                "subgroup" => "Public Contact Info",
            ]
        ],

    /** ERRORS */
        /* The id attribute of the body tag when errors happen in a web context. */
        "HTTP_error_body_id" => [
            "default" => "cobalt_http_error"
        ],
        "Cobalt_autoload_exit_on_failure" => [
            'default' => true
        ],
    /** SEARCH AND SEO */
        "keywords" => [
            "default" => "",
            "meta" => [
                "type" => new TextType(),
                "label" => "Keywords",
                "help" => "A comma-delimited list of keywords included in the head of your document. NOTE: This has little-to-no real-world SEO value.",
                "group" => GROUP_SEO,
                "subgroup" => "General",
            ]
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
        "opengraph_type" => [
            "default" => "website",
        ],
        "opengraph_image" => [
            "default" => "/core-content/img/branding/cobalt-logo.svg",
        ],
        "opengraph_image_X" => [
            "default" => 500,
        ],
        "opengraph_image_Y" => [
            "default" => 500,
        ],
        "opengraph_description" => [
            "default" => "Cobalt engine is a fast, lightweight, and simple MVC-based framework written in PHP. Find out more at heavyelement.io",
        ],
        "fb_app_id" => [
            "default" => "",
            "meta" => [
                "type" => new StringType(),
                "label" => "Facebook App ID",
                "group" => GROUP_SEO,
                "subgroup" => "Opengraph",
            ]
        ],
        "Robots_txt_config" => [
            "default" => "User-agent: *\nAllow: /\nDisallow: /admin",
            "meta" => [
                "type" => new TextType(),
                "label" => "Robots.txt file",
                "help" => "Each User-agent rule must be followed by distinct 'Allow: /' or 'Disallow: /' rules. One Allow or Disallow rule per route.",
                "group" => GROUP_SEO,
                "subgroup" => SUBGROUP_SEO_ROBOTS,
            ]
        ],
        "Robots_txt_block_known_ai_crawlers" => [
            "default" => false,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Request AI Web Crawlers Ignore Site",
                "description" => "This will set up your robots.txt file to deny access to AI web crawlers. Note that this <strong>does not block facebookexternalhit</strong> since that would also break link previews.",
                "group" => GROUP_SEO,
                "subgroup" => SUBGROUP_SEO_ROBOTS,
            ]
        ],
        "AI_prohibit_scraping_notice" => [
            "default" => false,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Ask AI Web Crawlers to Ignore This App",
                "description" => "This will add &lt;meta&gt; tags to your page, include headers with every request, and create an <code>ai.txt</code> file in the root directory of your app. Some AI bots do not honor these requests.",
                "group" => GROUP_SEO,
                "subgroup" => SUBGROUP_SEO_ROBOTS,
            ]
        ],
        "Forbid_AI_webcrawler_access" => [
            "default" => false,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Forbid Access for AI Web Crawlers",
                "description" => "This will throw a 403 Forbidden when AI bots crawl your application.",
                "help" => "This is heavy-handed and may break things.",
                "group" => GROUP_SEO,
                "subgroup" => SUBGROUP_SEO_ROBOTS,
            ]
        ],

    /** MAIL **/
        "Mail_username" => [
            "default" => "",
            "directives" => [
                "config" => "smtp_username",
                "env" => "MAIL_USERNAME",
            ],
            "meta" => [
                "type" => new StringType(),
                "label" => "SMTP Username",
                "description" =>  "The username credential used to authenticate with your SMTP service",
                "group" => GROUP_SMTP,
                "subgroup" => SUBGROUP_SMTP_BASIC,
            ]
        ],

        "Mail_password" => [
            "default" => "",
            "directives" => [
                "config" => "smtp_password",
                "env" => "MAIL_PASSWORD",
            ],
            "meta" => [
                "type" => new PasswordHashType(),
                "description" =>  "The password credentials used to authenticate with your SMTP service",
                "group" => GROUP_SMTP,
                "subgroup" => SUBGROUP_SMTP_BASIC,
                "label" => "SMTP Password",
            ],
        ],

        "Mail_smtp_host" => [
            "default" => "",
            "directives" =>[
                "config" => "smtp_host",
                "env" => "MAIL_SMTP_HOST",
            ],
            "meta" => [
                "type" => new StringType(),
                "label" => "SMTP Host",
                "description" =>  "The hostname of your SMTP service",
                "group" => GROUP_SMTP,
                "subgroup" => SUBGROUP_SMTP_BASIC,
            ]
        ],

        "Mail_port" => [
            "default" => 587, // Verified this works with mailgun and PHPMailer::ENCRYPTION_STARTTLS
            "directives" =>[
                "config" => "smtp_port",
                "env" => "MAIL_PORT",
            ],
            "meta" => [
                "type" => new NumberType(),
                "label" => "SMTP Port",
                "description" =>  "The port number of your SMTP service",
                "group" => GROUP_SMTP,
                "subgroup" => SUBGROUP_SMTP_BASIC,
            ]
        ],
        "Mail_connection_security" => [
            "default" => PHPMailer::ENCRYPTION_STARTTLS,
            "meta" => [
                'type' => new EnumType(),
                "label" => "SMTP Connection Type",
                "description" =>  "The connection security for your SMTP service",
                "group" => GROUP_SMTP,
                "subgroup" => SUBGROUP_SMTP_BASIC,
                "valid" => [
                    PHPMailer::ENCRYPTION_SMTPS => "SSL",
                    PHPMailer::ENCRYPTION_STARTTLS => "TLS",
                    "none" => "None"
                ]
            ],
            "valid" => [
            ]
        ],
        
        "Mail_smtp_auth" => [
            "default" => true,
            "directives" => [
                "config" => "smtp_auth",
                "env" => "MAIL_AUTH",
                "description" =>  "",
            ],
            "meta" => [
                "type" => new BooleanType(),
                "label" => "SMTP Auth Enabled",
                "description" =>  "Determines if the SMTP connection should use authentication",
                "group" => GROUP_SMTP,
                "subgroup" => SUBGROUP_SMTP_BASIC,
            ]
        ],

        "Mail_reply_to_address" => [
            "default" => "",
            "directives" =>[
                "alias" => "Mail_from_address"
            ],
            "meta" => [
                "type" => new EmailAddressType(),
                "label" => "Reply To",
                "description" =>  "The email address that should be replied to when receiving an email from Cobalt",
                "group" => GROUP_SMTP,
                "subgroup" => SUBGROUP_SMTP_BASIC,
            ],
        ],
        "Mail_reply_to_name" => [
            "default" => "",
            "directives" =>[
                "alias" => "app_short_name"
            ],
            "meta" => [
                "type" => new StringType(),
                "label" => "Reply To Name",
                "description" =>  "The displayed name when receiving an email from Cobalt",
                "group" => GROUP_SMTP,
                "subgroup" => SUBGROUP_SMTP_BASIC,
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
                "type" => new StringType(),
                "label" => "From Address",
                "group" => GROUP_SMTP,
                "subgroup" => SUBGROUP_SMTP_BASIC,
            ]
        ],
        "Mail_from_name" => [
            "default" => "",
            "directives" =>[
                "config" => "smtp_from_name",
                "alias" => "app_short_name"
            ],
            "meta" => [
                "type" => new StringType(),
                "label" => "From Name",
                "group" => GROUP_SMTP,
                "subgroup" => SUBGROUP_SMTP_BASIC,
            ]
        ],

    /** LANDING PAGE */
        "Landing_page_home_route_options" => [
            "default" => [
                "anchor" => ["label" => "Home"],
                "navigation" => ["main_navigation"]
            ]
        ],
        "LandingPages_enabled" => [
            "default" => true,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Langing Page System",
                "description" => "Enable or disable Cobalt landing pages.",
                "group" => GROUP_PAGES,
                "subgroup" => SUBGROUP_PAGES_POSTS_GENERAL,
            ]
        ],
        "LandingPage_route_prefix" => [
            "default" => "/",
            "definititon" => "LandingPage_route_prefix",
            "meta" => [
                "type" => new StringType(),
                "label" => "Route Prefix",
                "help" => "Your pages will live at this location. It MUST start with a slash and may contain more.&#10;&#10;Changing this setting WILL break existing links to pages.",
                "group" => GROUP_PAGES,
                "subgroup" => SUBGROUP_BASIC_GENERAL,
            ]
        ],
        "LandingPage_table_of_contents_label" => [
            "default" => "Contents",
            "meta" => [
                "type" => new StringType(),
                "label" => "Contents Label Headline",
                "help" =>  "The headline displayed over the Landing Page's Table of Contents",
                "group" => GROUP_PAGES,
                "subgroup" => "Presentation",
            ]
        ],
        "LandingPage_table_of_contents_by_default" => [
            "default" => true,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Generate a Table Of Contents by default",
                "group" => GROUP_PAGES,
                "subgroup" => "Presentation",
            ]
        ],
        "LandingPage_bio_by_default" => [
            "default" => true,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Show a biography of the author by default",
                "group" => GROUP_PAGES,
                "subgroup" => "Biography",
            ]
        ],
        "LandingPages_include_footer_by_default" => [
            "default" => false,
        ],
        "LandingPage_bio_default_headline" => [
            "default" => "About the Author",
            "meta" => [
                "type" => new StringType(),
                "label" => "Default headline for author biography",
                "group" => GROUP_PAGES,
                "subgroup" => "Biography",
            ],
        ],
        "LandingPage_allow_custom_css_injection" => [
            "default" => true,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Allow Custom CSS Injection",
                "group" => GROUP_PAGES,
                "subgroup" => "Presentation",
            ]
        ],
        "LandingPage_related_content_title" => [
            "default" => "Related Pages",
            "meta" => [
                "type" => new StringType(),
                "label" => "Related Content Default Headline",
                "help" => "The default headline for the 'Other Content' section.",
                "group" => GROUP_PAGES,
                "subgroup" => "Related Content",
                "confirm" => "Changing this value will break existing links and search engines will need to crawl your site in order to fix them. Are you sure you want to change this setting?"
            ]
        ],
        "LandingPages_show_related" => [
            "default" => true,
            "meta" => [
                "type" => new StringType(),
                "label" => "Show related content",
                "help" => "Include links to related pages by default.",
                "group" => GROUP_PAGES,
                "subgroup" => "Related Content",
                "confirm" => "Changing this value will break existing links and search engines will need to crawl your site in order to fix them. Are you sure you want to change this setting?"
            ]
        ],
    
    /** POSTS */
        "Posts_default_enabled" => [
            'default'=> false,
            'meta' => [
                'type' => new BooleanType(),
                "label" => "Posts System",
                "description" => "Enable or disable the built-in blogging system.",
                "help" => "Toggling this setting will not delete posts you've made. It will simply hide them all.",
                'group' => GROUP_POSTS,
                'subgroup' => 'General',
            ]
        ],
        "Posts_index_post_count" => [
            "default" => 9,
            "meta" => [
                "type" => new NumberType(),
                "label" => "Post Index Count",
                "description" => "How many posts per page should be displayed on the public index of posts?",
                "group" => GROUP_POSTS,
                "subgroup" => "General",
            ]
        ],

        "Posts_index_mode" => [
            "default" => POSTS_INDEX_MODE_GRID,
            "meta" => [
                "type" => new RadioType(),
                "label" => "Post Index Mode",
                "description" => "How should your posts index be displaying your posts?",
                "group" => GROUP_POSTS,
                "subgroup" => "General",
                "valid" => [
                    // "notification" => "Notification",
                    POSTS_INDEX_MODE_GRID => "<i name='dots-grid'></i> Display as a \"Grid\"<br><small>Posts appear as a grid of posts displayed as the 'related content' preview.</small>",
                    POSTS_INDEX_MODE_FEED => "<i name='post-outline'></i> Display as a \"Feed\"<br><small>Posts will appear as a linear feed featuring the summary of each post.</small>",
                    POSTS_INDEX_MODE_LATEST => "<i name='format-float-left'></i> Latest Post<br><small>The index will automatically redirect to the most recent post.</small>",
                ]
            ]
        ],
        "PostPages_default_aside_visibility" => [
            "default" => false,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Sidebar",
                "description" => "Include a sidebar (with a table of contents) for posts by default",
                "group" => GROUP_POSTS,
                "subgroup" => "Default Post Settings",
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

        "Posts_collection_name" => [
            'default'=> "CobaltPosts",
        ],
        "Posts_default_name" => [
            'default'=> "Posts",
        ],
        "Posts_public_index" => [
            'default'=> "/posts",
        ],
        "Posts_public_index_options" => [
            'default'=> [
                "anchor" => ["label" => "Posts"],
                "navigation" => ["main_navigation"]
            ],
        ],
        "Posts_public_post" => [
            'default'=> "/posts/",
        ],
        "Posts_public_post_options" => [
            'default'=> [],

        ],
        "Posts_enable_rss_feed" => [
            "default" => true,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Enable Post RSS Feed",
                "group" => GROUP_POSTS,
                "subgroup" => SUBGROUP_PAGES_RSS,
            ],
            
        ],
        "Posts_rss_feed_name" => [
            "default" => "RSS Feed",
            "directives" => [
                "alias" => "app_name"
            ],
            "meta" => [
                "type" => new StringType(),
                "label" => "Post RSS Name",
                "group" => GROUP_POSTS,
                "subgroup" => SUBGROUP_PAGES_RSS,
            ]
        ],
        "Posts_rss_feed_description" => [
            "default" => "A Cobalt Engine RSS Feed",
            "directives" => [
                "alias" => "app_name"
            ],
            "meta" => [
                "type" => new TextType(),
                "label" => "Post RSS Feed Description",
                "group" => GROUP_POSTS,
                "subgroup" => SUBGROUP_PAGES_RSS,
            ]
        ],
        "Posts_rss_feed_path" => [
            "default" => "/posts/feed/",
            "meta" => [
                "type" => new StringType(),
                "label" => "Post RSS Feed Path",
                "group" => GROUP_POSTS,
                "subgroup" => SUBGROUP_PAGES_RSS,
            ]
        ],
        "Posts_default_index_display" => [
            "default" => "default"
        ],
        "Posts_rss_feed_include_unlisted" => [
            'default' => false,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Announce Unlisted",
                "description" => "Include \"Unlisted\" Posts in RSS Feed",
                "group" => GROUP_POSTS,
                "subgroup" => SUBGROUP_PAGES_RSS,
            ]
        ],

        /* If true, the settings will be cached after being processed and the cache 
        will only be updated if any of the settings files are modified. */
        "cache_settings" => [
            "default" => true
        ],
                
    
    /** CUSTOMIZATIONS */
        "Customizations_enabled" => [
            "default" => true,
            "directives" => [],
            "meta" => [
                "type" => new BooleanType(),
                "group" => GROUP_LOOK_FEEL,
                "label" => "Enable Customization Framework",
                "help" => "Enables customization",
                "subgroup" => "Customization"
            ],
        ],
        'Customizations_allow_prefetching' => [
            'default' => true,
        ],
        
        "error_on_missing_customization" => [
            "default" => true,
            "directives" => [],
            "meta" => [
                'type' => new BooleanType(),
                "label" => "Error on missing Customzations",
                "group" => GROUP_LOOK_FEEL,
                "help" => "When enabled, the CustomizationManager will throw an Exception if a value is missing.",
                "subgroup" => "Customization"
            ]
        ],

    /** LOOK & FEEL */
        /* A bespoke name to be listed in the copyright notice */
        "logo" => [
            "default" => [
                "media" =>[
                    "id" => null,
                    "filename" => '/core-content/img/branding/cobalt-logo.svg',
                    "meta" =>[
                        "width" => 1500,
                        "height" => 1500,
                        "mimetype" =>"image\/svg+xml"
                    ]
                ],
                "thumb" =>[
                    "id" => null,
                    "filename" => '/core-content/img/branding/cobalt-logo.svg',
                    "meta" =>[
                        "width" => 150,
                        "height" => 150,
                        "mimetype" =>"image\/svg+xml"
                    ]
                ]
            ],
            "meta" => [
                'type' => new ImageType(),
                // "group" => "Logo",
                // "subgroup" => SUBGROUP_BASIC_DETAILS,
                // "label" => "Logo",
                // "view" => "/admin/settings/inputs/logo.html"
            ]
        ],
        
        /* Decides if the logo should be shown in the default header */
        "display_masthead" => [
            "default" => true
        ],
        /* Fonts to be used. This can be referenced by the rendering engine for 
        email templates and more. */
        "fonts" => [
            "default" => [],
            // "definititon" => "Fonts",
            "directives" => [
                "merge" =>  [
                    "head" => [
                        "family" => "'Archivo Black', sans-serif",
                        'style' => 'normal',
                        'display' => 'swap',
                        'weight' => [400],
                        'src' => "url(https://cdn.jsdelivr.net/fontsource/fonts/archivo-black@latest/latin-400-normal.woff2) format('woff2'), url(https://cdn.jsdelivr.net/fontsource/fonts/archivo-black@latest/latin-400-normal.woff) format('woff')",
                        'unicode-range' => 'U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD',
                        // "import" => "Assistant:500,800"
                    ],
                    "body" => [
                        "family" => "'Open Sans', sans-serif",
                        "import" => "Open+Sans:400,400i,800,800i",
                        'weight' => [400]
                    ]
                ],
                "style" => true
            ]
            // "meta" => [
            //     "group" => GROUP_LOOK_FEEL,
            //     "label" => "Default Fonts",
            //     "view" => "/admin/settings/inputs/fonts.html"
            // ]
        ],
        "Font_backend" => [
            'default' => FONT_BACKEND_GOOGLE
        ],
        
        "Web_embedded_content_in_header" => [
            'default' => "",
            'meta' => [
                'type' => new MarkdownType(),
                "label" => "Frontend Code Injection (End of <code>&lt;head&gt;</code> tag)",
                'help' => "Allows you to inject HTML into the frontend of the site at the end of the &gt;head&lt; tag",
                'group' => GROUP_LOOK_FEEL,
                'subgroup' => 'Customization',
            ]
        ],
        "Web_embedded_content_after_footer" => [
            'default' => "",
            'meta' => [
                'type' => new MarkdownType(),
                "label" => "Frontend Code Injection (End of <code>&lt;footer&gt;</code> tag)",
                'help' => "Allows you to inject HTML into the frontend of the site after the footer content",
                'group' => GROUP_LOOK_FEEL,
                'subgroup' => 'Customization',
            ]
        ],
        
        "css-vars" => [
            "default" => [],
            "directives" => [
                "merge" => [
                    
                ],
                "style" => true
            ]
        ],
        "enable_default_parallax" => [
            "default" => true,
            "directives" =>[
                "public" => true
            ],
            "meta" => [
                "type" => new BooleanType(),
                "help" => "Allows you to specify [parallax-mode=\"\"] attributes on elements in your pages.",
                "label" => "Enable Parallax",
                "group" => GROUP_LOOK_FEEL,
            ]
        ],
        "use_mutation_observer_bootstrap" => [
            'default' => true,
            'directives' => [
                'public' => true,
            ]
        ],

        /* The name of the designer as well as their website and title text */
        "designer" => [
            "default" => [
                "prefix" => "Designed by",
                "label" =>   "Heavy Element",
                "href" =>   "https://heavyelement.com/",
                "title" =>  "Midcoast Maine's Premier Media Production Studio"
            ],
            // "meta" => [
            //     "group" => GROUP_BASIC,
            //     "subgroup" => SUBGROUP_BASIC_DETAILS,
            //     "label" => "Designer Credit",
            //     "view" => "/admin/settings/inputs/designer.html"
            // ]
        ],
        
        /* The image displayed when loading a page. */
        "login-hero-sidebar" => [
            "directives" => [
                "style" => true,
                "alias" => "logo.media.filename"
            ],
            "meta" => [
                "type" => new StringType(),
                "label" => "Sidebar Image",
                "group" => GROUP_LOOK_FEEL,
            ],
        ],
        "manifest_engine" => [
            "default" => 2
        ],
        "manifest_v2_package_js_files" => [
            "default" => true,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Enable JS packaging",
                "description" => "Manifest v2 settings",
                "help" => "This will enable bundling/packaging JavaScript files into a single `package.[context].js`. Applies only in PRODUCTION mode.",
                "group" => GROUP_DEV,
                "subgroup" => SUBGROUP_DEV_JS_PACKAGE,
            ]
        ],
        "manifest_v2_package_css_files" => [
            "default" => true,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Enable CSS packaging",
                "description" => "Manifest v2 settings",
                "help" => "This will enable bundling/packaging Cascading Style Sheets files into a single `package.[context].css`. Applies only in PRODUCTION mode.",
                "group" => GROUP_DEV,
                "subgroup" => SUBGROUP_DEV_CSS_PACKAGE,
            ]
        ],
        "manifest_v2_include_filenames" => [
            "default" => true,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Include Filenames in Packages",
                "description" => "Manifest v2 settings",
                "help" => "This will include sanitized filenames as comments in compiled packages. Applies only in PRODUCTION mode.",
                "group" => GROUP_DEV,
                "subgroup" => "General",
            ],
        ],
        "manifest_v2_minify_css" => [
            "default" => true,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Minify CSS Package",
                "description" => "Manifest v2 settings",
                "help" => "This will strip all unnecessary content from your CSS package. Applies only in PRODUCTION mode.",
                "group" => GROUP_DEV,
                "subgroup" => SUBGROUP_DEV_CSS_PACKAGE,
            ],
        ],
        "manifest_v2_minify_script" => [
            "default" => true,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Minify JS Package",
                "description" => "Manifest v2 settings",
                "help" => "This will strip all unnecessary content from your JS package. Applies only in PRODUCTION mode.",
                "group" => GROUP_DEV,
                "subgroup" => SUBGROUP_DEV_JS_PACKAGE,
            ],
        ],
        "universal_theme" => [
            "default" => false,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Universal Theme",
                "description" => "When on, themes will apply to the entire app (including the admin panel).",
                "help" => "By default, themes do not apply to the admin panel.",
                "group" => GROUP_LOOK_FEEL,
                "subgroup" => SUBGROUP_BASIC_GENERAL,
            ],
        ],
        "default_color_scheme" => [
            "default" => true
        ],

        // Used for the header navigation and admin panel
        "branding_increment" => [
            "default" => "0.05",
        ],
        "branding_rotation" => [
            "default" => "0"
        ],
        "color_branding" => [
            "default" => "#2F4858"
        ],


        // Used as an accent color for inputs and primary action buttons
        "primary_increment" => [
            "default" => "0.1",
        ],
        "primary_rotation" => [
            "default" => "0"
        ],
        "color_primary" => [
            "default" => "#009DDC"
        ],

        // Used for input trey areas, neutral buttons, etc
        "neutral_increment" => [
            "default" => "0.1",
        ],
        "neutral_rotation" => [
            "default" => "0"
        ],
        "color_neutral" => [
            "default" => "#D2D6DA"//"#D3D7D9"
        ],
        
        // Used for the background color of the page
        "background_increment" => [
            "default" => "0.1",
        ],
        "background_rotation" => [
            "default" => "0"
        ],
        "color_background" => [
            "default" => "#F4F5F6"
        ],

        "issue_increment" => [
            "default" => "0.1",
        ],
        "issue_rotation" => [
            "default" => "0"
        ],
        "color_issue" => [
            "default" => "#F96F5D"
        ],
        // Used as the font family
        "color_font_body" => [
            "default" => "#02040F"
        ],
        
        "color_mixed_percentage" => [
            "default" => 75
        ],
        "pwa" => [
            "default" => [
                "display" => "standalone",
                "background_color" => "#000"
            ]
        ],





    /** PLUGINS & EXTENSIONS */
        "Plugin_enable_plugin_support" => [
            "default" => true
        ],
        "Plugin_enabled_plugins" => [
            "default" => []
        ],
        "Plugin_blacklisted_plugins" => [
            "default" => []
        ],
        
    /** WEB */
        "Enable_Content_Security_Policy_Nonce" => [
            'default' => false,
        ],
        "Disable_umami_for_logged_in_users" => [
            'default' => true
        ],
        "Web_include_app_branding" => [
            "default" => true,
            // "meta" => [
            //     "group" => GROUP_USERBAR,
            //     "subgroup" => SUBGROUP_BASIC_GENERAL,
            //     "label" => "Include logo in Web masthead?",
                
            //     "type" => new BooleanType()
            // ],
            // "validation" => [
            //     "type" => "bool"
            // ]
        ],
        "Web_privacy_policy" => [
            "default" => "",
            "meta" => [
                "type" => new StringType(),
                "label" => "Path to Privacy Policy",
                "group" => GROUP_LOOK_FEEL,
            ]
        ],
        "Web_terms_of_service" => [
            "default" => "",
            "meta" => [
                "type" => new StringType(),
                "label" => "Path to Terms of Service",
                "group" => GROUP_LOOK_FEEL,
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
    /** RENDERER */
        "Render_all_templates_as_native" => [
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
        "Template_debug_state" => [
            "default" => 0
        ],

    /** AUTHENTICATION */
        "session_cookie_name" => [
            "default" => "token_session" // Changing this in production will log everyone out.
        ],
        "session_secure_status" => [
            "default" => true,
            "directives" => [
                "env" => "SESSION_SECURE"
            ]
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
            "default" => false
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
        "Auth_login_mode" => [
            "default" => COBALT_LOGIN_TYPE_STAGES,
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
            "default" => true,
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
        'TwoFactorAuthentication_nag_unenrolled_users' => [
            'default' => true
        ],

    /** PUBLIC */
        "html_tag_classes" => [
            "default" => "",
        ],
        "Cookie_consent_prompt" => [
            "default" => false,
            "meta" => [
                "type" => new BooleanType(),
                "group" => GROUP_LOOK_FEEL,
                "label" => "Cookie Consent Prompt",
            ],
        ],
        "loading_spinner" => [
            "default" => "dashes",
            "directives" => [
                "public" => true
            ]
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
        "Mobile_nav_menu_closes_on_anchor_link_click" => [
            "default" => true,
            "directives" => [   
                "public" => true
            ]
        ],
        
    /** DEBUG */
        "debug" => [
            "default" => true,
            "definition" => "Debug",
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Debug status",
                "group" => GROUP_DEV,
                "subgroup" => "Debug",
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
                "type" => new BooleanType(),
                "label" => "Public Debugging",
                "description" => "Output detailed exception data publicly via route context handlers. <code style=\"color: var(--issue-color-1)\">DANGEROUS!</code>",
                "dangerous" => true,
                "group" => GROUP_DEV,
                "subgroup" => "Debug",
            ]
        ],
        /* Debug routes include things like the WebComponent input tests */
        "enable_debug_routes" => [
            "default" => false,
            "directives" => [
                "config" => "enable_debug_routes"
            ],
            "meta" => [
                "type" => new BooleanType(),
                "group" => GROUP_CACHE_DEBUG,
                "label" => "Enable debug routes",
                "debug" => true
            ]
        ],

        "route_cache_disabled" => [
            "default" => false,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Route Cache Disabled",
                "group" => GROUP_CACHE_DEBUG,
                "debug" => true
            ]
        ],
        "cached_content_disabled" => [
            "default" => false,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Cached Content Disabled",
                "group" => GROUP_CACHE_DEBUG,
                "debug" => true
            ]
        ],
        "settings_cache_disabled" => [
            "default" => false,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Settings Cahce Disabled",
                "group" => GROUP_CACHE_DEBUG,
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
                "type" => new BooleanType(),
                "label" => "Core Content Disabled",
                "group" => GROUP_CACHE_DEBUG,
                "debug" => true
            ]
        ],
        "Parallax_enable_debug" => [
            "default" => false,
            "directives" =>[
                "public" => true
            ],
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Enable Parallax Debug",
                "help" => "Allows the scroll manager to display debug output to help troubleshoot parallax issues.",
                "group" => GROUP_LOOK_FEEL,
            ]
        ],
        "header_nav_exclude_wrapper" => [
            "default" => false
        ],
        "apply_header_class_after_scroll" => [
            "default" => 0,
            "directives" =>[
                "public" => true
            ],
            "meta" => [
                "type" => new NumberType(),
                "label" => "Threshold to apply `scrolled` class to body",
                "description" => "After the scrollbar leaves scrolls beyond this value, the scroll manager will apply the class .scroll-manager--scroll-constraint-satisfied",
                "group" => GROUP_LOOK_FEEL,
            ],

        ],
        "apply_header_class_scroll_upwards_multiplier"  => [
            "default" => 1,
            "directives" =>[
                "public" => true
            ],
            "meta" => [
                "type" => new NumberType(),
                "label" => "`Scrolled` class upwards multiplier",
                "description" => "When scrolling upwards, the \"Threshold to apply `scrolled` class to body\" is multiplied by this value to find the upwards movement threshold",
                "group" => GROUP_LOOK_FEEL,
            ],

        ],
    /** PACKAGING */
        "Package_JS_script_content" => [
            "default" => false,
            "definition" => "Debug",
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Bundle JavaScript Content",
                "help" => "Compiles all client-side JavaScript into one file. May moderately decrease load times.",
                "group" => GROUP_DEV,
                "subgroup" => "Packaging",
            ]
        ],
        "Package_style_content" => [
            "default" => false,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Bundle CSS Content",
                "help" => "Compiles all CSS files into one. May significantly decrease load times.",
                "group" => GROUP_DEV,
                "subgroup" => "Packaging",
            ]
        ],
        "Package_style_minify" => [
            "default" => true,
            "meta" => [
                "type" => new BooleanType(),
                "label" => "Minify Bundled CSS Content",
                "help" => "When `Package_style_content` is enabled, package.css is minified. May significantly decrease load times.",
                "group" => GROUP_DEV,
                "subgroup" => "Packaging",
            ]
        ],
        
    /** FILESYSTEM */
        "Database_fs_enabled" => [
            "default" => true
        ],
        "Database_fs_public_endpoint" => [
            "default" => "/dbfs/"
        ],
    /** PAYMENTS */
        "PaymentGateways_enabled" => [
            "default" => false
        ],
                
    /** BLOCKEDITOR CONTENT */
        "Block_Editor_endpoints" => [
            "default" => true
        ],
        "BlockContent_paragraph_external_links_to_blank" => [
            "default" => true,
        ],
    
    /** SOCIAL MEDIA */
        // "SocialMedia_email" => [
        //     'default' => '',
        //     'meta' => [
        //         "type" => new StringType(),
        //         "label" => "<i name=\"email\"></i> Email Newsletter",
        //         "description" =>  "Used in combination with the Social Media functionality.",
        //         'group' => 'Basic',
        //         'subgroup' => 'Social',
        //     ]
        // ],
        // "SocialMedia_fediverse" => [
        //     'default' => '',
        //     'meta' => [
        //         "type" => new URLType(),
        //         "label" => "<i name=\"fediverse\"></i> Fediverse",
        //         "description" =>  "Used in combination with the Social Media functionality.",
        //         'group' => 'Basic',
        //         'subgroup' => 'Social',
        //     ]
        // ],
        // "SocialMedia_facebook" => [
        //     'default' => '',
        //     'meta' => [
        //         "type" => new URLType(),
        //         "label" => "<i name=\"facebook\"></i> Facebook",
        //         "description" =>  "Used in combination with the Social Media functionality.",
        //         'group' => 'Basic',
        //         'subgroup' => 'Social',
        //     ]
        // ],
        // 'SocialMedia_instagram' => [
        //     'default' => '',
        //     'meta' => [
        //         "type" => new URLType(),
        //         "label" => "<i name=\"instagram\"></i> Instagram",
        //         "description" =>  "Used in combination with the Social Media functionality.",
        //         'group' => 'Basic',
        //         'subgroup' => 'Social',
        //     ]
        // ],
        // 'SocialMedia_twitter' => [
        //     'default' => '',
        //     'meta' => [
        //         "type" => new URLType(),
        //         "label" => "<i name=\"twitter\"></i> Twitter",
        //         "description" =>  "Used in combination with the Social Media functionality.",
        //         'group' => 'Basic',
        //         'subgroup' => 'Social',
        //     ],
        // ],
        // 'SocialMedia_mastodon' => [
        //     'default' => '',
        //     'meta' => [
        //         "type" => new URLType(),
        //         "label" => "<i name=\"mastodon\"></i> Mastodon",
        //         "description" =>  "This should be the URL for your Mastodon account, not the @account@mastodon.social format.",
        //         'group' => 'Basic',
        //         'subgroup' => 'Social',
        //     ],
        //     'validate' => [
        //         'type' => 'string'
        //     ]
        // ],
        // 'SocialMedia_pixelfed' => [
        //     'default' => '',
        //     'meta' => [
        //         "type" => new URLType(),
        //         "label" => "<i name=\"pixelfed\"></i> Pixelfed",
        //         "description" =>  "This should be the URL for your PixelFed account, not the @account@pixelfed.social format.",
        //         'group' => 'Basic',
        //         'subgroup' => 'Social',
        //     ],
        // ],
        // 'SocialMedia_shown' => [
        //     'default' => [],
        // ],
    /** DOCUMENTATION */
        'Documentation_enable_in_userbar' => [
            'default' => true,
        ],
        // 'Documentation_enable_public' => [
        //     'default' => false // unimplemented
        // ],
    /** WEBMENTION */
        'Webmentions_enable_recieving' => [
            'default' => true,
        ],
        'Webmentions_enable_sending' => [
            'default' => true,
        ],
    /** UTM CONTENT */
        'UTM_redirect_enabled' => [
            'default' => true,
        ],
        'UTM_tracking_enabled' => [
            'default' => true,
        ],

    /** CONTEXT PREFIXES */
        /* API Routes consist of prefixes for URI path names. These prefixes are 
        used to load the appropriate routing table and tell the engine which 
        processor to use to handle the request. */
        "context_prefixes" => [
            "default" => [],
            "directives" =>[
                "prepend" => [
                    "CLI" => [
                        "processor" => "Handlers\\CLIHandler",
                        "prefix" => "__cli_context",
                        "exception_mode" => "cli",
                        "no_session_exception" => "\\Exceptions\\HTTP\\Unauthorized",
                        "mode" => "cli",
                        "permission" => "Admin_panel_access",
                        "session_refresh" => true,
                        "api_access" => true,
                        "router_boundry" => true,
                        "vars" => [
                            "html_class" => "cli"
                        ]
                    ],
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
                    "documentation" => [
                        "processor" => "Handlers\\DocumentationHandler",
                        "prefix" => "/documentation/",
                        "exception_mode" => "web",
                        "mode" => "text/html",
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
                    "res" => [
                        'processor' => "Handlers\\SharedHandler",
                        'mode' => "application/json",
                        "session_refresh" => false,
                        "api_access" => false,
                        "prefix" => "/res/"
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
    /** DEPRECATED */
        "UploadResult_default_thumbnail" => [
            "default" => [450, null]
        ],
        "Validation_exclude_unregistered_keys_by_default" => [
            "default" => true
        ],
        "Validation_strict_data_submission_policy_by_default" => [
            "default" => false
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
        "Renderer_parse_for_multiline_functions" => [
            // When true, the trailing semicolon is REQUIRED.
            "default" => false
        ],
        "Prototypeable_required_field_label" => [
            "default" => "*"
        ],
        "Websocket_default_port" => [
            "default" => 9640,
        ],
        "Websocket_default_message_handler" => [
            "default" => "",
        ],
        "Websocket_heartbeat_tick_interval_in_milliseconds" => [
            'default' => 20000 // 
        ],
        "Websocket_tick_rate_in_milliseconds" => [
            'default' => 500, // 2 ticks per second
        ]
];