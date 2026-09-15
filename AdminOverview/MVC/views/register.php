
<?php

include 'Error404Connection.php';

//get streets from database
$sql = "SELECT address_id, ward_id, street_name
        FROM address_directory
        ORDER BY street_name";

$result = mysqli_query($conn, $sql);

if (!$result) {

    die("Could not load streets: " . mysqli_error($conn));

}

//store streets so they can be used in the form
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

    <link rel="stylesheet" href="registration.css">
    <link rel="stylesheet" href="publicfaultBoard.css">
    <link rel="stylesheet" href="backButton.css">

    <title>M-LINK | Sign Up</title>

</head>

<body>

<!--navigation bar-->
<nav class="navbar">

<div class="logo">
 <a href="index.php">

           \ <img src="makanalogo.jpeg"
                 alt="M-LINK">

        </a>

    </div>

</nav>


<!--back button-->
<div class="backNavWrapper">

    <div class="backNavContainer">

        <a href="javascript:history.back()"
           class="backBtn">

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


<!--registration section-->
<section class="registration">

    <div class="registrationBody">


        <!--registration heading-->
        <div class="registrationHeader">

            <div class="header-content">

                <h4>
                    Create Your M-LINK Account
                </h4>

                <p>
                    Register your account to connect
                    with your community.
                </p>

            </div>

        </div>


        <!--registration form-->
        <form id="registrationForm"
      action="MVC/controllers/RegistrationController.php"
      method="POST"
      autocomplete="off">


                <!--select user role-->
                <div class="form-group">

                    <label for="role">

                        Select Your Role *

                    </label>

                    <select id="role"
                            name="role"
                            required>

                        <option value="">

                            -- Select your role --

                        </option>

                        <option value="resident">

                            RESIDENT

                        </option>

                        <option value="ward_councillor">

                            WARD COUNCILLOR

                        </option>

                        <option value="municipal_staff">

                            MUNICIPAL STAFF

                        </option>

                        <option value="organisation">

                            ORGANISATION

                        </option>

                    </select>

                    <small>

                        Select the role that applies
                        to your account.

                    </small>

                </div>


                <!--first name and surname-->
                <div class="form-row">

                    <div class="form-group">

                        <label for="first_name">

                            First Name *

                        </label>

                        <input type="text"
                               id="first_name"
                               name="first_name"
                               placeholder="Enter your first name"
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
                               maxlength="100"
                               required>

                    </div>

                </div>


                <!--username-->
                <!--username-->
<div class="form-group">

    <label for="username">

        Email Address *

    </label>

    <input type="email"
           id="username"
           name="username"
           placeholder="Enter your email address"
           maxlength="100"
           required>

    <small>

        Enter a valid email address.

    </small>

</div>

                <!--contact number-->
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
                           required>

                    <small>

                        Enter exactly 10 digits.

                    </small>

                </div>


                <!--password-->
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


                <!--street selection-->
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


                <!--hidden ward id-->
                <input type="hidden"
                       id="ward_id"
                       name="ward_id"
                       value="">


                <!--full address-->
                <div class="form-group">

                    <label for="address">

                        Address

                    </label>

                    <textarea
                        id="address"
                        name="address"
                        placeholder="Enter your full residential address"
                        maxlength="500"></textarea>

                    <small>

                        Optional. Include suburb and
                        postal code where applicable.

                    </small>

                </div>


                <!--form buttons-->
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

        </div>

    </div>


    <!--sign in link-->
    <div class="account-signin">

        <p>

            Already have an account?

            <a href="login.php">

                Sign in here

            </a>

        </p>

    </div>

</section>


<script>

//get the street, ward and message elements
const streetSelect =
    document.getElementById("address_id");

const wardIdInput =
    document.getElementById("ward_id");

const wardBtn =
    document.getElementById("wardBtn");

const wardMessage =
    document.getElementById("wardMessage");


//get the ward when a street is selected
streetSelect.addEventListener(
    "change",
    function () {

        const selectedOption =
            this.options[this.selectedIndex];

        const wardId =
            selectedOption.getAttribute("data-ward");


        //check if a ward was found
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


//check if the passwords match
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


//reset the ward when the form is reset
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

