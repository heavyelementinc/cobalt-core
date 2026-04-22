<gallery-item data-id="{{item._id}}" draggable="draggable">
    <?= embed_image($item->avatar) ?>
    <label>
        <?= ($item->fname->value) ? "$item->fname $item->lname" : "" ?>
        {{item.uname}}
    </label>
</gallery-item>