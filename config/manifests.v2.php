<?php

use Cobalt\Manifests\Classes\Item;

return [
    //(new Item)->ingest( [
    //     "href" => "/editorjs/editorjs.mjs",
    //     "contexts" => ["common"],
    //     "module" => true
    // ]),
    (new Item)->ingest([
        "href" => "components/slide-show.css",
        "contexts" => ["inline" ]
    ]),
    (new Item)->ingest([
        "href" =>"Preferences.js",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" =>"global_functions.js",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "Cobalt/UpdateOperation.js",
        "contexts" => ["common"]
    ]),
    //(new Item)->ingest(// [
    //     "href" =>"Cobalt.js",
    //     "contexts" => ["common"]
    // ]),
    (new Item)->ingest([
        "href" =>"Modal.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"Cobalt/CobaltModal.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"StatusMessage.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"AsyncMessageHandler.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"sortable.js",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" =>"AsyncFetch.js",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" =>"ApiFetch.js",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "Cobalt/CobaltWebSocket.js",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" =>"ActionMenu.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/custom-button.js",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" =>"components/simplemde.min.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/autocomplete-interface.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/radio-buttons.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/split-button.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/button-toggle.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/input-array.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    //(new Item)->ingest(// [
    //     "href" =>"components/input-switch.js",
    //     "contexts" => ["common"],
    //     "package" => "deferred"
    // ]),
    (new Item)->ingest([
        "href" =>"components/datepicker.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/input-datetime.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/autocomplete.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    //(new Item)->ingest([
    //     "href" =>"components/input-password.js",
    //     "contexts" => ["common"],
    //     "package" => "deferred"
    // ]),
    (new Item)->ingest([
        "href" =>"components/cobalt-carousel.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/match-update.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/fs-listing.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/fold-out.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/tab-nav.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/credit-card.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/flex-table.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/action-menu-element.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/NotificationsPanel.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/input-text.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    //(new Item)->ingest([
    //     "href" =>"custom-components/object-gallery-picker.js",
    //     "contexts" => ["common"],
    //     "package" => "deferred"
    // ]),
    //(new Item)->ingest([
    //     "href" =>"custom-components/object-gallery.js",
    //     "contexts" => ["common"],
    //     "package" => "deferred"
    // ]),
    //(new Item)->ingest([
    //     "href" =>"components/markdown-area.js",
    //     "contexts" => ["common"],
    //     "package" => "deferred"
    // ]),
    (new Item)->ingest([
        "href" =>"components/CountdownTimer.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"Documentation.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/horizontal-scroll.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    //(new Item)->ingest([
    //     "href" =>"components/block-editor.js",
    //     "contexts" => ["common"],
    //     "package" => "deferred"
    // ]),
    (new Item)->ingest([
        "href" =>"components/image-result.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/NumberReveal.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"components/type-writer.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"Cobalt/Shadowbox.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"DateConverter.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"TabbedUI.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"InputClasses.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"InputComponents.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"AsyncButton.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"UserMenu.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"Events.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"PaginatedContainer.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    (new Item)->ingest([
        "href" =>"AudioPlayer.js",
        "contexts" => ["common"],
        "package" => "deferred"
    ]),
    //(new Item)->ingest([
    //     "href" =>"components/InputObjectArray.js",
    //     "contexts" => ["common"],
    //     "package" => "deferred"
    // ]),
    //(new Item)->ingest([
    //     "href" =>"CobaltScrollManager.js",
    //     "contexts" => ["common"]
    // ]),
    (new Item)->ingest([
        "href" =>"PushNotifications.js",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "protected/Posts.js",
        "contexts" => ["admin"]
    ]),
    (new Item)->ingest([
        "href" => "protected/UserPanel.js",
        "contexts" => ["admin"]
    ]),
    (new Item)->ingest([
        "href" => "protected/UserManager.js",
        "contexts" => ["admin"]
    ]),
    (new Item)->ingest([
        "href" => "protected/EventManager.js",
        "contexts" => ["admin"]
    ]),
    (new Item)->ingest([
        "href" => "protected/FSManager.js",
        "contexts" => ["admin"]
    ]),
    (new Item)->ingest([
        "href" => "protected/PageEditor.js",
        "contexts" => ["admin"]
    ]),
    (new Item)->ingest([
        "href" =>"custom-components/_ComponentDeclarations.js",
        "contexts" => ["common"],
        "package" => "components",
        "append" => true,
        "module" => true
    ]),
    (new Item)->ingest([
        "href" =>"main.js",
        "contexts" => ["common"], 
        "append" => true
    ]),
    (new Item)->ingest([
        "href" =>"app.js",
        "contexts" => ["common"], 
        "append" => true
    ]),
    (new Item)->ingest([
        "href" =>"Cobalt/MobileNavMenu.js",
        "contexts" => ["common"], 
        "append" => true
    ]),
    //(new Item)->ingest([
    //     "href" =>"ClientRouter.js",
    //     "contexts" => ["common"], 
    //     "append" => true
    // ],
    /* CSS */
    //(new Item)->ingest([
    //     "href" => "material-design/css/material.min.css",
    //     "contexts" => ["common"],
    //     "package" => "inline",
    //     "inline" => true
    // ]),
    (new Item)->ingest([
        "href" => "reset.css",
        "contexts" => ["common"],
        "version" => 2
    ]),
    (new Item)->ingest([
        "href" => "web.css",
        "contexts" => ["web"],
        "version" => 2
    ]),
    (new Item)->ingest([
        "href" => "basic-buttons.css",
        "contexts" => ["common"],
        "version" => 2
    ]),
    (new Item)->ingest([
        "href" => "basic-input.css",
        "contexts" => ["common"],
        "version" => 2
    ]),
    (new Item)->ingest([
        "href" => "components/input-array.css",
        "contexts" => ["common"],
        "version" => 2
    ]),
    (new Item)->ingest([
        "href" => "components/object-gallery.css",
        "contexts" => ["common"],
        "version" => 2
    ]),
    (new Item)->ingest([
        "href" => "components/misc-components.css",
        "contexts" => ["common"],
        "version" => 2
    ]),
    (new Item)->ingest([
        "href" => "components/flex-table.css",
        "contexts" => ["common"],
        "version" => 2
    ]),
    (new Item)->ingest([
        "href" => "components/datetime.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "components/tag-select.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "components/fold-out.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "components/split-button.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "components/tab-nav.css",
        "contexts" => ["common"],
        "version" => 2
    ]),
    (new Item)->ingest([
        "href" => "components/cobalt-carousel.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "components/action-menu-element.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "components/input-text.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "components/markdown-area.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "components/shadowbox.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "components/countdown-timer.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "components/horizontal-scroll.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "components/block-editor.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" =>"components/type-writer.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "simplemde.min.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "loading-spinner.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "notification.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "action-menu.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "action-menu-refactor.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "status-message.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "form-request.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "login-form.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "posts.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "cobalt-events.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "modal.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "tabs-drawer.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "calendar.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "audio-player.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "paginated-container.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "parallax.css",
        "contexts" => ["common"]
    ]),
    (new Item)->ingest([
        "href" => "pages.css",
        "contexts" => ["common"],
        "version" => 2
    ]),
    (new Item)->ingest([
        "href" => "header.css",
        "contexts" => ["common"],
        "version" => 2
    ]),
    (new Item)->ingest([
        "href" => "template.css",
        "contexts" => ["common"],
        "version" => 2
    ]),
    (new Item)->ingest([
        "href" =>"projects.css",
        "contexts" => ["web"]
    ]),
    (new Item)->ingest([
        "href" => "main.css",
        "contexts" => ["web"]
    ]),
    (new Item)->ingest([
        "href" => "admin.css",
        "contexts" => ["admin"],
        "version" => 2
    ]),
];