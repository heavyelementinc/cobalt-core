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
                <a href="#basic"><i class="information-outline"></i> Basic Info</a>
                <a href="#content"><i class="edit"></i> Content</a>
                <a href="#public"><i class=""></i> Public</a>
                <a href="#advanced"><i class="cog"></i> Advanced</a>
            </nav>
            <div id="basic">
                <fieldset>
                    <legend>Metadata</legend>
                    <ul class="list-panel">
                        <li>
                            <label>Internal Name <help-span value="This is for internal reference only and is not displayed publicly. HOWEVER, this field is not entirely hidden from the public. Do not put sensitive info in this field."></help-span></label>
                            {{doc.name.field()}}
                            <small>Do not store sensitive info in this field.</small>
                        </li>
                        <li>
                            <label>Event Type</label>
                            {{doc.type.field()}}
                        </li>
                        <li>
                            <label>"Display Again" Policy <help-span value="When to display the event again after closure."></help-span>
                                <div class="hbox">
                                    {{doc.session_policy.field()}}
                                    {{doc.session_policy_hours.field()}}
                                </div>
                            </label>
                        </li>
                    </ul>
                </fieldset>
                
                <fieldset>
                    <legend>Start/End&nbsp;Times</legend>
                    <ul class="list-panel">
                        <li>
                            <label>Event Starts <help-span value="Start times are when the event starts displaying on the website."></help-span></label>
                            {{doc.start_date.field()}}
                        </li>
                        <li>
                            <label>Event Ends <help-span value="End times are when the event ceases to be displayed on the website."></help-span></label>
                            {{doc.end_date.field()}}
                        </li>
                    </ul>
                </fieldset>
            </div>
            
            <div id="content">
                <fieldset style="flex-grow:1">
                    <legend>Primary Content</legend>
                    <ul class="list-panel">
                        <li>
                            <label id="charlimit">Headline</label>
                            {{doc.headline.field()}}
                        </li>
                        <!-- oninput="charlimit(120,'#charlimit')" -->
                        <li>
                            <label>Body</label>
                            {{doc.body.field()}}
                        </li>
                        <li>
                            <label>Call to Action Text <help-span
                                    value="The user will be presented with a button. This is the text of that button."></help-span></label>
                                {{doc.call_to_action_prompt.field()}}
                        </li>
                        <li>
                            <label>CTA Link <help-span
                                    value="When the user clicks the Call to Action button, this is where they'll be taken. May be a relative link or another URL.">
                                </help-span>
                            </label>
                            {{doc.call_to_action_href.field()}}
                        </li>
                        
                        <li>
                            <label>CTA Button Color <help-span
                                    value="Choose the background color of the 'Call To Action' button. A contrasting text color will be automatically assigned (either black or white).">
                                </help-span></label>
                            <div class="hbox">
                                {{doc.btnColor.field()}}
                            </div>
                        </li>
                    </ul>
                </fieldset>
                <fieldset>
                    <legend>Styling</legend>
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
                </fieldset>
            </div>
            <div id="public">
                <fieldset>
                    <legend>Public Content</legend>
                    {{doc.public.body.field()}}
                </fieldset>
            </div>
            <div id="advanced">
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
                    </ul>
                </fieldset>
            </div>
            
        </tab-nav>

        <div class="hbox" style="justify-content:flex-end">
            <switch-container>
                <label class="hbox" style="justify-content:left;gap:1ch;">
                        Published <help-span value="In order for an event to be displayed, this switch must be 'on'."></help-span>
                </label>
                <input-switch name="published" checked="{{doc.published}}" tiny></input-switch>
            </switch-container>
            <label>
                <input-switch id="preview-after-save" checked="pref('Events-preview-on-save') || true)" tiny></input-switch>
                Preview on save
            </label>
            {{!submit_button}}
        </div>
    
    </form-request>
<!-- </tab-nav> -->

<details>
    <summary>Learn More About Cobalt Events</summary>
    <p>Cobalt Events are messages that will be showed to visitors of your site.
        This can take the form of banners which stick to the top of the screen
        or modal boxes which pop up over the content in the page.
    </p>
    <h3>Banner vs. Pop up</h3>
    <p>Generally speaking, banners are less intrusive to the end users experience
        on your site while modals will guarantee that the user sees the event.
    </p>
    <p>Too many pop-ups will cause the end user to become annoyed and leave,
        especially if they happen frequently. So, please, only use them for
        important info everyone visiting your site needs to see.
    </p>
    <h3>Tracking "seen" status</h3>
    <p>When an event is displayed to the user the user may click the <em>Call to Action</em> button
        or the close (<i name="close"></i>) button.
    </p>
    <p>Either action will <strong>dismiss</strong> the event dialog and the event
        will be considered "seen." This "seen" status is stored on the user's
        device upon interacting with the event.
    </p>
    <p>The exact time and date of their closing the event dialog is stored along
        with this status. Finally, the event's "last_updated_on" value is also
        stored with this data.
    </p>
    <p>The seen status is stored individually for each event. So if you have two
        (or more) events running at the same time, the user will see the one
        ending most recently unless they've already "seen" it.
    </p>
    <p>Multiple events can be displayed at the same time by unchecking the
        Advanced<i name="arrow-right"></i>Exclusive box. However, this is
        <strong>strongly</strong> discouraged. Especially multiple events of the
        same type (two banners, two pop-ups, etc).
    </p>
    <h3>Will people who have "seen" an Event see it again?</h3>
    <p>Depending on the "Display Again" Policy, the seen status for the device will
        eventually expire. Most of these are self-explanatory. However, one of them
        is not. <em>Half time between close and event end</em> has proved to be unintuitive
        to understand.</p>
    <cite>In this instance, "closing the event" and "marking an event as seen" are used
        interchangably.
    </cite>
        
    <p>Essentially, the user will see the event again once they reach the middle
        point between the time they closed the event and the scheduled time the
        Event Ends.
    </p>
    <blockquote>
        <p>For example:</p>
        <p>If I close an event that is scheduled to end ten days from now, I will
            see it again in five days.</p>
        <p>If I close an event ending in four days, I'd see it again in two days.</p>
        <p>If I close an event at 8 AM that ends at 12PM, I'll see it again at about 10 AM.</p>
    </blockquote>

    <p>The event will <strong>not</strong> be shown to them again until their "seen" status expires
        for this specific event <strong>or</strong> you update the event with the
        Advanced<i name="arrow-right"></i><em>Changes override "display again" policy</em> setting.
    </p>
    <p></p>
</fold-out>

<style>
    fieldset :is(h1, h2, h3) {
        margin-top: 0;
    }

    fieldset .hbox {
        flex-wrap: nowrap;
    }

    #event-edit {
        display: flex;
        flex-wrap: wrap;
    }

    .admin-panel--container {
        padding: .2rem .4rem;
        border: 1px solid var(--project-color-input-border-nofocus);
        border-radius: 4px;
    }

    input[type='color'] {
        min-width: 100%;
        flex-grow: 1;
        height: 36px;
    }
    radio-group {
        display: flex;
        justify-content: space-evenly;
    }
    radio-group > label {
        display: inline-flex;
        justify-content: center;
        flex-direction: column;
    }
</style>

