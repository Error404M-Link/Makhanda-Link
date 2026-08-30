
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="regisration.css">
    <link rel="stylesheet" href="publicfaultBoard.css">
    <link rel="stylesheet" href="backButton.css">

    <title>M-LINK | Sign Up</title>

    <style>

        /* =====================================================
   M-LINK COLOUR PALETTE
===================================================== */

:root {

    --military-green: #4B5320;
    --dark-green: #354016;
    --light-green: #687044;

    --beige: #E8DFCF;
    --light-beige: #F4EFE6;

    --brown: #6B4F3A;
    --dark-brown: #493426;

    --grey: #6B6F68;
    --light-grey: #D9D8D2;

    --white: #FFFFFF;
    --text: #2F332D;

}


/* =====================================================
   GLOBAL
===================================================== */

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: var(--light-beige);

    color: var(--text);

}


/* =====================================================
   NAVBAR
===================================================== */

.navbar {

    height: 80px;

    width: 100%;

    background:
        linear-gradient(
            135deg,
            var(--dark-green),
            var(--military-green)
        );

    display: flex;

    align-items: center;

    padding: 0 35px;

    box-shadow:
        0 3px 12px rgba(0, 0, 0, 0.18);

    position: relative;

    z-index: 10;

}


/* Logo */

.logo {

    display: flex;

    align-items: center;

    height: 100%;
}

.logo a {

    display: flex;

    align-items: center;

    height: 100%;

    text-decoration: none;

}

.logo img {

    max-height: 58px;

    max-width: 220px;

    width: auto;

    height: auto;

    object-fit: contain;

    display: block;

}


/* =====================================================
   BACK NAVIGATION
===================================================== */

.backNavWrapper {

    background: var(--beige);

    border-bottom:
        1px solid rgba(75, 83, 32, 0.15);

}

.backNavContainer {

    width: 100%;

    max-width: 1100px;

    margin: 0 auto;

    padding: 15px 20px;

}

.backBtn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    color: var(--dark-green);

    text-decoration: none;

    font-weight: 600;

    font-size: 14px;

    padding: 8px 14px;

    border-radius: 6px;

    transition: all 0.2s ease;

}

.backBtn:hover {

    background: var(--light-grey);

    color: var(--dark-brown);

}

.backBtn svg {

    stroke-width: 2.5;

}


/* =====================================================
   REGISTRATION PAGE
===================================================== */

.registration {

    min-height:
        calc(100vh - 80px);

    background:

        linear-gradient(
            rgba(244, 239, 230, 0.94),
            rgba(244, 239, 230, 0.94)
        ),

        radial-gradient(
            circle at top right,
            rgba(75, 83, 32, 0.15),
            transparent 40%
        );

    padding: 40px 20px 60px;

}


/* =====================================================
   REGISTRATION CONTAINER
===================================================== */

.registrationBody {

    width: 100%;

    max-width: 1000px;

    margin: 0 auto;

    background: var(--white);

    border-radius: 12px;

    overflow: hidden;

    border:
        1px solid rgba(75, 83, 32, 0.15);

    box-shadow:
        0 10px 35px rgba(73, 52, 38, 0.14);

}


/* =====================================================
   REGISTRATION HEADER
===================================================== */

.registrationHeader {

    background:

        linear-gradient(
            135deg,
            var(--military-green),
            var(--dark-green)
        );

    color: var(--white);

    padding: 32px 40px;

    position: relative;

    overflow: hidden;

}


/* Decorative brown strip */

.registrationHeader::after {

    content: "";

    position: absolute;

    bottom: 0;

    left: 0;

    width: 100%;

    height: 5px;

    background: var(--brown);

}


.registrationHeader::before {

    content: "";

    position: absolute;

    width: 180px;

    height: 180px;

    border-radius: 50%;

    background:
        rgba(232, 223, 207, 0.08);

    right: -70px;

    top: -80px;

}


.header-content {

    position: relative;

    z-index: 1;

}

.header-content h4 {

    margin: 0 0 8px;

    font-size: 28px;

    letter-spacing: 0.3px;

}

.header-content p {

    color: #E8E8E3 !important;

    font-size: 15px;

    margin: 0;

    line-height: 1.6;

}


/* =====================================================
   FORM AREA
===================================================== */

.registrationForm {

    padding: 35px 40px 40px;

}


/* =====================================================
   ACCOUNT TYPE
===================================================== */

