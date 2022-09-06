/** How to use the AutoCompleteInterface
 * Add a connectedCallback to the inheriting class and invoke the 
 * `getAutocompleteSearchField` method. This method returns the search field
 * container which you MUST manually append to the inheriting element.
 * 
 * You MUST also listen for the "autocompleteselect" event on `this` and handle 
 * it however is appropriate for your custom element.
 * 
 * You MUST also provide an "options" getter method in your class. This should
 * return the a node list of all "option" tags.
 * 
 * connectedCallback() {
 *  this.addEventListener("autocompleteselect", e => {
 *      e.detail.value // Get the value of the autocomplete element selected
 *      e.detail.label // Get the label of the autocomplete element selected
 *  })
 * }
 * 
 * Also, make sure you fire a "change" event on your custom element using 
 * `this.dispatchEvent(new Event("change"))`
 * 
 * Valid Attributes:
 *   static-results: Keeps results on the page until a selection is made
 *   exclude-current: Excludes the value(s) of the current input item (false)
 */

class AutoCompleteInterface extends HTMLElement {
    constructor() {
        super();
        
        this.arrowKeySelectionIndex = -1; // Default value

        this.specialKeys = {
            ArrowUp:  { 
                indexValue: -1,
                callback: "navigateWithArrowKeys"
            },
            ArrowDown:{ 
                indexValue: 1,
                callback: "navigateWithArrowKeys"
            },
            Enter:    { callback: "selectFromEnter" },
            Escape:   { callback: "clearResults" }
        };

        this.searchResults = null;
        this.displayResultsUntilSelection = string_to_bool(this.getAttribute("static-results")) ?? false;
        this.excludeCurrentValues = string_to_bool(this.getAttribute("exclude-current")) ?? false;
        this.hasFocus = false;
        this.timeout = null;
    }

    getAutocompleteSearchField() {
        if("AutocompleteSearchField" in this) return this.AutocompleteSearchFieldContainer;
        this.AutocompleteSearchFieldContainer = document.createElement("fieldset")
        this.AutocompleteSearchFieldContainer.classList.add("autocomplete--search-container");
        this.AutocompleteSearchField = document.createElement("input");
        this.AutocompleteSearchField.type = "search";
        this.AutocompleteSearchField.placeholder = this.getAttribute("placeholder") || "Start typing...";
        this.AutocompleteSearchFieldContainer.appendChild(this.AutocompleteSearchField);

        // this.AutocompleteSearchClear = document.createElement("input");
        // this.AutocompleteSearchClear.type = "button";
        // this.AutocompleteSearchClear.value = "✖";
        // this.AutocompleteSearchFieldContainer.appendChild(this.AutocompleteSearchClear);
        // this.AutocompleteSearchClear.zIndex = "9";
        // this.AutocompleteSearchClear.addEventListener("click", e => {
        //     this.AutocompleteSearchField.value = "";
        //     this.clearResults();
        // })
        this.eventPropagation();
        return this.AutocompleteSearchFieldContainer;
    }

    eventPropagation() {
        // this.AutocompleteSearchField.addEventListener("input", (e) => {
        //     this.stopBubbling(e);
        // });

        this.AutocompleteSearchField.addEventListener("change", (e) => {
            this.stopBubbling(e);
        });

        this.AutocompleteSearchField.addEventListener("keyup", e => {
            this.stopBubbling(e);
            this.keyUpListener(e);
        });

        this.AutocompleteSearchField.addEventListener("click", e => {
            this.keyUpListener(e);
        });

        this.AutocompleteSearchField.addEventListener("focusin", (e) => {
            this.hasFocus = true;
            this.appendSearchResults();
            clearTimeout(this.timeout);
        });

        this.AutocompleteSearchField.addEventListener("focusout", (e) => {
            this.hasFocus = false;
            if(!this.displayResultsUntilSelection) this.timeout = setTimeout(() => {
                this.clearResults()
            },800);
        });
    }

