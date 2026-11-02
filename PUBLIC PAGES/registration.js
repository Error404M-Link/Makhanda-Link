const resident = document.getElementById("residentSection");
const organisation = document.getElementById("organisationSection");
const login = document.getElementById("loginSection");
const formWrapper = document.getElementById("formWrapper");
const radios = document.querySelectorAll("input[name='accountType']");

const registerHeader = document.getElementById("registerHeader");
const loginHeader = document.getElementById("loginHeader");
const registerFooter = document.getElementById("registerFooter");
const loginFooter = document.getElementById("loginFooter");
const accountTypeSelector = document.getElementById("accountTypeSelector");

let currentMode = 'register';

//  safety checks to prevent errors if elements are missing
function updateWrapperHeight(targetSection) {
    if (formWrapper && targetSection) {
        formWrapper.style.height = targetSection.offsetHeight + "px";
    }
}

// disables 'required' on hidden form inputs so they don't block submission
function updateRequiredFields(activeSection) {
    if (!formWrapper) return;
    const allInputs = formWrapper.querySelectorAll("input");
    allInputs.forEach(input => {
        if (activeSection.contains(input)) {
            // Restore required attribute if field needs it
            if (input.dataset.required === "true") {
                input.required = true;
            }
        } else {
            // Track originally required fields and temporarily disable them
            if (input.required) {
                input.dataset.required = "true";
            }
            input.required = false;
        }
    });
}

function switchPageMode(mode) {
    currentMode = mode;

    if (mode === 'login') {
        // Header update
        registerHeader.classList.remove("active");
        loginHeader.classList.add("active");

        // Form section update
        resident.classList.remove("active");
        organisation.classList.remove("active");
        login.classList.add("active");

        // Hide radio buttons & update footer text
        accountTypeSelector.style.display = "none";
        registerFooter.style.display = "none";
        loginFooter.style.display = "block";

        updateWrapperHeight(login);
        updateRequiredFields(login); // Toggles field requirements for login
    } else {
        // Header update
        loginHeader.classList.remove("active");
        registerHeader.classList.add("active");

        // Restore active radio selection
        const selectedType = document.querySelector("input[name='accountType']:checked").value;
        login.classList.remove("active");
        
        if (selectedType === "resident") {
            resident.classList.add("active");
            organisation.classList.remove("active"); //Explicitly deactivate org section
            updateWrapperHeight(resident);
            updateRequiredFields(resident); //Toggles field requirements for resident
        } else {
            organisation.classList.add("active");
            resident.classList.remove("active"); //Explicitly deactivate resident section
            updateWrapperHeight(organisation);
            updateRequiredFields(organisation); //Toggles field requirements for org
        }

        // Show radio buttons & update footer text
        accountTypeSelector.style.display = "flex";
        loginFooter.style.display = "none";
        registerFooter.style.display = "block";
    }
}

// Handle resident/organisation toggle
radios.forEach(radio => {
    radio.addEventListener("change", function() {
        if (currentMode === 'register') {
            if (this.value === "resident") {
                organisation.classList.remove("active");
                resident.classList.add("active");
                updateWrapperHeight(resident);
                updateRequiredFields(resident); //updates required fields on radio change
            } else {
                resident.classList.remove("active");
                organisation.classList.add("active");
                updateWrapperHeight(organisation);
                updateRequiredFields(organisation); // updates required fields on radio change
            }
        }
    });
});

// Initialize view
updateWrapperHeight(resident);
updateRequiredFields(resident); // set initial input requirements on initial load

window.addEventListener("resize", () => {
    const activeSection = document.querySelector(".section.active");
    if (activeSection) updateWrapperHeight(activeSection);
});

/* ######### event listener for when the login page is clicked #############*/

window.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    const mode = urlParams.get("mode");

    if (mode === "login") {
        switchPageMode("login");
    } else if (mode === "register") {
        switchPageMode("register");
    }
});