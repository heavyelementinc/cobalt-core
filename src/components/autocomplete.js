/** # AutoComplete
 * @description The Cobalt Engine Autocomplete webcomponent
 * @element <input-autocomplete>
 * @todo Make this element extend the new AutocompleteInterface
 * 
 * @attribute allow-custom []
 * @attribute placeholder
 * @attribute readonly
 * @attribute url
 * @attribute min 
 * @attribute clear-button
 * 
 * @emits clear when the clearButton is pressed
 * 
 */

class AutoComplete extends HTMLElement {
    constructor() {
        super();
        /** Store the current and previous value */
        this.previousVal = null;
        this.val = "";

        /** Set up the default state */
        this.readonly = false;
        this.value = "";

        /** Other optional states */
        this.url = false;
        this.min = 1;

        /** Store our options */
        this.options = {};
        this.validity = {
            notAvailableError: false
        };

        this.withClearButton = true;
        this.errorState = true;
        this.selectOnEnter = "input-array--will-select-on-enter";
        this.placeholder = "Start typing...";
        this.customElementClass = "input-array--list-custom";
        this.arrowKeySelectionIndex = -1;
        this.setAttribute("__custom-input", "true");
        this.addEventListener("clear", e => this.reset(e));
    }

    get value() {
        /** Return the value we're storing */
        return this.val;
    }

    set value(val) {
        // Store our updated state
        this.updated = false;

        // If we haven't yet instantiated our search field, do nothing.
        if (!this.searchField) {
            this.val = val;
            return;
        }

        // Check if val is in valid options
        if (this.options && val in this.options) {
            this.val = val;
            this.searchField.value = this.options[val].search;
            this.updated = true;
        } else if (this.allowCustomInputs) {
            // Otherwise check if we allow custom inputs
            this.val = val;
            this.searchField.value = val ?? "";
            this.updated = true;
        }
        // else if (Object.values(this.options).includes(val)) {
        //     this.value = Object.keys(this.options)[Object.values(this.options).indexOf(val)];
        //     updated = true;
        // }
        this.errorState = !this.updated;
        // Set the validity state
        if (val) this.setValidity("notAvailableError", !this.updated);
        // if(this.updated === false) this.val = null;
        return this.updated;
    }

    inOptions(val) {
        if (val in this.options) return true;
        return false;
    }

    valueInOptions(val) {
        for (const i in this.options) {
            if (val === i['search']) return true;
        }
        return false;
    }

    setValidity(name, value) {
        const classes = (value) ? "add" : "remove";
        if (!this.validity) this.validity = {};
        this.validity[name] = value;
        this.classList[classes]("invalid");
    }

    connectedCallback() {
        this.getOptions();
        this.initClearButton();
        this.searchElements();
        this.initSearchField();
        this.value = this.initValue();
    }

    disconnectedCallback() {
        const search = this.querySelector("input[type='search']");
        console.log(search);
        if (!search) search.parentNode.removeChild(search);
    }

    getOptions() {
        const opts = this.querySelectorAll("option");
        for (const i of opts) {
            this.options[i.getAttribute('value')] = {
                search: i.innerText,
                label: i.innerHTML
            };
        }
        if (!opts) this.options = {};
    }

    initValue() {
        let val = this.querySelector("option[selected='selected']");
        if(val) return val.getAttribute("value");
        return this.getAttribute("value")
    }

    /*** Handle attribute changes ***/
    static get observedAttributes() {
        return ['value', 'url', 'min', 'readonly', 'placeholder', 'clear-button'];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        const callable = `change_handler_${name.replace("-", "_")}`;
        if (callable in this) {
            this[callable](newValue, oldValue);
        }
    }

    get allowCustomInputs() {
        return string_to_bool(this.getAttribute("allow-custom"));
    }

    set allowCustomInputs(value) {
        this.setAttribute("allow-custom", JSON.stringify(value ? true : false));
    }

    change_handler_clear_button(newValue) {
        this.withClearButton = newValue;
    }

    change_handler_placeholder(newValue) {
        this.placeholder = newValue;
    }

    change_handler_readonly(newValue) {
        this.readonly = string_to_bool(newValue);
    }

    change_handler_value(newValue) {
        this.value = newValue;
    }

    change_handler_min(newValue) {
        this.limit = Number(newValue);
    }

    change_handler_url(newValue) {
        this.url = newValue;
    }



