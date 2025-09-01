<li class="settings-panel--setting settings-panel--{{setting}}">
    <div class="settings-panel--description">
        <label>{{!name}}{{!help}}</label>
        {{!small}}
        {{!reset}}
    </div>
    <input-password type='password' name='{{setting}}' {{disabled}} autocomplete="new-password"></input-password>
</li>