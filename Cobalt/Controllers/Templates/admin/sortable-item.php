<?php 
$disabled = key_exists($field['name'] ?? "", $_GET[QUERY_PARAM_SORT_NAME] ?? []) ? "" : 'disabled="disabled"';
$dirBoxChecked = "";
if(!$disabled && $_GET[QUERY_PARAM_SORT_NAME][$field['name']] == '-1') $dirBoxChecked = "checked='checked'";

?>
<div class="hypermedia--sortable">
    <label>
        <input type='checkbox' 
            <?= !$disabled ? "checked='checked'" : "" ?> 
                name="<?= QUERY_PARAM_SORT_NAME ?>[{{field.name}}]" 
                value="1"
                oninput="this.closest('.hypermedia--sortable').querySelectorAll('.hypermedia--toggleable').forEach(el => el.disabled = !this.checked)" 
            > Sort by <strong>{{field.getLabel()}}</strong>
    </label>
    <label><input class="hypermedia--toggleable" type="checkbox" name="<?= QUERY_PARAM_SORT_NAME ?>[{{field.name}}]" value="-1" <?= $disabled ?> <?= $dirBoxChecked ?>> <i name="menu-down"></i></label>
</div>