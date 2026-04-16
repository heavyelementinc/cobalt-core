<gallery-item data-id="{{item._id}}" draggable="draggable" mime-type="{{item.meta.mimetype}}">
    <?= embed_image($item) ?>
    <action-menu>
        <option type="prompt" icon="pencil" name="filename" title="Rename the file &lt;code><?= pathinfo($item['filename'], PATHINFO_BASENAME) ?>&lt;/code>" value="<?= pathinfo($item['filename'], PATHINFO_FILENAME) ?>">Rename</option>
        <option type="prompt" icon="tag" name="alt" title="Provide a description for this image" value="{{item.alt}}">Update alt text</option>
        <option type="prompt" icon="palette" name="accent_color" title="Update the accent color" value="{{item.meta.accent_color}}">Accent Color</option>
        <option type="prompt" icon="palette" name="contrast_color" title="Update the contrast color" value="{{item.meta.contrast_color}}">Contrast Color</option>
    </action-menu>
</gallery-item>