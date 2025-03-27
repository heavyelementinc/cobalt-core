<?php

use Cobalt\Notifications\Classes\NotificationManager;
?>
<div class="notifications--notifications-panel" aria-hidden="true">
    <hgroup>
        <h1>Notifications</h1>
    </hgroup>
    <div class="hbox filter-container">
        <div class="filters">
            <select name="status" title="Filter by message status">
                <option value="<?= NotificationManager::STATE_ANY ?>">Any Status</option>
                <option value="<?= NotificationManager::STATE_READ ?>">Read</option>
                <option value="<?= NotificationManager::STATE_UNREAD ?>">Unread</option>
                <option value="<?= NotificationManager::STATE_SEEN ?>">Seen</option>
                <option value="<?= NotificationManager::STATE_UNSEEN ?>">Unseen</option>
            </select>
            <select name="sort" title="Sort message status">
                <option value="-1">Newest</option>
                <option value="1">Oldest</option>
            </select>
            <label title="Mute notifications"><i name="bell-off"></i> <span class="sr-only">Mute notifications</span><input type="checkbox" name="mute"></label>
        </div>
        <action-menu>
            <option onclick="window.Cobalt.NotificationsPanel.updatePanelContent()">Refresh</option>
            <option method="PUT" action="/api/notifications/me/read-all">Mark all as 'read'</option>
        </action-menu>
    </div>
    <ul class="notifications--list">
        <li>Nothing here.</li>
    </ul>
    <form-request class="notifications--send" method="POST" action="/api/notifications/send">
        <fieldset>
            <legend>Send Notification</legend>
            <input-user name="for[]" placeholder="Recipient" method="GET" action="/api/notifications/addressees" disabled></input-user>
            <button class="input-user-duplicate" disabled><i name="plus-circle"></i></button>
            <input-wrapper>
                <textarea name="body" placeholder="Message body" disabled></textarea>
                <button type="submit" disabled><i name="send"></i></button>
            </input-wrapper>
        </fieldset>
    </form-request>
</div>
