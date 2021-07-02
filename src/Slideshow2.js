class SlideshowContainer extends HTMLElement {
    constructor() {
        super();
        this.elements = [];
        this.pointer = 0;
        this.validModes = ["defaults"];
        this.mode = this.getAttribute("animation") || "default";

        this.controls = string_to_bool(this.getAttribute("controls")) || false;
        this.breadcrumbs = string_to_bool(this.getAttribute("breadcrumbs")) || true;

        this.playState = string_to_bool(this.getAttribute("play-state")) || true;
        this.delay = this.getAttribute("delay") * 1000 || 5000;
        this.displayClasses = this.classList();
    }

    connectedCallback() {
        this.initSlides();
        this.initBreadcrumbs();
        this.initControls();
        this.mainLoop();
    }

    initSlides() {
        if (!this.validModes.includes(this.mode)) this.mode = "default";
        this.modeClass = `slideshow--mode-${this.mode}`;

        this.elements = [...this.children]; // Don't just pass a reference, create a new list.
        console.log(this.elements);

        this.elements.forEach((e, i) => {
            e.classList.add("slideshow--slide", this.modeClass);
            e.style.setProperty("--z-index", i);
        });
        // this.previous.classList.add(this.displayClasses.previous);
        this.current.classList.add(this.displayClasses.current);
        this.next.classList.add(this.displayClasses.next);
    }

    async mainLoop() {
        if (!this.playState) return;
        return new Promise(async (resolve, reject) => {
            this.reject = reject;
            while (this.playState) {
                // Wait the specific time
                await this.timeout(this.delay);

                await this.progressNext();
            }
        })
    }

    async progressNext() {
        this.querySelectorAll(`.${this.displayClasses.previous}`).forEach(e => {
            e.classList.remove(this.displayClasses.previous);
        });
        // Get the previous and current elements in names that make working
        // with them more intuitive
        const previous = this.current,
            current = this.next

        // Animate our previous and our current elements
        wait_for_animation(previous, this.displayClasses.previous);

        this.querySelectorAll(`.${this.displayClasses.current}`).forEach(e => {
            e.classList.remove(this.displayClasses.current);
        });

        await wait_for_animation(current, this.displayClasses.current, false);

        current.classList.remove(this.displayClasses.next);

        this.pointer++;
        const next = this.next;
        next.classList.add(this.displayClasses.next);
    }

    async timeout(delay) {
        return new Promise((resolve, reject) => {
            this.reject = reject;
            setTimeout(() => {
                resolve();
            }, delay)
        });
    }

    classList() {
        return {
            current: `slideshow--current`,
            previous: `slideshow--previous`,
            next: `slideshow--next`,
        }
    }

    get previous() {
        if (this.pointer - 1 < 0) return this.elements.length - 1;
        return this.elements[this.pointer - 1];
    }

    get current() {
        if (this.pointer >= 0 && this.pointer < this.elements.length) return this.elements[this.pointer];
        throw new Error("The current pointer does not exist in the list");
    }

    get next() {
        if (this.pointer + 1 >= this.elements.length) {
            this.pointer = 0;
        }
        return this.elements[this.pointer + 1];
    }

    pause() {
        this.resolve();
    }

    initBreadcrumbs() {
        if (!this.breadcrumbs) return;

    }

    initControls() {
        if (!this.controls) return;
        let controls = document.createElement("div");
        controls.innerHTML = `
        <button class="pause">Pause</button>
        <button class="next">Next</button>
        <input type='number' value='${this.delay}'>
        `
        controls.querySelector(".pause").addEventListener('click', e => {
            this.playState = !this.playState;
            this.reject();
            if (this.playState === true) this.mainLoop();
        });

        controls.querySelector(".next").addEventListener('click', e => {
            this.playState = false;
            this.reject();
            this.progressNext();
        });

        controls.querySelector("[type='number']").addEventListener("change", e => {
            this.delay = e.target.value;
        });

        this.parentNode.insertBefore(controls, this.nextSibling);
    }

}

customElements.define("slideshow-container", SlideshowContainer);
