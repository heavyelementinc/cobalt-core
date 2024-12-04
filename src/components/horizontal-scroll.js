class HorizontalScroll extends HTMLElement {

    constructor() {
        super();
        this.isDown = false;
        this.finalizeScrollInitiator = false;
        this.observer = null;
        this.lastObservedList = [];
        let startX;
        let scrollLeft;
        this.style.scrollBehavior = "smooth";
        this.scrollFinalizeTimeout = null;
        this.scrollableTrack = document.createElement("div");
        this.scrollableTrack.classList.add("scrollable-track");
        this.dotContainer = document.createElement("ul");

        this.scrollableTrack.addEventListener("touchstart", () => {
          this.finalizeScrollInitiator = false;
        })

        this.scrollableTrack.addEventListener("wheel", () => {
          this.finalizeScrollInitiator = false;
        })
        
        this.scrollableTrack.addEventListener('mousedown', (e) => {
          // this.lastObservedList = [];
          this.isDown = true;
          this.classList.add('active');
          startX = e.pageX - this.scrollableTrack.offsetLeft;
          scrollLeft = this.scrollableTrack.scrollLeft;
          this.finalizeScrollInitiator = false;
        });

        this.scrollableTrack.addEventListener('scroll', (e) => {
          clearTimeout(this.scrollFinalizeTimeout);
        });

        this.scrollableTrack.addEventListener('scrollend', () => {
          if(this.finalizeScrollInitiator) return;
          this.scrollFinalizeTimeout = setTimeout(() => {
            this.finalizeScroll();
          }, 75);
        });

        this.scrollableTrack.addEventListener('mouseleave', () => {
          this.isDown = false;
          this.classList.remove('active');
          // this.finalizeScroll();
        });

        this.scrollableTrack.addEventListener('mouseup', () => {
          this.isDown = false;
          this.classList.remove('active');
          // this.finalizeScroll();
        });

        this.scrollableTrack.addEventListener('mousemove', (e) => {
          if(!this.isDown) return;
          e.preventDefault();
          const x = e.pageX - this.scrollableTrack.offsetLeft;
          const walk = (x - startX) * this.scrollSpeed;
          this.scrollableTrack.scrollLeft = scrollLeft - walk;
        });
    }

    connectedCallback() {
      // We're going to append the children of this element to the scrollable track
      const children = Array.from(this.children)
      for(const el of children) {
        this.scrollableTrack.appendChild(el);
      }
      // Then we'll append the track to this element.
      this.appendChild(this.scrollableTrack);

      this.lastObservedList = [this.specifyLastObserved(this.scrollableTrack.children[0])];

      const options = {
        root: this.scrollableTrack,
        rootMargin: this.style.marginLeft,
        threshold: 0.01
      }
      this.observer = new IntersectionObserver((entries, observer) => {
        this.lastObservedList = entries;
      }, options);
      for(const e of this.scrollableTrack.children) {
        this.observer.observe(e);
      }
      if(this.hasAttribute("paginated")) {
        this.initPagination();
      }
    }

    initPagination() {
      this.dotContainer.innerHTML = "";
      this.dotContainer.classList.add("pagination-controls");

      let index = 0;
      for(const e of this.scrollableTrack.children) {
        const listItem = document.createElement("li");
        const button = document.createElement("button");
        listItem.appendChild(button);
        button.dataset.index = index;
        button.addEventListener("click", () => {
          this.finalizeScrollInitiator = false;
          this.lastObservedList = [this.specifyLastObserved(this.scrollableTrack.children[button.dataset.index])];
          this.finalizeScroll();
          // this.scrollableTrack.children[button.dataset.index].scrollIntoView({behavior: "smooth", block: "nearest", inline: "center"});
        });
        this.dotContainer.appendChild(listItem);
        this.scrollableTrack.children[button.dataset.index].dataset.index = index;
        index += 1;
      }
      this.appendChild(this.dotContainer);
      this.finalizeScroll();
    }

    disconnectedCallback() {
      this.observer = null;
    }

    finalizeScroll() {
      if(!this.hasAttribute("paginated")) return;
      this.finalizeScrollInitiator = true;
      let candidate = null;
      let lastRatio = 0;
      /** @var {IntersectionObserverEntry} */
      for(const el of this.lastObservedList) {
        if(candidate === null) candidate = el;
        if(!el.isIntersecting) continue;
        if(el.intersectingRatio > 0.50 && el.intersectingRatio >= lastRatio) {
          candidate = el;
          lastRatio = el.intersectingRatio;
        }
      }

      if(lastRatio < 100) {
        candidate.target.scrollIntoView({behavior: "smooth", block: "nearest", inline: "nearest"});
        for(const el of this.dotContainer.children) {
          el.classList.remove("current");
          if(el.children[0].dataset.index === candidate.target.dataset.index) el.classList.add("current");
        }
      }
    }

    get scrollSpeed() {
      if(this.hasAttribute("scroll-speed")) return Number(this.getAttribute("scroll-speed") || 1)
      return 1
    }

    specifyLastObserved(target) {
      const boundingClientRect = target.getBoundingClientRect() ?? new DOMRectReadOnly(0,0,0,0);
      return {
        boundingClientRect,
        intersectingRatio: 1.0,
        intersectionRect: boundingClientRect,
        isIntersecting: true,
        intersectingRatio: boundingClientRect,
        rootBounds: boundingClientRect,
        target: target,
        time: performance.now(),
      }
    }
}

customElements.define("horizontal-scroll", HorizontalScroll);
