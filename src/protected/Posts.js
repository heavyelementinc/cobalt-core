class PostManager {
    constructor(id) {
        this.id = id;
        this.initUI();
    }

    initUI() {
        const gallery = document.querySelector(".cobalt--fs-directory-listing.cfs--picture-gallery");

        if(gallery) {
            gallery.addEventListener("dragstart", (e) => {
                e.dataTransfer.setData("text", `![](${String(location.origin)}${e.target.getAttribute("full-resolution")})`);
            });
        }
    }
}