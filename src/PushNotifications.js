function urlB64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
      .replace(/\-/g, '+')
      .replace(/_/g, '/');
  
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
  
    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
  }

class PushNotifications {
    STATE_INITIALIZING = 0;
    STATE_UNSUPPORTED = 1;
    STATE_READY = 2;
    STATE_WORKING = 3;
    STATE_DENIED = 4;
    STATE_FAILED_PUB_KEY = 5;
    STATE_OTHER_ERROR = 6;

    constructor() {
        this.vapid_key_endpoint = "/api/notifications/resources/vapid-pub-key";
        this.applicationServerPublicKey = null;
        this.swRegistration = null;
        this.subscription = null;
        this.lastState = null;
        this.disabled  = true;
        this.innerHTML = "Initializing...";
        this.initialize();
    }

    async initialize() {
        await this.uiState(this.STATE_INITIALIZING);
        if('serviceWorker' in navigator === false) return this.uiState(this.STATE_UNSUPPORTED);
        if('PushManager' in window === false) return this.uiState(this.STATE_UNSUPPORTED);

        try{ 
            const swReg = await navigator.serviceWorker.register('/ServiceWorker.js')
            console.log("Service Worker is registered", swReg);
            this.swRegistration = swReg;
            await this.uiState(this.STATE_READY);
        } catch(error) {
            console.error('Service Worker Error', error);
            await this.uiState(this.STATE_UNSUPPORTED);
        }
    }

    async uiState(status = null) {
        if(status !== null) this.lastState = status;
        else status = this.lastState;

        switch(status) {
            case this.STATE_INITIALIZING:
                this.disabled = true;
                this.innerHTML = "<loading-spinner></loading-spinner> One moment...";
                break;
            case this.STATE_UNSUPPORTED:
                this.disabled = true;
                this.innerHTML = "Unsupported Browser";
                break; 
            case this.STATE_READY:
                this.disabled = false;
                const sub = await this.swRegistration.pushManager.getSubscription();
                this.subscription = !(sub === null);
                this.innerHTML =  `${(this.subscription) ? "Disable" : "Enable"} Push Notifications`;
                break;
            case this.STATE_WORKING:
                this.disabled = true;
                this.innerHTML = "<loading-spinner></loading-spinner> Working...";
                break;
            case this.STATE_DENIED: 
                this.disabled = true;
                this.innerHTML = "Missing permission <help-span value='Please enable the notification permission for this site to continue.'></help-span>";
                this.status = "denied";
                break;
            case this.STATE_FAILED_PUB_KEY:
                this.disabled = false;
                this.innerHTML = "Failed to fetch public key";
                break;
            case this.STATE_OTHER_ERROR:
            default:
                this.disabled = false;
                this.innerHTML = "Unknown Error";
                break;
        }

        document.body.dispatchEvent(new CustomEvent("PushNotificationStateUpdate"));
    }

    toggleSubscription() {
        this.uiState(this.STATE_WORKING);
        if(!this.subscription) return this.enroll();
        return this.unsub();
    }

    async enroll() {
        try {
            const api = new AsyncFetch(this.vapid_key_endpoint, "GET", {});
            this.applicationServerPublicKey = await api.get();
        } catch (error) {
            console.error("Failed to fetch this application's public key");
            this.uiState(this.STATE_FAILED_PUB_KEY);
        }

        let applicationServerKey;
        let subscription;
        try {
            applicationServerKey = urlB64ToUint8Array(this.applicationServerPublicKey);
        } catch(error) {
            console.error(error);
            this.uiState(this.STATE_OTHER_ERROR);
            return;
        }
        try {
            subscription = await this.swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey
            });
        } catch (error) {
            console.error(error);
            this.uiState(this.STATE_OTHER_ERROR);
            return;
        }
        
        try{
            await this.updateSubscriptionOnServer(subscription, "subscribed");
            this.subscription = true;
            this.uiState(this.STATE_READY);
        } catch (error) {
            console.error("Failed to enroll", error);
            this.uiState(this.STATE_OTHER_ERROR);
        }
    }

    async unsub() {
        const subscription = await this.swRegistration.pushManager.getSubscription();
        try{ 
            subscription.unsubscribe();
        } catch (error) {
            console.error("There was an error unsubscribing", error);
        }
        this.updateSubscriptionOnServer(subscription, "unsubscribed");
        this.status = false;
        this.uiState("ready");
    }

    updateSubscriptionOnServer(subscription, state = "enrolled", userId = null) {
        if(!userId) userId = "me";
        const api = new AsyncFetch(`/api/v1/user/${userId}/push/enrollment/${state}`,"POST",{});
        return api.submit(subscription);
    }
}

class PushNotificationEnrollmentButton extends CustomButton {
    connectedCallback() {
        this.innerHTML = "<loading-spinner></loading-spinner> Loading...";
        super.connectedCallback();
        this.disabled = true;

        this.actionMenu = this.parentNode.querySelector("action-menu");
        this.optionsTest = this.actionMenu?.querySelector("option[name='test']");

        this.addEventListener("click", () => {
            window.Cobalt.PushNotificationInstance.toggleSubscription();
        });
        document.body.addEventListener("PushNotificationStateUpdate", this.updateSelf.bind(this));
        this.updateSelf();
    }

    disconnectedCallback() {
        // document.body.removeEventListener("PushNotificationStateUpdate", this.updateSelf.bind(this));
    }

    updateSelf() {
        this.disabled  = window.Cobalt.PushNotificationInstance.disabled;
        this.innerHTML = window.Cobalt.PushNotificationInstance.innerHTML || "Oops!";
        console.log(this.optionsTest);
        this.optionsTest.disabled = this.disabled;
    }

    get disabled() {
        return this.hasAttribute("disabled");
    }

    set disabled(val) {
        if(val) this.setAttribute("disabled", "disabled");
        else this.removeAttribute("disabled");
    }
}

window.Cobalt.PushNotificationInstance = new PushNotifications();
customElements.define("push-enrollment-button", PushNotificationEnrollmentButton);
