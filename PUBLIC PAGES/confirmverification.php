
<?php

session_start();

require_once "Error404Connection.php";


$message = "";
$messageType = "";


// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $verificationCode = trim(
        $_POST["verificationCode"] ?? ""
    );


    // Check if code was entered
    if ($verificationCode === "") {

        $message = "Please enter your verification code.";
        $messageType = "error";

    }

    // Check if code is exactly 5 digits
    elseif (!preg_match('/^\d{5}$/', $verificationCode)) {

        $message = "Please enter a valid 5-digit verification code.";
        $messageType = "error";

    }

    else {

        $resetId = $_SESSION['codes_reset_id'];

        // Find a matching unused and non-expired code
        $stmt = $conn->prepare("
            SELECT reset_id, username, code_hash, expires_at
            FROM password_reset_codes
            WHERE reset_id = ?
            AND used = 0
            AND expires_at > NOW()
            ORDER BY created_at DESC
            LIMIT 1
        ");


        if (!$stmt) {

            $message =
                "A system error occurred. Please try again.";

            $messageType = "error";

        } else {

            $stmt->bind_param(
                "s",
                $resetId
            );

            $stmt->execute();

            $result = $stmt->get_result();

            // Check if a matching code was found
            if ($result && $result->num_rows === 1) 
            {

                $verification = $result->fetch_assoc();

                $verificationCodeHashed = hash(
                    "sha256",
                    $verificationCode
                );



                if ($verificationCodeHashed === $verification["code_hash"]) 
                {
                    $resetToken = $_SESSION['reset_token'];

                    // $message =
                    // "Verification successful!" . $resetToken;

                    // $messageType = "success";

                    $locationUrl = "resetPassword.php?token=" . urlencode($resetToken);
                    header("Location: " . $locationUrl);
                    exit;

                }
                else
                {
                    $message =
                "Wrong code " . $resetId;

                $messageType = "success";
                }

                


            } else {

                $message =
                    "The verification code is incorrect or has expired.";

                $messageType = "error";
            }


            $stmt->close();
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Confirm Verification - M-LINK</title>


    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;

            font-family: Arial, sans-serif;

            background-color: #F5F5DC;
        }


        .verification-container {

            width: 100%;
            max-width: 450px;

            padding: 35px;

            background-color: #ffffff;

            border-radius: 12px;

            box-shadow:
                0 4px 15px rgba(0, 0, 0, 0.15);
        }


        h1 {

            margin-top: 0;

            text-align: center;

            color: #000000;
        }


        .description {

            text-align: center;

            color: #555555;

            margin-bottom: 25px;
        }


        .form-group {

            margin-bottom: 20px;
        }


        label {

            display: block;

            margin-bottom: 8px;

            font-weight: bold;
        }


        input {

            width: 100%;

            padding: 12px;

            border: 1px solid #cccccc;

            border-radius: 6px;

            font-size: 18px;

            text-align: center;

            letter-spacing: 4px;
        }


        input:focus {

            outline: none;

            border-color: #B5B35C;
        }


        .submitBtn {

            width: 100%;

            padding: 12px;

            border: none;

            border-radius: 6px;

            background-color: #C08552;

            color: white;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;
        }


        .submitBtn:hover {

            opacity: 0.9;
        }


        .message {

            padding: 12px;

            margin-bottom: 20px;

            border-radius: 6px;

            text-align: center;
        }


        .message.success {

            background-color: #d4edda;

            color: #155724;
        }


        .message.error {

            background-color: #f8d7da;

            color: #721c24;
        }

    </style>

</head>


<body>


<div class="verification-container">


    <h1>Verify Your Code</h1>


    <p class="description">

        Enter the 5-digit verification code
        that was sent to your ;o pioiiyggDg.

    </p>


    <?php if (!empty($message)): ?>

        <div
            class="message <?= htmlspecialchars($messageType) ?>"
        >

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        action="confirmVerification.php"
        autocomplete="off"
    >


        <div class="form-group">

            <label for="verificationCode">

                Verification Code

            </label>


            <input
                type="text"
                id="verificationCode"
                name="verificationCode"
                maxlength="5"
                pattern="[0-9]{5}"
                inputmode="numeric"
                placeholder="Enter 5-digit code"
                required
            >

        </div>


        <button
            type="submit"
            class="submitBtn"
        >

            Continue

        </button>


    </form>


</div>


</body>

</html>

