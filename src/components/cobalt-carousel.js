/** # Cobalt Carousel
 * @description An infinitely-scrolling carousel with buttons
 * @note Make sure you limit white space in this element as it can throw off alignment of fake targets going forward.
 * 
 * @element - <cobalt-carousel>
 * 
 * @attribute scroll [null]|"drag"|"drag-scroll" - Using "drag" or "drag-scroll" will disable buttons and allow the user to drag scroll the carousel with their mouse
 * @attribute center-align [null]|"false" - Using "false", you can prevent fake elements from being appended/prepended to the validTargets (prevents smooth infinite scrolling)
 * @attribute autoscroll [null]|Integer - Specifying an integer will delay a duration in milliseconds to wait before autoscrolling to the next item in the list.
 * @attribute height [null]|Integer - Specify an integer to apply a uniform height to all scrollable elements.
 * @attribute fit ["cover"]|"contain" - Changes how img, picture, and video tags fit their contents in available space.
 * @attribute sizing ["uniform-min"]|"uniform-max"|"natural" - Changes how img, picture, and video tags are styles versus each other
 * 
 * @copyright 2022 Heavy Element, Inc.
 * @author Gardiner Bryant
 */
class CobaltCarousel extends HTMLElement {
    constructor() {
        super();
        this.visible = "auto";    // Visible
        this.container = null;    // The scrollable container that items are moved into.
        this.validTargets = null; // The child elements that are meant to be scrolled through.
        this.index = null;        // The index into the validTargets array that is currently visible.
        this.pagination = true;   // Controls a yet-to-be-implemented pagination routine.
        this.buttons = true;      // Controls if next/previous buttons are to be displayed.
    }

    observedAttributes() {
        return ['height', 'fit', 'sizing'];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        switch(name) {
            case "height":
                if(newValue && newValue.toString()[newValue.toString().length - 1] !== "x") newValue = `${newValue}px`;
            case "fit":
                this.updateCSSVar(name, newValue);
                break;
            case "sizing":
                this.updateSizing(name, newValue);
                break;
        }
    }

    updateCSSVar(name, value) {
        this.style.setProperty(`--${name}`, value);
    }

    async updateSizing(name, value) {
        let promises = [];
        let targets = [];
        this.validTargets.forEach(el => {
            let target = null;
            if(["IMG","PICTURE","VIDEO"].includes(el.tagName)) target = el;
            else {
                const element = el.querySelector("img, picture, video");
                if(element) target = element;
            }
            if(target) promises.push(new Promise((resolve, reject) => {
                target.addEventListener("load", e => resolve());
                target.addEventListener("error", e => resolve());
                if(target.complete === true) resolve();
            }));
            targets.push(target);
        });

        await Promise.all(promises);

        let sizes = [];

        targets.forEach(el => {
            sizes.push(el.getAttribute("height") || el.naturalHeight);
        });

        const max = Math.max(...sizes),
        min = Math.min(...sizes);

        console.log({min, max, sizes, targets});

        switch(value) {
            case "natural":
            case "initial":
                if(!this.getAttribute("height")) this.style.removeProperty("--fit");
                // if(!this.getAttribute("height")) this.style.removeProperty("--height");
            case "uniform-max":
                if(!this.getAttribute("height")) this.attributeChangedCallback("height", null, max.toString());
                break;
            case "uniform-min":
            default:
                if(!this.getAttribute("height")) this.attributeChangedCallback("height", null, min.toString());
                break;
        }
    }

    connectedCallback() {
        this.attributeChangedCallback("fit",null,this.getAttribute("fit"));
        this.attributeChangedCallback("height",null,this.getAttribute("height"))
        // Let's initialize our element by moving the children of the element into the container
        this.index = 0;
        this.container = document.createElement("div");
        this.container.classList.add("cobalt-carousel--scroll-container");
        this.container.innerHTML = this.innerHTML; // Moving elements of this element into container
        this.innerHTML = ""; // Deleting everything in this element
        this.append(this.container); // Append the container to this element

        // Now let's add a scroll event so we can fire our custom event when scrolling stops.
        this.container.addEventListener("scroll", () => {
            clearTimeout(this.scrollStopTimeout);
            this.scrollStopTimeout = setTimeout(() => this.container.dispatchEvent(new CustomEvent("scrollend")), 150);
        });

        // Let's instantiate the valid targets of this carousel
        // We're spreading the children property so we break the pointer to the
        // children property (this gets modified later)
        this.validTargets = [...this.container.children];

        this.attributeChangedCallback("sizing",null,this.getAttribute("sizing"));
        // This html attribute changes the behavior of the element.
        if(this.getAttribute('center-align') !== "false") this.initFakeElements();

        // This makes the container scrollable via dragging
        if(this.getAttribute('scroll') && ['drag', 'drag-scroll'].includes(this.getAttribute('scroll'))) {
            this.initDragToScroll();
            this.buttons = false;
            this.pagination = false;
        }
        
        // Initialize our buttons
        if(this.buttons) this.initNextPrevButtons();
        
        // A yet-to-be-implemented pagination routine
        // if(this.pagination) this.initPagination();

        // Check if we're supposed to autoscroll through results.
        this.autoScroll = parseInt(this.getAttribute("autoscroll")) || null;
        if(this.autoScroll) this.invokeAutoScroll();

        this.updateScroll();
        
    }

