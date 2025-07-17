<hgroup>
    <h1>{{title}}</h1>
    <action-menu>
        <option method="DELETE" action="/api/v1/integrations/{{name}}/reset">Reset</option>
    </action-menu>
</hgroup>
<form-request method="POST" action="/api/v1/integrations/{{name}}/update">
    <ul class="list-panel">
        <li>
            <label>Key</label>
            <input name="key" value="{{config.key}}">
        </li>
        <li>
            <label>Domain Name</label>
            <input name="domain_name" value="{{config.domain_name}}">
        </li>
    </ul>
</form-request>