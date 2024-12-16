class CountdownTimer extends HTMLElement {
    constructor() {
        super()
        this.props = {
            targetDate: null
        }
        this.innerHTML = `
            <span class="clock-segment days tens" title="Days">0</span>
            <span class="clock-segment days ones" title="Days">0</span>
            <span class="clock-segment colon">:</span>
            <span class="clock-segment hours tens" title="Hours">0</span>
            <span class="clock-segment hours ones" title="Hours">0</span>
            <span class="clock-segment colon">:</span>
            <span class="clock-segment mins tens" title="Minutes">0</span>
            <span class="clock-segment mins ones" title="Minutes">0</span>
            <span class="clock-segment colon">:</span>
            <span class="clock-segment seconds tens" title="Seconds">0</span>
            <span class="clock-segment seconds ones" title="Seconds">0</span>
        `
    }

    addInnerHtml(element) {
        element.innerHTML = ``
    }

    connectedCallback() {
        this.targetDate = this.getAttribute("target");
        this.updateClock();
        this.interval = setInterval(() => this.updateClock(), 1000)
    }

    get targetDate() {
        return this.props.targetDate
    }

    set targetDate(value) {
        let format = this.getAttribute("format");
        if(!format) format = "iso";
        switch(format.toLowerCase()) {
            case "iso":
            default:
                this.props.targetDate = new Date(this.getAttribute("target"));
        }
        return;
    }

    updateType(type, value) {
        const tens = this.querySelector(`.${type}.tens`);
        const ones = this.querySelector(`.${type}.ones`);
        const count = String(value).length;
        if(value >= 100) throw new Error("Cannot display more than 100");
        if(value < 10) tens.innerText = "0";
        else tens.innerText = String(value)[count - 2];
        ones.innerText = String(value)[count - 1];
    }

    updateClock() {
        const now = new Date().getTime();
        const target = this.targetDate.getTime();
        if(now > target && this.getAttribute("negative") != "true") {
            return;
        }
        const distance = target - now;
        const days    = Math.floor(distance / (1000 * 60 * 60 * 24))
        const hours   = Math.floor(distance % (1000 * 60 * 60 * 24) / (1000 * 60 * 60))
        const mins    = Math.floor(distance % (1000 * 60 * 60) / (1000 * 60))
        const seconds = Math.floor(distance % (1000 * 60) / 1000);
        this.updateType(    "days", days);
        this.updateType(   "hours", hours);
        this.updateType(    "mins", mins);
        this.updateType( "seconds", seconds);
    }
}

customElements.define("countdown-timer", CountdownTimer);
