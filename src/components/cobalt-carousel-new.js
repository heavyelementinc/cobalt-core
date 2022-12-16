/** Cobalt Carousel
 * @description An infinitely-scrolling carousel with buttons
 * @note Make sure you limit white space in this element as it can throw off alignment of fake targets going forward.
 * 
 * @element - <cobalt-carousel>
 * 
 * @attribute auto [null]|Integer - Specifying an integer will delay a duration in milliseconds to wait before autoscrolling to the next item in the list.
 * @attribute max-height [null]|Integer - Specify an integer to apply a uniform height to all scrollable elements.
 * @attribute fit ["cover"]|"contain" - Changes how img, picture, and video tags fit their contents in available space.
 * @attribute sizing ["uniform-min"]|"uniform-max"|"natural" - Changes how img, picture, and video tags are styles versus each other
 * 
 * @copyright 2022 Heavy Element, Inc.
 * @author Gardiner Bryant
 */

class CobaltCarousel extends HTMLElement {
    constructor() {
        super();
        this.validTargets = null;
        this.sizingPromises = [];
        this.index = null;
        this.pagination = true;
        this.buttons = false;
    }

    connectedCallback() {
        this.previousScrollPosition = 0;
        this.scrollDirection = 1;
        this.initTargetContainer();
        
        // Now let's add a scroll event so we can fire our custom event when scrolling stops.
        this.container.addEventListener("scroll", () => {
            clearTimeout(this.scrollStopTimeout);
            this.scrollDirection = (this.previousScrollPosition < this.container.scrollLeft) ? 1 : -1;
            this.previousScrollPosition = this.container.scrollLeft;
            this.scrollStopTimeout = setTimeout(() => this.container.dispatchEvent(new CustomEvent("scrollend")), 150);
        });
        this.initValidTargets();
        this.initFakeElements();

    }

    initTargetContainer() {
        // Let's initialize our element by moving the children of the element into the container
        this.index = 0;
        this.container = document.createElement("div");
        this.container.classList.add("cobalt-carousel--scroll-container");
        this.container.innerHTML = this.innerHTML; // Moving elements of this element into container
        this.innerHTML = ""; // Deleting everything in this element
        this.append(this.container); // Append the container to this element
    }

    initValidTargets() {
        // Let's instantiate the valid targets of this carousel
        // We're spreading the children property so we break the pointer to the
        // children property (this gets modified later)
        this.validTargets = [...this.container.children];

        // Initialize our mutation observer
        if(!this.elementObserver) this.elementObserver = new MutationObserver(e => {
            console.log(e);
            e[0].target.style.setProperty("--position". e[0].target.getAttribute("position"));
        });

        const options = {
            attributes: true,
            attributeFilter: ["position"]
        }
        
        this.validTargets.forEach(el => {
            el.classList.add("cobalt-carousel--carousel-item");
            el.style.setProperty("--position",el.getAttribute("position") || "");
            this.elementObserver.observe(el, options);
        });
        
        this.observeIntersection();
    }

    observeIntersection() {
        let options = {
            root: this.container,
            rootMargin: '0px',
            threshold: 1
        }

        this.observe = new IntersectionObserver((element) => {
            // if(!this.hasAttribute("scroll")) return;
            this.observationTarget(element[0].target);
        }, options);
        
        for(const el of this.container.children) {
            this.observe.observe(el);
        }
    }

    observationTarget(target) {
        console.log(target);
        if(this._observationTargetIgnoreScroll) return;
        
        this.validTargets.forEach((e, i) => {
            // if(e === target) this.scrollButton(this.scrollDirection, i);
        });

        
        this.setCurrentTarget(target);

        if("reference" in target) {
            this.jumpScrollClonePointer = target.reference;
            this.performJumpScroll();
        }
    }

    setCurrentTarget(element) {
        // Clean up the current target
        const currentTargetClass = "cobalt-carousel--current-scroll-target";
        const currentTargetElements = this.container.querySelectorAll(`.${currentTargetClass}`);

        currentTargetElements.forEach(e => {
            e.classList.remove(currentTargetClass)
        });
        
        element.classList.add(currentTargetClass); // Add the current target class
        if(this.jumpScrollClonePointer) element = this.jumpScrollClonePointer; // Let's get ready for a jump scroll
        element.classList.add(currentTargetClass);
        return element;
    }

    initFakeElements() {
        // Clone nodes
        const fakeClass = "cobalt-carousel--fake-entry",
        firstClone = this.validTargets[0].cloneNode(true),
        secondClone = this.validTargets[1].cloneNode(true),
        penultimateClone = this.validTargets[this.validTargets.length - 2].cloneNode(true),
        lastClone = this.validTargets[this.validTargets.length - 1].cloneNode(true);

        this.validTargets[0].clone = firstClone;
        this.validTargets[1].clone = secondClone;
        this.validTargets[this.validTargets.length - 2].clone = penultimateClone;
        this.validTargets[this.validTargets.length - 1].clone = lastClone;

        firstClone.classList.add(fakeClass);
        firstClone.reference = this.validTargets[0];
        secondClone.classList.add(fakeClass);
        secondClone.reference = this.validTargets[1];
        penultimateClone.classList.add(fakeClass);
        penultimateClone.reference = this.validTargets[this.validTargets.length - 2];
        lastClone.classList.add(fakeClass);
        lastClone.reference = this.validTargets[this.validTargets.length - 1];

        this.firstClone = firstClone;
        this.lastClone = lastClone;

        this.container.append(firstClone);
        this.container.append(secondClone);
        this.container.prepend(lastClone);
        this.container.prepend(penultimateClone);
    }
}
customElements.define("cobalt-carousel", CobaltCarousel);
