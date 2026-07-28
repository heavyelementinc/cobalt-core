<hgroup>
    <div class='hrow'>
        <h1>Documentation</h1>
    </div>
    <action-menu>
        {{!action_menu}}
        <option method="DELETE" action="/api/v1/contact/delete/{{doc._id}}">Delete</option>
    </action-menu>
</hgroup>
<form-request id="document-editor" method="{{method}}" action="{{action}}" {{!autosave}}>
    <tab-nav>
        <nav>
            <a href="#basic"><i name="information-outline"></i> Status</a>
            <a href="#advanced"><i name="settings"></i> Advanced</a>
        </nav>
        <div id="basic">
            <ul class="list-panel">
                <li>
                    {{doc.title.getLabel()}}
                    {{doc.title.field()}}
                </li>
                <li>
                    {{doc.status.getLabel()}}
                    {{doc.status.field()}}
                </li>
                <li>
                    {{doc.route.getLabel()}}
                    {{doc.route.field()}}
                </li>
                <li>
                    {{doc.body.getLabel()}}
                    {{doc.body.field()}}
                </li>
            </ul>
        </div>
        <div id="advanced">
            <ul class="list-panel">
                <li>
                    {{doc.privileged.getLabel()}}
                    {{doc.privileged.field()}}
                </li>
            </ul>
        </div>
    </tab-nav>
</form-request>