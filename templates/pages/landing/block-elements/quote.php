<blockquote class="blockeditor--content blockeditor--quote-block blockeditor--quote-alignment-{{block.data.alignment}}">
    <?= from_markdown(lookup_js_notation("block.data.text",$vars)) ?>
    <footnote><?= from_markdown(lookup_js_notation('block.data.caption',$vars)) ?></footnote>
</blockquote>