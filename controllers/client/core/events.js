{
    navigation_callback: (id = null) => {
        if(id) {
            const button = document.createElement("button"),
                headline = document.querySelector("#internal_name");
            headline.appendChild(button);
            button.addEventListener("click", e => {
                const menu = new ActionMenu({event: e, title: "Manage", mode: "modal"});
                    menu.registerAction({
                        label: "Delete",
                        dangerous: true,
                        request: {
                            method: "DELETE",
                            action: `/api/v1/cobalt-events/${id}`
                        }
                    });
                    menu.draw();
            });
        }
    }
}