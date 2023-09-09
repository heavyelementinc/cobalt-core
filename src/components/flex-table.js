
class FlexTable extends HTMLElement {
    connectedCallback() {

        this.initCheckboxes();

        // Get our computed width
        this.computedWidth = parseInt(getComputedStyle(this).width.replace("px",""));

        // Query for our columns
        let columns = this.querySelectorAll("flex-row");

        // The max count of columns
        let max = 0;
        // How many iterations we've gone without updating the column count
        let same = 0;

        let index = -1;

        // First thing we need to do is find out how many columns are in this table
        for (const i of columns) {
            index += 1;
            // Check if there is another row with more columns
            if (i.childElementCount > max) max = i.childElementCount;

            // If we've gone through iterations or more and they've all been the
            // same then we abort the loop
            if (i.childElement === max) {
                same += 1;
                if (same >= 3) break;
            }
        }

        // Set the max column count
        this.style.setProperty("--column-count", max);

        // An array of each column's max widths
        this.maxWidths = [];
        // Next, we need to establish the values of the elements
        for (const i of columns) {
            // Get the cell width of the current row
            this.getCellWidths(i);
        }

        // Get the min and max values so we can normalize our data
        this.minColumnWidth = Math.min(...this.maxWidths);
        this.clampLimitParentWidth = this.computedWidth / max;

        // Loop through the max widths and clamp them
        this.maxWidths.forEach((e, i) => {
            this.maxWidths[i] = this.clamp(e,this.minColumnWidth, this.clampLimitParentWidth);
        });

        this.maxColumnWidth = Math.max(...this.maxWidths);

        console.log(this.maxWidths);

        
        for (const row of columns) {
            this.cellCallback(row, (cell, index, skip) => {
                cell.style.setProperty("--flex-column-grow", `${this.normalizeWidths(this.maxWidths[index], this.minColumnWidth, this.maxColumnWidth)}`)
                cell.style.setProperty("--col-width",`${this.maxWidths[index]}px`);
                // this.calculatePercentage(this.maxWidths[index],)
                // cell.style.setProperty("--col-width", `%`);
                // `${this.maxWidths[index]}px`);
            });
        }
        
        // this.style.setProperty("--max-column-width", `px`);
        this.classList.add("hydrated");
    }
   
    normalizeWidths(val, min, max) {
        return Math.abs((val - min) / (max - min));
    }

    clamp(val, min, max) {
        return Math.min(Math.max(val, min), max);
    }
    
    getCellWidths(row) {
        return this.cellCallback(row,
            (cell, index, skip) => {
                if(!this.maxWidths[index]) this.maxWidths[index] = 0;
                const canvas = document.createElement("canvas");
                const context = canvas.getContext("2d");
                const style = getComputedStyle(cell);
                const font = `${style.fontWeight} ${style.fontSize} ${style.fontFamily}`;
                context.font = font;
                let {
                    width
                } = context.measureText(cell.innerText);

                const actionMenu = cell.querySelector("action-menu");
                if(actionMenu) {
                    if(["options", "option"].includes(actionMenu.getAttribute("type"))) width += get_offset(actionMenu).w;
                    else width += get_offset(actionMenu).w;
                }
                
                if(width > this.maxWidths[index]) this.maxWidths[index] = Math.ceil(width);
            }
        )
    }

    cellCallback(row, callback = (cell) => {}) {
        let index = 0, skip = 0;
        for(const cell of row.children) {
            if(skip > 0) {
                skip -= 1;
                index += 1;
                continue;
            }
            callback(cell, index, skip);
            index += 1;
            if(cell.getAttribute("span")) {
                skip = parseInt(cell.getAttribute("span"));
            }
        }
    }

    calculatePercentage(rowSize, total) {
        return (100 * rowSize) / total;
    }

    get value() {
        const checked = this.querySelectorAll("input[type='checkbox']:checked,input-switch :checked");
        let values = [];
        checked.forEach(element => {
            values.push(element.value);
        });
        return values;
    }

    initCheckboxes() {
        const checks = Array.from(this.querySelectorAll("input[type='checkbox'],input-switch"));
        if(checks.length === 0) return;
        // Determine if the first and second checkboxes have a flex-header parent
        // Get the "flex-header" parent node and see if its next sibling is another header
        // If so, we can probably assume that this is the 'header' flex-row
        // Was originally (checks[0].closest("flex-header") && checks[1].closest("flex-header"))
        let firstCheckboxIsSelectAll = (checks[0].closest("flex-header") && checks[0].closest("flex-header").nextElementSibling.tagName === "FLEX-HEADER");
        let selectAll = null;
        if(firstCheckboxIsSelectAll) {
            selectAll = checks.shift();
            selectAll.classList.add("select-all")
            selectAll.addEventListener("click", (event) => {
                checks.forEach(element => {
                    if(event.ctrlKey) return element.checked = !element.checked;
                    element.checked = selectAll.checked;
                })
            });
        }

        // Set the initial checkbox to the first element in the array.
        this.lastChecked = checks[0];

        checks.forEach(element => {
            element.addEventListener("click", event => {
                console.log(event);
                if(event.shiftKey) {
                    this.shiftSelect(this.lastChecked, element, checks, event);
                }
                this.lastChecked = element;

                if(!selectAll) return;
                let checked = this.querySelectorAll("input[type='checkbox']:not(.select-all):checked");
                if(checked.length === 0) selectAll.indeterminate = false;
                else if (checked.length >= checks.length) {
                    selectAll.checked = true;
                    selectAll.indeterminate = false;
                }
                else {
                    selectAll.checked = false;
                    selectAll.indeterminate = true;
                }
            });
        });
    }

    shiftSelect(previous, current, list, event) {
        let firstIndex = list.indexOf(previous);
        let lastIndex = list.indexOf(current);

        // Do some musical chairs
        if(firstIndex > lastIndex) {
            let temp = firstIndex;
            firstIndex = lastIndex;
            lastIndex = temp + 1;
        }

        list.slice(firstIndex, lastIndex).forEach(element => {
            if(event.ctrlKey) return element.checked = !element.checked;
            element.checked = current.checked;
        });
    }

    // static get observedAttributes() {
    //     return ['columns'];
    // }

    // attributeChangedCallback(name, oldValue, newValue) {
    //     const callable = `change_handler_${name.replace("-", "_")}`;
    //     if (callable in this) {
    //         this[callable](newValue, oldValue);
    //     }
    // }

    // change_handler_columns(val) {
    //     this.style.setProperty("--column-count", val);
    // }
}

customElements.define("flex-table", FlexTable);
