<gallery-item data-id="{{doc._id}}" draggable="draggable" mime-type="{{doc.meta.mimetype}}">
    <?= embed_image($vars['doc']) ?>
    <action-menu type="options">
        <option method="DELETE" action="">Delete</option>
    </action-menu>
</gallery-item>