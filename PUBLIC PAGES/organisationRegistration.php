<?php

include 'Error404Connection.php';

$error = $_GET["error"] ?? "";
$success = $_GET["success"] ?? "";

$errorMessages = [
    "missing" =>
        "Please complete all required fields.",
    "invalid" =>
        "One or more fields are too long or contain characters that are not allowed.",
    "orgname" =>
        "The organisation name may only contain letters, numbers, spaces and the symbols & ' . - (2 to 100 characters).",
    "email_long" =>
        "The email address must be 50 characters or fewer.",
    "email_number" =>
        "The email address cannot start with a number.",
    "email_com" =>
        "The email address must end with .com",
    "email" =>
        "Please enter a valid email address that starts with a letter and ends with .com",
    "contact" =>
        "Contact number must contain exactly 10 digits (numbers only).",
    "password" =>
        "Password must contain at least 8 characters.",
    "password_weak" =>
        "That password is too weak. Use 8 to 72 characters with an uppercase letter, a lowercase letter, a number and a special character, at least 5 different characters, and avoid common passwords or your name.",
    "password_match" =>
        "The passwords do not match.",
    "street" =>
        "Please select a valid street.",
    "ward" =>
        "The selected street does not match the assigned ward.",
    "exists" =>
        "An account with this email address already exists.",
    "system" =>
        "The organisation account could not be created. Please try again."
];

$successMessages = [

    "registered" =>
        "Organisation successfully registered. You can now log in."

];

$sql = "SELECT address_id, ward_id, street_name, postal_code
        FROM address_directory
        ORDER BY street_name ASC";

$result = mysqli_query($conn, $sql);

if (!$result) {

    die(
        "Could not load streets: "
        . mysqli_error($conn)
    );

}

$streets = [];

