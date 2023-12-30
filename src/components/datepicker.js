// const daysTag = document.querySelector(".days"),
// currentDate = document.querySelector(".current-date"),
// prevNextIcon = document.querySelectorAll(".icons span");

// // getting new date, current year and month
// let date = new Date(),
// currYear = date.getFullYear(),
// currMonth = date.getMonth();

class DatePicker extends HTMLElement {
    constructor() {
        super();
        this.props = {
            date: null,
            time: null,
            hidden: true,
        }

        this.months = [
            "January", "February", "March",
            "April", "May", "June",
            "July", "August", "September",
            "October", "November", "December"
        ];
        this.daysTag = document.createElement("div")
        this.daysTag.classList.add("days");
        this.timeInput = document.createElement("input");
        this.timeInput.type = "time";
        this.timeInput.addEventListener('input', () => {
            this.timeValue = this.timeInput.value;
        })

        this.hgroup = document.createElement("hgroup");
        this.monthContainer = document.createElement("select");
        
        this.monthContainer.addEventListener("change", e => {
            this.currentMonth = Number(this.monthContainer.value);
            this.render();
        });
        
        this.yearContainer = document.createElement("input");
        this.yearContainer.classList.add("year-selector");
        this.yearContainer.value = this.currentYear;
        this.yearContainer.addEventListener("change", () => {
            this.currentYear = Number(this.yearContainer.value);
            this.render();
        });

        this.nextButton = document.createElement("button");
        this.nextButton.innerHTML = "<i name='chevron-right'></i>";
        this.nextButton.value = 1;
        
        this.prevButton = document.createElement("button");
        this.prevButton.innerHTML = "<i name='chevron-left'></i>";
        this.prevButton.value = -1;

        this.hgroup.appendChild(this.prevButton);
        this.hgroup.appendChild(this.monthContainer);
        this.hgroup.appendChild(this.yearContainer);
        this.hgroup.appendChild(this.nextButton);

        this.hr = document.createElement("hr");
        // this.appendChild

        this.setButton = document.createElement("button");
        this.setButton.innerHTML = "<i name='check'></i>";
        this.setButton.classList.add("set");
        this.setButton.addEventListener("click", e => {
            if(!this.dateValue) new StatusMessage({message:"Select a date", id: "invaliddate"});
            if(!this.timeValue) new StatusMessage({message:"Select a time", id: "invalidtime"});
            if(this.dateValue && this.timeValue) this.dispatchEvent(new CustomEvent("dateselect", {detail: this.value}))
        })

    }

    get value() {
        return new Date(`${this.dateValue} ${this.timeValue}`);
    }

    set value(date) {
        if(!date) date = new Date();
        if(date.toString() === "Invalid Date") date = new Date();
        this.date = date;
        this.currentYear = this.date.getFullYear();
        this.currentMonth = this.date.getMonth();
        this.dateValue = this.makeDateString(date);
        this.render();
    }

    get dateValue() {
        return this.props.date;
    }

    set dateValue(d) {
        this.props.date = d;
        this.updateSetButton();
    }

    get timeValue() {
        return this.props.time;
    }

    set timeValue(t) {
        this.props.time = t;
        this.updateSetButton();
    }

    updateSetButton() {
        if(this.dateValue === null && this.timeValue === null) this.setButton.disabled = true;
        else this.setButton.disabled = false;
    }
    
    connectedCallback() {
        this.setAttribute("__custom-input", "true");

        if(!this.props.date) {
            this.value = new Date();
        }

        const nextPrevCallback = (e) => { // adding click event on both icons
            // if clicked icon is previous icon then decrement current month by 1 else increment it by 1
            const modifier = Number(e.currentTarget.value);
            this.currentMonth = this.currentMonth + modifier;
    
            if(this.currentMonth < 0 || this.currentMonth > 11) { // if current month is less than 0 or greater than 11
                // creating a new date of current year & month and pass it as date value
                this.date = new Date(this.currentYear, this.currentMonth, 1);
                this.currentYear = this.date.getFullYear(); // updating current year with new date year
                this.currentMonth = this.date.getMonth(); // updating current month with new date month
            } else {
                this.date = new Date(); // pass the current date as date value
            }
            this.render(); // calling renderCalendar function
        };
        this.nextButton.addEventListener("click", nextPrevCallback);
        this.prevButton.addEventListener("click", nextPrevCallback);
        this.appendChild(this.daysTag);
        this.appendChild(document.createElement("hr"));        
        this.appendChild(this.timeInput);
        this.appendChild(this.setButton);

        this.render();
        this.hide()
    }