.account-type {

    display: flex;

    gap: 15px;

    margin-bottom: 30px;

}


.account-type label {

    flex: 1;

    padding: 15px;

    border:
        1px solid var(--light-grey);

    border-radius: 8px;

    text-align: center;

    cursor: pointer;

    font-weight: 600;

    color: var(--dark-green);

    background: var(--light-beige);

    transition: all 0.2s ease;

}


.account-type label:hover {

    border-color: var(--military-green);

    background: var(--beige);

}


.account-type input {

    accent-color: var(--military-green);

    margin-right: 8px;

}


/* =====================================================
   SECTION
===================================================== */

.section h3 {

    color: var(--dark-green);

    margin-top: 0;

    margin-bottom: 25px;

    font-size: 22px;

    padding-bottom: 10px;

    border-bottom:
        2px solid var(--beige);

}


/* =====================================================
   FORM ROW
===================================================== */

.form-row {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 20px;

}


/* =====================================================
   FORM GROUP
===================================================== */

.form-group {

    margin-bottom: 20px;

}


.form-group label {

    display: block;

    margin-bottom: 7px;

    color: var(--text);

    font-weight: 600;

    font-size: 14px;

}


.form-group input,
.form-group select,
.form-group textarea {

    width: 100%;

    box-sizing: border-box;

    padding: 13px 14px;

    border:
        1px solid #C9C8C1;

    border-radius: 7px;

    background: var(--white);

    color: var(--text);

    font-size: 15px;

    outline: none;

    font-family: inherit;

    transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease,
        background 0.2s ease;

}


.form-group textarea {

    min-height: 120px;

    resize: vertical;

    line-height: 1.5;

}


.form-group input::placeholder,
.form-group textarea::placeholder {

    color: #999B94;

}


/* Focus */

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {

    border-color:
        var(--military-green);

    background: #FEFEFC;

    box-shadow:
        0 0 0 3px
        rgba(75, 83, 32, 0.12);

}


.form-group small {

    display: block;

    margin-top: 5px;

    color: var(--grey);

    font-size: 12px;

}


/* =====================================================
   WARD
===================================================== */

.ward-wrapper {

    display: flex;

    gap: 10px;

}


.ward-wrapper select {

    flex: 1;

}


.wardBtn {

    padding:
        0 20px;

    border: none;

    border-radius: 7px;

    background:
        var(--light-grey);

    color:
        #85877F;

    font-weight: 600;

    cursor: not-allowed;

    white-space: nowrap;

}


/* =====================================================
   FORM BUTTONS
===================================================== */

.formButtons {

    display: flex;

    justify-content: flex-end;

    gap: 12px;

    margin-top: 30px;

    padding-top: 20px;

    border-top:
        1px solid var(--light-grey);

}


.formButtons button {

    padding:
        12px 25px;

    border-radius: 7px;

    border: none;

    cursor: pointer;

    font-size: 15px;

    font-weight: 600;

    transition:
        all 0.2s ease;

}


/* Reset */

.resetBtn {

    background:
        var(--light-grey);

    color:
        var(--dark-brown);

}


.resetBtn:hover {

    background:
        #C9C8C1;

}


/* Submit */

.submitBtn {

    background:
        var(--military-green);

    color:
        var(--white);

    box-shadow:
        0 3px 8px
        rgba(75, 83, 32, 0.25);

}


.submitBtn:hover {

    background:
        var(--dark-green);

    transform:
        translateY(-1px);

    box-shadow:
        0 5px 12px
        rgba(75, 83, 32, 0.30);

}


/* =====================================================
   SIGN IN
===================================================== */

.account-signin {

    text-align: center;

    margin-top: 25px;

    color:
        var(--grey);

}


.account-signin a {

    color:
        var(--brown);

    font-weight: bold;

    text-decoration: none;

}


.account-signin a:hover {

    color:
        var(--dark-brown);

    text-decoration: underline;

}


/* =====================================================
   HIDDEN SECTIONS
===================================================== */

.section {

    display: none;

}


.section.active {

    display: block;

}


/* =====================================================
   DISABLED ORGANISATION
===================================================== */

.account-type label:has(input:disabled) {

    opacity: 0.55;

    cursor: not-allowed;

    background:
        #E2E1DC;

}


