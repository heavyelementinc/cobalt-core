<?php $inArray = in_array($name, $_GET[QUERY_PARAM_FILTER_NAME] ?? []); ?>
<div style="gap: 0.4em">
    <label>{{schema.getLabel()}}</label>
    <input type='hidden' 
        name='<?= QUERY_PARAM_FILTER_NAME ?>[]' 
        value="{{name}}" class="hypermedia--filter-select" 
        <?= $inArray ? "" : "disabled=\"disabled\"" ?>
    >
    <input type="checkbox" 
        oninput="this.previousElementSibling.disabled = !this.checked; this.nextElementSibling.disabled = !this.checked" 
        <?= ($inArray) ? "checked='checked'" : "" ?>
    >
    <select name="<?= QUERY_PARAM_FILTER_VALUE ?>[]" 
        data-field="{{name}}" 
        style="display: block; width: 100%" 
        <?= $inArray ? "" : "disabled=\"disabled\"" ?>
    >
        {{!options}}
    </select>
</div>
<hr>
