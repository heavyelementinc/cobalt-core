<blockquote class="blockeditor--content blockeditor--quote-block blockeditor--quote-alignment-{{block.data.alignment}}">
    <?= from_markdown($vars['block']['data']['text']) ?>
    <footnote><?= from_markdown($vars['block']['data']['caption']) ?></footnote>
</blockquote>