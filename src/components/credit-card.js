/** # Cobalt Credit Card
 * @description A credit card container
 * 
 * @element - <credit-card>
 * 
 * @copyright 2022 Heavy Element, Inc.
 * @author Gardiner Bryant
 */
 class CreditCard extends HTMLElement {
    constructor() {
        super();
    }

    connectedCallback() {
        this.shippingToggle = this.querySelector("#cobalt-credit-card--enable-shipping-address");
        if(this.shippingToggle) this.initShippingToggle();
    }

    initShippingToggle() {
        this.shippingToggle.addEventListener("change", this.shippingToggleChange.bind(this));
        this.shippingContainer = this.querySelector("#cobalt-credit-card--shipping-address");
        if(!this.shippingToggle.checked && this.shippingContainer) this.shippingContainer.classList.add("disabled");
    }

    shippingToggleChange() {
        if(!this.shippingContainer) return;
        
        this.shippingContainer.classList.toggle("disabled");

        const targets = this.querySelectorAll(".shipping-toggle--target");
        targets.forEach(element => {
            element.disabled = !this.shippingToggle.checked;
        });
    }
}

customElements.define("credit-card", CreditCard);