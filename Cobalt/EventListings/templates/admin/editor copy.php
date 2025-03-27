<form-request id="event-editor" method="{{method}}" action="{{action}}" {{!autosave}}>
<fieldset>
    <legend>Display Control</legend>
    <ul class="list-panel">
        <li>
            <label style="width: auto">Public Index Status<help-span value="Determines if this event is elligible for display on the optional Public Event Index"></help-span></label>
            {{doc.advanced.public_index.field()}}
            <small>The Public Event Index is an optional listing of upcoming events marked as "Displayed."</small>
        </li>
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
    </ul>
    {{!submit_button}}

</fieldset>
</form-request>