<li class="settings-panel--setting settings-panel--switch-container settings-panel--{{setting}}">
    <div class="settings-panel--description">
        <label>{{!name}}{{!help}}</label>
        {{!small}}
        {{!reset}}
    </div>
    <input-switch name='{{setting}}' checked='{{$value}}' {{disabled}}></input-switch>
</li>
