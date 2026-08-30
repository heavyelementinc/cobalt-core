class TemporalDatePicker extends EventTarget {
	constructor(inputElement, options = {}) {
		super();
		this.input = inputElement;
		// Setup async fetching state
        this.getValidDatesAsync = options.getValidDatesAsync || null;
        this.validDatesSet = new Set();
        this.isLoading = false;

		// Callback function to determine if a given Temporal.PlainDate is disabled
		this.isDateDisabled = options.isDateDisabled || (() => false);
		this.isPreviousMonthDisabled = options.isPreviousMonthDisabled || (() => {
			const min = this.input.getAttribute('min');
			if(!min) return false;
			// Handle Prev/Next button disabled states by comparing the first day of the month
			const firstDayOfCurrentMonth = this.currentMonth.with({ day: 1 });

			const firstDayOfMinMonth = Temporal.PlainDate.from(min).with({ day: 1 });
            this.prevBtn.disabled = Temporal.PlainDate.compare(firstDayOfCurrentMonth, firstDayOfMinMonth) <= 0;
		});
		this.isNextMonthDisabled = options.isPreviousMonthDisabled || (() => {
			const max = this.input.getAttribute('max');
			if(!max) return false;
			// Handle Prev/Next button disabled states by comparing the first day of the month
			const firstDayOfCurrentMonth = this.currentMonth.with({ day: 1 });

			const firstDayOfMaxMonth = Temporal.PlainDate.from(max).with({ day: 1 });
            this.nextBtn.disabled = Temporal.PlainDate.compare(firstDayOfCurrentMonth, firstDayOfMaxMonth) >= 0;
		});
		// Initialize standard state using Temporal
		this.today = Temporal.Now.plainDateISO();
		this.selectedDate = null;
		// Check if input already has a valid date string
		if (this.input.value) {
			try {
				this.selectedDate = Temporal.PlainDate.from(this.input.value);
				this.currentMonth = this.selectedDate.with({
					day: 1
				});
			} catch (e) {
				this.currentMonth = this.today.with({
					day: 1
				});
			}
		} else {
			this.currentMonth = this.today.with({
				day: 1
			});
		}
		this.initUI();
		
		this.loadMonthData();
	}
	initUI() {
		// 1. Build Wrapper & Popup
		this.wrapper = document.createElement('div');
		this.wrapper.className = 'temporal-datepicker-wrapper';
		this.input.parentNode.insertBefore(this.wrapper, this.input);
		this.wrapper.appendChild(this.input);
		this.popup = document.createElement('div');
		this.popup.className = 'temporal-calendar-popup';
		// 2. Build Header
		this.header = document.createElement('div');
		this.header.className = 'temporal-calendar-header';
		this.prevBtn = document.createElement('button');
		this.prevBtn.type = 'button';
		this.prevBtn.textContent = '◀';
		this.nextBtn = document.createElement('button');
		this.nextBtn.type = 'button';
		this.nextBtn.textContent = '▶';
		this.monthLabel = document.createElement('span');
		this.monthLabel.className = 'temporal-month-label';
		this.header.append(this.prevBtn, this.monthLabel, this.nextBtn);
		// 3. Build Grid
		this.grid = document.createElement('div');
		this.grid.className = 'temporal-calendar-grid';
		this.popup.append(this.header, this.grid);
		this.wrapper.appendChild(this.popup);
		// 4. Bind Events
		this.input.addEventListener('click', () => this.toggle());
		// Use Temporal's immutable math to switch months safely
		this.prevBtn.addEventListener('click', () => {
          if (this.isLoading) return; // Prevent double-clicking
          this.currentMonth = this.currentMonth.subtract({ months: 1 });
          this.loadMonthData();
        });
        
        this.nextBtn.addEventListener('click', () => {
          if (this.isLoading) return; // Prevent double-clicking
          this.currentMonth = this.currentMonth.add({ months: 1 });
          this.loadMonthData();
        });
		document.addEventListener('click', (e) => {
			if (!this.wrapper.contains(e.target)) {
				this.close();
			}
		});
	}
	async loadMonthData() {
        if (!this.getValidDatesAsync) {
          this.render();
          return;
        }

        this.isLoading = true;
        this.render(); // Render immediately to show the loading state

        try {
          // Pass the year and month to your custom fetch function
          const validDateStrings = await this.getValidDatesAsync(
            this.currentMonth.year, 
            this.currentMonth.month
          );
          this.validDatesSet = new Set(validDateStrings);
        } catch (error) {
          console.error("Failed to fetch valid dates:", error);
          this.validDatesSet = new Set(); // Fallback to empty on error
        } finally {
          this.isLoading = false;
          this.render(); // Re-render with the fetched dates
        }
      }
	toggle() {
		this.popup.classList.toggle('open');
	}
    open() {
		this.popup.classList.add('open');
	}
	close() {
		this.popup.classList.remove('open');
	}
	render() {
		this.grid.innerHTML = '';
		if (this.isLoading) {
          this.grid.innerHTML = '<div style="grid-column: 1 / -1; padding: 20px; text-align: center; color: #64748b;">Loading dates...</div>';
          return;
        }
		this.isPreviousMonthDisabled();
		this.isNextMonthDisabled();
		// Set Month/Year Label
		const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
		// Temporal components are safely 1-indexed (Month is 1-12)
		this.monthLabel.textContent = `${monthNames[this.currentMonth.month - 1]} ${this.currentMonth.year}`;
		// Render Weekday Headers (Sunday -> Saturday)
		const daysOfWeek = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
		daysOfWeek.forEach(d => {
			const el = document.createElement('div');
			el.className = 'temporal-day-header';
			el.textContent = d;
			this.grid.appendChild(el);
		});
		// Figure out offset for the first day of the month.
		// Temporal's dayOfWeek: 1 = Monday, ..., 7 = Sunday
		const firstDay = this.currentMonth.with({
			day: 1
		});
		const startOffset = firstDay.dayOfWeek % 7;
		// Render empty offset slots
		for (let i = 0; i < startOffset; i++) {
			const el = document.createElement('div');
			el.className = 'temporal-day empty';
			this.grid.appendChild(el);
		}
		// Render actual days using Temporal's native daysInMonth property
		const daysInMonth = this.currentMonth.daysInMonth;
		for (let day = 1; day <= daysInMonth; day++) {
			const currentDate = this.currentMonth.with({
				day
			});
			const el = document.createElement('div');
			el.className = 'temporal-day';
			el.textContent = day;
			// Check states using Temporal's .equals() method
			if (this.today.equals(currentDate)) {
				el.classList.add('today');
			}
			if (this.selectedDate && this.selectedDate.equals(currentDate)) {
				el.classList.add('selected');
			}
			// Check if date is excluded by the async valid dates list
			const isExcludedByAsync = this.getValidDatesAsync && !this.validDatesSet.has(currentDate.toString());
			
			// Check if disabled by bounds, async list, OR the custom callback
			if (isExcludedByAsync || this.isDateDisabled(currentDate)) {
				el.classList.add('disabled');
			} else {
				// Bind click to select date
				el.addEventListener('click', () => {
					this.selectedDate = currentDate;
					this.input.value = currentDate.toString(); // Output format: YYYY-MM-DD
					this.render();
					this.close();
					this.dispatchEvent(new CustomEvent("selected"));
				});
			}
			this.grid.appendChild(el);
		}
	}
}
