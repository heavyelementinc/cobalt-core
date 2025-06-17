import ICustomInput from "./ICustomInput.js";

/** TODO: Finish */
export default class IAutocompleteInput extends ICustomInput {
    constructor() {
        super();
    }
    get list() {
        return this.getAttribute("list");
    }

    get datalist() {
        const list = this.querySelector("datalist");
        if(list || !list && !this.list) return list;
        return document.querySelector(`datalist[id='${this.list}']`);
    }
    
    
}