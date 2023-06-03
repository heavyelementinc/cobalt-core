self.addEventListener('push', async function (event) {
    console.log(event);
    const data = await event.data.json();
    let json = {};
    try {
      if(data && typeof data === "string") json = JSON.parse(data);
      else if(typeof data === "object") json = data;
    } catch (e) {
      console.log("[Service Worker] Notification recieved but the data was not valid JSON!");
      return;
    }

    const title = json.subject;
    let options = {
      body: json.message,
      data: json.data || {}
    };

    if(json.details?.icon || json.icon) options.icon = json.details?.icon || json.icon;
    if(json.details?.badge || json.badge) options.badge = json.details?.badge || json.badge;
    if(json.details?.image) options.image = json.details?.image;
  
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function(event) {
  event.notification.close();

  if(!event.notification.data?.path) return;
  event.waitUntil(
    clients.openWindow(event.notification.data?.path)
  );
});
