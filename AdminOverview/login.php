<?php

session_start();

$error = $_GET["error"] ?? "";

$success = $_GET["success"] ?? "";

$errorMessages = [

    "invalid" => "Invalid username or password.",

    "not_verified" => "Your account is not verified.",

    "inactive" => "Your account is currently inactive. Please contact the administrator.",

    "missing" => "Please enter your username and password.",

    "role" => "Your account has an invalid user role.",

    "system" => "A system error occurred. Please try again later."

];

$successMessages = [

    "registered" => "Your account has been created successfully. You can now log in."

];

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>M-LINK Login</title>

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

            background: #ffffff;

            padding: 5px;

        }


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


        .login-error {

            margin: 25px 40px 0;

            padding: 13px 15px;

            background: #fce8e6;

            color: #b42318;

            border: 1px solid #f5c2c0;

            border-radius: 7px;

            font-size: 14px;

            line-height: 1.5;

        }


        .login-success {

            margin: 25px 40px 0;

            padding: 13px 15px;

            background: #e9f5e8;

            color: #356b2f;

            border: 1px solid #c5dfc2;

            border-radius: 7px;

            font-size: 14px;

            line-height: 1.5;

        }


        .login-form {

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


        .form-group input[type="text"],

        .form-group input[type="password"] {

            width: 100%;

            height: 48px;

            padding: 0 14px;

            border: 1px solid #cfcac0;

            border-radius: 7px;

            background: #ffffff;

            color: #222222;

            font-size: 15px;

            outline: none;

            transition: border-color 0.2s ease, box-shadow 0.2s ease;

        }


        .form-group input::placeholder {

            color: #999999;

        }


        .form-group input[type="text"]:focus,

        .form-group input[type="password"]:focus {

            border-color: #4b561b;

            box-shadow: 0 0 0 3px rgba(75, 86, 27, 0.12);

        }


        .remember-forgot {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-top: 5px;

            margin-bottom: 30px;

            font-size: 14px;

        }


        .remember-forgot label {

            display: flex;

            align-items: center;

            gap: 8px;

            color: #333333;

            cursor: pointer;

        }


        .remember-forgot input[type="checkbox"] {

            width: 17px;

            height: 17px;

            accent-color: #4b561b;

            cursor: pointer;

        }


        .remember-forgot a {

            color: #4b561b;

            font-weight: 600;

            text-decoration: none;

        }


        .remember-forgot a:hover {

            text-decoration: underline;

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

            transition: background 0.2s ease, transform 0.1s ease;

        }


        .submitBtn:hover {

            background: #3d4616;

        }


        .submitBtn:active {

            transform: translateY(1px);

        }


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


            .login-error,

            .login-success {

                margin-left: 25px;

                margin-right: 25px;

            }


            .account-register {

                padding: 22px 25px;

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


            .login-form {

                padding: 25px 20px;

            }


            .login-error,

            .login-success {

                margin-left: 20px;

                margin-right: 20px;

            }


            .remember-forgot {

                flex-direction: column;

                align-items: flex-start;

                gap: 14px;

            }

        }

    </style>

</head>


<body>

    <div class="navbar">

        <div class="logo">

            <a href="#">

                <img src="makanalogo.jpeg" alt="M-LINK">

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

                <h1>Login</h1>

                <p>Sign in to your M-LINK account.</p>

            </div>


            <?php if (!empty($error) && isset($errorMessages[$error])): ?>

                <div class="login-error">

                    <?= htmlspecialchars($errorMessages[$error]); ?>

                </div>

            <?php endif; ?>


            <?php if (!empty($success) && isset($successMessages[$success])): ?>

                <div class="login-success">

                    <?= htmlspecialchars($successMessages[$success]); ?>

                </div>

            <?php endif; ?>


            <form
                class="login-form"
                action="login_process.php"
                method="POST"
                autocomplete="off"
            >

                <div class="form-group">

                    <label for="username">Username</label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Enter your username"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                </div>


                <div class="remember-forgot">

                    <label>

                        <input type="checkbox" name="remember">

                        <span>Remember me</span>

                    </label>


                    <a href="#">Forgot Password?</a>

                </div>


                <div class="formButtons">

                    <button type="submit" class="submitBtn">

                        Login

                    </button>

                </div>

            </form>


            <div class="account-register">

                <p>

                    Don't have an account?

                    <a href="register.php">Register</a>

                </p>

            </div>

        </div>

    </main>

</body>

</html>