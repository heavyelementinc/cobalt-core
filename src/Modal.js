/**
 * You should know everything you want in your modal before you instantiate it, 
 * because new Modal() will create a new modal window. 
 * 
 * The Modal Constructor accepts an object literal as its sole argument. You may
 * use the following 
 * 
 * 
 * 
 * 
 * The chrome property will be used as your buttons. Specifying false or null 
 * will prevent any chrome from being drawn.
 * 
 * If you want to easily attach logic to buttons, you may assign logic to 'okay'
 * or 'close' (or any other button you specified in `chrome` object when you
 * instantiated the Modal window)
 * 
 * Use the following syntax to recieve data from the button click:
 * 
 * async function foo(){
 *      const modal = new Modal();
 *      const result = await modal.on('okay',(container) => {
 *          return "some result";
 *      })
 *      if (result === "some result") // true!
 * }
 * 
 * This is obviously only good for one go around, though. If you need to collect
 * data you can listen for the 'modalButtonPress' event on the `modal` property
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
        url = "", // A URL to use to download the modal content of a page
        type = "",
        chrome = {}, // A list of buttons and callbacks we want to include or a non-true value for no buttons
        close_btn = true, // Include a close '✖️' button in the top right corner of the screen
        dismiss_on_router_event = true, // ADD THIS FUNCTIONALITY
        clickoutCallback = async () => false, // Callback used when clicking outside modal window (black space). Return TRUE to close the window.
        animations = true, // Allow or deny spawn in/out animations
        immediate = false, // You can wait to spawn the modal by setting this to false
        lockViewport = true,
        event = null,
        zIndex = null,
        pageTitle = null,
    }) {
        this.id = id;
        this.classes = classes;
        this.parentClass = parentClass;
        this.body = body;
        this.url = url;
        this.chrome = chrome;
        this.close_btn = close_btn;
        this.dismiss_on_router_event = dismiss_on_router_event;
        this.clickoutCallback = clickoutCallback;
        this.animations = animations;
        this.dialog = document.createElement("modal-box");
        this.buttonResult = {};
        this.shouldLockViewport = lockViewport
        this.lockedViewportClass = "scroll-locked";
        this.zIndex = zIndex;
        this.event = event;
        this.type = type;


        this.chooseButtonsAndLayout()

        this.pageTitle = document.title;
        this.modalTitle = pageTitle;

        // Animation stuff
        this.container_opacity_start = 0; // Starts RELATIVE to spawning
        this.container_opacity_end = 1; // REVERSE order for despawning
        this.window_transform_start = "scale(.95)";
        this.window_transform_end = "";
        this.set_animation_state();

        if (immediate) this.draw()
    }

    /** Render the container and modal box */
    async draw() {
        if (this.shouldLockViewport) {
            this.lockViewport()
        }

        if (this.modalTitle) document.title = this.modalTitle;

        // Create our container
        this.container = document.createElement("modal-container");
        this.container.classList = this.parentClass;
        this.container.style.opacity = this.container_opacity_start; // Animation stuff

        if (this.zIndex) this.container.style.zIndex = this.zIndex;

        // Append our modal container and its children to the DOM
        document.querySelector("body").appendChild(this.container);
        if (!this.zIndex && event) {
            const spawnIndex = spawn_priority(event);
            if (spawnIndex) this.container.style.zIndex = spawnIndex + 1;
        }
        this.close_button(); // Add our close button

        // Set a window 
        history.pushState({ page: 1 }, this.modalTitle || document.title, "");
        window.addEventListener("popstate", e => { if (this.container.parentNode !== null) this.close(e) }, { once: true })


        // Handle animation stuff
        setTimeout(() => {
            this.handle_container_click();
            // Set SPAWN animation values
            this.container.style.opacity = this.container_opacity_end;
        }, 50);

        this.loading_spinner_start();

        let body = await this.get_body_content(); // Await body content

        setTimeout(() => {
            this.loading_spinner_end();
            this.dialog.style.opacity = this.container_opacity_end;
            this.dialog.style.transform = this.window_transform_end;
        }, 50);

        // Add our modal box
        this.dialog.id = this.id || "";
        this.dialog.setAttribute("class", this.classes || "");
        this.dialog.innerHTML = `<section class='modal-body'>${body}</section><modal-button-row></modal-button-row>`;
        this.container.appendChild(this.dialog);

        this.dialog = this.container.querySelector('modal-box');

        this.dialog.style.transform = this.window_transform_start; // Animation stuff

        this.button_row = this.dialog.querySelector("modal-button-row");

        // Generate our buttons
        this.buttons();

        this.handleLightboxGallery()

        if (this.url) window.router.navigation_event(null, this.url);

        return this.dialog;
    }

    lockViewport() {
        let width = get_offset(document.body).w;
        document.body.style.overflow = "hidden";
        document.body.style.width = `${width}px`;
    }

    unlockViewport() {
        document.body.style.overflow = "unset";
        document.body.style.width = "unset";
    }

    loading_spinner_start() {
        this.loading_spinner = document.createElement("loading-spinner");
        this.loading_spinner_timeout = setTimeout(() => {
            this.container.appendChild(this.loading_spinner);
            let offset_top = this.loading_spinner.offsetTop;
            let offset_left = this.loading_spinner.offsetLeft;
            this.loading_spinner.style.position = "absolute";
            this.loading_spinner.style.top = `${offset_top}px`;
            this.loading_spinner.style.left = `${offset_left}px`;
            this.loading_spinner.style.color = "white";
        }, 200);
        this.loading_spinner_timeout_error = setTimeout(() => {
            if ("parentNode" in this.loading_spinner && this.loading_spinner.parentNode) this.loading_spinner.parentNode.removeChild(this.loading_spinner)
            this.container.append("Something went wrong.");
        }, 1000 * 20);
    }

    loading_spinner_end() {
        clearTimeout(this.loading_spinner_timeout);
        clearTimeout(this.loading_spinner_timeout_error);
        if ("parentNode" in this.loading_spinner && this.loading_spinner.parentNode) this.loading_spinner.parentNode.removeChild(this.loading_spinner);
    }

    async get_body_content() {
        // If we haven't been explicitly given a URL, return body even if it's blank
        if (!this.url) return this.body;
        try {
            const page = new ApiFetch(`/api/v1/page/?route=${this.url}`, 'GET', {});
            let body = await page.get();
            this.pageTitle = document.title
            document.title = body.title
            return body.body;
        } catch (error) {
            return error.result.error;
        }
    }

    buttons() {
        const defaults = this.defaults;

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
            let color = c.color | "var(--project-gray)";
            let classes = "modal-button";
            if (c.dangerous) classes = " modal--button-dangerous";
            // Create our button element
            // We use actual HTML button elements for accessibility and tab indexing
            const element = document.createElement("button");
            element.classList.add(`modal-button`, `modal-button-${i}`, classes); // Keeping things organized
            element.innerText = c.label || i;// Set the button's label
            // element.style.background = color;

            // Add the button to the row of buttons
            this.button_row.appendChild(element);

            element.addEventListener("click", (e) => {
                // Listen for button interactions and use the button handler method
                this.buttonResult = this.button_event(i, e);
            })
        }
    }

    /** This is the method that handles button events. If the button's provided callback
     * returns a truth-y value, the modal will close automatically!
    */
    async button_event(btn, event) {

        let result = await this.chrome[btn].callback(this.container, event, btn); // Await a promise resolution

        const modalButton = new CustomEvent("modalButtonPress", { detail: { type: btn, result: result } })
        this.dialog.dispatchEvent(modalButton);
        if (result === false) return result; // If the return value is false, we do not close the modal
        this.close(); // Otherwise we close the modal
        return result;
    }

    /** Assign a callback to a button after spawning a modal button,
     *      Callback recieved:
     *         *container*
     *         *event*
     *         *button*
     * 
     * @param button the name of the button
     * @param callback the callback to be fired when that button is clicked
     */
    async on(button, callback) {
        if (button in this.chrome) this.chrome[button].callback = callback;
        else if (button in this.defaults) this.defaults[button].callback = callback;
        else throw new Error(`That button (${button}) doesn't exist`);

        return new Promise((resolve, reject) => {
            this.dialog.addEventListener("modalButtonPress", e => {
                resolve(e);
            });
        });
    }

    /** The close handler. Call this method to close a modal programatically. */
    close(e = null) {
        this.unlockViewport();

        // Unset popstate listener. We return if e is null because this function
        // will be called again. Probably really hacky, but it works.
        if (e === null) return history.back();

        /** Handle in case of no animations */
        if (!this.animations) this.container.parentNode.removeChild(this.container);
        /** Handle despawning if animations run */
        this.container.addEventListener("transitionend", e => {
            this.container.parentNode.removeChild(this.container);
        })
        /** Set despawn animation values */
        this.container.style.opacity = this.container_opacity_start;
        this.dialog.style.transform = this.window_transform_start;
        if (this.pageTitle) document.title = this.pageTitle;
    }

    /** Generates the close '✖️' button */
    close_button() {
        if (!this.close_btn) return;
        let btn = document.createElement("button");
        btn.classList.add("modal-close-button");
        btn.innerHTML = window.closeGlyph;
        this.container.appendChild(btn);
        btn.addEventListener("click", e => this.close());
    }

    /** Handles clicks outside of the modal box (clicks in the container) */
    handle_container_click() {
        this.container.addEventListener('click', async e => {
            let callbackResult = true;
            if (e.target === this.container && typeof this.clickoutCallback === "function") {
                try {
                    callbackResult = await this.clickoutCallback(e)
                    if (callbackResult) this.close();
                } catch (error) {
                    
                }
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

    /**
     * @todo fix the jank
     * @returns 
     */
    handleLightboxGallery() {
        if(this.parentClass !== "lightbox") return;
        // if("originalTarget" in this.event === false) return;

        let currentLightboxUrl = this.container.querySelector("img");
        const fullRes = `[full-resolution="${currentLightboxUrl.getAttribute("src")}"]`,
            src = `[src="${currentLightboxUrl.getAttribute("src")}"]`
        
        const node = document.querySelector(fullRes) ?? document.querySelector(src);
        let nextSibling = node.nextSibling ?? node.parentNode.childNodes[0] ?? null;
        let prevSibling = node.previousSibling ?? node.parentNode.childNodes[node.parentNode.childNodes.length - 1] ?? null

        if(nextSibling === node) return;
        if(prevSibling === node) return;

        const next = document.createElement("button"),
            prev = document.createElement("button");
        next.tabIndex = 0;
        prev.tabIndex = 0;

        next.addEventListener('click', e => {
            this.container.parentNode.removeChild(this.container);
            // @todo launch new lightbox where we have control over animations
            lightbox(nextSibling,false);
            // nextSibling.dispatchEvent(new Event("click"));
        });

        prev.addEventListener('click', e => {
            this.container.parentNode.removeChild(this.container);
            lightbox(prevSibling,false);

            // prevSibling.dispatchEvent(new Event("click"));
        });

        this.container.prepend(prev);
        this.container.append(next);
    }

    chooseButtonsAndLayout() {
        // Our default button configuration will be merged with whatever the
        // user provided
        const buttons = {
            default: {
                cancel: {
                    label: "Cancel",
                    dangerous: false,
                    callback: async (event) => true, // If true, close the modal
                },
                okay: {
                    label: "Okay",
                    dangerous: false,
                    callback: async (event) => true, // If true, close the modal,
                    // color: "var(--project-progress)"
                },
            }
        }

        // const body = {
        //     default: 
        // }

        let buttonSelector = "default";

        switch(this.type) {
            default:
                buttonSelector = "default";
                break;
        }

        this.defaults = buttons[buttonSelector];
    }

}