<li>
    <label>
        Enable Blog Post system
    </label>
    <input-switch name="Posts.default_enabled" checked="{{value.default_enabled}}"></input-switch>
    {{!reset}}
</li>
<li>
    <label>Post link name</label>
    <input name="Posts.default_name" value="{{value.default_name}}">
    @view("Cobalt/Settings/templates/settings/inputs/reset.php", ['setting' => 'Posts.default_name', 'name' => $this->vars['name'], 'value' => $this->vars['value']]);
</li>
