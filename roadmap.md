Version 0.1
 - [ ] Add Cobalt version checking
 - [ ] Fix .session.html loading
 - [ ] Disable unauthenticated sessions from being stored in the database
   - [ ] Will require creating new database record for user's cookie token at login
 - [ ] Ensure UserCRUD is working 100%
   - [ ] Update CLI user stuff to use UserCRUD
   - [x] Fix user creation UI
   - [x] Allow deleting users. Include route.
   - [ ] Add "Password Reset Required" field.
 - [ ] Make sure GROUP permissions are working
   - [ ] Make sure one user cannot escalate privileges above their own
   - [ ] Make "root" a permission, not a group
 - [ ] Finish <help-span>
 - [ ] Finish the 301 Moved Permanently exception and how it connects to ApiFetch

Version 0.2
 - [ ] Upgrade to version number 0.2
 - [ ] User's personal dashboard
   - [ ] Allow account management and preferences
   - [ ] Include a user preferences panel in account manager
   - [ ] User stats implement in account manager
 - [ ] Add <async-button> which should use FormRequest to carry out async stuff
 - [ ] Fire requestFailed CustomEvent in FormRequest.js and test
 - [ ] Add widgets to admin dashboard
 - [ ] Create admin dashboard container class
 - [ ] 

Version 0.3
 - [ ] 