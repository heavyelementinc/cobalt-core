# Version 0.1
 - [x] Add Cobalt versions
   - [x] App checking
 - [x] Fix .session.html loading
   - [ ] Create a single template loading routine and update the multiple routines to use it.
 - [x] Disable unauthenticated sessions from being stored in the database
   - [x] Will require creating new database record for user's cookie token at login
   - [x] Support session token updating (right now it breaks the system)
 - [x] Ensure UserCRUD is working 100%
   - [x] Update CLI user stuff to use UserCRUD
   - [x] Fix user creation UI
   - [x] Allow deleting users. Include route.
   - [x] Add "Password Reset Required" field.
 - [x] Make sure GROUP permissions are working
 - [x] Make sure one user cannot escalate privileges above their own
 - [x] Finish <help-span>


# Version 0.2
 - [x] Upgrade to version number 0.2
 - [x] Finish <input-object-array> & validation
   <!-- Is there a better way to do input-object-array? -->
 - [x] Create admin dashboard container class `.cobalt-admin--fieldset`
 - [x] Finish plugins system
   - [x] Plugin management in admin panel
   - [x] register_permissions
   - [x] register_templates
   - [x] register_shared_content_dir
   - [x] register_dependencies
   - [x] register_public_dir
   - [x] register_packages
   - [x] register_variables
 - [x] Fire requestFailed CustomEvent in FormRequest.js and test
 - [x] Make form-login-request listen for the enter key being pressed.
 - [x] Make form-login-request reload the page when a login has occurred successfully
 - [x] Track down WSOD (white screen of death) <!-- This is happening because the plugins weren't loading correctly and the catch routine wasn't handling correctly. -->


# Version 0.3
 - [ ] Upgrade to version number 0.3
 - [ ] Add user account verification
   - [x] Add settings to require verification before authenticated actions can be carried out
   - [ ] Add user account verification email process
 - [ ] Add password reset process
   - [ ] Add setting to enable/disable this
 - [ ] Add user preferences
 - [x] Cron system & CLI interface
 - [x] Add customizable user account icons
 - [x] Add ring privilege checks--no promoting accounts to higher access levels than your own
 - [x] Make navigation permission-sensitive
 - [x] Fix issue where router cache gets regenerated when on admin page and returns empty web cache.
 - [x] Add CLI command to promote user to `root` group
   - [x] Also added 'demote' command
 - [x] Events scheduler system
 - [ ] Add template/renderer debugging!!!
 - [x] Make templates able to load from any directory even if its overridden by a higher context (use __PLUGIN__ -> relative to current plugin, __APP__, __ENV__ as prefixes)
 - [ ] <replicator-button> contains internal <template>
 - [ ] Cobalt plugins
   - [ ] Cobalt version checking for each plugin
   - [ ] register_cli_commands


# Version 0.4
- [ ] Upgrade to version 0.4
- [ ] Finish &lt;async-wizard&gt;
- [ ] Allow plugins to display their own panel when you click on their name.
- [x] Add widgets to admin dashboard
   - [ ] Apps can add their own widgets
   - [ ] Plugins can add widgets
 - [ ] Finish autocomplete
   - [ ] Fire event on autocomplete found - This should be a CHANGE event.
   - [ ] Replace the search element in input-array with auto-complete
- [ ] Define criteria for ring privileges
 - [ ] User's personal dashboard
   - [ ] Allow account management
   - [ ] Include a user preferences panel in account manager
   - [ ] User stats implement in account manager
 - [ ] Add <async-button> which should use FormRequest to carry out async stuff
 - [ ] Finish the 301 Moved Permanently exception and how it connects to ApiFetch


# Todo
 - [ ] Make duotone icon set a plugin
 - [ ] Update the Settings Manager so it instantiates Settings class (interface of iterable)
   - [ ] Settings class contains every default value as a method

# Version 0.5
 - [ ] Remove plugin system completely because it never worked.
 - [ ] Finalize tokening system and implement email address verification, password reset, and email login
 - [ ] Fix the YouTube token issue
