// If this page is restored from the browser's back-forward cache
// (bfcache), force a real reload so the server gets a chance to
// check the session again and redirect if the user is logged out.
window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
        window.location.reload();
    }
});