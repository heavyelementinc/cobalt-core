<div id='user-panel-header'>
    <?php
    $container = "<span class='switch-user-button'>";
    $container_end = "</span>";
    if(auth()->getSession()->represents->length() > 1) {
        $container = '<button class="switch-user-button" onclick="switchUserAccounts()">';
        $container_end = '</button>';
    }
    ?>
    <?= $container ?>
        {{session.uname.tag()}}
    <?= $container_end ?>
    <action-menu title="User" type="popover">
        <option icon="account" href="/admin/me">Edit Profile</option>
        <option icon="plus" href="/login/?reset">Log in Another Account</option>
        <option icon="logout" method="GET" action="/api/v1/logout">Logout</option>
    </action-menu>
</div>
