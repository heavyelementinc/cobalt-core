
/**
 * `<input-array>` valid attibutes include:
 *   * name - the name of the array
 *   * value - a JSON-encoded array (can be &quot; escaped)
 *   * readonly - [false] 'true' disables addition and removal of items from list
 *   * multiselect - [false] 'true' allows a single <option> to be added multiple times
 *   * allow-custom - [false] 'true' allow the user to add custom entries
 *   * pattern - [""] the pattern for custom elements to be matched against
 */
 class InputArray extends HTMLElement {

    static get observedAttributes() {
        return [
            'value',
            'allow-custom',
            'multiselect',
            // 'readonly'
        ];
    }

    constructor() {
        super();

        /** Establish our values */
        this.optHTML = "";
        this.searchField = null;
        this.fieldSet = null;
        this.arrowKeySelectionIndex = -1;
        this.customElementClass = "input-array--list-custom";

        this.name = this.getAttribute("name") || null;
        this.readonly = this.getAttribute("readonly") || "false";
        this.multiSelect = this.getAttribute("multiselect") || "false";
        this.allowCustomInputs = this.getAttribute("allow-custom") || "false";
        this.pattern = this.getAttribute("pattern") || "";
        this.placeholder = this.getAttribute("placeholder") || "Search";
        this.limit = 0;
    }

    connectedCallback() {
        this.initUI();

        this.options = this.initOptions();

        this.initSearchField();
    }

    get value() {
        let value = [];
        for(const i of this.fieldSet) {
            value.push(i.value);
        }
        return value;
    }

    set value(value) {
        const errorMessage = "Invalid type assignment to InputArray. Must be an array or parsable JSON.";
        let val = value;
        switch(typeof value) {
            case "object":
                if(Array.isArray(value)) break;
            case "string":
                try{
                    val = JSON.parse(value);
                } catch(error) {
                    throw new Error(errorMessage);
                }
                break;
            default:
                throw new Error(errorMessage);
        }

        this.initSelectedValues(val);
    }

    /** Init the valid options of the current component */
    initOptions() {
        const opts = this.querySelectorAll("option");
        let options = {};
        let optHTML = "";
        let selected = [];
        opts.forEach(e => { // Loop through our options
            if(e.getAttribute("selected")) selected.push(e.innerText);
            options[e.getAttribute("value")] = {
                "search": e.innerText, // Create searchable index
                "label": e.innerHTML   // Create label index
            };
            optHTML += e.outerHTML // We rebuild our opts in <optgroup> later
        });
        if(selected) this.value = selected;
        this.optHTML = optHTML;
        return options;
    }

    initUI() {
        const ro = this.searchElements();
        this.innerHTML = `<fieldset></fieldset><optgroup>${this.optHTML}</optgroup>${ro}`;
        // delete this.optHTML; // Cleanup. We don't need this anymore.

        /** Establish our UI elements */
        this.fieldSet = this.querySelector("fieldset");
        this.searchField = this.querySelector("input[type='search']");
        // this.searchResults = this.querySelector("ul.search-results");
    }

    initSelectedValues(value) {
        this.fieldSet.innerHTML = ""; // Clobber the existing selected values

        let tags = ""; // Create our new elements
        let tempOpts = this.options;
        for (const i of value) {
            /** Check if we allow custom values */
            if (this.allowCustomInputs === true && i in tempOpts === false) tempOpts[i] = i;
            if (i in tempOpts === false) continue;

            tags += this.addTag(i, tempOpts[i].label, this.readonly);
        }

        this.fieldSet.innerHTML += tags;
    }

    searchElements() {
        if (this.readonly === "readonly") return "";
        let placeholder = this.placeholder;
        let pattern = "";
        if (this.allowCustomInputs && this.pattern) pattern = " " + this.pattern;
        this.searchResults = document.createElement("ul");
        this.searchResults.classList.add("input-array--search-results");
        return `<input type="search" placeholder="${placeholder}"${pattern}>`;
    }

    initSearchField() {
        if (!this.searchField) return;
        this.searchField.addEventListener("keyup", e => {
            switch (e.key) {
                case "Enter":
                    this.selectFromEnter();
                    return;
                case "ArrowDown":
                case "ArrowUp":
                    e.preventDefault();
                    this.selectFromArrows(e.key);
                    return;
            }
            this.arrowKeySelectionIndex = -1;
            this.searchResults.innerHTML = "";
            let toSearch = e.target.value;
            if (toSearch === "") return;

            const val = new RegExp(`${toSearch}`, 'i');

            let tempOpts = { ...this.options };
            if (this.allowCustomInputs === "true") tempOpts[toSearch] = {
                "search": toSearch,
                "label": toSearch,
                "custom": true
            }

            if (!this.parentNode.contains(this.searchResults)) this.parentNode.insertBefore(this.searchResults, this)

            this.searchResults.style.top = this.searchField.offsetTop + this.searchField.offsetHeight - 1 + "px";
            this.searchResults.style.left = this.searchField.offsetLeft + 4 + "px";
            this.searchResults.style.width = this.searchField.offsetWidth - 8 + "px";

            for (var i in tempOpts) {
                const matchAgainst = tempOpts[i].search;
                // Ignore any options we've already selected
                if (this.multiSelect === "false" && this.querySelector(`input-array-item[value='${i}']`) !== null) continue;

                // Test if we have a match
                if (val.test(matchAgainst)) {
                    // Append the result of searchFieldListResults to the results element
                    this.searchResults.appendChild(this.listSearchFieldResults(i, tempOpts, matchAgainst))
                }

            }
        })

        this.searchField.addEventListener("focusout", e => {
            this.focusOutTimeout = setTimeout(() => this.focusOutHandler(e), 600);
        })

        this.searchField.addEventListener("focus", e => {
            clearTimeout(this.focusOutTimeout)
        })
    }

    focusOutHandler(e) {
        this.searchResults.innerHTML = "";
    }

    listSearchFieldResults(i, options, matchAgainst) {
        let listItem = document.createElement("li");
        listItem.tabIndex = 0;

        /** Convert our search param into a regular expression we can use to
         higlight our search results with */
        const regex = new RegExp(`(${this.searchField.value})`, 'i');

        /** Check if this is the custom option and add a file */
        if ("custom" in options[i]) {
            listItem.classList.add(this.customElementClass);
            matchAgainst = `${escapeHtml(matchAgainst)}`;
            i = escapeHtml(i);
        }

        listItem.innerHTML = matchAgainst.replace(regex, "<strong>$1</strong>");
        listItem.setAttribute("value", i);
        listItem.setAttribute("label", options[i]);


        listItem.addEventListener("click", e => this.selectSearchResult(e.target));
        listItem.addEventListener("keydown", e => {
            if (e.key === "Enter") this.selectSearchResult(e.target);
            // if (e.key === "DownArrow") 
        });
        return listItem;
    }

    selectSearchResult(target) {
        let tag = document.createElement("div");
        let val = target.getAttribute('value');
        let label = val;
        if (val in this.options) label = this.options[val].label;

        tag.innerHTML = this.addTag(val, label);

        this.searchResults.innerHTML = "";
        this.searchField.value = "";

        this.fieldSet.appendChild(tag.querySelector("input-array-item"));
    }

    addTag(val, label, readonly = false) {
        let ro = (readonly === "readonly") ? ' readonly="readonly"' : "";
        return `<input-array-item value="${val}"${ro}><span>${label}</span></input-array-item>`;
    }

    selectFromEnter() {
        if (this.searchResults.childNodes.length === 0) return;
        if (this.arrowKeySelectionIndex === -1) return this.selectSearchResult(this.searchResults.childNodes[0]);
        if (this.searchResults.childNodes[this.arrowKeySelectionIndex]) return this.selectSearchResult(this.searchResults.childNodes[this.arrowKeySelectionIndex]);
    }

    selectFromArrows(keyCode) {
        const arrows = { "ArrowUp": -1, "ArrowDown": 1 };
        const nodes = this.searchResults.childNodes;
        if (this.arrowKeySelectionIndex === -1 && keyCode === "ArrowUp") return;
        if (nodes.length === 0) return;
        let index = this.arrowKeySelectionIndex + arrows[keyCode];
        if (index === -1) return;
        if (nodes.length - 1 < index) return;
        if (nodes[index]) this.arrowKeySelectionIndex = index;
        const selectOnEnter = "input-array--will-select-on-enter";
        nodes.forEach(e => e.classList.remove(selectOnEnter));
        nodes[index].classList.add(selectOnEnter)
    }



    attributeChangedCallback(name, oldValue, newValue) {
        const callable = `change_handler_${name.replace("-", "_")}`;
        if (callable in this) {
            this[callable](newValue, oldValue);
        }
    }

    change_handler_allow_custom(newValue, oldValue) {
        if (newValue === "true") this.allowCustomInputs = "true";
        else this.allowCustomInputs = "false";
    }

    change_handler_multiselect(newValue) {
        if (newValue === "true") this.multiSelect = "true";
        else this.multiSelect = "false";
    }

    change_handler_readonly(newValue) {
        if (newValue === "readonly") this.readonly = "readonly";
        else this.readonly = "false";

        this.initUI();
    }

    change_handler_value(newValue, oldValue) {
        let val;
        try {
            val = JSON.parse(newValue);
        } catch (error) {
            throw new Error("TypeError: Assigned value to input-array must be parseable JSON");
        }
        this.initSelectedValues(val);
    }

    change_handler_limit(newValue) {
        this.limit = newValue;
    }
}

customElements.define("input-array", InputArray)

class InputArrayItem extends HTMLElement {
    constructor() {
        super();
        this.readonly = this.getAttribute("readonly");
        this.init();
    }

    get value() {
        return this.getAttribute("value");
    }

    init() {
        if (this.readonly !== "readonly") this.addDeleteButton();
    }

    addDeleteButton() {
        const button = document.createElement("input");
        button.type = "button";
        button.value = "✖";
        button.addEventListener("click", e => {
            this.parentNode.removeChild(this);
        })
        this.appendChild(button);
    }
}

customElements.define("input-array-item", InputArrayItem)