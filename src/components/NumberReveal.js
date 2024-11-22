class NumberReveal extends HTMLElement {

    constructor() {
        super();
        this.truthyRunningValues = ['true', 'running'];
        this.updateStartedAt = 0;
        this.props = {
            value: null,
            displayValue: null,
            startAt: null,
            increment: null,
            running: null,
            speed: null,
        };
    }

    get displayValue() {
        return Number(this.props.displayValue ?? 0);
    }

    set displayValue(val) {
        this.props.displayValue = val;
        this.innerHTML = Math.round(val * 10) * 0.1;
    }

    get value() {
        return Number(this.props.value ?? this.getAttribute("value") ?? 0);
    }

    set value(val) {
        this.props.value = val;
        this.setAttribute("value", val);
    }

    get startAt() {
        return this.props.startAt ?? this.getAttribute("start-at");
    }

    set startAt(val) {
        this.props.startAt = val;
        this.setAttribute("start-at", val);
    }

    get increment() {
        return this.props.increment ?? Number(this.getAttribute("increment"));
    }

    set increment(val) {
        this.props.increment = Number(val);
        this.setAttribute("increment", val);
    }

    get running() {
        return this.props.state ?? (this.truthyRunningValues.includes(this.getAttribute("running"))) 
    }

    set running(val) {
        if(typeof val === "boolean") this.props.running = val;
        else this.props.running = this.truthyRunningValues.includes(val);
        this.setAttribute("running", (this.props.running === true) ? "running" : "stopped");
        if(this.props.running === true) {
            window.requestAnimationFrame(this.runUpdate.bind(this));
            console.log("Starting runner");
        }
    }

    get speed() {
        return this.props.speed ?? Number(this.getAttribute("speed"));
    }

    set speed(val) {
        this.props.startAt = Number(val);
        this.setAttribute("speed", val);
    }

    connectedCallback() {
        this.value = this.value
        this.startAt = this.startAt;
        this.increment = this.increment;
        this.running = this.running;
        this.displayValue = this.startAt;
        this.startUpdate();
    }

    startUpdate() {
        this.running = true;
        this.updateStartedAt = performance.now();
        this.runUpdate(this.updateStartedAt);
    }

    runUpdate(timestamp) {
        // if(delta < speed) {
        //     window.requestAnimationFrame(this.runUpdate.bind(this))
        //     return;
        // }
        
        if(this.displayValue = this.value) {
            // Let's snap our displayedValue to the target value
            this.displayValue = this.value;
            this.running = "stopped";
            return;
        }
        
        const updateTarget = this.easeOutSine(this.displayValue / this.value || this.increment);
        this.displayValue += updateTarget;
        
        // Finally, we set up for our next paint.
        if(this.running) window.requestAnimationFrame(this.runUpdate.bind(this))
    }

    easeOutSine(x) {
        return Math.sin((x * Math.PI) / 2);
    }

}

customElements.define("number-reveal", NumberReveal);
