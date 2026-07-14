<?php
use Components\ServiceAreas\Models\County;

/** 
 * @var County $doc
 **/
?>

<form-request method="POST" action="{{action}}" {{autosave}}>
    <ul class="list-panel">
        <li>
            {{doc.name.getLabel()}}
            {{doc.name.field()}}
        </li>
        <li>
            {{doc.href.getLabel()}}
            {{doc.href.field()}}
        </li>
        <li>
            {{doc.location.getLabel()}}
            {{doc.location.field()}}
        </li>
        <li>
            {{doc.img.getLabel()}}
            {{doc.img.field()}}
        </li>
        <li>
            {{doc.credit.getLabel()}}
            {{doc.credit.field()}}
        </li>
        <li>
            {{doc.blurb.getLabel()}}
            {{doc.blurb.field()}}
        </li>
        <li>
            {{doc.include.getLabel()}}
            {{doc.include.field()}}
        </li>
    </ul>
    {{!submit_button}}
</form-request>