while ($row = mysqli_fetch_assoc($result)) {

    $streets[] = $row;

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        M-LINK | Organisation Sign Up
    </title>

    <style>

        /* ===== base (matches login.php) ===== */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f0e7;
            color: #111111;
            min-height: 100vh;
        }

        /* ===== navbar + logo (matches login.php) ===== */
        .navbar {
            height: 76px;
            width: 100%;
            background: #4b561b;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            padding-left: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.12);
        }

        .logo a {
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .logo img {
            width: 96px;
            height: 106px;
            object-fit: contain;
            padding: 5px;
        }

        /* ===== back navigation (matches login.php) ===== */
        .backNavWrapper {
            width: 100%;
            height: 68px;
            background: #f7f7f7;
            border-bottom: 1px solid #e2dfd7;
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }

        .backNavContainer {
            width: 100%;
            max-width: 1050px;
            margin: 0;
            padding: 20px;
        }

        .backBtn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 17px;
            background: #ffffff;
            color: #333333;
            border: 1px solid #d6d2c8;
            border-radius: 7px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .backBtn:hover {
            background: #eee9dd;
            color: #4b561b;
        }

        /* ===== page / card layout (matches login-page / login-container) ===== */
        .registration {
            width: 100%;
            padding: 40px 20px 60px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .registrationBody {
            width: 100%;
            max-width: 900px;
            background: #ffffff;
            border-radius: 11px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.10);
        }

        .registrationHeader {
            background: #4b561b;
            padding: 36px 40px 32px;
            position: relative;
            overflow: hidden;
        }

        .registrationHeader::after {
            content: "";
            position: absolute;
            width: 170px;
            height: 170px;
            right: -65px;
            top: -85px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.07);
        }

        .registrationHeader .header-content {
            position: relative;
            z-index: 1;
        }

        .registrationHeader h4 {
            color: #ffffff;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .registrationHeader p {
            color: #ffffff;
            font-size: 15px;
            line-height: 1.5;
        }

        /* ===== status messages (matches login-error / login-success) ===== */
        .registration-success {
            margin: 25px 40px 0;
            padding: 13px 15px;
            background: #e9f5e8;
            color: #356b2f;
            border: 1px solid #c5dfc2;
            border-radius: 7px;
            font-size: 14px;
            line-height: 1.5;
            opacity: 1;
            transition: opacity 0.8s ease;
        }

        .registration-success.fade-out {
            opacity: 0;
        }

        .registration-error {
            margin: 25px 40px 0;
            padding: 13px 15px;
            background: #fce8e6;
            color: #b42318;
            border: 1px solid #f5c2c0;
            border-radius: 7px;
            font-size: 14px;
            line-height: 1.5;
        }

        /* ===== form (matches login-form) ===== */
        .registrationForm {
            padding: 40px;
            background: #ffffff;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #111111;
            font-size: 14px;
            font-weight: 600;
        }

        .form-group small,
        #wardMessage {
            display: block;
            margin-top: 8px;
            color: #777777;
            font-size: 12.5px;
            line-height: 1.4;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
            min-width: 0;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            height: 48px;
            padding: 0 14px;
            border: 1px solid #cfcac0;
            border-radius: 7px;
            background: #ffffff;
            color: #222222;
            font-size: 15px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-group textarea {
            height: auto;
            min-height: 70px;
            padding: 12px 14px;
            resize: vertical;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #999999;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #4b561b;
            box-shadow: 0 0 0 3px rgba(75, 86, 27, 0.12);
        }

        /* ===== ward selector row ===== */
        .ward-wrapper {
            display: flex;
            gap: 10px;
        }

        .ward-wrapper select {
            flex: 1;
        }

        .wardBtn {
            min-width: 150px;
            height: 48px;
            padding: 0 16px;
            border: 1px solid #cfcac0;
            border-radius: 7px;
            background: #f4f0e7;
            color: #4b561b;
            font-size: 14px;
            font-weight: 700;
            cursor: not-allowed;
            transition: background 0.2s ease;
        }

        .wardBtn:not(:disabled) {
            cursor: default;
            background: #eef1e2;
        }

        /* ===== buttons (matches submitBtn) ===== */
        .formButtons {
            border-top: 1px solid #ddd8ce;
            padding-top: 25px;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        .submitBtn {
            min-width: 190px;
            height: 44px;
            padding: 0 25px;
            border: none;
            border-radius: 7px;
            background: #4b561b;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.1s ease;
        }

        .submitBtn:hover {
            background: #3d4616;
        }

        .submitBtn:active {
            transform: translateY(1px);
        }

        .resetBtn {
            min-width: 120px;
            height: 44px;
            padding: 0 25px;
            background: #ffffff;
            color: #333333;
            border: 1px solid #d6d2c8;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .resetBtn:hover {
            background: #eee9dd;
            color: #4b561b;
        }

        /* ===== sign-in footer (matches account-register) ===== */
        .account-signin {
            border-top: 1px solid #ddd8ce;
            padding: 25px 40px;
            text-align: center;
            background: #ffffff;
        }

        .account-signin p {
            color: #777777;
            font-size: 14px;
        }

        .account-signin a {
            color: #4b561b;
            font-weight: 700;
            text-decoration: none;
            margin-left: 4px;
        }

        .account-signin a:hover {
            text-decoration: underline;
        }

        .success-actions {
            padding: 30px;
            text-align: center;
        }

        .signinBtn {
            display: inline-block;
            padding: 12px 30px;
            background: #4b561b;
            color: #ffffff;
            border-radius: 7px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
        }

        .signinBtn:hover {
            background: #3d4616;
        }

        /* ===== responsive (matches login.php breakpoints) ===== */
        @media (max-width: 700px) {

            .registration {
                padding: 25px 12px 45px;
            }

            .registrationBody {
                border-radius: 9px;
            }

            .registrationHeader {
                padding: 30px 25px;
            }

            .registrationHeader h4 {
                font-size: 23px;
            }

            .registrationForm {
                padding: 30px 25px;
            }

            .registration-error,
            .registration-success {
                margin-left: 25px;
                margin-right: 25px;
            }

            .account-signin {
                padding: 22px 25px;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .formButtons {
                justify-content: stretch;
            }

            .formButtons .submitBtn,
            .formButtons .resetBtn {
                width: 100%;
            }
        }

        @media (max-width: 450px) {

            .navbar {
                height: 70px;
            }

            .logo img {
                width: 52px;
                height: 52px;
            }

            .backNavWrapper {
                height: 60px;
            }

            .backNavContainer {
                padding: 0 12px;
            }

            .registrationForm {
                padding: 25px 20px;
            }

            .registration-error,
            .registration-success {
                margin-left: 20px;
                margin-right: 20px;
            }

            .ward-wrapper {
                flex-direction: column;
                align-items: stretch;
            }

            .wardBtn {
                width: 100%;
            }
        }

    </style>

</head>

<body>

<nav class="navbar">

    <div class="logo">

        <a href="index.php">

            <img src="makanalogo.svg"
                 alt="M-LINK">

        </a>

    </div>

</nav>

<div class="backNavWrapper">

    <div class="backNavContainer">

        <a href="register.php"
           class="backBtn">

            <span>Back</span>

        </a>

    </div>

</div>

<section class="registration">

    <div class="registrationBody">

        <div class="registrationHeader">

            <div class="header-content">

                <h4>
                    Create Organisation Account
                </h4>

                <p>
                    Register your organisation with M-LINK.
                </p>

            </div>

        </div>

        <?php if (
            !empty($success) &&
            isset($successMessages[$success])
        ): ?>

            <div class="registration-success">

                <?= htmlspecialchars(
                    $successMessages[$success]
                ); ?>

            </div>

        <?php endif; ?>

        <?php if (
            !empty($error) &&
            isset($errorMessages[$error])
        ): ?>

            <div class="registration-error">

                <?= htmlspecialchars(
                    $errorMessages[$error]
                ); ?>

            </div>

        <?php endif; ?>

        <?php if ($success !== "registered"): ?>

        <div class="registrationForm">

            <form id="organisationRegistrationForm"
                  action="organisationRegisterProcess.php"
                  method="POST"
                  autocomplete="off">

                <input type="hidden"
                       name="role"
                       value="organisation">

                <div class="form-group">

                    <label for="organisation_name">

                        Organisation Name *

                    </label>

                    <input type="text"
                           id="organisation_name"
                           name="organisation_name"
                           placeholder="Enter organisation name"
                           maxlength="100"
                           pattern="[A-Za-z0-9&'. -]+"
                           title="Enter a valid organisation name."
                           required>

                    <small>

                        Enter the official name of your organisation.

                    </small>

                </div>

                <div class="form-group">

                    <label for="username">

                        Email Address *

                    </label>

                    <input type="email"
                           id="username"
                           name="username"
                           placeholder="example@gmail.com"
                           maxlength="50"
                           pattern="(?!.*\.\.)[A-Za-z][A-Za-z0-9._%+\-]{0,63}@(?:[A-Za-z0-9](?:[A-Za-z0-9\-]*[A-Za-z0-9])?\.)+[Cc][Oo][Mm]"
                           title="Must start with a letter and end with .com (for example name@example.com)"
                           autocapitalize="none"
                           spellcheck="false"
                           autocomplete="email"
                           required>

                    <small>

                        This email address will be used to log in. It must start with a letter and end with .com

                    </small>

                </div>

                <div class="form-group">

                    <label for="contact">

                        Contact Number *

                    </label>

                    <input type="text"
                           id="contact"
                           name="contact"
                           placeholder="0821234567"
                           maxlength="10"
                           minlength="10"
                           pattern="[0-9]{10}"
                           inputmode="numeric"
                           autocomplete="tel"
                           required>

                    <small>

                        Enter exactly 10 digits.

                    </small>

                </div>

                <div class="form-row">

                    <div class="form-group">

                        <label for="password">

                            Password *

                        </label>

                        <input type="password"
                               id="password"
                               name="password"
                               placeholder="Create a password"
                               minlength="8"
                               autocomplete="new-password"
                               required>

                        <small>

                            Password must contain at least 8 characters.

                        </small>

                    </div>

                    <div class="form-group">

                        <label for="confirm_password">

                            Confirm Password *

                        </label>

                        <input type="password"
                               id="confirm_password"
                               name="confirm_password"
                               placeholder="Confirm your password"
                               minlength="8"
                               autocomplete="new-password"
                               required>

                        <small>

                            Re-enter your password.

                        </small>

                    </div>

                </div>

                <div class="form-group">

                    <label for="address_id">

                        Street *

                    </label>

                    <div class="ward-wrapper">

                        <select id="address_id"
                                name="address_id"
                                required>

                            <option value="">

                                -- Select your street --

                            </option>

                            <?php foreach ($streets as $row): ?>

                                <option
                                    value="<?= htmlspecialchars(
                                        $row["address_id"]
                                    ); ?>"
                                    data-ward="<?= htmlspecialchars(
                                        $row["ward_id"]
                                    ); ?>"
                                    data-postal="<?= htmlspecialchars(
                                        $row["postal_code"]
                                    ); ?>">

                                    <?= htmlspecialchars(
                                        $row["street_name"]
                                    ); ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <button type="button"
                                class="wardBtn"
                                id="wardBtn"
                                disabled>

                            Ward Selection

                        </button>

                    </div>

                    <small id="wardMessage">

                        Select a street to determine your ward.

                    </small>

                </div>

                <input type="hidden"
                       id="ward_id"
                       name="ward_id"
                       value="">

                <div class="form-group">

                    <label for="address">

                        Address *

                    </label>

                    <textarea
                        id="address"
                        name="address"
                        placeholder="Select a street above"
                        maxlength="500"
                        readonly
                        required></textarea>

                    <small>

                        Ward, street name and postal code
                        are filled automatically.

                    </small>

                </div>

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

            </form>

        </div>

        <!-- <?php else: ?>

            <div class="success-actions">

                <a href="login.php"
                   class="signinBtn">

                    Sign In

                </a>

            </div> -->

        <?php endif; ?>

    </div>

    <?php if ($success !== "registered"): ?>

        <div class="account-signin">

            <p>

                Already have an organisation account?

                <a href="login.php">

                    Sign in here

                </a>

            </p>

        </div>

    <?php endif; ?>

