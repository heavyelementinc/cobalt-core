<hgroup>
    <div class='hrow'>
        <h1>Contact Form Submission</h1>
        <date>{{doc.date.relative()}}</date>
    </div>
    <action-menu>
        <option method="DELETE" action="/api/v1/contact/delete/{{doc._id}}">Delete</option>
    </action-menu>
</hgroup>
<ul class="list-panel">
    <li>
        <label>Name</label>
        {{doc.name}}
    </li>
    <?php
        use Cobalt\Model\Types\MixedType;
        $details = "";
        /** @var MixedType $value */
        foreach($doc as $field => $value) {
            if($value instanceof MixedType === false) continue;
            $f = $value->getLabel();
            $v = "";
            switch($field) {
                case "additional":
                case "date":
                case "read":
                    continue 2;
                case "email":
                    $v = "<a href='mailto:$value?subject=RE:".urlencode(__APP_SETTINGS__['short_name'])."+Contact+Form'>$value</a>";
                    break;
                default:
                    $v = $value->display();
                    break;
            }
            if(!$v) continue;
            $details .= <<<HTML
                <li>
                    $f
                    <span>$v</span>
                </li>
            HTML;
        }
        echo $details;
    ?>
</ul>
<details>
    <summary>Seen by</summary>
    {{doc.read.display()}}
</details>

<h2>Message</h2>
<blockquote>
    {{doc.additional.md()}}
</blockquote>
<style>
    main {
        ul.list-panel label {
            width: 12ch;
        }
    }
</style>