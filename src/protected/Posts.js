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

// class PublicPostManager {
//     constructor(slug) {
//         this.url_slug = slug;
//         this.feed_button = document.querySelector("button.follow-button");
//         this.init()
//     }

//     init() {
//         if(!this.feed_button) return;
//         this.feed_button.addEventListener("click", () => {
//             this.actionMenu()
//         });
//     }

    
// }