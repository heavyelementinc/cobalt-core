class EventManager {
    constructor(id) {
        this.id = id;
        this.form = document.querySelector("#event-editor");
        this.previewSwitch = document.querySelector("#preview-after-save");
        this.menuActions = [
            (menu) => {
                menu.registerAction({
                    label: "Preview",
                    callback: () => {
                        this.getPreviewFromForm();
                        return true;
                    }
                });
            }
        ];
        this.initEditor();
        this.initEstablishedEvent();
    }

    initEditor() {
        this.form.addEventListener("requestSuccess", e => {
            console.log(e);
            this.previewEvent(e.detail);
        })

        const button = document.createElement("button"),
        headline = document.querySelector("#internal_name");
        headline.appendChild(button);
        button.addEventListener("click", e => {
            const menu = new ActionMenu({event: e, title: "Manage", mode: "modal"});
            this.menuActions.forEach((e) => {
                e(menu);
            })
            menu.draw();
        });
    }

    initEstablishedEvent(){
        if(!this.id) return;
        this.menuActions.push((menu) => {
            menu.registerAction({
                label: "Delete",
                dangerous: true,
                request: {
                    method: "DELETE",
                    action: `/api/v1/cobalt-events/${this.id}`
                }
            });
        })
    }

    previewEvent(event) {
        if(!this.previewSwitch.checked) return;
        window.CobaltEventManager.initializeEvent(event, true);
    }

    getPreviewFromForm() {
        this.previewEvent(this.form.value);
    }
}
