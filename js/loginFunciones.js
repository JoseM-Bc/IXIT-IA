// Evitar que el cache permita volver atrás
window.onpageshow = function(event) {
    if (event.persisted) {
        window.location.reload();
    }
};
