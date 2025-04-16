<gallery-item data-id="{{img._id}}" draggable="draggable" mime-type="{{img.meta.mimetype}}">
    <?= embed_image($vars['img']) ?>
    <action-menu type="options">
        <option method="DELETE" action="">Delete</option>
    </action-menu>
</gallery-item>