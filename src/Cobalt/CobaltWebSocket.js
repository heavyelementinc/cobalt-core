
class CobaltWebSocket extends EventTarget {
    SERVER_HOST;

    constructor(host = null) {
        super();
        this.SERVER_HOST = host ?? `${location.host}/socket/`;
    }

    initSocket(onSocketOpenMessageType, onSocketOpenMessageDetails) {
        console.log("Initializing WebSocket");
        this.SOCKET = new WebSocket(`wss://${this.SERVER_HOST}`);
        this.SOCKET.onopen = (event) => {
            this.sendMessage(onSocketOpenMessageType, onSocketOpenMessageDetails);
            this.extendedOpenHandler(event);
        }

        this.SOCKET.onmessage = (event) => {
            this.fulfillNewMessage(event.data, event);
            this.extendedMessageHandler(event);
        }

        this.SOCKET.onclose = (event) => {
            console.log("Close", event);
            this.extendedCloseHandler(event);
        }

        this.SOCKET.onerror = (event) => {
            new StatusError({message: `An error has occured!<br>${event.data}`});
            console.log();
            this.extendedErrorHandler(event);
        }
    }

    /** 
     * Send a WebSocket message
     * @param {string} type
     * @param {object} details
     */
    sendMessage(type, details) {
        if(typeof details != 'object') throw new Error("Details must be an object");
        if(Array.isArray(details)) throw new Error("Details may not be an array");
        this.SOCKET.send(JSON.stringify({type, ...details}));
    }

    extendedOpenHandler(event) {}
    extendedMessageHandler(event) {}
    extendedCloseHandler(event) {}
    extendedErrorHandler(event) {}

    /**
     * This method is called when a new message is recieved. It will look for a 
     * `type` field in the incoming data and then it will try to run a function
     * within this class if it exists.
     * 
     * It also executes all commands.
     * @param {string} data - JSON-encoded data
     * @param {MessageEvent} event - WebSocket message event
     */
    fulfillNewMessage(data, event) {
        // this.dispatchEvent(new CustomEvent(data.type ?? "genericMessage", {detail: {data: details, event}}));
        const details = JSON.parse(data);
        const type = details.type ?? "genericMessage";
        const functionName = `on${type[0].toUpperCase()}${type.substring(1)}`;
        if(functionName in this) {
            this[functionName](details, event);
        }

        if("command" in details) {
            for(const command in details.command) {
                const methodName = `cmd${command[0].toUpperCase()}${command.substring(1)}`
                if(methodName in this) {
                    this[methodName](details.command[command], details, event);
                }
            }
        }
    }
}