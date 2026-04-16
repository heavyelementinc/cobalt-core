<gallery-item data-id="{{item._id}}" draggable="draggable" mime-type="{{item.meta.mimetype}}">
    <?= embed_image($item) ?>
    <action-menu>
        <option type="prompt" icon="pencil" name="filename" title="Please rename this file:" value="<?= pathinfo($item['filename'], PATHINFO_FILENAME) ?>">Rename</option>
        <option type="prompt" icon="tag" name="alt" title="Please provide a description for this image:" value="{{item.alt}}">Update alt text</option>
    </action-menu>
</gallery-item>