    initNextPrevButtons() {
        const next = document.createElement("button"),
        prev = document.createElement("button");

        next.classList.add("cobalt-carousel--button", "cobalt-carousel--next");
        prev.classList.add("cobalt-carousel--button", "cobalt-carousel--prev");

        next.addEventListener("click", evt => {
            this.scrollButton(1);
            this.updateScroll();
        });
        prev.addEventListener("click", evt => {
            this.scrollButton(-1);
            this.updateScroll();
        });

        this.prepend(prev);
        this.append(next);
    }

    scrollButton(direction = 1) {
        this.index += direction;
        // Prevent overflow
        if(this.index >= this.validTargets.length && direction >= 1) {
            this.index = 0;
            this.jumpScroll = this.firstClone;
        }
        if(this.index < 0 && direction <= -1) {
            this.index = this.validTargets.length - 1;
            this.jumpScroll = this.lastClone;
        }
    }

    async updateScroll(fakeElement = null) {
        this.classList.add("locked");
        clearTimeout(this.timeout);

        // Clean up the current target
        const currentTargetClass = "cobalt-carousel--current-scroll-target";
        const currentTargetElements = this.container.querySelectorAll(`.${currentTargetClass}`);

        currentTargetElements.forEach(e => e.classList.remove(currentTargetClass));

        // Select the current element
        let element = this.validTargets[this.index];
        element.classList.add(currentTargetClass); // Add the current target class
        if(this.jumpScroll) element = this.jumpScroll; // Let's get ready for a jump scroll
        element.classList.add(currentTargetClass); 

        let elementCenter = this.containerOffset(element);

        await this.waitForScrollComplete(elementCenter);

        this.invokeAutoScroll();

        if(this.jumpScroll) {
            this.container.style.scrollBehavior = "auto";
            let offset = this.containerOffset(this.validTargets[this.index]);
            this.container.scrollLeft = offset;
            this.container.style.removeProperty("scroll-behavior");
            this.jumpScroll = false;
        }
        this.classList.remove("locked");
    }

    waitForScrollComplete(offset) {
        return new Promise((resolve, reject) => {
            this.container.addEventListener("scrollend",() => {
                resolve();
                
            }, {once: true});
            this.container.scrollLeft = offset;
            // setTimeout(() => resolve(),400);
        });
    }

    containerOffset(element) {
        const elementOffset = get_offset(element);
        const elementOffsetFromX = elementOffset.x;
        const elementCenter = elementOffset.w * .5;

        const containerOffset = get_offset(this.container);
        const containerOffsetFromX = containerOffset.x;
        const containerCenter = containerOffset.w * .5;
        const finalScrollPosition = elementOffsetFromX - containerOffsetFromX;

        return finalScrollPosition - elementCenter;
    }


    invokeAutoScroll() {
        if(this.autoScroll) this.timeout = setTimeout(() => {
            this.scrollButton(1);
            this.updateScroll();
        }, this.autoScroll);
    }


    initDragToScroll() {
        this.classList.add("cobalt-carousel--draggable");
        this.addEventListener('mousedown', this.mouseDownHandler.bind(this));
    }

    mouseDownHandler(evt) {
        this.classList.add("grabbing");

        const position = {
            left: this.scrollLeft,
            top: this.scrollTop,
            x: evt.clientX,
            y: evt.clientY
        }

        const mouseMoveHandler = (evt) => {
            const deltaX = evt.clientX - position.x;
            const deltaY = evt.clientY - position.y;
            
            this.scrollTop = position.top - deltaY;
            this.scrollLeft = position.left - deltaX;
        }

        const mouseUpHandler = (evt) => {
            document.removeEventListener('mousemove', mouseMoveHandler);
            document.removeEventListener('mouseup', mouseUpHandler);
    
            this.classList.remove("grabbing");
        }

        document.addEventListener('mousemove', mouseMoveHandler);
        document.addEventListener('mouseup', mouseUpHandler);
    }


    initFakeElements() {
        // Clone nodes
        const fakeClass = "cobalt-carousel--fake-entry",
        firstClone = this.validTargets[0].cloneNode(true),
        secondClone = this.validTargets[1].cloneNode(true),
        penultimateClone = this.validTargets[this.validTargets.length - 2].cloneNode(true),
        lastClone = this.validTargets[this.validTargets.length - 1].cloneNode(true);
        firstClone.classList.add(fakeClass);
        secondClone.classList.add(fakeClass);
        penultimateClone.classList.add(fakeClass);
        lastClone.classList.add(fakeClass);

        this.firstClone = firstClone;
        this.lastClone = lastClone;

        this.container.append(firstClone);
        this.container.append(secondClone);
        this.container.prepend(lastClone);
        this.container.prepend(penultimateClone);
    }
}

customElements.define("cobalt-carousel", CobaltCarousel);