class SplitButton extends HTMLElement {
    constructor() {
        super();
        this.initObserver();
        this.setAttribute("__custom-input", "true");
    }

    connectedCallback() {
        this.main = this.querySelector("button, async-button");
        if(!this.main) {
            this.main = this.querySelector("option");
            this.main.parentNode.removeChild(this.main);
            this.main = this.initButton(this.main);
        }
        this.initSplit();
    }

    disconnectedCallback() {

    }

    menuFromOptions(event = {preventDefault: () => {},target: this}) {
        const options = this.querySelectorAll("option");
        const menu = new ActionMenu({event})
        for(const opt of options) {
            this.actionItem(opt, menu);
        }
        menu.draw();
    }

    actionItem(option, menu) {
        if(option.onclick) return this.optionCallback(option, menu);
        if(option.hasAttribute("action")) return this.optionRequest(option, menu);
        return menu.registerAction({
            label: option.innerHTML,
            icon: option.getAttribute("icon") || null,
        });
    }

    optionRequest(option, menu) {
        return menu.registerAction({
            label: option.innerHTML,
            icon: option.getAttribute("icon") || null,
            request: {
                method: option.getAttribute("method") || "POST",
                action: option.getAttribute("action"),
                value: JSON.parse(option.getAttribute("value"))
            }
        });
    }

    optionCallback(option, menu) {
        return menu.registerAction({
            label: option.innerHTML,
            icon: option.getAttribute("icon") || null,
            callback: () => {
                option.onclick();
                return true;
            }
        });
    }

    initButton(option, type = "async-button") {
        const button = document.createElement(type);
        button.innerHTML = option.innerHTML;
        if(option.onclick) {
            button.onclick = option.onclick;
        }
        if(option.hasAttribute("action")) {
            button.action = option.getAttribute("action");
            button.method = option.getAttribute("method") || "POST";
        }
        this.prepend(button);
        return button;
    }

    initSplit() {
        const button = document.createElement("button");
        button.setAttribute("native","");
        button.innerHTML = "<i name='chevron-down'></i>";
        button.classList.add("split-button--additional-options");
        this.insertBefore(button, this.main.nextElementSibling);
        
        button.addEventListener("click", event => {
            event.preventDefault();
            this.menuFromOptions(event);
        });
        
        return button;
    }

    initObserver() {
        const boolby = true;
        if(boolby) return
        const config = {attributes: false, childList: true, subtree: false};

        const callback = (mutationList, observer) => {
            for(const mutation of mutationList) {
                if(mutation.type === "childList") return;
            }
        }
        this.observer = new MutationObserver(callback)
        this.observer.observe(this, config);
    }
}

customElements.define("split-button", SplitButton);
