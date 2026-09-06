<STYLE>
    /* /* /* /* ==================================================
   M-LINK LOGIN
   Matches Registration Page Style
================================================== */


/* ==================================================
   RESET
================================================== */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, Helvetica, sans-serif;

    background: #f4f0e7;

    color: #111;

    min-height: 100vh;
}


/* ==================================================
   NAVIGATION / LOGO
================================================== */

.navbar {
    height: 76px;

    width: 100%;

    background: #4b561b;

    display: flex;

    justify-content: center;

    align-items: center;

    box-shadow:
        0 2px 5px rgba(0, 0, 0, 0.12);
}

.logo img {
    width: 56px;

    height: 56px;

    object-fit: contain;

    background: #fff;

    padding: 5px;
}

.logo a {
    display: flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;
}


/* ==================================================
   BACK BUTTON
================================================== */

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

    margin: 0 auto;

    padding: 0 20px;
}

.backBtn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 9px 17px;

    background: #ffffff;

    color: #333;

    border-radius: 7px;

    text-decoration: none;

    font-size: 15px;

    font-weight: 500;

    box-shadow:
        0 1px 3px rgba(0, 0, 0, 0.08);
}

.backBtn:hover {

    background: #eee9dd;

    color: #4b561b;
}


/* ==================================================
   LOGIN PAGE
================================================== */

.login-page {

    width: 100%;

    padding: 40px 20px 60px;

    display: flex;

    justify-content: center;

    align-items: flex-start;
}


/* ==================================================
   LOGIN CONTAINER
================================================== */

.login-container {

    width: 100%;

    max-width: 900px;

    background: #ffffff;

    border-radius: 11px;

    overflow: hidden;

    box-shadow:
        0 12px 30px rgba(0, 0, 0, 0.10);
}


/* ==================================================
   LOGIN HEADER
================================================== */

.login-header {

    background: #4b561b;

    padding: 36px 40px 32px;

    position: relative;

    overflow: hidden;
}


/*
   Decorative circle similar to
   your registration page
*/

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


/* ==================================================
   FORM AREA
================================================== */

.login-form {

    padding: 40px;

    background: #ffffff;
}


/* ==================================================
   FORM GROUP
================================================== */

.form-group {

    margin-bottom: 25px;
}

.form-group label {

    display: block;

    margin-bottom: 8px;

    color: #111;

    font-size: 14px;

    font-weight: 600;
}


/* ==================================================
   INPUTS
================================================== */

.form-group input[type="text"],
.form-group input[type="password"] {

    width: 100%;

    height: 48px;

    padding: 0 14px;

    border: 1px solid #cfcac0;

    border-radius: 7px;

    background: #ffffff;

    color: #222;

    font-size: 15px;

    outline: none;

    transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease;
}


.form-group input::placeholder {

    color: #999;
}


.form-group input[type="text"]:focus,
.form-group input[type="password"]:focus {

    border-color: #4b561b;

    box-shadow:
        0 0 0 3px rgba(75, 86, 27, 0.12);
}


/* ==================================================
   REMEMBER / FORGOT
================================================== */

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

    color: #333;

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


/* ==================================================
   BUTTON AREA
================================================== */

.formButtons {

    border-top: 1px solid #ddd8ce;

    padding-top: 25px;

    display: flex;

    justify-content: flex-end;
}


/* ==================================================
   LOGIN BUTTON
================================================== */

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

    transition:
        background 0.2s ease,
        transform 0.1s ease;
}


.submitBtn:hover {

    background: #3d4616;
}


.submitBtn:active {

    transform: translateY(1px);
}


/* ==================================================
   REGISTER LINK
================================================== */

.account-register {

    border-top: 1px solid #ddd8ce;

    padding: 25px 40px;

    text-align: center;

    background: #ffffff;
}


.account-register p {

    color: #777;

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


/* ==================================================
   MOBILE
================================================== */

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


/* ==================================================
   SMALL MOBILE
================================================== */

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

    .remember-forgot {

        flex-direction: column;

        align-items: flex-start;

        gap: 14px;
    }

}
</STYLE>

<form class="login-form" action="login_process.php" method="POST">

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