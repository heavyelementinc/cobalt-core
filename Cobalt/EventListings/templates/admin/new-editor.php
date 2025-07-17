<hgroup>
    <h1 id="internal_name">{{title}}</h1>

    <action-menu id="more-menu">
        {{!delete_option}}
        <option method="DELETE" action="" dangerous="true">Delete</option>
    </action-menu>
</hgroup>
<form-request id="event-editor" method="{{method}}" action="{{action}}" {{!autosave}}>
    <tab-nav>
        <nav>
            <a href="#basic"><i class="information-outline"></i> Common Details</a>
            <a href="#content"><i class="edit"></i> Content</a>
            <a href="#styling"><i class="edit"></i> Styling</a>
            <a href="#public"><i class=""></i> Public</a>
            <a href="#advanced"><i class="cog"></i> Advanced</a>
        </nav>
        <div id="basic">
            <ul class="list-panel">
                <li>
                    <label>Internal Name <help-span value="This is for internal reference only and is not displayed publicly. HOWEVER, this field is not entirely hidden from the public. Do not put sensitive info in this field."></help-span></label>
                    {{doc.event_name.field()}}
                    <small>Do not store sensitive information in this field!</small>
                </li>
                <li>
                    {{doc.type.getLabel()}}
                    {{doc.type.field()}}
                </li>
                <li>
                    {{doc.start_date.getLabel()}}
                    <small>Start times are when the event starts displaying on the website.</small>
                    {{doc.start_date.field()}}
                </li>
                <li>
                    {{doc.end_date.getLabel()}}
                    <small>End times are when the event ceases to be displayed on the website.</small>
                    {{doc.end_date.field()}}
                </li>
            </ul>
        </div>
        <div id="content">
            <ul class="list-panel">
                <li>
                    {{doc.headline.getLabel()}}
                    {{doc.headline.field()}}
                </li>
                <li>
                    {{doc.body.getLabel()}}
                    {{doc.body.field()}}
                </li>
                <li>
                    {{doc.call_to_action_prompt.getLabel()}}
                    <small>The user will be presented with a button. This is the text of that button.</small>
                    {{doc.call_to_action_prompt.field()}}
                </li>
                <li>
                    {{doc.call_to_action_href.getLabel()}}
                    <small>When the user clicks the Call to Action button, this is where they'll be taken. May be a relative link or another URL.</small>
                    {{doc.call_to_action_href.field()}}
                </li>
                <li>
                    {{doc.btnColor.getLabel()}}
                    <small>Choose the background color of the 'Call To Action' button. A contrasting text color will be automatically assigned (either black or white).</small>
                    {{doc.btnColor.field()}}
                </li>
            </ul>
        </div>
        <div id="styling">
            <ul class="list-panel">
                <li>
                    <label>Background Color <help-span value="Choose the background color of your event."></help-span>
                    </label>
                    <div class="hbox">
                        {{doc.bgColor.field()}}
                    </div>
                </li>
                <li>
                    <label>Text Color <help-span value="Choose the text color for your event.">
                        </help-span></label>
                    <div class="hbox">
                        {{doc.txtColor.field()}}
                    </div>
                </li>
                <li>
                    <label>Text Justification <help-span value="This will have no effect if there is a Call to Action button."></help-span></label>
                    <radio-group name="txtJustification" value="{{doc.txtJustification}}">
                        <label>
                            <i name="format-align-left"></i>
                            <input type='radio' name='txtJustification' value='space-between' {{disabled}}>
                        </label>
                        <label>
                            <i name="format-align-center"></i>
                            <input type='radio' name='txtJustification' value='center' {{disabled}}>
                        </label>
                        <label>
                            <i name="format-align-right"></i>
                            <input type='radio' name='txtJustification' value='flex-end' {{disabled}}>
                        </label>
                    </radio-group>
                </li>
            </ul>
        </div>
        <div id="public">
            <ul class="list-panel">
                <li>
                    <label style="width: auto">Public Index Status<help-span value="Determines if this event is elligible for display on the optional Public Event Index"></help-span></label>
                    {{doc.public_index.field()}}
                    <small>The Public Event Index is an optional listing of upcoming events marked as "Displayed."</small>
                </li>
                <li>
                    <label>Public Headline</label>
                    {{doc.public_head.field()}}
                </li>
                <li>
                    <label>Public Content</label>
                    {{doc.public_body.field()}}
                </li>
                <li>
                    <label>Event Image</label>
                    {{doc.public_image.field()}}
                </li>
            </ul>
        </div>
        <div id="advanced">
            <ul class="list-panel">
                <li>
                    <label>Included paths <help-span
                            value="If the user has navigated to a path which matches one of the entries on this list, this event will be considered 'showable'. Leave blank to ignore.">
                        </help-span>
                    </label>
                    {{doc.advanced.included_paths.field()}}
                </li>
                <li>
                    <label>Excluded paths <help-span
                            value="If the URL path name matches an entry in this list, then the path will be considered excluded and the event will not be shown. Leave blank to ignore.">
                        </help-span>
                    </label>
                    {{doc.advanced.excluded_paths.field()}}
                </li>
                <li>
                    <switch-container>
                        <label>
                            Exclusive <help-span value="An exclusive event will prevent other events from displaying. Turning this off will allow other events to display at the same time as this one. Be careful!"></help-span>
                        </label>
                        {{doc.advanced.exclusive.field()}}
                    </switch-container>
                </li>
                <li>
                    <label>Delay time <help-span value="Number of seconds to wait until this event gets showed. Max is 90.">
                        </help-span></label>
                        {{doc.advanced.delay.field()}}
                </li>
                <li>
                    <label>Container ID <help-span
                            value="The container of this event will be given this ID. (Useful if you want to bind CSS to this event)">
                        </help-span>
                    </label>
                    {{doc.container_id.field()}}
                </li>
                <li>
                    <switch-container>
                        <label>Changes override "display again" policy <help-span value="When this box is checked, any changes you make here will trigger this event to be shown again to the end user, even if the 'Display Again' timout hasn't expired for end users who have 'seen' this event."></help-span></label>
                        {{doc.changes_override.field()}}
                    </switch-container>
                </li>
                <li>
                    <label>"Display Again" Policy</label>
                    <small>When to display the event again after closure.</small>
                    <div class="hbox">
                        {{doc.session_policy.field()}}
                        {{doc.session_policy_hours.field()}}
                    </div>
                </li>
            </ul>
        </div>
    </tab-nav>
</form-request>
