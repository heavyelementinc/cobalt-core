class Shadowbox {
    constructor(group, firstElement) {
        this.group = group;
        this.currentElement = firstElement;
        this.container = document.createElement("div");
        this.body = document.createElement("div");
        this.btnClose = document.createElement("button");
        this.btnPrev = document.createElement("button");
        this.btnNext = document.createElement("button");
    }

    initUI() {
        this.container.classList.add("shadowbox-container");
        this.container.appendChild(this.btnClose);
        this.btnClose.innerHTML = "<i name='close'></i>";

        this.container.appendChild(this.btnPrev);
        this.btnPrev.innerHTML = "<i name='chevron-left'></i>";
        this.btnPrev.value = -1;
        this.btnPrev.addEventListener("click", (event) => {
            this.handleImageChange(event);
        });

        this.container.appendChild(this.body);
        this.body.classList.add("shadowbox-body");
        this.body.innerHTML = "<img>";
        this.img = this.body.querySelector("img");

        this.container.appendChild(this.btnNext);
        this.btnNext.innerHTML = "<i name='chevron-right'></i>";
        this.btnNext.value = 1;
        this.btnNext.addEventListener("click", (event) => {
            this.handleImageChange(event);
        });
    }


    updateImage() {
        const img = modal.dialog.querySelector("img");
        console.log(evt);
        let galleryIndex = currentElement.value + evt.currentTarget.value;
        if(group) {
            currentElement = document.querySelector(`[data-group='${group}'][data-group-index='${galleryIndex}']`);
            if(currentElement === null) {
                galleryIndex = (galleryIndex < 0) ? 0 : document.querySelectorAll(`[data-group='${group}']`).length - 1;
                currentElement = document.querySelector(`[data-group='${group}'][data-group-index='${galleryIndex}']`);
            }
        }
        
        let src = currentElement.dataset.mediaSrc;
        img.src = src;
    }
}

var currentElement = element;

    function updateImage(evt){
        
    }

    let lightbox_content = `<img>`;
    // const ytContent = lightbox_content = `<iframe width="560" height="315" src="https://www.youtube.com/embed/${imageUrl.split(".be/")[1]}" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
    // if (imageUrl.indexOf("youtube.com") !== -1) lightbox_content = ytContent;
    // if (imageUrl.indexOf("youtu.be") !== -1) lightbox_content = ytContent;

    const modal = new Modal({
        parentClass: "shadowbox",
        body: lightbox_content,
        chrome: null,
        animate: true,
        clickoutCallback: e => true,
    });
    await modal.draw();

    if(group) {
        const btnPrev = document.createElement("button");
        btnPrev.innerHTML = "<i name='chevron-left'></i>";
        btnPrev.classList.add("shadowbox-button", "previous");
        btnPrev.value = -1;
        const btnNext = document.createElement("button");
        btnNext.innerHTML = "<i name='chevron-right'></i>";
        btnNext.classList.add("shadowbox-button", "next");
        btnNext.value = 1;
        btnPrev.addEventListener("click", evt => updateImage(evt));
        btnNext.addEventListener("click", evt => updateImage(evt));
        modal.container.insertBefore(btnPrev, modal.dialog);
        modal.container.appendChild(btnNext);
    }

    updateImage({currentTarget: {value: 0}});

    return modal;