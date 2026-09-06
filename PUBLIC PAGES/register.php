<?php

include 'db.php';

/* ==================================================
   GET STREETS FROM DATABASE
================================================== */

$sql = "SELECT address_id, ward_id, street_name
        FROM address_directory
        ORDER BY street_name";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Could not load streets: " . mysqli_error($conn));
}

/* Store streets so we can use them for both sections */
$streets = [];

while ($row = mysqli_fetch_assoc($result)) {
    $streets[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="registration.css">
    <link rel="stylesheet" href="publicfaultBoard.css">
    <link rel="stylesheet" href="backButton.css">

    <title>M-LINK | Sign Up</title>

</head>

<body>

<!-- ==================================================
     NAVBAR
================================================== -->

<nav class="navbar">

    <div class="logo">

        <a href="index.php">

            <img src="makanalogo.jpeg" alt="M-LINK">

        </a>

    </div>

</nav>


<!-- ==================================================
     BACK BUTTON
================================================== -->

<div class="backNavWrapper">

    <div class="backNavContainer">

        <a href="javascript:history.back()" class="backBtn">

            <svg width="18"
                 height="18"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 stroke-linecap="round"
                 stroke-linejoin="round">

                <line x1="19"
                      y1="12"
                      x2="5"
                      y2="12">
                </line>

                <polyline points="12 19 5 12 12 5">
                </polyline>

            </svg>

            <span>Back</span>

        </a>

    </div>

</div>


<!-- ==================================================
     REGISTRATION
================================================== -->

<section class="registration">

    <div class="registrationBody">


        <!-- HEADER -->

        <div class="registrationHeader">

            <div class="header-content">

                <h4>Create Your M-LINK Account</h4>

                <p>
                    Register as a resident or organisation
                    to connect with your community.
                </p>

            </div>

        </div>


        <!-- ==================================================
             FORM
        ================================================== -->

        <div class="registrationForm">

            <form id="registrationForm"
                  action="urreg.php"
                  method="POST"
                  autocomplete="off">


                <!-- ==================================================
                     ACCOUNT TYPE
                ================================================== -->

                <div class="account-type">

                    <label class="account-option">

                        <input type="radio"
                               name="accountType"
                               value="resident"
                               checked>

                        <span>Resident</span>

                    </label>


                    <label class="account-option">

                        <input type="radio"
                               name="accountType"
                               value="organisation">

                        <span>Organisation</span>

                    </label>

                </div>


                <!-- ==================================================
                     RESIDENT SECTION
                ================================================== -->

                <div id="residentSection"
                     class="section active">

                    <h3>Resident Registration</h3>


                    <!-- FIRST NAME + SURNAME -->

                    <div class="form-row">

                        <div class="form-group">

                            <label for="resident_first_name">
                                First Name *
                            </label>

                            <input type="text"
                                   id="resident_first_name"
                                   name="first_name"
                                   placeholder="Enter your first name"
                                   maxlength="100"
                                   required>

                        </div>


                        <div class="form-group">

                            <label for="resident_surname">
                                Surname *
                            </label>

                            <input type="text"
                                   id="resident_surname"
                                   name="surname"
                                   placeholder="Enter your surname"
                                   maxlength="100"
                                   required>

                        </div>

                    </div>


                    <!-- USERNAME -->

                    <div class="form-group">

                        <label for="resident_username">
                            Username *
                        </label>

                        <input type="text"
                               id="resident_username"
                               name="username"
                               placeholder="Choose a username"
                               maxlength="50"
                               required>

                        <small>
                            Your username will be used when logging in.
                        </small>

                    </div>


                    <!-- CONTACT -->

                    <div class="form-group">

                        <label for="resident_contact">
                            Contact Number *
                        </label>

                        <input type="text"
                               id="resident_contact"
                               name="contact"
                               placeholder="0821234567"
                               maxlength="10"
                               minlength="10"
                               pattern="[0-9]{10}"
                               inputmode="numeric"
                               required>

                        <small>
                            Enter exactly 10 digits.
                        </small>

                    </div>


                    <!-- PASSWORD -->

                    <div class="form-row">

                        <div class="form-group">

                            <label for="resident_password">
                                Password *
                            </label>

                            <input type="password"
                                   id="resident_password"
                                   name="password"
                                   placeholder="Create a password"
                                   minlength="8"
                                   required>

                        </div>


                        <div class="form-group">

                            <label for="resident_confirm_password">
                                Confirm Password *
                            </label>

                            <input type="password"
                                   id="resident_confirm_password"
                                   name="confirm_password"
                                   placeholder="Confirm your password"
                                   minlength="8"
                                   required>

                        </div>

                    </div>


                    <!-- STREET -->

                    <div class="form-group">

                        <label for="resident_address_id">
                            Street *
                        </label>

                        <div class="ward-wrapper">

                            <select id="resident_address_id"
                                    name="address_id"
                                    required>

                                <option value="">
                                    -- Select your street --
                                </option>

                                <?php foreach ($streets as $row): ?>

                                    <option
                                        value="<?= htmlspecialchars($row['address_id']); ?>"
                                        data-ward="<?= htmlspecialchars($row['ward_id']); ?>">

                                        <?= htmlspecialchars($row['street_name']); ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>


                            <button type="button"
                                    class="wardBtn"
                                    id="residentWardBtn"
                                    disabled>

                                Ward Selection

                            </button>

                        </div>


                        <small id="residentWardMessage">

                            Select a street to determine your ward.

                        </small>

                    </div>


                    <!-- HIDDEN WARD ID -->

                    <input type="hidden"
                           id="resident_ward_id"
                           name="ward_id"
                           value="">


                    <!-- FULL ADDRESS -->

                    <div class="form-group">

                        <label for="resident_address">
                            Address
                        </label>

                        <textarea
                            id="resident_address"
                            name="address"
                            placeholder="Enter your full residential address"
                            maxlength="500"></textarea>

                        <small>
                            Optional. Include suburb and postal code where applicable.
                        </small>

                    </div>


                    <!-- BUTTONS -->

                    <div class="formButtons">

                        <button type="reset"
                                class="resetBtn">

                            Reset

                        </button>


                        <button type="submit"
                                class="submitBtn">

                            Create Resident Account

                        </button>

                    </div>

                </div>


                <!-- ==================================================
                     ORGANISATION SECTION
                ================================================== -->

                <div id="organisationSection"
                     class="section">


                    <h3>Organisation Registration</h3>


                    <p class="section-description">

                        Register your organisation on M-LINK
                        to connect with the community.

                    </p>


                    <!-- ORGANISATION NAME -->

                    <div class="form-group">

                        <label for="organisation_name">
                            Organisation Name *
                        </label>

                        <input type="text"
                               id="organisation_name"
                               name="organisation_name"
                               placeholder="Enter organisation name"
                               maxlength="150"
                               disabled>

                    </div>


                    <!-- ORGANISATION USERNAME -->

                    <div class="form-group">

                        <label for="organisation_username">
                            Username *
                        </label>

                        <input type="text"
                               id="organisation_username"
                               name="organisation_username"
                               placeholder="Choose a username"
                               maxlength="50"
                               disabled>

                        <small>
                            This username will be used when logging in.
                        </small>

                    </div>


                    <!-- ORGANISATION CONTACT -->

                    <div class="form-group">

                        <label for="organisation_contact">
                            Contact Number *
                        </label>

                        <input type="text"
                               id="organisation_contact"
                               name="organisation_contact"
                               placeholder="0821234567"
                               maxlength="10"
                               minlength="10"
                               pattern="[0-9]{10}"
                               inputmode="numeric"
                               disabled>

                    </div>


                    <!-- ORGANISATION PASSWORD -->

                    <div class="form-row">

                        <div class="form-group">

                            <label for="organisation_password">
                                Password *
                            </label>

                            <input type="password"
                                   id="organisation_password"
                                   name="organisation_password"
                                   placeholder="Create a password"
                                   minlength="8"
                                   disabled>

                        </div>


                        <div class="form-group">

                            <label for="organisation_confirm_password">
                                Confirm Password *
                            </label>

                            <input type="password"
                                   id="organisation_confirm_password"
                                   name="organisation_confirm_password"
                                   placeholder="Confirm your password"
                                   minlength="8"
                                   disabled>

                        </div>

                    </div>


                    <!-- ORGANISATION STREET -->

                    <div class="form-group">

                        <label for="organisation_address_id">
                            Street *
                        </label>

                        <div class="ward-wrapper">

                            <select id="organisation_address_id"
                                    name="organisation_address_id"
                                    disabled>

                                <option value="">
                                    -- Select organisation street --
                                </option>

                                <?php foreach ($streets as $row): ?>

                                    <option
                                        value="<?= htmlspecialchars($row['address_id']); ?>"
                                        data-ward="<?= htmlspecialchars($row['ward_id']); ?>">

                                        <?= htmlspecialchars($row['street_name']); ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>


                            <button type="button"
                                    class="wardBtn"
                                    id="organisationWardBtn"
                                    disabled>

                                Ward Selection

                            </button>

                        </div>


                        <small id="organisationWardMessage">

                            Select a street to determine the organisation's ward.

                        </small>

                    </div>


                    <!-- ORGANISATION WARD -->

                    <input type="hidden"
                           id="organisation_ward_id"
                           name="organisation_ward_id"
                           value="">


                    <!-- ORGANISATION ADDRESS -->

                    <div class="form-group">

                        <label for="organisation_address">
                            Organisation Address
                        </label>

                        <textarea
                            id="organisation_address"
                            name="organisation_address"
                            placeholder="Enter the organisation's physical address"
                            maxlength="500"
                            disabled></textarea>

                    </div>


                    <!-- BUTTONS -->

                    <div class="formButtons">

                        <button type="reset"
                                class="resetBtn">

                            Reset

                        </button>


                        <button type="submit"
                                class="submitBtn">

                            Create Organisation Account

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- SIGN IN -->

    <div class="account-signin">

        <p>

            Already have an account?

            <a href="login.php">
                Sign in here
            </a>

        </p>

    </div>

</section>


<!-- ==================================================
     JAVASCRIPT
================================================== -->

<script>

/* ==================================================
   ACCOUNT TYPE
================================================== */

const accountTypeInputs =
    document.querySelectorAll(
        'input[name="accountType"]'
    );

const residentSection =
    document.getElementById("residentSection");

const organisationSection =
    document.getElementById("organisationSection");


/* ==================================================
   RESIDENT FIELDS
================================================== */

const residentFields = [

    document.getElementById("resident_first_name"),

    document.getElementById("resident_surname"),

    document.getElementById("resident_username"),

    document.getElementById("resident_contact"),

    document.getElementById("resident_password"),

    document.getElementById("resident_confirm_password"),

    document.getElementById("resident_address_id"),

    document.getElementById("resident_address")

];


/* ==================================================
   ORGANISATION FIELDS
================================================== */

const organisationFields = [

    document.getElementById("organisation_name"),

    document.getElementById("organisation_username"),

    document.getElementById("organisation_contact"),

    document.getElementById("organisation_password"),

    document.getElementById("organisation_confirm_password"),

    document.getElementById("organisation_address_id"),

    document.getElementById("organisation_address")

];


/* ==================================================
   ACCOUNT TYPE SWITCH
================================================== */

accountTypeInputs.forEach(function(input) {

    input.addEventListener("change", function() {

        if (this.value === "resident") {

            residentSection.classList.add("active");

            organisationSection.classList.remove("active");


            residentFields.forEach(function(field) {

                field.disabled = false;

            });


            organisationFields.forEach(function(field) {

                field.disabled = true;

            });

        }


        else if (this.value === "organisation") {

            residentSection.classList.remove("active");

            organisationSection.classList.add("active");


            residentFields.forEach(function(field) {

                field.disabled = true;

            });


            organisationFields.forEach(function(field) {

                field.disabled = false;

            });

        }

    });

});


/* ==================================================
   RESIDENT PASSWORD CHECK
================================================== */

const residentPassword =
    document.getElementById("resident_password");

const residentConfirmPassword =
    document.getElementById("resident_confirm_password");


function checkResidentPassword() {

    if (
        residentPassword.value !==
        residentConfirmPassword.value
    ) {

        residentConfirmPassword.setCustomValidity(
            "Passwords do not match."
        );

    } else {

        residentConfirmPassword.setCustomValidity("");

    }

}


residentPassword.addEventListener(
    "input",
    checkResidentPassword
);

residentConfirmPassword.addEventListener(
    "input",
    checkResidentPassword
);


/* ==================================================
   ORGANISATION PASSWORD CHECK
================================================== */

const organisationPassword =
    document.getElementById("organisation_password");

const organisationConfirmPassword =
    document.getElementById("organisation_confirm_password");


function checkOrganisationPassword() {

    if (
        organisationPassword.value !==
        organisationConfirmPassword.value
    ) {

        organisationConfirmPassword.setCustomValidity(
            "Passwords do not match."
        );

    } else {

        organisationConfirmPassword.setCustomValidity("");

    }

}


organisationPassword.addEventListener(
    "input",
    checkOrganisationPassword
);

organisationConfirmPassword.addEventListener(
    "input",
    checkOrganisationPassword
);


/* ==================================================
   RESIDENT STREET → WARD
================================================== */

const residentStreet =
    document.getElementById("resident_address_id");

const residentWardBtn =
    document.getElementById("residentWardBtn");

const residentWardMessage =
    document.getElementById("residentWardMessage");

const residentWardId =
    document.getElementById("resident_ward_id");


residentStreet.addEventListener("change", function() {

    const selectedOption =
        this.options[this.selectedIndex];

    const wardId =
        selectedOption.getAttribute("data-ward");


    if (wardId) {

        residentWardBtn.textContent =
            "Ward " + wardId;

        residentWardBtn.disabled = false;

        residentWardMessage.textContent =
            "You have been assigned to Ward " + wardId + ".";

        residentWardId.value = wardId;

    }

    else {

        residentWardBtn.textContent =
            "Ward Selection";

        residentWardBtn.disabled = true;

        residentWardMessage.textContent =
            "Select a street to determine your ward.";

        residentWardId.value = "";

    }

});


/* ==================================================
   ORGANISATION STREET → WARD
================================================== */

const organisationStreet =
    document.getElementById("organisation_address_id");

const organisationWardBtn =
    document.getElementById("organisationWardBtn");

const organisationWardMessage =
    document.getElementById("organisationWardMessage");

const organisationWardId =
    document.getElementById("organisation_ward_id");


organisationStreet.addEventListener("change", function() {

    const selectedOption =
        this.options[this.selectedIndex];

    const wardId =
        selectedOption.getAttribute("data-ward");


    if (wardId) {

        organisationWardBtn.textContent =
            "Ward " + wardId;

        organisationWardBtn.disabled = false;

        organisationWardMessage.textContent =
            "Organisation assigned to Ward " + wardId + ".";

        organisationWardId.value = wardId;

    }

    else {

        organisationWardBtn.textContent =
            "Ward Selection";

        organisationWardBtn.disabled = true;

        organisationWardMessage.textContent =
            "Select a street to determine the organisation's ward.";

        organisationWardId.value = "";

    }

});


/* ==================================================
   FORM RESET
================================================== */

document
    .getElementById("registrationForm")
    .addEventListener("reset", function() {

        setTimeout(function() {

            /* Restore Resident */

            residentSection.classList.add("active");

            organisationSection.classList.remove("active");


            residentFields.forEach(function(field) {

                field.disabled = false;

            });


            organisationFields.forEach(function(field) {

                field.disabled = true;

            });


            /* Restore radio */

            accountTypeInputs[0].checked = true;


            /* Restore resident ward */

            residentWardBtn.textContent =
                "Ward Selection";

            residentWardBtn.disabled = true;

            residentWardMessage.textContent =
                "Select a street to determine your ward.";

            residentWardId.value = "";


            /* Restore organisation ward */

            organisationWardBtn.textContent =
                "Ward Selection";

            organisationWardBtn.disabled = true;

            organisationWardMessage.textContent =
                "Select a street to determine the organisation's ward.";

            organisationWardId.value = "";


        }, 0);

    });

</script>

</body>

</html>