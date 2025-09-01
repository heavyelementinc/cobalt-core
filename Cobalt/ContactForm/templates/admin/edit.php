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
    <?php
        use Cobalt\Model\Types\MixedType;
        $details = "";
        /** @var MixedType $value */
        foreach($doc as $field => $value) {
            $f = $value->getLabel();
            $v = "";
            switch($field) {
                case "additional":
                case "date":
                case "read":
                    continue 2;
                case "email":
                    $v = "<a href='mailto:$value->email?subject=RE:".__APP_SETTINGS__['short_name']."+Contact+Form'>$value->email</a>";
                    break;
                default:
                    $v = $value->display();
                    break;
            }
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
    {{!doc.read}}
</details>

<h2>Message</h2>
<blockquote>
    {{doc.additional.md()}}
</blockquote>
