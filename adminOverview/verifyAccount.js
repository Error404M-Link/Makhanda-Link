const passwordModal =
    document.getElementById("passwordModal");

const modalAction =
    document.getElementById("modalAction");

const modalUsername =
    document.getElementById("modalUsername");

const adminPassword =
    document.getElementById("adminPassword");

const passwordModalTitle =
    document.getElementById("passwordModalTitle");

const passwordModalMessage =
    document.getElementById("passwordModalMessage");

const showPasswordButton =
    document.getElementById("showPasswordButton");


function openPasswordPopup(action, username) {

    modalAction.value = action;

    modalUsername.value = username;

    adminPassword.value = "";

    adminPassword.type = "password";

    showPasswordButton.textContent = "Show";


    if (action === "verify") {

        passwordModalTitle.textContent =
            "Verify Account";

        passwordModalMessage.textContent =
            "Enter your administrator password to verify this account.";

    } else {

        passwordModalTitle.textContent =
            "Reject Account";

        passwordModalMessage.textContent =
            "Enter your administrator password to reject this account.";

    }


    passwordModal.classList.add("show");


    setTimeout(function() {

        adminPassword.focus();

    }, 100);

}


function closePasswordPopup() {

    passwordModal.classList.remove("show");

    adminPassword.value = "";

    adminPassword.type = "password";

    showPasswordButton.textContent = "Show";

}


function togglePassword() {

    if (adminPassword.type === "password") {

        adminPassword.type = "text";

        showPasswordButton.textContent = "Hide";

    } else {

        adminPassword.type = "password";

        showPasswordButton.textContent = "Show";

    }

}


passwordModal.addEventListener(
    "click",
    function(event) {

        if (event.target === passwordModal) {

            closePasswordPopup();

        }

    }
);


document.addEventListener(
    "keydown",
    function(event) {

        if (
            event.key === "Escape" &&
            passwordModal.classList.contains("show")
        ) {

            closePasswordPopup();

        }

    }
);
