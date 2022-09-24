class CobaltCarousel extends HTMLElement {
    constructor() {
        super();
        this.visible = 3;
    }
    connectedCallback() {
        
    }

    
}

customElements.define("cobalt-carousel", CobaltCarousel);