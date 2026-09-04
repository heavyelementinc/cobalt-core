<?php

namespace Cobalt\Auth\Users\Types;

use Cobalt\DataModel\Directives\BoolDirective;
use Cobalt\DataModel\Directives\Fieldset;
use Cobalt\DataModel\Directives\Label;
use Cobalt\DataModel\Types\BooleanType;
use Cobalt\Model\Types\DictionaryType;

abstract class AppPermissions extends DictionaryType {
    #[Fieldset("Self")]    
    #[BoolDirective("dangerous",true)]
    #[Label("Able to modify basic info for their own account.", "This includes first/last, username, email address, and other info.")]
    readonly BooleanType $self;

    #[Fieldset("Admin")]
    #[BoolDirective("dangerous",true)]
    #[Label("Access to the admin panel.", "Access to the /admin section of this Cobalt application.")]
    readonly BooleanType $Admin_panel_access;

    #[Fieldset("Admin")]
    #[BoolDirective("dangerous",true)]
    #[Label("Modify Cobalt settings.", "Access to the Cobalt Settings panel, able to modify them.")]
    readonly BooleanType $Auth_modify_cobalt_settings;

    #[Fieldset("Admin")]
    #[BoolDirective("dangerous",false)]
    #[Label("Allows the user to access submissions to the contact form", "")]
    readonly BooleanType $Contact_form_submissions_access;

    #[Fieldset("Admin")]
    #[BoolDirective("dangerous",true)]
    #[Label("Allows the user to delete submissions to the contact form", "")]
    readonly BooleanType $Contact_form_submissions_delete;

    #[Fieldset("Admin")]
    #[BoolDirective("dangerous",true)]
    #[Label("Access to debug tools for web developers.", "Most people do NOT need this.")]
    readonly BooleanType $Debug_access;

    #[Fieldset("Posts")]
    #[BoolDirective("dangerous",false)]
    #[Label("Allows user to be credited a Post an author", "")]
    readonly BooleanType $Post_allowed_author;

    #[Fieldset("Posts")]
    #[BoolDirective("dangerous",true)]
    #[Label("Access to the Posts index page ", "This is a fairly useless permission without the ability to edit")]
    readonly BooleanType $Post_index;

    #[Fieldset("Posts")]
    #[BoolDirective("dangerous",true)]
    #[Label("Create new posts", "")]
    readonly BooleanType $Post_create;

    #[Fieldset("Posts")]
    #[BoolDirective("dangerous",true)]
    #[Label("Update existing posts", "")]
    readonly BooleanType $Post_update;

    #[Fieldset("Posts")]
    #[BoolDirective("dangerous",true)]
    #[Label("Delete Posts", "")]
    readonly BooleanType $Post_destroy;

    #[Fieldset("Posts")]
    #[BoolDirective("dangerous",true)]
    #[Label("Read Posts", "")]
    readonly BooleanType $Post_read;

    #[Fieldset("Posts")]
    #[BoolDirective("dangerous",false)]
    #[Label("Allows the user to manage posts (but not publish them).", "")]
    readonly BooleanType $Posts_manage_posts;

    #[Fieldset("Posts")]
    #[BoolDirective("dangerous",true)]
    #[Label("Allows the user to publish posts (but not edit them).", "")]
    readonly BooleanType $Posts_publish_posts;

    #[Fieldset("Posts")]
    #[BoolDirective("dangerous",false)]
    #[Label("Allows the user to access privileged Page/Post fields.", "")]
    readonly BooleanType $Posts_enable_privileged_fields;

    #[Fieldset("Pages")]
    #[BoolDirective("dangerous",true)]
    #[Label("Allows user to appear as an author of pages.", "")]
    readonly BooleanType $Pages_allowed_author;

    #[Fieldset("Pages")]
    #[BoolDirective("dangerous",true)]
    #[Label("Create Pages", "")]
    readonly BooleanType $Pages_create;

    #[Fieldset("Pages")]
    #[BoolDirective("dangerous",true)]
    #[Label("Read Pages", "")]
    readonly BooleanType $Pages_read;

    #[Fieldset("Pages")]
    #[BoolDirective("dangerous",true)]
    #[Label("View Page Index", "")]
    readonly BooleanType $Pages_index;

    #[Fieldset("Pages")]
    #[BoolDirective("dangerous",true)]
    #[Label("Update Pages", "")]
    readonly BooleanType $Pages_update;

    #[Fieldset("Pages")]
    #[BoolDirective("dangerous",true)]
    #[Label("Delete Pages", "")]
    readonly BooleanType $Pages_destroy;

