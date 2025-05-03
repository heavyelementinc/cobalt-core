
class CobaltWebSocket extends EventTarget {
    SERVER_HOST;
    LLCOMMANDS = {
        acknowledged: "ACK:",
        replay: "REP:"
    }
    constructor(host = null) {
        super();
        this.SERVER_HOST = host ?? `${location.host}/socket/`;
        this.__sent_message_history = [];
        this.__message_id = 0;
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
        
        this.__message_id += 1;
        const __message_id = this.__message_id;
        const message_value = `${__message_id}!${JSON.stringify({type, ...details})}`;
        this.__sent_message_history[__message_id] = {
            id: __message_id,
            body: message_value,
            // sha1: crypto.subtle.digest('sha1', message_value),
            ack: false,
            reps: 0,
        }
        this.SOCKET.send(message_value);
    }

    llAcknowledgeMessage(id, sha1 = null) {
        const message = this.__sent_message_history[id];
        if(message.sha1 === sha1) {
            message.ack = true;
        }
    }

    llRepeatMessage(id, sha1 = null) {
        if(id in this.__sent_message_history) {
            if(this.__send_message_history[id].reps > 3) {
                console.warn(`Failed to send message too many times: ${this.__send_message_history[id].body}`);
            }
            this.SOCKET.send(this.__send_message_history[id].body);
            this.__send_message_history[id].reps += 1;
        }
    }

    parseLowLevelCommand(data) {
        let arr = data.split(":");
        arr.shift();
        return arr;
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
        const llcommand = data.substring(0, 3);
        switch(llcommand) {
            case LLCOMMANDS.acknowledged:
                this.llAcknowledgeMessage(...this.parseLowLevelCommand(data));
                return;
            case LLCOMMANDS.replay:
                this.llRepeatMessage(...this.parseLowLevelCommand(data));
                return;
        }
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