/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 700px) {

    .navbar {

        height: 70px;

        padding:
            0 20px;

    }


    .logo img {

        max-height: 48px;

        max-width: 180px;

    }


    .registration {

        padding:
            20px 12px 40px;

    }


    .registrationHeader {

        padding:
            25px 20px;

    }


    .registrationForm {

        padding:
            25px 20px 30px;

    }


    .form-row {

        grid-template-columns: 1fr;

        gap: 0;

    }


    .account-type {

        flex-direction: column;

    }


    .formButtons {

        flex-direction: column;

    }


    .formButtons button {

        width: 100%;

    }


    .ward-wrapper {

        flex-direction: column;

    }


    .wardBtn {

        padding: 12px;

    }

}
/* =========================
   NAVBAR
========================= */

.navbar {
    width: 100%;
    height: 80px;

    background: #4B5320;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 10px 20px;

    box-sizing: border-box;

    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
}


/* =========================
   LOGO
========================= */

.logo {
    display: flex;
    align-items: center;
    justify-content: center;

    height: 100%;
}

.logo a {
    display: flex;
    align-items: center;
    justify-content: center;

    height: 100%;

    text-decoration: none;
}

.logo img {
    display: block;

    width: 70px;
    height: 55px;

    object-fit: contain;

    max-width: 100%;
    max-height: 100%;

    border-radius: 6px;
}


    </style>

</head>


<body>


    <!-- =========================
         NAVIGATION
    ========================= -->

   <nav class="navbar">

    <div class="logo">

        <a href="index.php">

            <img
                src="makanalogo.jpeg"
                alt="M-LINK"
            >

        </a>

    </div>