</section>

<?php if ($success !== "registered"): ?>

<script>

const form =
    document.getElementById(
        "organisationRegistrationForm"
    );

const organisationName =
    document.getElementById(
        "organisation_name"
    );

const contact =
    document.getElementById(
        "contact"
    );

const password =
    document.getElementById(
        "password"
    );

const confirmPassword =
    document.getElementById(
        "confirm_password"
    );

const streetSelect =
    document.getElementById(
        "address_id"
    );

const wardIdInput =
    document.getElementById(
        "ward_id"
    );

const wardBtn =
    document.getElementById(
        "wardBtn"
    );

const wardMessage =
    document.getElementById(
        "wardMessage"
    );

const addressInput =
    document.getElementById(
        "address"
    );

organisationName.addEventListener(
    "input",
    function () {

        this.value =
            this.value.replace(
                /[^A-Za-z0-9&'. -]/g,
                ""
            );

    }
);

contact.addEventListener(
    "input",
    function () {

        this.value =
            this.value.replace(
                /[^0-9]/g,
                ""
            );

    }
);

function checkPasswords() {

    if (
        confirmPassword.value !== "" &&
        password.value !==
        confirmPassword.value
    ) {

        confirmPassword.setCustomValidity(
            "Passwords do not match."
        );

    }

    else {

        confirmPassword.setCustomValidity("");

    }

}

password.addEventListener(
    "input",
    checkPasswords
);

confirmPassword.addEventListener(
    "input",
    checkPasswords
);

function fillAddress() {

    const selectedOption =
        streetSelect.options[
            streetSelect.selectedIndex
        ];

    if (streetSelect.value === "") {

        wardIdInput.value =
            "";

        wardBtn.textContent =
            "Ward Selection";

        wardBtn.disabled =
            true;

        wardMessage.textContent =
            "Select a street to determine your ward.";

        addressInput.value =
            "";

        return;

    }

    const wardId =
        selectedOption.getAttribute(
            "data-ward"
        );

    const postalCode =
        selectedOption.getAttribute(
            "data-postal"
        );

    const streetName =
        selectedOption.textContent.trim();

    wardIdInput.value =
        wardId;

    wardBtn.textContent =
        "Ward " + wardId;

    wardBtn.disabled =
        false;

    wardMessage.textContent =
        "Organisation assigned to Ward "
        + wardId + ".";

    addressInput.value =
        "Ward " +
        wardId +
        ", " +
        streetName +
        ", " +
        postalCode;

}

streetSelect.addEventListener(
    "change",
    fillAddress
);

form.addEventListener(
    "submit",
    function (event) {

        fillAddress();

        checkPasswords();

        if (!form.checkValidity()) {

            event.preventDefault();

            form.reportValidity();

        }

    }
);

form.addEventListener(
    "reset",
    function () {

        setTimeout(
            function () {

                wardIdInput.value =
                    "";

                wardBtn.textContent =
                    "Ward Selection";

                wardBtn.disabled =
                    true;

                wardMessage.textContent =
                    "Select a street to determine your ward.";

                addressInput.value =
                    "";

                confirmPassword.setCustomValidity(
                    ""
                );

            },
            0
        );

    }
);

</script>

<?php endif; ?>

<script>
// Username and password rules (browser-side help only - the server
// checks the same rules)
(function () {

    const usernameField = document.getElementById("username");
    const passwordField = document.getElementById("password");
    const orgNameField = document.getElementById("organisation_name");

    const usernamePattern =
        /^(?!.*\.\.)[A-Za-z][A-Za-z0-9._%+\-]{0,63}@(?:[A-Za-z0-9](?:[A-Za-z0-9\-]*[A-Za-z0-9])?\.)+com$/i;

    function checkUsername() {

        const value = usernameField.value.trim();

        usernameField.setCustomValidity("");

        if (value === "") {

            return;

        }

        if (/^[0-9]/.test(value)) {

            usernameField.setCustomValidity(
                "The email address cannot start with a number."
            );

        }

        else if (!/^[A-Za-z]/.test(value)) {

            usernameField.setCustomValidity(
                "The email address must start with a letter."
            );

        }

        else if (!/\.com$/i.test(value)) {

            usernameField.setCustomValidity(
                "The email address must end with .com"
            );

        }

        else if (value.length > 50 || !usernamePattern.test(value)) {

            usernameField.setCustomValidity(
                "Enter a valid email address (maximum 50 characters), for example name@example.com"
            );

        }

    }

    usernameField.addEventListener("input", checkUsername);
    usernameField.addEventListener("blur", checkUsername);

    function extraWords() {

        return [orgNameField.value];

    }

    // ---- password strength (same rules the server applies) ----
    const commonWords =
        ["password","passw0rd","p@ssword","qwerty","asdfgh","zxcvbn","123456","12345678","111111","letmein","welcome","iloveyou","admin","abc123","monkey","dragon","football","sunshine","changeme","mlink","m-link","makhanda","makana"];

    function passwordProblem(pw) {

        if (pw === "") {

            return "";

        }

        if (pw.length < 8) {

            return "Password must be at least 8 characters.";

        }

        if (new TextEncoder().encode(pw).length > 72) {

            return "Password must be 72 characters or fewer.";

        }

        if (
            !/\p{Ll}/u.test(pw) ||
            !/\p{Lu}/u.test(pw) ||
            !/[0-9]/.test(pw) ||
            !/[^\p{L}\p{N}\s]/u.test(pw)
        ) {

            return "Password must contain an uppercase letter, a lowercase letter, a number and a special character (for example ! @ # $ %).";

        }

        if (new Set(Array.from(pw)).size < 5) {

            return "Password must contain at least 5 different characters.";

        }

        const lower = pw.toLowerCase();

        if (commonWords.some(function (word) { return lower.includes(word); })) {

            return "That password is too easy to guess. Please choose a less common password.";

        }

        const forbidden = [];

        extraWords().forEach(function (value) {

            forbidden.push(value);

            value.split(/[\s\-'\u2019]+/).forEach(function (part) {

                forbidden.push(part);

            });

        });

        const emailValue = usernameField.value.trim();

        if (emailValue.indexOf("@") > 0) {

            forbidden.push(emailValue.split("@")[0]);

        }

        for (const word of forbidden) {

            const w = word.trim().toLowerCase();

            if (w.length >= 3 && lower.includes(w)) {

                return "Password must not contain your name or email address.";

            }

        }

        return "";

    }

    function checkPasswordStrength() {

        passwordField.setCustomValidity(
            passwordProblem(passwordField.value)
        );

    }

    passwordField.addEventListener("input", checkPasswordStrength);
    usernameField.addEventListener("input", checkPasswordStrength);

})();
</script>

</body>

</html>