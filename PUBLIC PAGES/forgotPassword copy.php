<?php

session_start();

require_once "Error404Connection.php";


$message = "";
$messageType = "";
$resetLink = "";


//check if form was submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");


    //check if username was entered
    if ($username === "") {

        $message = "Please enter your registered username.";
        $messageType = "error";

    } else {

        //check if the account exists
        $stmt = $conn->prepare("
            SELECT username
            FROM `user`
            WHERE username = ?
            LIMIT 1
        ");


        if (!$stmt) {

            $message = "A system error occurred. Please try again.";
            $messageType = "error";

        } else {

            $stmt->bind_param(
                "s",
                $username
            );

            $stmt->execute();

            $result = $stmt->get_result();


            if (
                $result &&
                $result->num_rows === 1
            ) {

                $user = $result->fetch_assoc();

                $accountUsername = $user["username"];


                //invalidate old unused reset links
                $oldStmt = $conn->prepare("
                    UPDATE password_reset_tokens
                    SET used = 1
                    WHERE username = ?
                    AND used = 0
                ");


                if ($oldStmt) {

                    $oldStmt->bind_param(
                        "s",
                        $accountUsername
                    );

                    $oldStmt->execute();

                    $oldStmt->close();
                }


                //generate secure reset token
                $token = bin2hex(
                    random_bytes(32)
                );


                //store only the token hash
                $tokenHash = hash(
                    "sha256",
                    $token
                );


                //reset link expires after 30 minutes
                $expiresAt = date(
                    "Y-m-d H:i:s",
                    time() + (30 * 60)
                );


                //save reset request
                $insertStmt = $conn->prepare("
                    INSERT INTO password_reset_tokens
                    (
                        username,
                        token_hash,
                        expires_at,
                        used
                    )
                    VALUES (?, ?, ?, 0)
                ");


                if (!$insertStmt) {

                    $message =
                        "A system error occurred. Please try again.";

                    $messageType = "error";

                } else {

                    $insertStmt->bind_param(
                        "sss",
                        $accountUsername,
                        $tokenHash,
                        $expiresAt
                    );


                    if ($insertStmt->execute()) {

                        //development reset link
                        $resetLink =
                            "resetPassword.php?token=" .
                            urlencode($token);


                        $message =
                            "Your password reset request has been created.";

                        $messageType = "success";

                    } else {

                        $message =
                            "The password reset request could not be created.";

                        $messageType = "error";
                    }


                    $insertStmt->close();
                }

            } else {

                $message =
                    "No account was found with that username.";

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

    <title>Forgot Password | M-LINK</title>


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

            width: 56px;

            height: 56px;

            object-fit: contain;

            padding: 5px;

        }


        /*back button*/

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

            padding: 9px 17px;

            background: #ffffff;

            color: #333333;

            border: 1px solid #d6d2c8;

            border-radius: 7px;

            text-decoration: none;

            font-size: 15px;

            font-weight: 500;

            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);

        }


        .backBtn:hover {

            background: #eee9dd;

            color: #4b561b;

        }


        /*page*/

        .forgot-page {

            width: 100%;

            padding: 40px 20px 60px;

            display: flex;

            justify-content: center;

            align-items: flex-start;

        }


        .forgot-container {

            width: 100%;

            max-width: 900px;

            background: #ffffff;

            border-radius: 11px;

            overflow: hidden;

            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.10);

        }


        /*header*/

        .forgot-header {

            background: #4b561b;

            padding: 36px 40px 32px;

            position: relative;

            overflow: hidden;

        }


        .forgot-header::after {

            content: "";

            position: absolute;

            width: 170px;

            height: 170px;

            right: -65px;

            top: -85px;

            border-radius: 50%;

            background: rgba(255, 255, 255, 0.07);

        }


        .forgot-header h1 {

            position: relative;

            z-index: 1;

            color: #ffffff;

            font-size: 30px;

            font-weight: 700;

            margin-bottom: 10px;

        }


        .forgot-header p {

            position: relative;

            z-index: 1;

            color: #ffffff;

            font-size: 15px;

            line-height: 1.5;

        }


        /*messages*/

        .message {

            margin: 25px 40px 0;

            padding: 13px 15px;

            border-radius: 7px;

            font-size: 14px;

            line-height: 1.5;

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

        .forgot-form {

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


        .form-group input {

            width: 100%;

            height: 48px;

            padding: 0 14px;

            border: 1px solid #cfcac0;

            border-radius: 7px;

            background: #ffffff;

            color: #222222;

            font-size: 15px;

            outline: none;

        }


        .form-group input:focus {

            border-color: #4b561b;

            box-shadow: 0 0 0 3px rgba(75, 86, 27, 0.12);

        }


        .help-text {

            color: #777777;

            font-size: 13px;

            margin-top: 8px;

            line-height: 1.5;

        }


        /*buttons*/

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


        /*reset section*/

        .reset-section {

            margin: 0 40px 40px;

            padding: 20px;

            background: #f7f7f7;

            border: 1px solid #ddd8ce;

            border-radius: 7px;

        }


        .reset-section p {

            color: #555555;

            font-size: 14px;

            line-height: 1.5;

            margin-bottom: 15px;

        }


        .resetBtn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 44px;

            padding: 0 20px;

            background: #4b561b;

            color: #ffffff;

            border-radius: 7px;

            text-decoration: none;

            font-size: 14px;

            font-weight: 700;

        }


        .resetBtn:hover {

            background: #3d4616;

        }


        /*login section*/

        .account-login {

            border-top: 1px solid #ddd8ce;

            padding: 25px 40px;

            text-align: center;

            background: #ffffff;

        }


        .account-login p {

            color: #777777;

            font-size: 14px;

        }


        .account-login a {

            color: #4b561b;

            font-weight: 700;

            text-decoration: none;

            margin-left: 4px;

        }


        .account-login a:hover {

            text-decoration: underline;

        }


        /*responsive*/

        @media (max-width: 700px) {

            .forgot-page {

                padding: 25px 12px 45px;

            }


            .forgot-header {

                padding: 30px 25px;

            }


            .forgot-header h1 {

                font-size: 25px;

            }


            .forgot-form {

                padding: 30px 25px;

            }


            .message {

                margin-left: 25px;

                margin-right: 25px;

            }


            .reset-section {

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


            .forgot-form {

                padding: 25px 20px;

            }


            .message {

                margin-left: 20px;

                margin-right: 20px;

            }


            .reset-section {

                margin-left: 20px;

                margin-right: 20px;

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


    <!--forgot password page-->

    <main class="forgot-page">

        <div class="forgot-container">


            <div class="forgot-header">

                <h1>Forgot Password</h1>

                <p>
                    Enter your registered username to continue.
                </p>

            </div>


            <!--message-->

            <?php if ($message !== ""): ?>

                <div
                    class="message <?= htmlspecialchars($messageType); ?>"
                >

                    <?= htmlspecialchars($message); ?>

                </div>

            <?php endif; ?>


            <!--form-->

            <form
                class="forgot-form"
                method="POST"
                action="forgotPassword.php"
                autocomplete="off"
            >

                <div class="form-group">

                    <label for="username">
                        Username
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Enter your registered username"
                        value="<?=
                            htmlspecialchars(
                                $_POST["username"] ?? ""
                            );
                        ?>"
                        required
                    >

                    <p class="help-text">
                        Enter the username you normally use to log in to M-LINK.
                    </p>

                </div>


                <div class="formButtons">

                    <button
                        type="submit"
                        class="submitBtn"
                    >
                        Continue
                    </button>

                </div>

            </form>


            <!--reset password link-->

            <?php if ($resetLink !== ""): ?>

                <div class="reset-section">

                    <p>
                        Your account has been confirmed.
                        Continue below to create a new password.
                    </p>

                    <a
                        href="<?= htmlspecialchars($resetLink); ?>"
                        class="resetBtn"
                    >
                        Continue to Reset Password
                    </a>

                </div>

            <?php endif; ?>


            <!--back to login-->

            <div class="account-login">

                <p>

                    Remember your password?

                    <a href="login.php">
                        Login
                    </a>

                </p>

            </div>


        </div>

    </main>


</body>

</html>