    /* ======== *\
    HANDLE  SEARCH
    \* ======== */
    /** Renders the search results container (the unordered list of elements)
     * which gets updated on every input as well as the search field.
     * */
    searchElements() {
        if (this.querySelector("input[type='search']")) return;
        if (this.readonly === true) return "";
        let placeholder = this.placeholder;
        let pattern = "";

        if (this.allowCustomInputs && this.pattern) pattern = " " + this.pattern;
        this.searchResults = document.createElement("ul");
        this.searchResults.classList.add("input-array--search-results");
        this.searchField = document.createElement('input');
        this.searchField.type = 'search';
        this.searchField.placeholder = placeholder;
        this.searchField.pattern = pattern;
        this.searchField.addEventListener("focusin", e => {
            this.placeResults()
            this.drawSearchResults(this.options, this.searchField.value, (this.searchField.value) ? true : false);
            // clearTimeout(this.focusOutTimeout);
            // e.stopPropagation();
            // e.preventDefault();
        });
        if(this.options.length) {
            this.searchField.value = this.options[this.val].search || this.val;
        }
        // if(this.searchResults.)
        this.appendChild(this.searchField);
    }

    /** Initializes the search field so it listens for appropriate button presses
     * and can disappear the searchResults container on focusout
     */
    initSearchField() {
        if (!this.searchField) {
            this.searchField = this.querySelector("input[type='search']");
            if (!this.searchField) {
                throw new Error("There's no search field for this autocomplete element");
            }
        }
        this.searchField.addEventListener("change", e => {
            this.dispatchEvent(new Event("change", e));
            e.preventDefault();
            e.stopPropagation();
        })

        this.searchField.addEventListener("input", e => {
            this.dispatchEvent(new Event("input", e));
            if (this.searchField.value == "") this.searchElements();
        });

        // this.searchField.addEventListener("keydown", e => {
        //     console.log(e)
        //     switch(e.key) {
        //         case "ArrowUp":
        //         case "ArrowDown":
        //             e.preventDefault();
        //             break;
        //     }
        // });
        this.searchField.addEventListener("keyup", e => this.handleSearchKeyUp(e));

        this.addEventListener("focusin", e => {
            this.updated = false;
            clearTimeout(this.focusOutTimeout);
            this.setValidity("incompleteEntry", false);
            this.previousVal = this.val;

            let sf = this.searchField.value;

            // Maintain previous select if we're in an error state
            this.previousSelect = (sf !== this.previousSelect) ? sf : this.previousSelect;
            // console.log(sf, this.previousSelect, (sf !== this.previousSelect) ? sf : this.previousSelect, this.errorState);
            this.startedEditing = true;
            this.updated = false;
        });

        this.addEventListener("focusout", e => {
            this.focusOutTimeout = setTimeout(() => {
                this.focusOutHandler(e);
            }, 600);
            this.startedEditing = false;
            const sf = this.searchField.value;
            if (this.errorState && !sf) {
                this.value = "";
            }
            // Check if the field's been focused into and modified without having
            // selected an entry from the search results.
            if (sf !== "" && this.updated === false && this.errorState) {
                if (this.allowCustomInputs) this.value = sf;
                else {
                    this.errorState = true;
                    this.setValidity("incompleteEntry", true);
                }
            }

            // if(!this.updated && !this.valueInOptions(this.searchField.value)) this.setValidity("incompleteEntry", true);
        });
    }

    reset(e) {
        this.searchField.value = "";
        this.searchField.dispatchEvent(new Event("input"))
    }

    initClearButton() {
        if (!this.withClearButton) return;
        const btn = document.createElement("button");
        btn.innerHTML = window.closeGlyph;
        btn.addEventListener("click", e => {
            this.dispatchEvent(new CustomEvent("clear"));
        });
        this.clearButton = btn;
        this.addEventListener('input', () => {
            if (!this.value) this.clearButtonHide();
            else this.clearButtonShow();
        });
    }

    clearButtonShow() {
        this.clearButton.style.display = "inline-block";
    }

    clearButtonHide() {
        this.clearButton.style.display = "none";
    }

    async handleSearchKeyUp(e) {
        e.preventDefault();
        let toSearch = e.target.value;
        let tempOpts = { ...this.options };

        if(this.url) {
            const api = new ApiFetch(this.url, this.getAttribute("method") || "GET", {});
            const opts = await api.send({search: toSearch});
            tempOpts = {};
            for(const el in opts) {
                if(typeof opts === "object" && "search" in opts && "label" in opts) {
                    tempOpts[el] = opts[el];
                } else {
                    tempOpts[el] = {
                        search: opts[el],
                        label: opts[el]
                    };
                }
            }
        }

        if (this.allowCustomInputs === true && toSearch !== "") tempOpts[toSearch] = {
            "search": toSearch,
            "label": toSearch,
            "custom": true
        }

        switch (e.key) {
            case "Enter":
                this.selectFromEnter();
                return;
            case "ArrowDown":
                if (this.searchResults.innerHTML === "") {
                    this.drawSearchResults(tempOpts, "", false);
                }
            case "ArrowUp":
                e.preventDefault();
                e.stopPropagation();
                this.selectFromArrows(e.key);
                return;
        }

        this.focusOutHandler();
        this.placeResults();

        if (toSearch === "") return;

        // const val = new RegExp(`${toSearch.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&')}`, 'i');

        this.drawSearchResults(tempOpts, toSearch, true);
    }

