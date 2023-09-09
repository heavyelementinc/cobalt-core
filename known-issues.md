# Server-side Router
- [x] Router-generated navigation panels are not creating icons or sub menus

# RouteGroup
- [x] RouteGroup is mostly complete but is missing subgroups

# Client-side Router
- [x] Router will sometimes skip several history items on pop state
- [x] Router is not displaying errors 
- [x] When not in regular web context, (`/admin`, for example), any nav links to the context root are given the `navigation--current` class
- [x] Navigating to context roots through a pop state not working (except for web root /).
- [x] Navigating to a subdirectory (i.e. "some-dir") rather than a full pathname (i.e. "/admin/some-dir") results in the entire URL being appended.

# AsyncRequest
- [x] Not displaying StatusMessage errors

# AutoComplete Interface
- [ ] Does not detect mouse clicks for autocomplete options

# notify-button
- [ ] `contextmenu` `ActionMenu` does not hide after selecting 'mute'/'unmute' option

# Notifications Panel
- [ ] Notifications panel is not showing up when you click on the notification button

# ActionMenu
- [ ] 

# form-request
- [ ] submission events on non-autosave forms are submitting the whole form when one change is made.
- [x] input[type='number'] returns a string and not a number

# Modal.js
- [ ] Unresolved router.js dependencies that must be updated! <!-- Should I just rebuild modal.js from scratch? -->

# Settings
- [x] CSS Vars are not working properly