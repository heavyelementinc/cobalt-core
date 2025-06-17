<hgroup>
    <h1>{{title}}</h1>
    <action-menu>
        <option method="DELETE" action="/api/v1/integrations/{{name}}/reset">Reset</option>
    </action-menu>
</hgroup>
<form-request method="POST" action="/api/v1/integrations/{{name}}/update">
    <ul class="list-panel">
        <li>
            <label>Campaign ID</label>
            <input name="campaign_id" value="{{config.campaign_id}}">
            <small>You can find your campaign ID by visiting the Patreon campaign 
                you want to access, opening the console, and running the following
                command: <code>javascript:prompt('Campaign ID',window.patreon.bootstrap.creator.data.id);</code>
            </small>
        </li>
        <li>
            <label>Client ID</label>
            <input name="client_id" value="{{config.client_id}}">
        </li>
        <li>
            <label>Client Secret</label>
            <input-password name="client_secret" value="{{config.client_secret}}"></input-password>
        </li>
        <li>
            <label>Access Token</label>
            <input name="access_token" value="{{config.access_token}}">
        </li>
        <li>
            <label>Refresh Token</label>
            <input name="refresh_token" value="{{config.refresh_token}}">
        </li>
    </ul>
</form-request>