</nav>


    <!-- =========================
         BACK BUTTON
    ========================= -->

    <div class="backNavWrapper">

        <div class="backNavContainer">

            <a
                href="javascript:history.back()"
                class="backBtn"
            >

                <svg
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >

                    <line
                        x1="19"
                        y1="12"
                        x2="5"
                        y2="12"
                    ></line>

                    <polyline
                        points="12 19 5 12 12 5"
                    ></polyline>

                </svg>

                <span>Back</span>

            </a>

        </div>

    </div>


    <!-- =========================
         REGISTRATION
    ========================= -->

    <section class="registration">

        <div class="registrationBody">


            <!-- HEADER -->

            <div class="registrationHeader">

                <div class="header-content">

                    <h4>Registration</h4>

                    <p>
                        Create your M-LINK community account
                        to report faults and stay connected
                        with your ward.
                    </p>

                </div>

            </div>


            <!-- FORM -->

            <div class="registrationForm">


                <!-- =========================
                     ACCOUNT TYPE
                ========================= -->

                <div class="account-type">

                    <label>

                        <input
                            type="radio"
                            name="accountType"
                            value="resident"
                            checked
                        >

                        Resident

                    </label>


                    <label>

                        <input
                            type="radio"
                            name="accountType"
                            value="organisation"
                            disabled
                        >

                        Organisation

                    </label>

                </div>


                <!-- =========================
                     REGISTRATION FORM
                ========================= -->

                <form
                    id="registrationForm"
                    action="urreg.php"
                    method="POST"
                    autocomplete="off"
                >


                    <!-- =========================
                         RESIDENT SECTION
                    ========================= -->

                    <div
                        id="residentSection"
                        class="section active"
                    >

                        <h3>
                            Resident Details
                        </h3>


                        <!-- FIRST NAME + SURNAME -->

                        <div class="form-row">

                            <div class="form-group">

                                <label for="first_name">
                                    First Name *
                                </label>

                                <input
                                    type="text"
                                    id="first_name"
                                    name="first_name"
                                    placeholder="Enter your first name"
                                    maxlength="100"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label for="surname">
                                    Surname *
                                </label>

                                <input
                                    type="text"
                                    id="surname"
                                    name="surname"
                                    placeholder="Enter your surname"
                                    maxlength="100"
                                    required
                                >

                            </div>

                        </div>


                        <!-- USERNAME -->

                        <div class="form-group">

                            <label for="username">
                                Username *
                            </label>

                            <input
                                type="text"
                                id="username"
                                name="username"
                                placeholder="Choose a username"
                                maxlength="50"
                                required
                            >

                            <small>
                                Your username will be used when logging in.
                            </small>

                        </div>


                        <!-- CONTACT -->

                        <div class="form-group">

                            <label for="contact">
                                Contact Number *
                            </label>

                            <input
                                type="text"
                                id="contact"
                                name="contact"
                                placeholder="0821234567"
                                maxlength="10"
                                minlength="10"
                                pattern="[0-9]{10}"
                                inputmode="numeric"
                                required
                            >

                            <small>
                                Enter exactly 10 digits.
                            </small>

                        </div>


                        <!-- PASSWORD -->

                        <div class="form-row">

                            <div class="form-group">

                                <label for="password">
                                    Password *
                                </label>

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    placeholder="Create a password"
                                    minlength="8"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label for="confirm_password">
                                    Confirm Password *
                                </label>

                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    placeholder="Confirm your password"
                                    minlength="8"
                                    required
                                >

                            </div>

                        </div>


                        <!-- =========================
                             WARD
                        ========================= -->

                        <div class="form-group">

                            <label for="ward_id">
                                Ward *
                            </label>

                            <div class="ward-wrapper">

                                <select
                                    id="ward_id"
                                    name="ward_id"
                                    required
                                >

                                    <option value="">
                                        -- Select your ward --
                                    </option>

                                </select>


                                <button
                                    type="button"
                                    class="wardBtn"
                                    disabled
                                >
                                    Ward Selection
                                </button>

                            </div>

                            <small>
                                Ward selection is currently
                                unavailable.
                            </small>

                        </div>


                        <!-- =========================
                             ADDRESS TEXTAREA
                        ========================= -->

                        <div class="form-group">

                            <label for="address">
                                Address *
                            </label>

                            <textarea
                                id="address"
                                name="address"
                                placeholder="Enter your full residential address"
                                maxlength="500"
                                required
                            ></textarea>

                            <small>
                                Enter your street address,
                                suburb and postal code if applicable.
                            </small>

                        </div>


                        <!-- =========================
                             USER ROLE
                        ========================= -->

                        <input
                            type="hidden"
                            name="user_role"
                            value="Resident"
                        >


                        <!-- =========================
                             BUTTONS
                        ========================= -->

                        <div class="formButtons">

                            <button
                                type="reset"
                                class="resetBtn"
                            >
                                Reset
                            </button>


                            <button
                                type="submit"
                                class="submitBtn"
                            >
                                Create Account
                            </button>

                        </div>

                    </div>


                    <!-- =========================
                         ORGANISATION
                    ========================= -->

                    <div
                        id="organisationSection"
                        class="section"
                    >

                        <h3>
                            Organisation Registration
                        </h3>

                        <p>
                            Organisation registration will be
                            available once the organisation
                            account structure has been connected
                            to the M-LINK database.
                        </p>

                    </div>

                </form>

            </div>

        </div>


        <!-- =========================
             SIGN IN
        ========================= -->

        <div class="account-signin">

            <p>

                Already have an account?

                <a href="login.php">
                    Sign in here
                </a>

            </p>

        </div>

    </section>


    <!-- =========================
         JAVASCRIPT
    ========================= -->

    <script>

        const accountTypeInputs =
            document.querySelectorAll(
                'input[name="accountType"]'
            );

        const residentSection =
            document.getElementById(
                "residentSection"
            );

        const organisationSection =
            document.getElementById(
                "organisationSection"
            );


        /* =========================
           ACCOUNT TYPE
        ========================= */

        accountTypeInputs.forEach(function(input) {

            input.addEventListener(
                "change",
                function() {

                    if (this.value === "resident") {

                        residentSection.classList.add(
                            "active"
                        );

                        organisationSection.classList.remove(
                            "active"
                        );

                    }

                }
            );

        });


        /* =========================
           PASSWORD MATCH
        ========================= */

        const password =
            document.getElementById(
                "password"
            );

        const confirmPassword =
            document.getElementById(
                "confirm_password"
            );


        confirmPassword.addEventListener(
            "input",
            function() {

                if (
                    password.value !==
                    confirmPassword.value
                ) {

                    confirmPassword.setCustomValidity(
                        "Passwords do not match."
                    );

                } else {

                    confirmPassword.setCustomValidity(
                        ""
                    );

                }

            }
        );


        password.addEventListener(
            "input",
            function() {

                if (
                    confirmPassword.value !== ""
                ) {

                    if (
                        password.value !==
                        confirmPassword.value
                    ) {

                        confirmPassword.setCustomValidity(
                            "Passwords do not match."
                        );

                    } else {

                        confirmPassword.setCustomValidity(
                            ""
                        );

                    }

                }

            }
        );

    </script>

</body>

</html>
