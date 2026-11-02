<?php

include 'Error404Connection.php';

$sql = "SELECT address_id, ward_id, street_name
        FROM address_directory
        ORDER BY street_name";

$result = mysqli_query($conn, $sql);

if (!$result) {

    die("Could not load streets: " . mysqli_error($conn));

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

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>M-LINK | Sign Up</title>

    <style>

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

        /* ---------- navbar + logo (same as login.php) ---------- */

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

        /* ---------- back button (same as login.php) ---------- */

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

        /* ---------- card (same as login.php) ---------- */

        .login-page {
            width: 100%;
            padding: 40px 20px 60px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .login-container {
            width: 100%;
            max-width: 900px;
            background: #ffffff;
            border-radius: 11px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.10);
        }

        .login-header {
            background: #4b561b;
            padding: 36px 40px 32px;
            position: relative;
            overflow: hidden;
        }

        .login-header::after {
            content: "";
            position: absolute;
            width: 170px;
            height: 170px;
            right: -65px;
            top: -85px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.07);
        }

        .login-header h1 {
            position: relative;
            z-index: 1;
            color: #ffffff;
            font-size: 30px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .login-header p {
            position: relative;
            z-index: 1;
            color: #ffffff;
            font-size: 15px;
            line-height: 1.5;
        }

        .login-form {
            padding: 40px;
            background: #ffffff;
        }

        /* ---------- form fields (login.php look) ---------- */

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

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select {
            width: 100%;
            height: 48px;
            padding: 0 14px;
            border: 1px solid #cfcac0;
            border-radius: 7px;
            background: #ffffff;
            color: #222222;
            font-family: inherit;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-group textarea {
            width: 100%;
            min-height: 110px;
            padding: 12px 14px;
            border: 1px solid #cfcac0;
            border-radius: 7px;
            background: #ffffff;
            color: #222222;
            font-family: inherit;
            font-size: 15px;
            line-height: 1.5;
            resize: vertical;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #999999;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #4b561b;
            box-shadow: 0 0 0 3px rgba(75, 86, 27, 0.12);
        }

        .form-group small {
            display: block;
            margin-top: 6px;
            color: #777777;
            font-size: 12px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            column-gap: 20px;
        }

        /* street + ward selection */

        .ward-wrapper {
            display: flex;
            gap: 10px;
        }

        .ward-wrapper select {
            flex: 1;
            min-width: 0;
        }

        .wardBtn {
            padding: 0 20px;
            border: none;
            border-radius: 7px;
            background: #e2dfd7;
            color: #85877f;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
            cursor: not-allowed;
        }

        .wardBtn:not(:disabled) {
            background: #4b561b;
            color: #ffffff;
            cursor: default;
        }

        /* ---------- buttons (login.php look) ---------- */

        .formButtons {
            border-top: 1px solid #ddd8ce;
            padding-top: 25px;
            margin-top: 10px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .resetBtn {
            min-width: 120px;
            height: 44px;
            padding: 0 25px;
            border: 1px solid #d6d2c8;
            border-radius: 7px;
            background: #ffffff;
            color: #333333;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .resetBtn:hover {
            background: #eee9dd;
            color: #4b561b;
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

        /* ---------- sign in link ---------- */

        .account-register {
            border-top: 1px solid #ddd8ce;
            padding: 25px 40px;
            text-align: center;
            background: #ffffff;
        }

        .account-register p {
            color: #777777;
            font-size: 14px;
        }

        .account-register a {
            color: #4b561b;
            font-weight: 700;
            text-decoration: none;
            margin-left: 4px;
        }

        .account-register a:hover {
            text-decoration: underline;
        }

        /* ---------- responsive ---------- */

        @media (max-width: 700px) {

            .login-page {
                padding: 25px 12px 45px;
            }

            .login-container {
                border-radius: 9px;
            }

            .login-header {
                padding: 30px 25px;
            }

            .login-header h1 {
                font-size: 25px;
            }

            .login-form {
                padding: 30px 25px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .ward-wrapper {
                flex-direction: column;
            }

            .wardBtn {
                padding: 12px;
            }

            .account-register {
                padding: 22px 25px;
            }

            .formButtons {
                flex-direction: column-reverse;
            }

            .resetBtn,
            .submitBtn {
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

            .login-form {
                padding: 25px 20px;
            }

        }

    </style>

</head>

<body>

    <div class="navbar">

        <div class="logo">

            <a href="index.php">

                <img src="makanalogo.svg" alt="M-LINK">

            </a>

        </div>

    </div>


    <div class="backNavWrapper">

        <div class="backNavContainer">

            <a href="javascript:history.back()" class="backBtn">

                Back

            </a>

        </div>

    </div>


    <main class="login-page">

        <div class="login-container">

            <div class="login-header">

                <h1>Create Your M-LINK Account</h1>

                <p>Register your account to connect with your community.</p>

            </div>


            <form class="login-form"
                  id="registrationForm"
                  action="urreg.php"
                  method="POST"
                  autocomplete="off">

                <div class="form-group">

                    <label for="role">

                        Select Your Role *

                    </label>

                    <select id="role"
                            name="role"
                            required>

                        <option value="">

                             Select your role 

                        </option>

                        <option value="resident">

                            Resident

                        </option>

                        <option value="ward_councillor">

                            Ward councillor

                        </option>

                        <option value="municipal_staff">

                            Municipal staff

                        </option>

                        <option value="organisation">

                            Organisation

                        </option>

                    </select>

                    <small>

                        Select the role that applies
                        to your account.

                    </small>

                </div>

                <div class="form-row">

                    <div class="form-group">

                        <label for="first_name">

                            First Name *

                        </label>

                        <input type="text"
                               id="first_name"
                               name="first_name"
                               placeholder="Enter your first name"
                               title="Letters, spaces, hyphens and apostrophes only"
                               maxlength="100"
                               required>

                    </div>

                    <div class="form-group">

                        <label for="surname">

                            Surname *

                        </label>

                        <input type="text"
                               id="surname"
                               name="surname"
                               placeholder="Enter your surname"
                               title="Letters, spaces, hyphens and apostrophes only"
                               maxlength="100"
                               required>

                    </div>

                </div>

                <div class="form-group">

                    <label for="username">

                        Email Address *

                    </label>

                    <input type="email"
                           id="username"
                           name="username"
                           placeholder="Enter your email address"
                           maxlength="50"
                           pattern="(?!.*\.\.)[A-Za-z][A-Za-z0-9._%+\-]{0,63}@(?:[A-Za-z0-9](?:[A-Za-z0-9\-]*[A-Za-z0-9])?\.)+[Cc][Oo][Mm]"
                           title="Must start with a letter and end with .com (for example name@example.com)"
                           autocapitalize="none"
                           spellcheck="false"
                           autocomplete="off"
                           required>

                    <small>

                        Must start with a letter and end with .com

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
                           title="Exactly 10 digits, numbers only"
                           inputmode="numeric"
                           autocomplete="off"
                           required>

                    <small>

                        Numbers only - exactly 10 digits.

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
                               required>

                        <small>

                            8-72 characters with upper and lower case
                            letters, a number and a special character.

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
                               required>

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

                                Select your street

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

                        Address

                    </label>

                    <textarea
                        id="address"
                        name="address"
                        placeholder="Enter your full residential address"
                        maxlength="255"></textarea>

                    <small>

                        Optional. Include suburb and
                        postal code where applicable.

                    </small>

                </div>

                <div class="formButtons">

                    <button type="reset"
                            class="resetBtn">

                        Reset

                    </button>

                    <button type="submit"
                            class="submitBtn">

                        Create Account

                    </button>

                </div>

            </form>


            <div class="account-register">

                <p>

                    Already have an account?

                    <a href="login.php">Sign in here</a>

                </p>

            </div>

        </div>

    </main>


<script>

const roleSelect =
    document.getElementById("role");


roleSelect.addEventListener(
    "change",
    function () {

        if (this.value === "organisation") {

            window.location.href =
                "organisationRegistration.php";

        }

    }
);


const streetSelect =
    document.getElementById("address_id");

const wardIdInput =
    document.getElementById("ward_id");

const wardBtn =
    document.getElementById("wardBtn");

const wardMessage =
    document.getElementById("wardMessage");


streetSelect.addEventListener(
    "change",
    function () {

        const selectedOption =
            this.options[this.selectedIndex];

        const wardId =
            selectedOption.getAttribute("data-ward");


        if (wardId) {

            wardIdInput.value = wardId;

            wardBtn.textContent =
                "Ward " + wardId;

            wardBtn.disabled = false;

            wardMessage.textContent =
                "You have been assigned to Ward "
                + wardId + ".";

        }

        else {

            wardIdInput.value = "";

            wardBtn.textContent =
                "Ward Selection";

            wardBtn.disabled = true;

            wardMessage.textContent =
                "Select a street to determine your ward.";

        }

    }
);


const password =
    document.getElementById("password");

const confirmPassword =
    document.getElementById("confirm_password");


function checkPassword() {

    if (password.value !== confirmPassword.value) {

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
    checkPassword
);

confirmPassword.addEventListener(
    "input",
    checkPassword
);


// ---------------------------------------------------------------
// Username rules: must start with a letter and end with ".com"
// (browser-side help only - the server must check these too)
// ---------------------------------------------------------------
const usernameInput =
    document.getElementById("username");

const usernamePattern =
    /^(?!.*\.\.)[A-Za-z][A-Za-z0-9._%+\-]{0,63}@(?:[A-Za-z0-9](?:[A-Za-z0-9\-]*[A-Za-z0-9])?\.)+com$/i;


function checkUsername() {

    const value = usernameInput.value.trim();

    usernameInput.setCustomValidity("");

    if (value === "") {

        return;

    }

    if (/^[0-9]/.test(value)) {

        usernameInput.setCustomValidity(
            "The email address cannot start with a number."
        );

    }

    else if (!/^[A-Za-z]/.test(value)) {

        usernameInput.setCustomValidity(
            "The email address must start with a letter."
        );

    }

    else if (!/\.com$/i.test(value)) {

        usernameInput.setCustomValidity(
            "The email address must end with .com"
        );

    }

    else if (!usernamePattern.test(value)) {

        usernameInput.setCustomValidity(
            "Enter a valid email address, for example name@example.com"
        );

    }

}


usernameInput.addEventListener("input", checkUsername);
usernameInput.addEventListener("blur", checkUsername);


// ---------------------------------------------------------------
// Contact number: digits only (letters and symbols are removed as
// the user types or pastes)
// ---------------------------------------------------------------
const contactInput =
    document.getElementById("contact");


function cleanContact() {

    contactInput.value =
        contactInput.value.replace(/[^0-9]/g, "").slice(0, 10);

    if (
        contactInput.value.length > 0 &&
        contactInput.value.length < 10
    ) {

        contactInput.setCustomValidity(
            "The contact number must be exactly 10 digits."
        );

    }

    else {

        contactInput.setCustomValidity("");

    }

}


contactInput.addEventListener("input", cleanContact);
contactInput.addEventListener("blur", cleanContact);


// ---------------------------------------------------------------
// Names, address and password rules (browser-side help only - the
// server checks the same rules)
// ---------------------------------------------------------------
(function () {

    const usernameField = document.getElementById("username");
    const passwordField = document.getElementById("password");
    const firstNameField = document.getElementById("first_name");
    const surnameField = document.getElementById("surname");
    const addressField = document.getElementById("address");

    const namePattern =
        /^\p{L}(?:[\p{L}\p{M}\u2019' \-]*[\p{L}\p{M}])?$/u;

    const addressPattern =
        /^[\p{L}\p{M}0-9 \r\n,.\-\u2019'\/#]*$/u;

    function checkName(field, label) {

        const value = field.value.trim();

        if (value !== "" && !namePattern.test(value)) {

            field.setCustomValidity(
                label + " may only contain letters, spaces, hyphens and apostrophes."
            );

        }

        else {

            field.setCustomValidity("");

        }

    }

    firstNameField.addEventListener(
        "input",
        function () { checkName(firstNameField, "First name"); }
    );

    surnameField.addEventListener(
        "input",
        function () { checkName(surnameField, "Surname"); }
    );

    addressField.addEventListener(
        "input",
        function () {

            if (!addressPattern.test(addressField.value)) {

                addressField.setCustomValidity(
                    "The address may only contain letters, numbers, spaces and the characters , . - ' / #"
                );

            }

            else {

                addressField.setCustomValidity("");

            }

        }
    );

    function extraWords() {

        return [firstNameField.value, surnameField.value];

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


document
    .getElementById("registrationForm")
    .addEventListener(
        "reset",
        function () {

            setTimeout(function () {

                wardIdInput.value = "";

                wardBtn.textContent =
                    "Ward Selection";

                wardBtn.disabled = true;

                wardMessage.textContent =
                    "Select a street to determine your ward.";

            }, 0);

        }
    );

</script>

</body>

</html>