    keyUpListener(e) {
        this.updatePosition();
        if("key" in e && Object.keys(this.specialKeys).includes(e.key)) {
            const result = this[this.specialKeys[e.key].callback](e.key, e);
            if(result !== true) return;
        }
        
        // Reset our selection index
        this.arrowKeySelectionIndex = -1;
        if(!this.searchResults) this.appendSearchResults();
        this.searchResults.innerHTML = "";
        let toSearch = e.target.value;
        if(!toSearch) return;

        const filter = new RegExp(`(${toSearch})`,'i');
        
        let custom = {};
        if(this.allowCustom === true) {
            custom = {
                value: toSearch,
                label: toSearch,
                custom: true
            };
        }

        let workingOptions = this.filterOptions(filter, custom);

        for(const i of workingOptions) {
            this.addSearchResult(i,filter);
        }
    }

    navigateWithArrowKeys(key, e) {
        if(key === "ArrowDown" && this.searchResults.innerHTML === "") return true;

        // Update the index
        this.arrowKeySelectionIndex += this.specialKeys[key].indexValue;
        // Cap the index at -1
        if(this.arrowKeySelectionIndex <= -2) this.arrowKeySelectionIndex = -1;
        // If the index is -1, do nothing.
        if(this.arrowKeySelectionIndex === -1) return;

        // Get the list of search results
        const nodes = this.searchResults.childNodes;
        // If it's empty, do nothing
        if(nodes.length === 0) return;
        if(nodes.length - 1 < this.arrowKeySelectionIndex) {
            // If the index is outside the bounds of the node length, set it equal to the length of the node list and do nothing.
            this.arrowKeySelectionIndex = nodes.length - 1;
            return;
        }
        const selectOnEnter = "input-array--will-select-on-enter";
        nodes.forEach(e => e.classList.remove(selectOnEnter));
        nodes[this.arrowKeySelectionIndex].classList.add(selectOnEnter)
    }

    selectFromEnter(key, e) {
        // If there are no child nodes, do nothing
        if (this.searchResults.childNodes.length === 0) return;
        // Select the first element if there are no results
        if (this.arrowKeySelectionIndex === -1) return this.selectSearchResult(this.searchResults.childNodes[0]);
        if (this.searchResults.childNodes[this.arrowKeySelectionIndex]) return this.selectSearchResult(this.searchResults.childNodes[this.arrowKeySelectionIndex]);
    }

    selectSearchResult(target) {
        let val = target.getAttribute("value"),
            label = target.getAttribute("label");
        this.dispatchEvent(new CustomEvent("autocompleteselect",{detail: {value: val,label}}));
        this.clearResults();
        clearTimeout(this.timeout);
    }

    clearResults(key = "", e = {}) {
        // We want to clean up any unselected values
        this.AutocompleteSearchField.value = "";
        // And remove the search results
        this.searchResults.innerHTML = "";
    }

    filterOptions(filter, custom = {}) {
        const val = this.value;
        const opts = this.options;
        let finalOptions = [];
        for(const i of opts) {
            const attr = i.getAttribute("value");
            // Let's exclude current values
            switch(typeof val) {
                case "object":
                    if(val.includes(attr)) continue;
                    break;
                case "string":
                    if(val === attr) continue;
                    break;
            }
            if(filter.test(i.innerText)) finalOptions.push({
                value: attr,
                label: i.innerText,
                custom: false
            });
        }
        finalOptions.push(custom);
        return finalOptions;
    }

    addSearchResult(option, filter){
        const result = document.createElement("li");
        result.setAttribute("value",option.value);
        result.setAttribute("label",option.label);
        result.classList.add("autocomplete--search-result-item");
        if(option.custom) result.classList.add("autocomplete--list-custom")

        result.addEventListener("click", e => {
            this.selectSearchResult(e.target);
        });

        result.innerHTML = option.label.replace(filter, "<strong>$1</strong>");
        this.searchResults.appendChild(result);
    }

    appendSearchResults() {
        let results = document.querySelector("autocomplete-results-container");
        if(!results) results = document.createElement("autocomplete-results-container");
        document.body.appendChild(results);
        this.searchResults = results;
        this.updatePosition();
    }

    updatePosition() {
        const offset = get_offset(this);
        this.searchResults.style.top = (offset.bottom - 1) + "px";
        this.searchResults.style.left = (offset.x + 4) + "px";
        this.searchResults.style.width = (offset.w - 8) + "px";
    }

    stopBubbling(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
    }
}