const socket = new WebSocket("ws://localhost:8080");
const bell = document.getElementById('bell-sound');
const notifyBox = document.getElementById('notification');
let hideNotifTimeout;

socket.onopen = () => {
};

socket.onmessage = function(event) {

let data;
try {
    data = JSON.parse(event.data);
} catch (e) {
    return;
}



// Play bell + show popup
bell.currentTime = 0;
bell.play();
notifyBox.textContent = msg;
notifyBox.classList.remove("show");
void notifyBox.offsetWidth;
notifyBox.classList.add("show");

// Hide after 3s
if (hideNotifTimeout) clearTimeout(hideNotifTimeout);
hideNotifTimeout = setTimeout(() => {
    notifyBox.classList.remove("show");
    hideNotifTimeout = null;
}, 3000);
// setTimeout(() => notifyBox.classList.remove("show"), 3000);

// If the full order is served, mark the table as green
if (data.type === "order" && data.table) {
    document.querySelectorAll('.table-card').forEach(card => {
    const name = card.querySelector('h4')?.textContent.trim();
    if (name === data.table) {
        card.classList.remove('occupied');
        card.classList.add('served');
    }
    });
}
};

function buildNotificationMessage(data) {
if (data.type === 'serve' && data.table && data.product) {
    return `${data.product} has been completed for ${data.table}`;
}
if (data.type === 'order' && data.table) {
    return ` Bàn ${data.table} đã pha chế xong`;
}
return null;
}