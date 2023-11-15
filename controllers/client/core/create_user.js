{
    onload: () => {
        let form = document.querySelector("#new-user");
        form.addEventListener("requestSuccess", e => {
            window.location = `/admin/manage/user/${e.detail._id.$oid}`
        });
    }
}