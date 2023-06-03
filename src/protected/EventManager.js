class EventManager {
    constructor(id) {
        this.id = id;
        this.form = document.querySelector("#event-editor");
        this.previewSwitch = document.querySelector("#preview-after-save");
        this.initPreviewSwitch();
        this.menuActions = [
            {
                label: "Preview",
                callback: () => {
                    this.getPreviewFromForm();
                    return true;
                }
            }
        ];
        this.initEstablishedEvent();
        this.initEditor();
    }

    initEditor() {

        this.form.addEventListener("submit", e => {
            if(!this.id && this.newEventNoContentWarning() === false) return;
            e.preventDefault();
        });

        this.form.addEventListener("requestSuccess", e => {
            console.log(e);
            this.previewEvent(e.detail);
        })

        const button = document.querySelector("#more-menu");
        for(const i of this.menuActions) {
            const opt = document.createElement("option");
            opt.innerText = i.label;
            if("callback" in i) opt.onclick = i.callback;
            if("request" in i) {
                opt.setAttribute("method", i.request.method);
                opt.setAttribute("action", i.request.action);
            }
            button.appendChild(opt);
        }
        // headline = document.querySelector("#options");
        // button.appendChild(button);
        // button.addEventListener("click", e => {
        //     // const menu = new ActionMenu({event: e, title: "Manage"});
        //     // this.menuActions.forEach((e) => {
        //     //     e(menu);
        //     // })
        //     // menu.draw();
        // });
    }

    newEventNoContentWarning() {
        const val = this.form.value;
        switch(val.type) {
            case "banner":
                if(!val.headline) this.issueWarning("[name='headline']", "content", "You need to specify a headline!");
                return true;
            case "modal":
                if(!val.body) this.issueWarning("[name='body']", "content", "You need to add some body content!");
                return true;
        }
    }

    issueWarning(query, nav, message) {
        const element = document.querySelector(query);
        if(element == null) return;

        const anchor = document.querySelector(`[href='#${nav}']`);
        const warningIcon = document.createElement("i");
        warningIcon.setAttribute("name", "alert-circle");
        warningIcon.style.color = "var(--project-color-problem)";
        anchor.appendChild(warningIcon);

        anchor.addEventListener("click", (e) => anchor.removeChild(warningIcon), {once: true});
        
        new StatusError({message, id: nav, icon: "alert"});
    }

    initEstablishedEvent(){
        if(!this.id) return;
        this.menuActions.push({
                label: "Delete",
                dangerous: true,
                request: {
                    method: "DELETE",
                    action: `/api/v1/cobalt-events/${this.id}`
                }
            }
        );
    }

    previewEvent(event) {
        if(!this.previewSwitch.checked) return;
        window.CobaltEventManager.initializeEvent(event, true);
    }

    getPreviewFromForm() {
        this.previewEvent(this.form.value);
    }

    resetToDefault(target) {
        target = document.querySelector(`[name='${target}']`)
        const reset = target.getAttribute("default");
        target.value = reset;
    }

    initPreviewSwitch() {
        const prefName = "Events-preview-on-save";
        const value = pref(prefName) || true
        this.previewSwitch.value = JSON.parse(value);
        this.previewSwitch.addEventListener("change", () => {
            window.Preferences.set("cobalt-events--preview-on-save", this.previewSwitch.value);
        });
    }
}
