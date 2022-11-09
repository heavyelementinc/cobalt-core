{
    navigation_callback: () => {
        let buttons = document.querySelectorAll("async-button:is(.toggle-read-status)"),
        unread = "read-status--unread",
        read = "read-status--read",
        updateButton = (el, toStatus = null) => {
            let add = unread, 
                remove = read,
                row = el.closest("flex-row"),
                isRead = (toStatus === null) ? row.classList.contains(read) : toStatus,
                label;
            switch(isRead) {
                case true:
                    add = read;
                    remove = unread;
                    label = "unread";
                    break;
                case false:
                default:
                    add = unread;
                    remove = read;
                    label = "read";
                    break;
            }
            row.classList.add(add);
            row.classList.remove(remove);
            el.innerText = label;
            el.value = !isRead;
        }
        buttons.forEach(el => {
            el.addEventListener("success", e => {
                console.log(e);
                updateButton(el, e.detail);
            });
        });
    }
}
