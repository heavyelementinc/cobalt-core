class FlexTable extends HTMLElement {
    constructor() {
        super();
        this.props = {
            maxWidth: null
        }
    }

    init() {
        this.mutationObserver = new MutationObserver(this.onRowChange.bind(this));
        this.mutationObserver.observe(this, {childList: true});

        window.addEventListener("resize", this.onWindowResize.bind(this));

        this.initCheckboxes();
    }

    connectedCallback() {
        this.init();
        this.initRowsAndColumns();
    }

    disconnectedCallback() {
        // Let's clean up after ourselves.
        window.removeEventListener("resize", this.onWindowResize.bind(this));
    }

    initRowsAndColumns() {
        // Create a list of max char lengths in the table
        this.cellData = [];
        this.maxCellLengths = [];
        this.maxColumnCount = [];

        /** @var {FlexRow} */
        for(const row of this.children) {
            if(row.tagName !== "FLEX-ROW") {
                // Eject non-<flex-row> elements from the table
                this.parentNode.insertBefore(row, this.nextSibling);
                continue;
            }
            /** @var {int} */
            const rowIndex = this.cellData.length;
            let columnCount = 0;
            // Let's set up our rows in the cell data
            this.cellData.push([]);
            this.maxCellLengths.push([]);
            this.maxColumnCount.push([]);
            
            // Now we'll loop through our children
            /** @var {FlexCell} */
            for(const cell of row.children) {
                if(cell.tagName !== "FLEX-CELL" && cell.tagName !== "FLEX-HEADER") {
                    // Eject non-<flex-cell>-derived elements from the row
                    this.parentNode.insertBefore(cell, this.nextSibling);
                    continue;
                }
                this.cellData[rowIndex].push(cell.cellData);
                
                // Check if the current cell's width is greater than this column's current contender
                if(this.maxCellLengths[columnCount] < cell.innerText.length) {
                    this.maxCellLengths[columnCount] = cell.innerText.length;
                }
                columnCount += 1;
            }
            this.maxColumnCount[rowIndex] = columnCount;
        }

        this.normalizeColumns();

    }

    normalizeColumns() {
        this.normalizedCellLengths = [];
        const ratio = Math.max.apply(Math, this.maxCellLengths) / 100,
            l = this.maxCellLengths.length;

        for (let i = 0; i < l; i++) {
            this.normalizedCellLengths[i] = Math.round(this.maxCellLengths[i] / ratio);
        }

        let rowIndex = 0;
        for(const row of this.children) {
            let columnIndex = 0;
            /** @var {FlexCell} cell */
            for(const cell of row.children) {
                // let column = cell.column;
                cell.style.setProperty('--cell-width-grow', `${this.normalizedCellLengths[columnIndex]}`);
                // columnIndex += column - columnIndex;
                columnIndex += 1;
            }
            rowIndex += 1;
            columnIndex = 0;
        }
    }

    onRowChange(mutationList, observer) {

    }

    onWindowResize(event) {

    }

    get rowCount() {
        return this.rowData.length;
    }

    get maxWidth() {
        if(this.props.maxWidth !== null) return this.props.maxWidth;
        const maxWidth = this.getAttribute("max-col-width");
        if(maxWidth !== null) {
            this.props.maxWidth = Number(maxWidth);
        } else {
            this.props.maxWidth = 30;
        }
        return this.props.maxWidth;
    }

    get minWidth() {

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
                    element.dispatchEvent(new Event("change"))
                })
            });
        }

        // Set the initial checkbox to the first element in the array.
        this.lastChecked = checks[0];

        checks.forEach(element => {
            element.addEventListener("click", event => {
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
            element.addEventListener("change", event => {
                const button = document.querySelectorAll("async-button[type='multidelete']");
                if(!button) return;
                for(const check of checks) {
                    if(check.checked === true) {
                        button.disabled = false
                        return;
                    }
                    button.disabled = true
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


}

customElements.define("flex-table", FlexTable);

class FlexRow extends HTMLElement {
    constructor() {
        super();
    }

    connectedCallback() {
        // this.parentTable = this.closest("flex-table");
    }

    get rowData() {
        if(!this.parentTable) return {};
        const rowNumber = Array.from(this.parentTable.children).indexOf(this);
        let isHeadlineRow = this.querySelectorAll("flex-header").length == this.children.length;
        return {
            rowNumber,
            isHeadlineRow,
        }
    }
}

customElements.define("flex-row", FlexRow);

class FlexCell extends HTMLElement {
    constructor() {
        super();
        this.props = {
            column: null
        }
    }

    connectedCallback() {
        // this.parentRow = this.closest("flex-row");
        // this.parentRow?.parentTable?.registerCellData(this);
        
    }

    get cellData() {
        return {
            type: this.type,
            width: this.innerText.length + this.padding,
            columnNumber: this.parentRow.registerCellData(this)
        }
    }

    get column() {
        if(this.props.column !== null) return this.props.column;
        if(!this.previousElementSibling) return 0;
        if("column" in this.previousElementSibling) {
            this.props = this.previousElementSibling.column + this.span;
        }
    }

    get span() {
        const span = this.getAttribute("span") ?? this.getAttribute("colspan") ?? null;
        if(span !== null) return Number(span);
        return 1
    }

    get type() {
        switch(this.tagName) {
            case "FLEX-HEADER":
                return "header";
            default:
                return "cell";
        }
    }

    get padding() {
        return this.getAttribute("padding") ?? 2;
    }

    set padding(value) {
        this.setAttribute("padding", value);
    }
}

customElements.define("flex-cell", FlexCell);


class FlexHeader extends FlexCell {
    constructor() {
        super();
    }
}

customElements.define("flex-header", FlexHeader);
