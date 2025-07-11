<?php
/** @param array{array{group:string,label:string,dangerous:bool,default:bool,display:bool,ring:int}} $permissions */
$permissions = [
    "self" => [
        "group" => "Self",
        "label" => "Able to modify basic info for their own account.<help-span value='This includes first/last, username, email address, and other info.'></help-span>",
        "dangerous" => true,
        "default" => true,
        "display" => false,
        "ring" => 3
    ],
    "Admin_panel_access" => [
        "group" => "Admin",
        "name" => "Admin Panel Access",
        "label" => "Access to the admin panel.<help-span value='Access to the /admin section of this Cobalt application.'></help-span>",
        "dangerous" => true,
        "default" => false,
        "ring" => 1
    ],
    "Auth_modify_cobalt_settings" => [
        "group" => "Admin",
        "name" => "Modify Cobalt Settings",
        "label" => "Modify Cobalt settings.<help-span value='Access to the Cobalt Settings panel, able to modify them.'></help-span>",
        "dangerous" => true,
        "default" => false,
        "ring" => 1
    ],
    "Contact_form_submissions_access" => [
        "group" => "Admin",
        "name" => "Access Contact Form Submissions",
        "label" => "Allows the user to access submissions to the contact form",
        "dangerous" => false,
        "default" => false,
        "ring" => 3
    ],
    "Contact_form_submissions_delete" => [
        "group" => "Admin",
        "name" => "Delete Contact Form Submissions",
        "label" => "Allows the user to delete submissions to the contact form",
        "dangerous" => true,
        "default" => false,
        "ring" => 3
    ],
    "Debug_access" => [
        "group" => "Admin",
        "name" => "Debug Access",
        "label" => "Access to debug tools for web developers.<help-span value='Most people do NOT need this.'></help-span>",
        "dangerous" => true,
        "default" => false,
        "ring" => 1
    ],
    "Post_allowed_author" => [
        "group" => "Posts",
        "label" => "Allows user to be credited a Post an author",
        "dangerous" => false,
        "default" => false,
        "ring" => 3
    ],
    "Post_index" => [
        "group" => "Posts",
        "label" => "Access to the Posts index page <help-span value='This is a fairly useless permission without the ability to edit'></help-span>",
        "dangerous" => true,
        "default" => false,
        "ring" => 2
    ],
    "Post_create" => [
        "group" => "Posts",
        "label" => "Create new posts",
        "dangerous" => true,
        "default" => false,
        "ring" => 2
    ],
    "Post_update" => [
        "group" => "Posts",
        "label" => "Update existing posts",
        "dangerous" => true,
        "default" => false,
        "ring" => 2
    ],
    "Post_destroy" => [
        "group" => "Posts",
        "label" => "Delete Posts",
        "dangerous" => true,
        "default" => false,
        "ring" => 2
    ],
    "Post_read" => [
        "group" => "Posts",
        "label" => "Read Posts",
        "dangerous" => true,
        "default" => false,
        "ring" => 2
    ],
    "Posts_manage_posts" => [
        "group" => "Posts",
        "label" => "Allows the user to manage posts (but not publish them).",
        "dangerous" => false,
        "default" => false,
        "ring" => 3
    ],
    "Posts_publish_posts" => [
        "group" => "Posts",
        "label" => "Allows the user to publish posts (but not edit them).",
        "dangerous" => true,
        "default" => false,
        "ring" => 3
    ],
    "Posts_enable_privileged_fields" => [
        "group" => "Posts",
        "label" => "Allows the user to access privileged Page/Post fields.",
        "dangerous" => false,
        "default" => false,
        "ring" => 2
    ],
    "Pages_allowed_author" => [
        "group" => "Pages",
        "label" => "Allows user to appear as an author of pages.",
        "dangerous" => true,
        "default" => false,
        "ring" => 1
    ],
    "Pages_create" => [
        "group" => "Pages",
        "label" => "Create Pages",
        "dangerous" => true,
        "default" => false,
        "ring" => 2
    ],
    "Pages_read" => [
        "group" => "Pages",
        "label" => "Read Pages",
        "dangerous" => true,
        "default" => false,
        "ring" => 2
    ],
    "Pages_index" => [
        "group" => "Pages",
        "label" => "View Page Index",
        "dangerous" => true,
        "default" => false,
        "ring" => 2
    ],
    "Pages_update" => [
        "group" => "Pages",
        "label" => "Update Pages",
        "dangerous" => true,
        "default" => false,
        "ring" => 2
    ],
    "Pages_destroy" => [
        "group" => "Pages",
        "label" => "Delete Pages",
        "dangerous" => true,
        "default" => false,
        "ring" => 2
    ],
    "Pages_enable_privileged_fields" => [
        "group" => "Posts",
        "label" => "Allows the user to access privileged Page/Post fields.",
        "dangerous" => false,
        "default" => false,
        "ring" => 2
    ],
    "Auth_allow_creating_users" => [
        "group" => "Users",
        "label" => "Able to create new user accounts.",
        "dangerous" => true,
        "default" => false,
        "ring" => 2
    ],
    "Auth_allow_editing_users" => [
        "group" => "Users",
        "label" => "Access user editing features and change user account info.<help-span value='Modify any user account information besides permissions and groups'></help-span>",
        "dangerous" => true,
        "default" => false,
        "ring" => 2
    ],
    "Auth_allow_modifying_user_permissions" => [
        "group" => "Users",
        "label" => "Modify user account permissions and add/remove users from groups.",
        "dangerous" => true,
        "default" => false,
        "ring" => 2
    ],
    "Auth_allow_deleting_users" => [
        "group" => "Users",
        "label" => "Able to delete user accounts",
        "dangerous" => true,
        "default" => false,
        "ring" => 2
    ],
    "Addressee_query" => [
        "group" => "Notifications",
        "label" => "Able to query for username and first/last name.<help-span value='This is used as part of the notification system.'></help-span>",
        "dangerous" => false,
        "default" => true,
        "ring" => 9
    ],
    "Notifications_can_send_notification" => [
        "group" => "Notifications",
        "label" => "Able to send a notification",
        "dangerous" => true,
        "default" => false,
        "ring" => 2
    ],
    "Notifications_can_access_any_notification" => [
        "group" => "Notifications",
        "label" => "Able to access any notification<help-span value='Typically, a user can only access a notification if they wrote it or it is addressed to them.'></help-span>",
        "dangerous" => true,
        "default" => false,
        "ring" => 2
    ],
    "CobaltEvents_crud_events" => [
        "group"  => "Admin",
        "label"  => "Create, delete, or modify Cobalt Events.",
        "dangerous" => true,
        "default" => false
    ],
    "Extensions_allow_management" => [
        "group" => "Extensions",
        "label" => "Allows the user to manage plugins. This is VERY dangerous.",
        "dangerous" => true,
        "default" => false,
        "ring" => 1
    ],

    "API_manage_keys" => [
        "group" => "API",
        "label" => "Allow the user to manage API keys",
        "dangerous" => true,
        "default" => false,
        "ring" => 1
    ],
    "CRUDControllerPermission" => [
        "group" => "Users",
        "label" => "Allow the user to access default CRUD endpoints",
        "dangerous" => true,
        "default" => false,
        "ring" => 1
    ],
    "Model_advanced_search_permission" => [
        "group" => "Users",
        "label" => "Allows the user to perform advanced queries outside of a given model's searchable fields (dangerous)",
        "dangerous" => true,
        "default" => false,
        "ring" => 1
    ],
    "Customizations_create" => [
        "group" => "Customization",
        "label" => "Allow the user to create arbitrary customized content (requires the modify privilege as well).",
        "dangerous" => true,
        "default" => false,
        "ring" => 1
    ],
    "Customizations_modify" => [
        "group" => "Customization",
        "label" => "Allow the user to modify the values of customized content",
        "dangerous" => true,
        "default" => false,
        "ring" => 1
    ],
    "Customizations_delete" => [
        "group" => "Customization",
        "label" => "Allow the user to delete existing customized content",
        "dangerous" => true,
        "default" => false,
        "ring" => 1
    ],
    "Customizations_update_parameters" => [
        "group" => "Customization",
        "label" => "Allow the user to modify customized content",
        "dangerous" => true,
        "default" => false,
        "ring" => 1
    ],
    "Database_database_export" => [
        "group" => "Admin",
        "label" => "Allow the user to export database files",
        "dangerous" => true,
        "default" => false,
        "ring" => 1
    ],
    "Database_database_import" => [
        "group" => "Admin",
        "label" => "Allow the user to import database files",
        "dangerous" => true,
        "default" => false,
        "ring" => 1
    ]
];
