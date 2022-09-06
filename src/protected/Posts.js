class PostManager {
    constructor(id) {
        this.id = id;

        this.initUI();
    }

    initUI() {
        const body = document.querySelector("textarea[name='body']"),
            gallery = document.querySelector(".cobalt--fs-directory-listing.cfs--picture-gallery");

        gallery.addEventListener("dragstart", (e) => {
            e.dataTransfer.setData("text", `![](${String(location.origin)}${e.target.getAttribute("full-resolution")})`);
        });

    }
}