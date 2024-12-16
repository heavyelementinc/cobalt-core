class PageEditor {
    constructor(id) {
        this.form = document.querySelector("#page-editor");
        // Split the method by "/" and select the fourth entry (index 3)
        this.type = form.getAttribute("method").split("/")[3];
        this.TOKEN_VALIDATION = `/api/v1/${this.type}/${id}/`;
        this.validationInterval = setInterval(() => {
            this.validateToken()
        }, 5 * 1000);
    }

    async validateToken() {
        const api = new AsyncFetch(this.TOKEN_VALIDATION, "GET", {
            headers: this.form.headers
        });
        try {
            const result = await api.submit();
            if(result === false) this.tokenFailure();
        } catch (error) {
            this.tokenFailure();
        }
    }

    tokenFailure() {
        const elements = this.form.querySelectorAll(universal_input_element_query);
        elements.forEach(e => {
            e.disabled = true;
            e.ariaDisabled = true;
        });
        modalConfirm(`Another tab (or browser) is editing this article. Editing this post is disabled. To edit this article, refresh this page.`, "I Understand", null);
    }
}