<div class="cobalt-session {{doc.this_session}}" class="hbox">
    <div class="hbox hgroup">
        <h1>{{doc.details.client.build}} on {{doc.details.platform.build}} {{doc.details.platform.version}}</h1>
        <action-menu type="options">
        <option method="DELETE" action="/api/v1/sessions/{{doc._id}}/" dangerous>Log out this session</option>
    </action-menu></div>
    <div class="platform-icon">
        <i class="client" name="<?= \Auth\SessionSchema::browser_lookup($this->vars['doc']['details']['client']['build']) ?>"></i>
        <i class="platform" name="<?= \Auth\SessionSchema::platform_lookup($this->vars['doc']['details']['platform']['build']) ?>"></i>
    </div>
    <ul class="list-panel">
        <li>
            <div>
                <label>Platform</label>
                {{doc.details.platform.build}} {{doc.details.platform.version}}
            </div>
            <div>
                <label>Client</label>
                {{doc.details.client.build}} {{doc.details.client.version}}
            </div>
        </li>
        <li>
            <div>
                <label>Created</label>
                <date-span value="{{doc.created}}"></date-span>
            </div>
            <div>
                <label>Expires</label>
                <date-span value="{{doc.expires}}"></date-span>
            </div>
        </li>
    </ul>
    
</div>
