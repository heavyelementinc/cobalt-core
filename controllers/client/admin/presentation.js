{
    onload: () => {
        const logoUpdate = document.querySelector("#logo-updater");
        const logoTarget = logoUpdate.querySelector("img");
        console.log({logoUpdate, logoTarget})
        logoUpdate.addEventListener("requestSuccess", (event) => {
            console.log(event.detail);
            logoTarget.src = event.detail.logo.media.filename;
        })
    }
}
