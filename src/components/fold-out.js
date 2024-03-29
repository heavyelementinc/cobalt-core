/**
 * # FoldOut
 * @element <fold-out>
 * @attribute title [null]|String - Used to determine the name of the fold-out element.
 */
class FoldOut extends HTMLElement {

    connectedCallback() {
        this.toggleClass = "fold-out--closed";
        this.state = this.getState();
        this.initTitleElement();
        this.__height = `${get_offset(this).h}px`;
        
        this.style.setProperty("--height", this.__height);
        this.toggleState(false);
    }

    initTitleElement() {
        this.titleElement = document.createElement("label");
        this.titleElement.innerHTML = `<span>${this.title ?? "Expand"}</span><i></i>`;
        this.titleElement.tabIndex = 0;

        this.prepend(this.titleElement);

        this.style.setProperty("--closed-height",`${this.titleElement.offsetHeight}px`);

        this.titleElement.addEventListener("click",(event) => {
            this.toggleState();
        });
    }
    
    get title() {
        let label = this.getAttribute("label");
        if(!label) label = this.getAttribute("title");
        if(!label) return "Expand";
        return label;
    }

    getState() {
        if(this.classList.contains(this.toggleClass)) return true;
        return (['open','true'].includes(this.getAttribute("open"))) ? true : false;
    }

    toggleState(toggle = true) {
        if(toggle) this.state = !this.state;
        if(this.state) this.openState()
        else this.closeState();
    }

    closeState() {
        this.classList.add(this.toggleClass);
    }

    openState() {
        this.classList.remove(this.toggleClass);
    }
}

customElements.define("fold-out", FoldOut);