    #[Fieldset("Posts")]
    #[BoolDirective("dangerous",false)]
    #[Label("Allows the user to access privileged Page/Post fields.", "")]
    readonly BooleanType $Pages_enable_privileged_fields;

    #[Fieldset("Users")]
    #[BoolDirective("dangerous",true)]
    #[Label("Able to create new user accounts.", "")]
    readonly BooleanType $Auth_allow_creating_users;

    #[Fieldset("Users")]
    #[BoolDirective("dangerous",true)]
    #[Label("Access user editing features and change user account info.", "Modify any user account information besides permissions and groups")]
    readonly BooleanType $Auth_allow_editing_users;

    #[Fieldset("Users")]
    #[BoolDirective("dangerous",true)]
    #[Label("Modify user account permissions and add/remove users from groups.", "")]
    readonly BooleanType $Auth_allow_modifying_user_permissions;

    #[Fieldset("Users")]
    #[BoolDirective("dangerous",true)]
    #[Label("Able to delete user accounts", "")]
    readonly BooleanType $Auth_allow_deleting_users;

    #[Fieldset("Notifications")]
    #[BoolDirective("dangerous",false)]
    #[Label("Able to query for username and first/last name.", "This is used as part of the notification system.")]
    readonly BooleanType $Addressee_query;

    #[Fieldset("Notifications")]
    #[BoolDirective("dangerous",true)]
    #[Label("Able to send a notification", "")]
    readonly BooleanType $Notifications_can_send_notification;

    #[Fieldset("Notifications")]
    #[BoolDirective("dangerous",true)]
    #[Label("Able to access any notification", "Typically, a user can only access a notification if they wrote it or it is addressed to them.")]
    readonly BooleanType $Notifications_can_access_any_notification;

    #[Fieldset("Admin")]
    #[BoolDirective("dangerous",true)]
    #[Label("Create, delete, or modify Cobalt Events.", "")]
    readonly BooleanType $CobaltEvents_crud_events;

    #[Fieldset("Extensions")]
    #[BoolDirective("dangerous",true)]
    #[Label("Allows the user to manage plugins. This is VERY dangerous.", "")]
    readonly BooleanType $Extensions_allow_management;

    #[Fieldset("API")]
    #[BoolDirective("dangerous",true)]
    #[Label("Allow the user to manage API keys", "")]
    readonly BooleanType $API_manage_keys;

    #[Fieldset("Users")]
    #[BoolDirective("dangerous",true)]
    #[Label("Allow the user to access default CRUD endpoints", "")]
    readonly BooleanType $CRUDControllerPermission;

    #[Fieldset("Users")]
    #[BoolDirective("dangerous",true)]
    #[Label("Allows the user to perform advanced queries outside of a given model's searchable fields (dangerous)", "")]
    readonly BooleanType $Model_advanced_search_permission;

    #[Fieldset("Customization")]
    #[BoolDirective("dangerous",true)]
    #[Label("Allow the user to create arbitrary customized content (requires the modify privilege as well).", "")]
    readonly BooleanType $Customizations_create;

    #[Fieldset("Customization")]
    #[BoolDirective("dangerous",true)]
    #[Label("Allow the user to modify the values of customized content", "")]
    readonly BooleanType $Customizations_modify;

    #[Fieldset("Customization")]
    #[BoolDirective("dangerous",true)]
    #[Label("Allow the user to delete existing customized content", "")]
    readonly BooleanType $Customizations_delete;

    #[Fieldset("Customization")]
    #[BoolDirective("dangerous",true)]
    #[Label("Allow the user to modify customized content", "")]
    readonly BooleanType $Customizations_update_parameters;

    #[Fieldset("Admin")]
    #[BoolDirective("dangerous",true)]
    #[Label("Allow the user to export database files", "")]
    readonly BooleanType $Database_database_export;

    #[Fieldset("Admin")]
    #[BoolDirective("dangerous",true)]
    #[Label("Allow the user to import database files", "")]
    readonly BooleanType $Database_database_import;

    #[Fieldset("Documentation")]
    #[BoolDirective("dangerous",false)]
    #[Label("Allows user to edit documentation ", "This can be dangerous if the user can edit sensitive documentation files")]
    readonly BooleanType $Documentation_edit;

    #[Fieldset("Documentation")]
    #[BoolDirective("dangerous",false)]
    #[Label("Allows user to delete documentation ", "This can be dangerous if the user can edit sensitive documentation files")]
    readonly BooleanType $Documentation_destroy;


}