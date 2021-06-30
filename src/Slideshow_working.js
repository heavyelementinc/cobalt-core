class Slideshow {
    /** Provide this class with an array of valid query strings */
    constructor({
        elements,
        timeout = 5000,
        hideCallback = el => {
            el.style.opacity = 0;
        },
        afterNextCallback = el => {
            // el.style.visibility = "hidden";
            el.style.transition = "";
        },
        beforeNextCallback = el => el.style.transition = "opacity .5s",
        showCallback = el => {
            el.style.visibility = "";
            el.style.opacity = 1;
        },
    }) {
        this.elements = [];
        this.pointer = 0;
        this.timeout = timeout;
        this.hideCallback = hideCallback;
        this.afterNextCallback = afterNextCallback;
        this.beforeNextCallback = beforeNextCallback;
        this.showCallback = showCallback;
        this.reset = false;

        // Built in styles
        this.initial_position = "absolute";
        this.initial_top = 0;
        this.initial_left = 0;

        // Start the real work!
        this.add_elements(elements);

        // Enforce parent styling:
        this.elements[0].parentNode.style.position = "relative"
    }

    /** Add a list of elements to then of the current slideshow elements */
    add_elements(elements) {
        // Loop backwards through the provided list of elements
        for (let i = elements.length - 1; i >= 0; i--) {
            // Search for the element
            const query = document.querySelector(elements[i]);
            if (query === null) continue; // If it doesn't exist in the DOM, ignore it.
            this.init_element(query); // Initialize the element
        }
        // Now that everything's initialized, they'll all be invisible. We need to make
        // sure that our first element is visible:
        this.showCallback(this.elements[this.pointer])
    }

    /** Initialize and push an element to the slideshow */
    init_element(el) {
        this.elements.push(el);
        this.hideCallback(el);

        // Let's stack our elements
        el.style.position = this.initial_position;
        el.style.top = this.initial_top;
        el.style.left = this.initial_left;
    }

    /** Call this to start the interval */
    start() {
        this.interval = setInterval(() => {
            this.showNextItem(); // Shows the next item
        }, this.timeout);
    }

    /** Will show the next item in the list of elements */
    showNextItem() {
        // Get the next valid pointer, current item, and next item.
        const nextPointer = this.get_pointer(1),
            currentItem = this.elements[this.get_pointer()],
            nextItem = this.elements[nextPointer];

        this.pointer = nextPointer; // Update the pointer for the next iteration

        this.showCallback(nextItem); // Make the next item opaque
        // this.afterNextCallback(nextItem); // Disable the transition for the next item
        this.beforeNextCallback(currentItem); // Sets up our transition
        currentItem.addEventListener('transitionend', this.transitionEndHandler); // Add out transitionend listener
        this.hideCallback(currentItem); // Make the current item transparent
    }

    /** Returns the next valid index of the current list of elements */
    get_pointer(add = 0) {
        let pointer = this.pointer + add;
        if (pointer >= this.elements.length) return 0;
        return pointer;
    }

    /** Rmoved the event listener once it's completed */
    transitionEndHandler(e) {
        e.target.removeEventListener('transitionend', this.transitionEndHandler)
        // afterNextCallback(e.target); // REMOVES our transition
    }

}