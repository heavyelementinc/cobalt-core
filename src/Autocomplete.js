/** <auto-complete> supports the following attributes
 *
 *  * name - the HTML name attribute of the resulting selection
 *  * endpoint - the api endpoint to query for matches. Matches should be returned in the following format:
 *      {
 *          "key": "search",
 *          "value": "Search"
 *      }
 *               
 */
class AutoComplete extends HTMLElement {
    constructor() {
        super();
        this.defineAttributes();
    }

    defineAttributes() {
        this.name = this.getAttribute("name");
        this.endpoint = this.getAttribute("endpoint");
        this.onSelect = this.getAttribute("onselect") || "";

        // this.timeout = this.getAttribute("timeout");
        // this.min = this.getAttribute("min");
    }
}

customElements.define("auto-complete", AutoComplete);