    render() {
        let firstDayofMonth = new Date(this.currentYear, this.currentMonth, 1).getDay(), // getting first day of month
        lastDateofMonth = new Date(this.currentYear, this.currentMonth + 1, 0).getDate(), // getting last date of month
        lastDayofMonth = new Date(this.currentYear, this.currentMonth, lastDateofMonth).getDay(), // getting last day of month
        lastDateofLastMonth = new Date(this.currentYear, this.currentMonth, 0).getDate(); // getting last date of previous month
        const ul = document.createElement("ol")
        ul.classList.add("calendar");
        const dayOfWeek = ['S', 'M', 'T', 'W', 'Th', 'F', 'S'];
        for(const d of dayOfWeek) {
            ul.appendChild(this.createDay(d, ["header"]));
        }

        for (let i = firstDayofMonth; i > 0; i--) { // creating li of previous month last days
            ul.appendChild(this.createDay(lastDateofLastMonth - i + 1, ["inactive", "previous"]));
        }
    
        for (let i = 1; i <= lastDateofMonth; i++) { // creating li of all days of current month
            // adding active class to li if the current day, month, and year matched
            let isToday = i === this.date.getDate() && this.currentMonth === new Date().getMonth() 
                         && this.currentYear === new Date().getFullYear() ? "active" : "";
            ul.appendChild(this.createDay(i, [isToday]));
        }
    
        for (let i = lastDayofMonth; i < 6; i++) { // creating li of next month first days
            ul.appendChild(this.createDay(i - lastDayofMonth + 1, ["inactive", "next"]))
        }
        this.daysTag.innerHTML = ``
        this.daysTag.appendChild(this.hgroup);
        this.monthContainer.innerHTML = "";
        this.months.forEach((e, i) => {
            const opt = document.createElement("option");
            opt.innerText = e;
            opt.value = i;
            if(this.currentMonth === i) opt.selected = "selected";
            this.monthContainer.appendChild(opt);
        });
        this.yearContainer.value = this.currentYear;
        this.daysTag.appendChild(ul);
        this.timeInput.value = `${this.formatNumber(this.date.getHours())}:${this.formatNumber(this.date.getMinutes())}`;
    }

    createDay(dayNumber, classes = []) {
        const day = document.createElement("li");
        const dateString = `${this.currentYear}-${this.formatNumber(this.currentMonth + 1)}-${this.formatNumber(dayNumber)}`;
        if(dateString === this.dateValue && !classes.includes("inactive")) {
            day.classList.add("selected");
        }
        if(classes.length && classes[0]) day.classList.add(...classes);
        const button = document.createElement("button");
        button.innerText = dayNumber;
        day.appendChild(button);
        

        button.addEventListener("click", () => {
            if(classes.includes("header") || classes.includes("inactive")) return;
            this.dateValue = dateString;
            this.render();
        });
        return day;
    }

    makeDateString(date) {
        return `${date.getFullYear()}-${this.formatNumber(date.getMonth() + 1)}-${this.formatNumber(date.getDate())}`;
    }

    formatNumber(num) {
        if(num.toString().length === 1) num = `0${num}`
        return num;
    }
    
    get hidden () {
        return this.props.hidden;
    }

    hide() {
        this.props.hidden = true;
        this.setAttribute('hidden', 'true');
    }

    show() {
        this.props.hidden = false;
        this.setAttribute('hidden', 'false');
    }
}

customElements.define("date-picker", DatePicker);
