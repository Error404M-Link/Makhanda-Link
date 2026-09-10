
<?php

require_once "dbConnection.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get form values
    $first_name = trim($_POST["first_name"] ?? "");
    $surname = trim($_POST["surname"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $contact = trim($_POST["contact"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    $ward_id = (int)($_POST["ward_id"] ?? 0);
    $address_id = (int)($_POST["address_id"] ?? 0);

    // -----------------------------
    // VALIDATION
    // -----------------------------

    if (
        empty($first_name) ||
        empty($username) ||
        empty($contact) ||
        empty($password) ||
        empty($confirm_password) ||
        $ward_id <= 0 ||
        $address_id <= 0
    ) {
        $message = "Please complete all required fields.";
        $message_type = "error";
    }

    // Validate contact number
    elseif (!preg_match('/^[0-9]{10}$/', $contact)) {
        $message = "Contact number must contain exactly 10 digits.";
        $message_type = "error";
    }

    // Validate password confirmation
    elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "error";
    }

    else {

        // -----------------------------
        // CHECK USERNAME
        // -----------------------------

        $sql = "SELECT username FROM user WHERE username = ?";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Database error: " . $conn->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $message = "That username is already registered.";
            $message_type = "error";

            $stmt->close();

        } else {

            $stmt->close();

            // -----------------------------
            // CHECK ADDRESS BELONGS TO WARD
            // -----------------------------

            $sql = "SELECT address_id
                    FROM address_directory
                    WHERE address_id = ?
                    AND ward_id = ?";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                die("Database error: " . $conn->error);
            }

            $stmt->bind_param("ii", $address_id, $ward_id);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 0) {

                $message = "The selected address does not belong to the selected ward.";
                $message_type = "error";

                $stmt->close();

            } else {

                $stmt->close();

                // -----------------------------
                // HASH PASSWORD
                // -----------------------------

                $password_hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // -----------------------------
                // START TRANSACTION
                // -----------------------------

                $conn->begin_transaction();

                try {

                    // -----------------------------
                    // INSERT INTO USER
                    // -----------------------------

                    $user_role = "Community Member";

                    $sql = "INSERT INTO user
                            (
                                username,
                                first_Name,
                                surname,
                                contact,
                                password_hash,
                                User_role
                            )
                            VALUES (?, ?, ?, ?, ?, ?)";

                    $stmt = $conn->prepare($sql);

                    if (!$stmt) {
                        throw new Exception($conn->error);
                    }

                    $stmt->bind_param(
                        "ssssss",
                        $username,
                        $first_name,
                        $surname,
                        $contact,
                        $password_hash,
                        $user_role
                    );

                    if (!$stmt->execute()) {
                        throw new Exception($stmt->error);
                    }

                    $stmt->close();


                    // -----------------------------
                    // INSERT INTO COMMUNITY_MEMBER
                    // -----------------------------

                    $sql = "INSERT INTO community_member
                            (
                                username,
                                ward_id,
                                address_id
                            )
                            VALUES (?, ?, ?)";

                    $stmt = $conn->prepare($sql);

                    if (!$stmt) {
                        throw new Exception($conn->error);
                    }

                    $stmt->bind_param(
                        "sii",
                        $username,
                        $ward_id,
                        $address_id
                    );

                    if (!$stmt->execute()) {
                        throw new Exception($stmt->error);
                    }

                    $stmt->close();


                    // -----------------------------
                    // EVERYTHING SUCCESSFUL
                    // -----------------------------

                    $conn->commit();

                    $message = "Registration successful! You can now log in.";
                    $message_type = "success";

                } catch (Exception $e) {

                    // Something failed
                    $conn->rollback();

                    $message = "Registration failed: " . $e->getMessage();
                    $message_type = "error";
                }
            }
        }
    }
}


// ------------------------------------
// GET ALL WARDS
// ------------------------------------

$wards = [];

$sql = "SELECT ward_id, ward_name, ward_number
        FROM ward
        ORDER BY ward_number";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $wards[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>M-LINK Registration</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f5f1eb;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 90%;
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: #5c4033;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input,
        select {
            width: 100%;
            padding: 11px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background: #5c4033;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #402c22;
        }

        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>Create M-LINK Account</h1>

    <?php if (!empty($message)): ?>

        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <form method="POST" action="urreg.php">

        <!-- FIRST NAME -->

        <div class="form-group">

            <label for="first_name">
                First Name *
            </label>

            <input
                type="text"
                id="first_name"
                name="first_name"
                required
                maxlength="100"
            >

        </div>


        <!-- SURNAME -->

        <div class="form-group">

            <label for="surname">
                Surname
            </label>

            <input
                type="text"
                id="surname"
                name="surname"
                maxlength="100"
            >

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
                required
                maxlength="50"
            >

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
                required
                maxlength="10"
                minlength="10"
                pattern="[0-9]{10}"
                placeholder="0821234567"
            >

        </div>


        <!-- PASSWORD -->

        <div class="form-group">

            <label for="password">
                Password *
            </label>

            <input
                type="password"
                id="password"
                name="password"
                required
            >

        </div>


        <!-- CONFIRM PASSWORD -->

        <div class="form-group">

            <label for="confirm_password">
                Confirm Password *
            </label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                required
            >

        </div>


        <!-- WARD -->

        <div class="form-group">

            <label for="ward_id">
                Ward *
            </label>

            <select
                id="ward_id"
                name="ward_id"
                required
            >

                <option value="">
                    -- Select Ward --
                </option>

                <?php foreach ($wards as $ward): ?>

                    <option value="<?php echo $ward['ward_id']; ?>">

                        <?php
                        echo htmlspecialchars(
                            "Ward " .
                            $ward['ward_number'] .
                            " - " .
                            $ward['ward_name']
                        );
                        ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- ADDRESS -->

        <div class="form-group">

            <label for="address_id">
                Address *
            </label>

            <select
                id="address_id"
                name="address_id"
                required
            >

                <option value="">
                    -- Select a ward first --
                </option>

            </select>

        </div>


        <!-- SUBMIT -->

        <button type="submit">
            Create Account
        </button>

    </form>

</div>


<script>

const wardSelect = document.getElementById("ward_id");
const addressSelect = document.getElementById("address_id");

wardSelect.addEventListener("change", function () {

    const wardId = this.value;

    addressSelect.innerHTML =
        '<option value="">Loading addresses...</option>';

    if (wardId === "") {

        addressSelect.innerHTML =
            '<option value="">-- Select a ward first --</option>';

        return;
    }

    fetch("get_addresses.php?ward_id=" + wardId)

        .then(response => response.json())

        .then(data => {

            addressSelect.innerHTML =
                '<option value="">-- Select Address --</option>';

            data.forEach(address => {

                const option = document.createElement("option");

                option.value = address.address_id;

                option.textContent =
                    address.street_name +
                    (address.suburb
                        ? " - " + address.suburb
                        : "") +
                    (address.postal_code
                        ? " (" + address.postal_code + ")"
                        : "");

                addressSelect.appendChild(option);

            });

        })

        .catch(error => {

            console.error(error);

            addressSelect.innerHTML =
                '<option value="">Unable to load addresses</option>';

        });

});

</script>

</body>
</html>
