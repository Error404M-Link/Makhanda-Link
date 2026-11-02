document.addEventListener("DOMContentLoaded", function () {
    // 1. Auto-dismiss alerts after 4 seconds
    const alertBox = document.querySelector(".alert");
    if (alertBox) {
        setTimeout(() => {
            alertBox.style.transition = "opacity 0.5s ease";
            alertBox.style.opacity = "0";
            setTimeout(() => alertBox.remove(), 500);
        }, 4000);
    }

    // 2. Client-side form validation before submitting
    const messageForm = document.getElementById("messageForm");
    if (messageForm) {
        messageForm.addEventListener("submit", function (e) {
            const receiver = document.getElementById("receiver_username").value;
            const subject = document.getElementById("subject").value.trim();
            const body = document.getElementById("message_body").value.trim();

            if (!receiver || !subject || !body) {
                e.preventDefault();
                alert("Please complete all required fields before sending.");
            }
        });
    }
});