    placeResults() {
        if (!this.parentNode.contains(this.searchResults)) this.appendChild(this.searchResults);

        this.searchResults.style.top = this.searchField.offsetTop + this.searchField.offsetHeight - 1 + "px";
        this.searchResults.style.left = this.searchField.offsetLeft + 4 + "px";
        this.searchResults.style.width = this.searchField.offsetWidth - 8 + "px";
    }

    drawSearchResults(tempOpts, string, match = true) {
        this.focusOutHandler(); // Clear
        let index = 0;
        let val = (string) ? new RegExp(`${string.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&')}`, 'i') : "";
        for (var i in tempOpts) {

            const matchAgainst = tempOpts[i].search;
            // Ignore any options we've already selected
            if (this.multiSelect === "false" && this.querySelector(`input-array-item[value='${i}']`) !== null) continue;

            // Test if we have a match
            if (match && !val.test(matchAgainst)) continue;
            // Append the result of searchFieldListResults to the results element
            const result = this.listSearchFieldResults(i, tempOpts, matchAgainst);
            if (index === 0) result.classList.add(this.selectOnEnter);
            this.searchResults.appendChild(result);
            result.addEventListener("focus", () => {
                clearTimeout(this.focusOutTimeout);
            })
            result.addEventListener("keyup", e => {
                switch (e.key) {
                    case "ArrowDown":
                    case "ArrowUp":
                        this.selectFromArrows(e.key)
                }
            })
            index++;
        }
    }

    /** Clears the search results */
    focusOutHandler(e) {
        this.arrowKeySelectionIndex = -1;
        this.searchResults.innerHTML = "";
    }

    /** Updates the search results on every keyup */
    listSearchFieldResults(i, options, matchAgainst) {

        let listItem = document.createElement("li");
        listItem.tabIndex = 0;

        /** Convert our search param into a regular expression we can use to
         higlight our search results with */
        const regex = new RegExp(`(${this.searchField.value.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&')})`, 'i');

        /** Check if this is the custom option and add a file */
        if ("custom" in options[i]) {
            listItem.classList.add(this.customElementClass);
            matchAgainst = `${escapeHtml(matchAgainst)}`;
            i = escapeHtml(i);
        }

        listItem.innerHTML = matchAgainst.replace(regex, "<strong>$1</strong>");
        listItem.setAttribute("value", i);
        listItem.setAttribute("label", options[i]);


        listItem.addEventListener("click", e =>
            this.selectSearchResult(e.target)
        );
        listItem.addEventListener("keydown", e => {
            if (e.key === "Enter") this.selectSearchResult(e.target);
            // if (e.key === "DownArrow") 
        });
        return listItem;
    }

    /** Selects the correct value from the searchResults container */
    selectSearchResult(target) {
        this.updated = true;
        let val = target.getAttribute('value');

        // let label = val;
        // if (val in this.options) label = this.options[val].label;

        this.value = val;
        // this.searchField.value = label;
        this.focusOutHandler(target);
        this.dispatchEvent(new Event("input"));
        this.dispatchEvent(new Event("change",{bubbles: true}));
    }

    /** Handles enter button events */
    selectFromEnter() {
        if (this.searchResults.childNodes.length === 0) return;
        if (this.arrowKeySelectionIndex === -1) return this.selectSearchResult(this.searchResults.childNodes[0]);
        if (this.searchResults.childNodes[this.arrowKeySelectionIndex]) return this.selectSearchResult(this.searchResults.childNodes[this.arrowKeySelectionIndex]);
    }

    /** Handles up and down arrows */
    selectFromArrows(keyCode) {
        const arrows = { "ArrowUp": -1, "ArrowDown": 1 };
        const nodes = this.searchResults.childNodes;

        if (this.arrowKeySelectionIndex === -1 && keyCode === "ArrowUp") this.searchField.focus();
        if (nodes.length === 0) return;
        let index = this.arrowKeySelectionIndex + arrows[keyCode];
        if (index === -1) return;
        if (nodes.length - 1 < index) return;
        if (nodes[index]) this.arrowKeySelectionIndex = index;

        nodes.forEach(e => e.classList.remove(this.selectOnEnter));
        nodes[index].classList.add(this.selectOnEnter)
        nodes[index].focus();
    }

}

customElements.define("input-autocomplete", AutoComplete);
