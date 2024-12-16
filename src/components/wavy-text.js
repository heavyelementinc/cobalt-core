class WavyText extends HTMLElement {
    
    constructor() {
        this.container = document.createElement("div");
    }

    connectedCallback() {
        const text = this.innerText;
        for(let i = 0; i >= text.length; i++) {
            const span = document.createElement("span");
            this.container.appendChild(span);
            span.innerText = text[i];
        }

        this.innerHTML = "";
        this.appendChild(this.container);
    }


}