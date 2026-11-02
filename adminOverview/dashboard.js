document.addEventListener("DOMContentLoaded", function () {

    //get the role dropdown
    const roleSelect = document.getElementById("role");

    //get the different role sections
    const residentSection = document.getElementById("residentSection");
    const wardCouncillorSection = document.getElementById("wardCouncillorSection");
    const adminSection = document.getElementById("adminSection");
    const organisationSection = document.getElementById("organisationSection");

    //get the registration form
    const registrationForm = document.getElementById("registrationForm");

    //get the street dropdown
    const addressSelect = document.getElementById("address_id");

    //get the ward button
    const wardButton = document.getElementById("wardBtn");

    //get the ward message
    const wardMessage = document.getElementById("wardMessage");

    //get the hidden ward id field
    const wardIdInput = document.getElementById("ward_id");

    //get the password fields
    const password = document.getElementById("password");
    const confirmPassword = document.getElementById("confirm_password");

    //get the password message
    const passwordMessage = document.getElementById("passwordMessage");


    //hide all role sections
    function hideAllRoleSections() {

        if (residentSection) {

            residentSection.style.display = "none";

        }

        if (wardCouncillorSection) {

            wardCouncillorSection.style.display = "none";

        }

        if (adminSection) {

            adminSection.style.display = "none";

        }

        if (organisationSection) {

            organisationSection.style.display = "none";

        }

    }


    //show the selected role section
    function showSelectedRole() {

        //hide all sections first
        hideAllRoleSections();

        //get the selected role
        const selectedRole = roleSelect
            ? roleSelect.value
            : "";


        //show the community member section
        if (
            selectedRole === "resident" &&
            residentSection
        ) {

            residentSection.style.display = "block";

        }


        //show the ward councillor section
        if (
            selectedRole === "ward_councillor" &&
            wardCouncillorSection
        ) {

            wardCouncillorSection.style.display = "block";

        }


        //show the admin section
        if (
            selectedRole === "admin" &&
            adminSection
        ) {

            adminSection.style.display = "block";

        }


        //show the organisation section
        if (
            selectedRole === "organisation" &&
            organisationSection
        ) {

            organisationSection.style.display = "block";

        }

    }


    //change the section when the role changes
    if (roleSelect) {

        roleSelect.addEventListener("change", function () {

            showSelectedRole();

        });

    }


    //show the correct section when the page loads
    showSelectedRole();


    //change the ward when a street is selected
    if (addressSelect) {

        addressSelect.addEventListener("change", function () {

            //get the selected street
            const selectedOption =
                addressSelect.options[
                    addressSelect.selectedIndex
                ];


            //check if a street was selected
            if (
                !selectedOption ||
                !selectedOption.value
            ) {

                if (wardButton) {

                    wardButton.disabled = true;

                    wardButton.textContent =
                        "Ward Selection";

                }

                if (wardMessage) {

                    wardMessage.textContent =
                        "Select a street to determine your ward.";

                }

                if (wardIdInput) {

                    wardIdInput.value = "";

                }

                return;

            }


            //get the ward id from the selected street
            const wardId =
                selectedOption.getAttribute("data-ward");


            //check if the street has a ward
            if (!wardId) {

                if (wardButton) {

                    wardButton.disabled = true;

                    wardButton.textContent =
                        "Ward Not Assigned";

                }

                if (wardMessage) {

                    wardMessage.textContent =
                        "This street has not been assigned to a ward.";

                }

                if (wardIdInput) {

                    wardIdInput.value = "";

                }

                return;

            }


            //store the ward id
            if (wardIdInput) {

                wardIdInput.value = wardId;

            }


            //enable the ward button
            if (wardButton) {

                wardButton.disabled = false;

                wardButton.textContent =
                    "Ward " + wardId;

            }


            //show the selected ward
            if (wardMessage) {

                wardMessage.textContent =
                    "Your selected street belongs to Ward " +
                    wardId + ".";

            }

        });

    }


    //check if the passwords match
    if (confirmPassword) {

        confirmPassword.addEventListener("input", function () {

            if (!password) {

                return;

            }


            //check if the password does not match
            if (
                confirmPassword.value !== "" &&
                password.value !== confirmPassword.value
            ) {

                if (passwordMessage) {

                    passwordMessage.textContent =
                        "Passwords do not match.";

                }

            } else if (
                confirmPassword.value !== ""
            ) {

                if (passwordMessage) {

                    passwordMessage.textContent =
                        "Passwords match.";

                }

            } else {

                if (passwordMessage) {

                    passwordMessage.textContent = "";

                }

            }

        });

    }


    //check the form before submitting
    if (registrationForm) {

        registrationForm.addEventListener("submit", function (event) {

            //get the selected role
            const selectedRole =
                roleSelect
                    ? roleSelect.value
                    : "";


            //check if a role was selected
            if (!selectedRole) {

                event.preventDefault();

                alert("Please select an account role.");

                return;

            }


            //check if a street was selected for a community member
            if (
                selectedRole === "resident" &&
                addressSelect &&
                !addressSelect.value
            ) {

                event.preventDefault();

                alert("Please select your street.");

                return;

            }


            //check if the passwords match before submitting
            if (
                password &&
                confirmPassword &&
                password.value !== confirmPassword.value
            ) {

                event.preventDefault();

                alert("Passwords do not match.");

                return;

            }

        });

    }

});