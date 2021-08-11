class PaginatedContainer {
    constructor(container = null, steps = null, globalAdvance = true) {
        this.classes = {
            next: "paginated-container--next",
            prev: "paginated-container--prev",
            current: "paginated-container--current",
        };
        this._globalAdvance = true;
        this._steps = {};
        this._container = null;
        this._registered = {
            steps: false,
            container: false
        };

        (container) ? this.registerContainer(container) : null;
        (steps) ? this.registerSteps(steps) : null;

        if (this._registered.steps && this._registered.container) this.init();
    }

    registerContainer(container) {
        if (typeof container === "string") container = document.querySelector(container);
        this._container = container;
        this._container.classList.add("paginated-container--parent");
        this._registered.container = true;

        this._container.slides = document.createElement("div");
        this._container.slides.classList.add("paginated-container--slides");

        if (!this._globalAdvance) return;
        this._button_row = document.createElement("div");
        this._button_row.classList.add("paginated-container--button-row");

        this._regress = document.createElement("button");
        this._regress.innerText = "Back";
        this._regress.classList.add("paginated-container--button", "button", "paginated-button--back");
        this._regress.addEventListener("click", e => {
            this.regress(this._steps[this._current]._previous);
        });
        this._button_row.appendChild(this._regress);

        this._advance = document.createElement("button");
        this._advance.innerText = "Next";
        this._advance.classList.add("paginated-container--button", "button", "paginated-button--next");
        this._advance.addEventListener("click", e => {
            this.progress(this._steps[this._current].next);
        });

        this._button_row.appendChild(this._advance);

        this._container.appendChild(this._button_row);
    }

    registerSteps(steps) {
        for (const i of steps) {
            this.registerStep(i);
        }
        this._registered.steps = true;
    }

    registerStep(step) {
        if (step.name === null) throw new Error("Step must have a name!");
        this._steps[step.name] = {
            name: null, // ✅ The internal name of the slide
            target: null, // ✅ Some selector
            url: null, // Some URL

            next: null, // ✅ The `name` of the next item
            // error: "",   // The `name` of the item
            // advance: null,  // Reference to advance button
            // regress: null,  // Reference to regress button
            canProceed: false, // ✅ Allowed to proceed?
            canRegress: false, // ✅ Allowed to go backwards?

            init: (step, thisReference) => { }, // Executed when either selector is matched OR url is downloaded
            onEnter: (step, thisReference) => { }, // Get things ready when entering the slide
            onLeave: (step, thisReference) => { }, // Housekeeping exiting the current slide
            onAdvance: (step, thisReference) => { },

            ...step, // Include the steps

            _container: null, // Assigned once we've found it
            _previous: null, // ✅ Assigned when entered and cannot otherwise be defined
        };
        if (step.target !== null) this.initSlide(this._steps[step.name]);
        this._registered.steps = true;
    }

    sanityCheck() {
        const insane = {};
        for (const i in this._steps) {
            const l = this._steps[i]
            if (!l.target && !l.url) insane[i].target = "No viable targets";
            if (i in insane) throw new Error(`${l.name} has errors: ${Object.values(insane[i]).join(". ")}`);
        }
    }

    init() {
        this.sanityCheck();
        if (!this._registered.steps && !this._registered.container) throw new Error("You haven't given us enough to work with!");
        let initialStep = "start";
        if ("start" in this._steps == false) this._steps[Object.keys(this._steps)[0]];
        this._current = initialStep;
        this.progress(initialStep);
    }


    async initSlide(step, previous = null) {
        step._previous = (previous !== step.name) ? previous : null;

        if (!step._container) {
            if ("url" in step && step.url) {
                step._container = document.createElement("div");
                this._container.appendChild(step._container);
                step._container.innerHTML = `<loading-spinner></loading-spinner>`;

                step._container.innerHTML = await this.getUrl(step.url);
                this.heights();
            }
            else step._container = this._container.querySelector(step.target);
        }
        step._container.classList.add("paginated-container--step");

        step.init(step, this);
    }

    async progress(key) {
        if (key in this._steps !== true) throw new Error("No valid entry");
        let step = this._steps[key];
        this.initSlide(step, this._current);
        this.go(key, true);
    }


    async regress(key) {
        this.go(key, false);
    }

    async go(next, forwards = true) {
        this._advance.disabled = true;
        this._regress.disabled = true;
        const dir = ["prev", "next"];
        let lastClass = dir[Number(!forwards)],
            newClass = this.classes[dir[Number(forwards)]],
            current = this._current
        // if (!forwards) newClass = "next";
        this._steps[current].onLeave(this._steps[current], this);

        if (forwards === true) {
            this._steps[current].onAdvance(this._steps[current], this);
        }

        this._steps[current]._container.classList.remove(this.classes.current);
        wait_for_animation(this._steps[current]._container, newClass, true);
        // console.log(newClass);

        this._steps[next]._container.classList.add(newClass);
        this.heights(next);
        await wait_for_animation(this._steps[next]._container, this.classes.current, false);
        this._steps[next]._container.classList.remove(newClass); // Cleanup

        this._current = next;

        this._steps[this._current].onEnter(this._steps[this._current], this);

        this.buttons();
    }

    heights(key = null) {
        if (!key) key = this._current;
        this._container.style.height = `calc(${this._steps[key]._container.offsetHeight}px + var(--button-row-height))`;
    }

    buttons() {
        if (this._steps[this._current].canProceed) this._advance.disabled = false;
        if (this._steps[this._current].canRegress && this._steps[this._current]._previous !== null) this._regress.disabled = false;
    }

    async getUrl(url) {
        try {
            const page = new ApiFetch(`/api/v1/page/?route=${url}`, 'GET', {});
            let body = await page.get();
            // this.pageTitle = document.title
            // document.title = body.title
            return body.body;
        } catch (error) {
            return error.result.error;
        }
    }


}