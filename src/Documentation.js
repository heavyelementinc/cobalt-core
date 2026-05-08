class Documentation {
    documentationWindow;
    constructor() {
        // this.dialog = document.querySelector("#documentationDialog");
        // if(!this.dialog) return;
        this.button = document.querySelector("#documentation-dialog-button");
        if(!this.button) return;
        this.button.addEventListener("click", e => {
            // switch(this.dialog.open) {
            //     case true:
            //         this.dialog.close();
            //         this.dialog.innerHTML = "<loading-spinner></loading-spinner>";
            //         break;
            //     case false:
            //         this.dialog.show();
            //         break;
            //     }
            this.loadDocumentationIndex();
        });

        document.addEventListener("navigationEvent", e => {
            this.onPageChange();
        });

        this.onPageChange();
    }

    async loadDocumentationIndex() {
        this.documentationWindow = window.open(
            `${this.button.getAttribute("action")}?path=${encodeURIComponent(window.location)}`,
            "documentationContainer",
            "width=400,height=550"
        );

        // const api = new AsyncFetch(this.button.getAttribute("action"),"GET");
        // const result = await api.submit();
        // this.dialog.innerHTML = result.body;
    }

    onPageChange() {
        const counter = this.button.querySelector(".documentation-count");
        if(counter.dataset.value === "0") {
            this.button.disabled = true;
        } else {
            this.button.disabled = false;
        }
    }
}

window.documentation = new Documentation();