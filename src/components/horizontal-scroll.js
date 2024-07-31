class HorizontalScroll extends HTMLElement {
    constructor() {
        super();
        this.isDown = false;
        let startX;
        let scrollLeft;
        
        this.addEventListener('mousedown', (e) => {
          this.isDown = true;
          this.classList.add('active');
          startX = e.pageX - this.offsetLeft;
          scrollLeft = this.scrollLeft;
        });

        this.addEventListener('mouseleave', () => {
          this.isDown = false;
          this.classList.remove('active');
        });

        this.addEventListener('mouseup', () => {
          this.isDown = false;
          this.classList.remove('active');
        });

        this.addEventListener('mousemove', (e) => {
          if(!this.isDown) return;
          e.preventDefault();
          const x = e.pageX - this.offsetLeft;
          const walk = (x - startX) * this.scrollSpeed;
          this.scrollLeft = scrollLeft - walk;
        });
    }

    get scrollSpeed() {
      if(this.hasAttribute("scroll-speed")) return Number(this.getAttribute("scroll-speed") || 1)
      return 1
    }
}

customElements.define("horizontal-scroll", HorizontalScroll);
