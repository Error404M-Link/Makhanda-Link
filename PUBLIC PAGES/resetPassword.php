<?php

session_start();

require_once "Error404Connection.php";


$message = "";
$messageType = "";


//get token from URL
$token = trim($_GET["token"] ?? $_POST["token"] ?? "");


//check if token exists
if ($token === "") {

    $message = "Invalid password reset request.";
    $messageType = "error";

} else {

    //hash token so it can be compared with database
    $tokenHash = hash(
        "sha256",
        $token
    );


    //find valid reset request
    $stmt = $conn->prepare("
        SELECT
            reset_id,
            username,
            expires_at,
            used
        FROM password_reset_tokens
        WHERE token_hash = ?
        LIMIT 1
    ");


    if (!$stmt) {

        $message = "A system error occurred.";
        $messageType = "error";

    } else {

        $stmt->bind_param(
            "s",
            $tokenHash
        );

        $stmt->execute();

        $result = $stmt->get_result();


        if (
            !$result ||
            $result->num_rows !== 1
        ) {

            $message = "This password reset link is invalid.";
            $messageType = "error";

        } else {

            $resetRequest = $result->fetch_assoc();


            //check if link was already used
            if ((int)$resetRequest["used"] === 1) {

                $message =
                    "This password reset link has already been used.";

                $messageType = "error";

            }

            //check if link expired
            elseif (
                strtotime($resetRequest["expires_at"]) <= time()
            ) {

                $message =
                    "This password reset link has expired. Please request a new one.";

                $messageType = "error";

            }

            //process new password
            elseif ($_SERVER["REQUEST_METHOD"] === "POST") {

                $newPassword =
                    $_POST["new_password"] ?? "";

                $confirmPassword =
                    $_POST["confirm_password"] ?? "";


                //validation
                if (
                    $newPassword === "" ||
                    $confirmPassword === ""
                ) {

                    $message =
                        "Please enter and confirm your new password.";

                    $messageType = "error";

                } elseif (
                    strlen($newPassword) < 8
                ) {

                    $message =
                        "Password must contain at least 8 characters.";

                    $messageType = "error";

                } elseif (
                    $newPassword !== $confirmPassword
                ) {

                    $message =
                        "The passwords do not match.";

                    $messageType = "error";

                } else {

                    $username =
                        $resetRequest["username"];

                    //hash new password
                    $passwordHash =
                        password_hash(
                            $newPassword,
                            PASSWORD_DEFAULT
                        );


                    try {

                        $conn->begin_transaction();


                        //update password in user table
                        $updateStmt =
                            $conn->prepare("
                                UPDATE `user`
                                SET password_hash = ?
                                WHERE username = ?
                                LIMIT 1
                            ");


                        if (!$updateStmt) {

                            throw new Exception(
                                "Could not update password."
                            );
                        }


                        $updateStmt->bind_param(
                            "ss",
                            $passwordHash,
                            $username
                        );


                        if (!$updateStmt->execute()) {

                            throw new Exception(
                                "Could not update password."
                            );
                        }


                        if ($updateStmt->affected_rows < 1) {

                            throw new Exception(
                                "Account could not be updated."
                            );
                        }


                        $updateStmt->close();


                        /*
                            If the account is an administrator,
                            also update admin.password_hashed
                        */

                        $roleStmt =
                            $conn->prepare("
                                SELECT User_role
                                FROM `user`
                                WHERE username = ?
                                LIMIT 1
                            ");


                        if (!$roleStmt) {

                            throw new Exception(
                                "Could not check account role."
                            );
                        }


                        $roleStmt->bind_param(
                            "s",
                            $username
                        );

                        $roleStmt->execute();

                        $roleResult =
                            $roleStmt->get_result();


                        if (
                            $roleResult &&
                            $roleResult->num_rows === 1
                        ) {

                            $userData =
                                $roleResult->fetch_assoc();

                            $userRole =
                                strtolower(
                                    trim(
                                        $userData["User_role"]
                                        ?? ""
                                    )
                                );


                            if ($userRole === "admin") {

                                $adminStmt =
                                    $conn->prepare("
                                        UPDATE admin
                                        SET password_hashed = ?
                                        WHERE username = ?
                                    ");


                                if ($adminStmt) {

                                    $adminStmt->bind_param(
                                        "ss",
                                        $passwordHash,
                                        $username
                                    );

                                    $adminStmt->execute();

                                    $adminStmt->close();
                                }
                            }
                        }


                        $roleStmt->close();


                        //mark reset token as used
                        $usedStmt =
                            $conn->prepare("
                                UPDATE password_reset_tokens
                                SET used = 1
                                WHERE reset_id = ?
                            ");


                        if (!$usedStmt) {

                            throw new Exception(
                                "Could not complete password reset."
                            );
                        }


                        $resetId =
                            (int)$resetRequest["reset_id"];


                        $usedStmt->bind_param(
                            "i",
                            $resetId
                        );

                        $usedStmt->execute();

                        $usedStmt->close();


                        //invalidate other unused reset links
                        $invalidateStmt =
                            $conn->prepare("
                                UPDATE password_reset_tokens
                                SET used = 1
                                WHERE username = ?
                                AND used = 0
                            ");


                        if ($invalidateStmt) {

                            $invalidateStmt->bind_param(
                                "s",
                                $username
                            );

                            $invalidateStmt->execute();

                            $invalidateStmt->close();
                        }


                        $conn->commit();


                        //send user back to login
                        header(
                            "Location: login.php?success=password_reset"
                        );

                        exit;


                    } catch (Throwable $e) {

                        $conn->rollback();

                        $message =
                            "Your password could not be reset. Please try again.";

                        $messageType = "error";
                    }
                }
            }
        }


        $stmt->close();
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

    <title>Reset Password | M-LINK</title>


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


        /*navbar*/

        .navbar {

            height: 76px;

            width: 100%;

            background: #4b561b;

            display: flex;

            align-items: center;

            padding-left: 20px;

            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.12);

        }


        .logo a {

            display: flex;

            align-items: center;

            justify-content: center;

        }


        .logo img {

            width: 56px;

            height: 56px;

            object-fit: contain;

            padding: 5px;

        }


        /*back navigation*/

        .backNavWrapper {

            width: 100%;

            height: 68px;

            background: #f7f7f7;

            border-bottom: 1px solid #e2dfd7;

            display: flex;

            align-items: center;

        }


        .backNavContainer {

            width: 100%;

            max-width: 1050px;

            padding: 20px;

        }


        .backBtn {

            display: inline-flex;

            align-items: center;

            padding: 9px 17px;

            background: #ffffff;

            color: #333333;

            border: 1px solid #d6d2c8;

            border-radius: 7px;

            text-decoration: none;

            font-size: 15px;

            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);

        }


        .backBtn:hover {

            background: #eee9dd;

            color: #4b561b;

        }


        /*page*/

        .reset-page {

            width: 100%;

            padding: 40px 20px 60px;

            display: flex;

            justify-content: center;

        }


        .reset-container {

            width: 100%;

            max-width: 900px;

            background: #ffffff;

            border-radius: 11px;

            overflow: hidden;

            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.10);

        }


        /*header*/

        .reset-header {

            background: #4b561b;

            padding: 36px 40px 32px;

            position: relative;

            overflow: hidden;

        }


        .reset-header::after {

            content: "";

            position: absolute;

            width: 170px;

            height: 170px;

            right: -65px;

            top: -85px;

            border-radius: 50%;

            background: rgba(255, 255, 255, 0.07);

        }


        .reset-header h1 {

            position: relative;

            z-index: 1;

            color: #ffffff;

            font-size: 30px;

            margin-bottom: 10px;

        }


        .reset-header p {

            position: relative;

            z-index: 1;

            color: #ffffff;

            font-size: 15px;

        }


        /*messages*/

        .message {

            margin: 25px 40px 0;

            padding: 13px 15px;

            border-radius: 7px;

            font-size: 14px;

        }


        .message.error {

            background: #fce8e6;

            color: #b42318;

            border: 1px solid #f5c2c0;

        }


        .message.success {

            background: #e9f5e8;

            color: #356b2f;

            border: 1px solid #c5dfc2;

        }


        /*form*/

        .reset-form {

            padding: 40px;

        }


        .form-group {

            margin-bottom: 25px;

        }


        .form-group label {

            display: block;

            margin-bottom: 8px;

            font-size: 14px;

            font-weight: 600;

        }


        .form-group input {

            width: 100%;

            height: 48px;

            padding: 0 14px;

            border: 1px solid #cfcac0;

            border-radius: 7px;

            font-size: 15px;

            outline: none;

        }


        .form-group input:focus {

            border-color: #4b561b;

            box-shadow:
                0 0 0 3px rgba(75, 86, 27, 0.12);

        }


        .help-text {

            color: #777777;

            font-size: 13px;

            margin-top: 8px;

        }


        .formButtons {

            border-top: 1px solid #ddd8ce;

            padding-top: 25px;

            display: flex;

            justify-content: flex-end;

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

        }


        .submitBtn:hover {

            background: #3d4616;

        }


        .invalid-section {

            padding: 40px;

            text-align: center;

        }


        .invalid-section a {

            display: inline-block;

            margin-top: 20px;

            padding: 12px 20px;

            background: #4b561b;

            color: #ffffff;

            border-radius: 7px;

            text-decoration: none;

            font-weight: 700;

        }


        /*responsive*/

        @media (max-width: 700px) {

            .reset-page {

                padding: 25px 12px 45px;

            }


            .reset-header {

                padding: 30px 25px;

            }


            .reset-form {

                padding: 30px 25px;

            }


            .message {

                margin-left: 25px;

                margin-right: 25px;

            }


            .formButtons {

                justify-content: stretch;

            }


            .submitBtn {

                width: 100%;

            }

        }

    </style>

</head>


<body>


    <!--navbar-->

    <div class="navbar">

        <div class="logo">

            <a href="index.php">

                <img
                    src="makanalogo.svg"
                    alt="M-LINK"
                >

            </a>

        </div>

    </div>


    <!--back button-->

    <div class="backNavWrapper">

        <div class="backNavContainer">

            <a
                href="login.php"
                class="backBtn"
            >
                Back
            </a>

        </div>

    </div>


    <main class="reset-page">

        <div class="reset-container">


            <div class="reset-header">

                <h1>Reset Password</h1>

                <p>
                    Create a new password for your M-LINK account.
                </p>

            </div>


            <?php if ($message !== ""): ?>

                <div
                    class="message <?= htmlspecialchars($messageType); ?>"
                >

                    <?= htmlspecialchars($message); ?>

                </div>

            <?php endif; ?>


            <?php if (
                isset($resetRequest) &&
                (int)$resetRequest["used"] === 0 &&
                strtotime($resetRequest["expires_at"]) > time()
            ): ?>


                <form
                    class="reset-form"
                    method="POST"
                    autocomplete="off"
                >

                    <!--keep token when form submits-->

                    <input
                        type="hidden"
                        name="token"
                        value="<?= htmlspecialchars($token); ?>"
                    >


                    <div class="form-group">

                        <label for="new_password">
                            New Password
                        </label>

                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            placeholder="Enter your new password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >

                        <p class="help-text">
                            Your password must contain at least 8 characters.
                        </p>

                    </div>


                    <div class="form-group">

                        <label for="confirm_password">
                            Confirm Password
                        </label>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Confirm your new password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >

                    </div>


                    <div class="formButtons">

                        <button
                            type="submit"
                            class="submitBtn"
                        >
                            Reset Password
                        </button>

                    </div>


                </form>


            <?php else: ?>


                <div class="invalid-section">

                    <p>
                        Request a new password reset link to continue.
                    </p>

                    <a href="forgotPassword.php">
                        Forgot Password
                    </a>

                </div>


            <?php endif; ?>


        </div>

    </main>


</body>

</html>