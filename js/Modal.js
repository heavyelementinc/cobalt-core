/** Constructing a Modal Window is simple
 * 
 * You should know everything you want in your modal before you instantiate it, because
 * new Modal() will create a new modal window.
 * 
 * It accepts an object literal as its sole argument.
 * 
 * The body property will be used as the body content of your modal
 * 
 * The chrome property will be used as your buttons. Specifying false or null will prevent
 * any chrome from being drawn.
 */

class Modal {
    /** First up, let's build our class. The constructor requires a object literal
     * as its sole argument. The object literal provided is merged with the default
     * values assigned as you can see below.
     */
    constructor({
        id = random_string(8), // Here we have a destructured argument
        classes = "", // The modal window's HTML classes
        parentClass = "", // The container's HTML classes
        body = "", // The body content of the modal window
        chrome = {}, // A list of buttons and callbacks we want to include or a non-true value for no buttons
        close_btn = true, // Include a close '✖️' button in the top right corner of the screen
        dismiss_on_router_event = true, // ADD THIS FUNCTIONALITY
        clickoutCallback = async () => false, // Callback used when clicking outside modal window (black space). Return TRUE to close the window.
        animations = true, // Allow or deny spawn in/out animations
        immediate = true, // You can wait to spawn the modal by setting this to false
    }) {
        this.id = id;
        this.classes = classes;
        this.parentClass = parentClass;
        this.body = body;
        this.chrome = chrome;
        this.close_btn = close_btn;
        this.dismiss_on_router_event = dismiss_on_router_event;
        this.clickoutCallback = clickoutCallback;
        this.animations = animations;

        // Animation stuff
        this.container_opacity_start = 0; // Starts RELATIVE to spawning
        this.container_opacity_end = 1; // REVERSE order for despawning
        this.window_transform_start = "scale(.95)";
        this.window_transform_end = "";
        this.set_animation_state();

        if (immediate) this.make_modal()
    }

    /** Render the container and modal box */
    make_modal() {
        // Create our container
        this.container = document.createElement("modal-container");
        this.container.classList = this.parentClass;
        this.container.style.opacity = this.container_opacity_start; // Animation stuff

        // Add our modal box
        this.container.innerHTML = `<modal-box id="${this.id}" class="${this.classes}"><section class='modal-body'>${this.body}</section><modal-button-row></modal-button-row></modal-box>`
        this.modal = this.container.querySelector('modal-box');
        this.modal.style.transform = this.window_transform_start; // Animation stuff

        this.button_row = this.modal.querySelector("modal-button-row");

        // Generate our buttons
        this.buttons();

        // Append our modal container and its children to the DOM
        document.querySelector("body").appendChild(this.container);

        // Handle animation stuff
        setTimeout(() => {
            this.handle_container_click();
            // Set SPAWN animation values
            this.container.style.opacity = this.container_opacity_end;
            this.modal.style.transform = this.window_transform_end;
        }, 50)
    }

    buttons() {
        // Our default button configuration will be merged with whatever
        // the user provided
        const defaults = {
            cancel: {
                label: "Cancel",
                callback: async (event) => true, // If true, close the modal
            },
            okay: {
                label: "Okay",
                callback: async (event) => true, // If true, close the modal,
                color: "var(--project-progress)"
            },
        }
        this.close_button(); // Add our close button

        // Check if our buttons are not true and do nothing in that case
        if (!this.chrome) return;

        // Merge our defaults with what the user provided (note, to override the
        // default "close" button, this.chrome must have a property named "close")
        this.chrome = { ...defaults, ...this.chrome };

        // Loop through our buttons
        for (const i in this.chrome) {
            const c = this.chrome[i]; // Our current button
            if ("display" in c && c.display === false) continue; // The display property set to false will ignore that button
            // UNIMPLEMENTED What color should the button be?
            let color = c.color | "var(--project-gray)"

            // Create our button element
            // We use actual HTML button elements for accessibility and tab indexing
            const element = document.createElement("button");
            element.classList.add(`modal-button`, `modal-button-${i}`); // Keeping things organized
            element.innerText = c.label || i;// Set the button's label

            // Add the button to the row of buttons
            this.button_row.appendChild(element);

            element.addEventListener("click", (e) => {
                // Listen for button interactions and use the button handler method
                this.button_event(this.chrome[i], e);
            })
        }
    }

    /** This is the method that handles button events. If the button's provided callback
     * returns a truth-y value, the modal will close automatically!
    */
    async button_event(btn, event) {
        let result = await btn.callback(event, btn); // Await a promise resolution
        const modalButton = new CustomEvent("modalButtonPress", { detail: { result: result } })
        this.modal.dispatchEvent(modalButton)
        if (!result) return; // If the return value is false, we do not close the modal
        this.close(); // Otherwise we close the modal
    }

    /** The close handler. Call this method to close a modal programatically. */
    close() {
        /** Handle in case of no animations */
        if (!this.animations) this.container.parentNode.removeChild(this.container);
        /** Handle despawning if animations run */
        this.container.addEventListener("transitionend", e => {
            this.container.parentNode.removeChild(this.container);
        })
        /** Set despawn animation values */
        this.container.style.opacity = this.container_opacity_start;
        this.modal.style.transform = this.window_transform_start;
    }

    /** Generates the close '✖️' button */
    close_button() {
        if (!this.close_btn) return;
        let btn = document.createElement("button");
        btn.classList.add("modal-close-button");
        btn.innerHTML = "&#10006;";
        this.container.appendChild(btn);
        btn.addEventListener("click", e => this.close());
    }

    /** Handles clicks outside of the modal box (clicks in the container) */
    handle_container_click() {
        this.container.addEventListener('click', async e => {
            let callbackResult = true;
            if (e.target === this.container && typeof this.clickoutCallback === "function") {
                callbackResult = await this.clickoutCallback(e)
                if (callbackResult) this.close();
            }
        })
    }

    /** If this.animations is false, this method will override the start and end
     * animation values used the spawn/despawn the modal.
     */
    set_animation_state() {
        if (this.animations) return; // Do nothing if true
        // If false, just override the start/end values so they're the same :P
        this.container_opacity_start = 1; // Full opacity
        this.container_opacity_end = 1;
        this.window_transform_start = ""; // No transformation
        this.window_transform_end = "";
    }


}