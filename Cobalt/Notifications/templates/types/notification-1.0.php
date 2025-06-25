<li>
    <<?= $tag ?? "notification-item" ?> href="<?= $ntfy->getHref() ?>" data-id="{{ntfy._id}}" class="notifications--notification-container notifications--notification-container-{{ntfy.version}}" 
        {{ntfy.for.attributes()}}
        {{ntfy.action.attributes()}}>
        <div class="notification--from">
            <strong><?= $vars['ntfy']->from->uname ?? "Web Admin" ?></strong>
            <action-menu stop-propagation="true" type="options" title="Notification">
                <option icon="email" name="state" method="PUT" action="/api/notifications/{{ntfy._id}}/state/" value="">Mark as "{{ntfy.myReadState}}"</option>
                <option icon="pencil" name="edit" onclick="modalView('/api/notifications/{{ntfy._id}}/update')">Edit</option>
                <option icon="delete" name="delete" method="DELETE" action="/api/notifications/{{ntfy._id}}/delete">Delete</option>
                <option icon="eye" name="addressees" onclick="modalView('/api/notifications/{{ntfy._id}}/recipients/')">See all recipients</option>
            </action-menu>
        </div>
        <div class="notification--body">
            {{!ntfy.body.md()}}
        </div>
        <div class="notification--foot">
            {{ntfy.sent.relative()}}
        </div>
    </<?= $tag ?? "notification-item" ?>>
</li>
