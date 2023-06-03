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
    constructor() {
        this.vapid_key_endpoint = "/resource/vapid-key.json";
        this.applicationServerPublicKey = null;
        this.swRegistration = null;
        this.subscription = null;
        this.lastState = null;
        this.disabled  = true;
        this.innerHTML = "Initializing...";
        this.initialize();
    }

    async initialize() {
        await this.uiState("initializing");
        if('serviceWorker' in navigator === false) return this.uiState("unsupported");
        if('PushManager' in window === false) return this.uiState("unsupported");

        try{ 
            const swReg = await navigator.serviceWorker.register('/ServiceWorker.js')
            console.log("Service Worker is registered", swReg);
            this.swRegistration = swReg;
            await this.uiState("ready");
        } catch(error) {
            console.error('Service Worker Error', error);
            await this.uiState("unsupported");
        }
    }

    async uiState(status = null) {
        if(status !== null) this.lastState = status;
        else status = this.lastState;

        switch(status) {
            case "initializing":
                this.disabled = true;
                this.innerHTML = "<loading-spinner></loading-spinner> One moment...";
                break;
            case "unsupported":
                this.disabled = true;
                this.innerHTML = "Unsupported Browser";
                break; 
            case "ready":
                this.disabled = false;
                const sub = await this.swRegistration.pushManager.getSubscription();
                this.subscription = !(sub === null);
                this.innerHTML =  (this.subscription) ? "Disable Push Notifications" : "Enable Push Notifications";
                break;
            case "working":
                this.disabled = true;
                this.innerHTML = "<loading-spinner></loading-spinner> Working...";
                break;
            case "denied": 
                this.disabled = true;
                this.innerHTML = "Missing permission <help-span value='Please enable the notification permission for this site to continue.'></help-span>";
                this.status = "denied";
                break;
            case "failed_pub_key":
                this.disabled = false;
                this.innerHTML = "Failed to fetch public key";
                break;
            case "other_error":
            default:
                this.disabled = false;
                this.innerHTML = "Unknown Error";
                break;
        }

        document.body.dispatchEvent(new CustomEvent("PushNotificationStateUpdate"));
    }

    toggleSubscription() {
        this.uiState("working");
        if(!this.subscription) return this.enroll();
        return this.unsub();
    }

    async enroll() {
        try {
            const api = new ApiFetch(this.vapid_key_endpoint, "GET", {});
            this.applicationServerPublicKey = await api.get();
        } catch (error) {
            console.error("Failed to fetch this application's public key");
            this.uiState("failed_pub_key");
        }

        const applicationServerKey = urlB64ToUint8Array(this.applicationServerPublicKey);
        const subscription = await this.swRegistration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: applicationServerKey
        });

        try{
            await this.updateSubscriptionOnServer(subscription, "subscribed");
            this.subscription = true;
            this.uiState('ready');
        } catch (error) {
            console.error("Failed to enroll", error);
            this.uiState('other_error');
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
        const api = new ApiFetch(`/api/v1/user/${userId}/push/enrollment/${state}`,"POST",{});
        return api.send(subscription);
    }
}

class PushNotificationEnrollmentButton extends CustomButton {
    connectedCallback() {
        
        this.innerHTML = "<loading-spinner></loading-spinner> Loading...";
        super.connectedCallback();
        this.disabled = true;
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

