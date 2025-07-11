<div class="event--button-container" href="#{{doc._id}}">
    <div class="event--headline-container">
        <div>
            <h1 class="event--headline">{{doc.headline}}</h1>
            <date>{{!doc.happening_now.display}} <date-span value="{{doc.start_time}}" format="D M jS g:i A"></date-span> &mdash; <date-span value="{{doc.end_time}}" format="M jS g:i A"></date-span></date>
        </div>
        <a class="button event--button" href="{{doc.call_to_action_href}}">{{doc.call_to_action_prompt}}</a>
    </div>
    <div class="event--body event--hidden-content">
        <?= from_markdown($this->vars['doc']->body) ?>
    </div>
</div>


<style>
    .event--button-container {
        display: block;
    }

    .event--headline-container {
        display: flex;
        justify-content: start;
        align-items: center;
        gap: 1em;
    }
    .event--button {
        margin-left: auto;
    }

    .events--happening-now::after {
        content:"";
        display: inline-block;
        border-radius: 50%;
        height: .7em;
        width: .7em;
        background-color: var(--project-color-inactive);
    }

    .events--happening-now.events--active-event::after {
        background-color: var(--project-color-active)
    }
</style>
