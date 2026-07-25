<gallery-item data-id="{{item._id}}" draggable="draggable" mime-type="{{item.meta.mimetype}}">
    <h2 class='filename'>{{item.filename}}</h2>
    <code>
        <?=
        $item->uploadDate->toDateTime()->format("r") . "<br>". 
        readableBytes((int)$item->length)
        ?>
    </code><br>
    <small>Mimetype: {{item.meta.mimetype}}</small><br>
    <code></code>
    <!-- <action-menu type="options">
        <option method="DELETE" action="">Delete</option>
    </action-menu> -->
</gallery-item>