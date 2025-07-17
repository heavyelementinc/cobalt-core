import { TagSelect } from "./TagSelect.js";
export default {};
export class InputBinary extends TagSelect {
    constructor() {
        super();
    }

    get value() {
        const tags = this.querySelectorAll("button[aria-pressed='true']");
        let value = 0;
        for(const tag of tags) {
            value += Number(tag.value);
        }
        return value;
    }

    set value(val) {
        const tags = this.querySelectorAll("button");
        for(const tag of tags) {
            tag.ariaPressed = false;
            if(Number(tag.value) & val) tag.ariaPressed = true;
        }
    }

}
