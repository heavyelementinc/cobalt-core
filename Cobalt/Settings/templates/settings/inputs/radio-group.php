<li class="settings-panel--setting settings-panel--{{setting}} settings-panel--radio-group">
    <div class="settings-panel--description">
        <label>{{!name}}{{!help}}</label>
        {{!small}}
        {{!reset}}
    </div>
    <radio-group class="pretty-select" name="{{setting}}" value="{{value}}">
        {{!options}}
    </radio-group>
</li>
