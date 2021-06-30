class Slideshow {
    /** Provide this class with an array of valid query strings */
    constructor({
        elements,
        timeout = 5000,
        controls = false,
        speedControls = false,
        hideCurrentItemCallback = el => true,
        afterNextCallback = el => true,
        beforeNextCallback = el => true,
        showCallback = el => true,
    }) {
        this.elements = [];
        this.pointer = -1;
        this.timeout = timeout;
        this.hideCurrentItemCallback = hideCurrentItemCallback;
        this.afterNextCallback = afterNextCallback;
        this.beforeNextCallback = beforeNextCallback;
        this.showCallback = showCallback;
        this.inactiveItemClass = "slideshow--inactiveItem";
        this.dissolvePreviousItemClass = "slideshow--dissolvePreviousItem";
        this.beforeQueueItemClass = "slideshow--beforeQueueItem";
        this.queueNextItemClass = "slideshow--queueNextItem";
        this.reset = false;
        this.speed = speedControls;

        // Built in styles
        this.initial_position = "absolute";
        this.initial_top = 0;
        this.initial_left = 0;

        // Start the real work!
        this.add_elements(elements);

        // Enforce parent styling:
        this.elements[0].parentNode.style.position = "relative";

        if (controls) this.controls();
    }

    /** Add a list of elements to then of the current slideshow elements */
    add_elements(elements) {
        // Loop backwards through the provided list of elements
        for (let i = 0; i <= elements.length; i++) {
            // Search for the element
            const query = document.querySelectorAll(elements[i]);
            if (query === null) continue; // If it doesn't exist in the DOM, ignore it.
            for (let e of query) {
                this.init_element(e); // Initialize the element
            }
        }
    }

    /** Initialize and push an element to the slideshow */
    init_element(el) {
        this.elements.push(el);

        // Let's stack our elements
        el.classList.add("slideshow-item", "slideshow--inactiveItem");
    }

    /** Call this to start the interval */
    start() {
        this.next(); // Start now, then wait for the interval
        this.interval = setInterval(() => {
            this.next(); // Shows the next item
        }, this.timeout);
    }

    stop() {
        clearInterval(this.interval);
    }

    /** Will show the next item in the list of elements */
    next() {
        // Get the next valid pointer, current item, and next item.
        const nextPointer = this.get_pointer(1), // When we call this the first time, this will be 0
            currentItem = this.elements[this.get_pointer()], // First time: LAST item in array
            nextItem = this.elements[nextPointer];
        this.pointer = nextPointer; // Update the pointer for the next iteration

        this.showNextItem(nextItem); // Make the next item opaque
        this.prepareForHideCurrentItem(currentItem); // Sets up our transition
        this.hideTheCurrentItem(currentItem); // Make the current item transparent
    }

    /** Returns the next valid index of the current list of elements */
    get_pointer(add = 0) {
        let pointer = this.pointer + add;
        if (pointer >= this.elements.length) return 0;
        if (pointer < 0) return this.elements.length - 1;
        return pointer;
    }

    set_timeout(e) {
        this.stop();
        this.timeout = e.value;
        this.start();
    }

    showNextItem(item) {
        this.showCallback(item);
        item.classList.add("slideshow--queueNextItem");
        item.classList.remove("slideshow--dissolvePreviousItem", "slideshow--inactiveItem");
    }

    prepareForHideCurrentItem(item) {
        this.beforeNextCallback(item); // Sets up our transition
        item.classList.add("slideshow--beforeQueueItem");
        item.addEventListener('transitionend', this.transitionEndHandler, { once: true }); // Add out transitionend listener
        item.addEventListener('animationend', this.transitionEndHandler, { once: true }); // Add out animationend listener
    }

    /** Rmoved the event listener once it's completed */
    transitionEndHandler(e) {
        e.target.removeEventListener('transitionend', this.transitionEndHandler);
        e.target.removeEventListener('animationend', this.transitionEndHandler);
        e.target.classList.remove('slideshow--beforeQueueItem');
        e.target.classList.add('slideshow--inactiveItem');
    }

    hideTheCurrentItem(item) {
        this.hideCurrentItemCallback(item); // Make the current item transparent
        item.classList.add("slideshow--dissolvePreviousItem");
        item.classList.remove("slideshow--queueNextItem");
    }

    controls() {
        // Set our buttons
        let controls = `<button class='slideshow-control slideshow-start' name='start'><ion-icon name='play'></ion-icon></button>
        <button class='slideshow-control slideshow-stop' name='stop'><ion-icon name='stop'></ion-icon></button>
        <button class='slideshow-control slideshow-next' name='next'><ion-icon name='play-skip-forward'></ion-icon></button>`;

        // Create the button container
        const container = document.createElement("section");
        container.classList.add("slideshow-controls");

        // Check if we want our speed indicator
        if (this.speed) controls += `<fieldset><input class='slideshow-control timeout-adjust' type='set_timeout' value='2000'> <label>Milliseconds</label></fieldset>`;

        // Append our buttons to the container
        container.innerHTML = controls;

        // Establish our parent and get everything set up
        const parent = this.elements[0].parentNode;
        parent.appendChild(container);
        container.style.top = `${parent.offsetHeight}px`;
        container.style.left = `${(parent.offsetWidth - container.offsetWidth) / 2}px`

        // Our event listener
        const controlFunc = ev => {
            let name = ev.target.getAttribute('name');
            if (name in this === false) return;
            console.log("Firing " + name)
            this[name](ev);
        };
        // Apply the listener to every element
        parent.querySelectorAll(".slideshow-control").forEach(e => {
            let listenFor = "click";
            if (e.tagName === "INPUT") listenFor = "change";
            e.addEventListener(listenFor, controlFunc);
        });
    }

}