<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

session_start();

require_once "Error404Connection.php";
require_once "inputValidation.php";

//check if the administrator is logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}

$adminUsername = $_SESSION["username"];

//check if the logged in user is an administrator
$stmt = $conn->prepare("
SELECT username
FROM admin
WHERE username = ?
LIMIT 1
");

$stmt->bind_param("s", $adminUsername);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    die("Access denied.");

}

$stmt->close();

$message = "";
$messageType = "";

//check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

// Read every field as a plain string (rejects arrays, over-long values,
// invalid UTF-8 and control characters). Passwords are not trimmed.
$firstName = postString("first_name", 100);
$surname = postString("surname", 100);
$username = postString("username", 100);
$contact = postString("contact", 50);
$password = postString("password", 255, false);
$confirmPassword = postString("confirm_password", 255, false);

//check that every field could be read
if (
$firstName === null ||
$surname === null ||
$username === null ||
$contact === null ||
$password === null ||
$confirmPassword === null
) {

$message = "One or more fields are too long or contain characters that are not allowed.";
$messageType = "error";

//check if all fields are filled in
} elseif (
$firstName === "" ||
$surname === "" ||
$username === "" ||
$contact === "" ||
$password === "" ||
$confirmPassword === ""
) {

$message = "Please complete all fields.";
$messageType = "error";

//username: valid email, starts with a letter, ends with .com, max 50
} elseif (strlen($username) > 50) {

$message = "The email address must be 50 characters or fewer.";
$messageType = "error";

} elseif (preg_match('/^[0-9]/', $username) === 1) {

$message = "The email address cannot start with a number.";
$messageType = "error";

} elseif (preg_match('/\.com\z/i', $username) !== 1) {

$message = "The email address must end with .com";
$messageType = "error";

} elseif (
!filter_var($username, FILTER_VALIDATE_EMAIL) ||
!isValidRegistrationUsername($username)
) {

$message = "Please enter a valid email address that starts with a letter and ends with .com";
$messageType = "error";

//names: letters, spaces, hyphens and apostrophes only
} elseif (!isValidPersonName($firstName)) {

$message = "First name may only contain letters, spaces, hyphens and apostrophes (maximum 100 characters).";
$messageType = "error";

} elseif (!isValidPersonName($surname)) {

$message = "Surname may only contain letters, spaces, hyphens and apostrophes (maximum 100 characters).";
$messageType = "error";

//contact: digits only, exactly 10
} elseif (!isValidContactNumber($contact)) {

$message = "Contact number must contain exactly 10 digits (numbers only).";
$messageType = "error";

} elseif ($password !== $confirmPassword) {

$message = "Passwords do not match.";
$messageType = "error";

} elseif (
($passwordProblem = passwordStrengthError(
$password,
$username,
[$firstName, $surname]
)) !== null
) {

$message = $passwordProblem;
$messageType = "error";

} else {

$stmt = $conn->prepare("
SELECT username
FROM `user`
WHERE username = ?
LIMIT 1
");

$stmt->bind_param("s", $username);
$stmt->execute();

$userResult = $stmt->get_result();

$stmt->close();

if ($userResult->num_rows > 0) {

$message = "An account with this email already exists.";
$messageType = "error";

} else {

$stmt = $conn->prepare("
SELECT username
FROM admin
WHERE username = ?
LIMIT 1
");

$stmt->bind_param("s", $username);
$stmt->execute();

$adminResult = $stmt->get_result();

$stmt->close();

if ($adminResult->num_rows > 0) {

$message = "This administrator already exists.";
$messageType = "error";

} else {

$passwordHash = password_hash(
$password,
PASSWORD_DEFAULT
);

$conn->begin_transaction();

try {

$stmt = $conn->prepare("
INSERT INTO `user`
(
username,
first_Name,
surname,
contact,
password_hash,
User_role,
active,
verified
)
VALUES (?, ?, ?, ?, ?, 'Admin', 1, 1)
");

$stmt->bind_param(
"sssss",
$username,
$firstName,
$surname,
$contact,
$passwordHash
);

if (!$stmt->execute()) {

throw new Exception(
"Could not create user account."
);

}

$stmt->close();

$stmt = $conn->prepare("
INSERT INTO admin
(
username,
password_hashed,
contact,
registered_date
)
VALUES (?, ?, ?, NOW())
");

$stmt->bind_param(
"sss",
$username,
$passwordHash,
$contact
);

if (!$stmt->execute()) {

throw new Exception(
"Could not create administrator account."
);

}

$stmt->close();

$conn->commit();

// The creator's password is used only to satisfy the NOT NULL
// column and is never revealed to the new admin. Instead we send
// them a one-time verification code + link so they set their own
// password on first login, the same flow forgotPassword.php uses.

// Defensive: clear out any stray unused tokens for this username
// (should not normally exist for a brand-new account).
$oldStmt = $conn->prepare("
UPDATE password_reset_tokens
SET used = 1
WHERE username = ?
AND used = 0
");

if ($oldStmt) {
$oldStmt->bind_param("s", $username);
$oldStmt->execute();
$oldStmt->close();
}

$resetEmailSent = false;
$resetEmailError = "the account was created, but the setup email could not be prepared.";

$token = bin2hex(random_bytes(32));
$tokenHash = hash("sha256", $token);
$tokenExpiresAt = date("Y-m-d H:i:s", time() + (30 * 60));

$insertTokenStmt = $conn->prepare("
INSERT INTO password_reset_tokens
(username, token_hash, expires_at, used)
VALUES (?, ?, ?, 0)
");

if ($insertTokenStmt) {

$insertTokenStmt->bind_param("sss", $username, $tokenHash, $tokenExpiresAt);
$insertTokenStmt->execute();
$insertTokenStmt->close();

$verificationCode = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
$codeHash = hash("sha256", $verificationCode);
$codeExpiresAt = date("Y-m-d H:i:s", time() + (10 * 60));

$insertCodeStmt = $conn->prepare("
INSERT INTO password_reset_codes
(username, code_hash, expires_at, used)
VALUES (?, ?, ?, 0)
");

if ($insertCodeStmt) {

$insertCodeStmt->bind_param("sss", $username, $codeHash, $codeExpiresAt);

if ($insertCodeStmt->execute()) {

$resetId = $conn->insert_id;
$insertCodeStmt->close();

// NOTE: confirmverification.php currently reads
// $_SESSION['reset_token'] / $_SESSION['codes_reset_id'].
// That session belongs to the ADMIN who is creating this
// account, not the new admin opening the email on their own
// device, so those session values are of no use here. The
// reset_id / username are passed as query parameters instead;
// confirmverification.php will need a small update to accept
// and validate them from $_GET as a fallback when no matching
// session is present. Until that change is made, this link will
// not work end-to-end for a genuinely separate recipient.
$resetLink = "http://is3-dev.ict.ru.ac.za/SysDev/Error%20404/Makhanda-Link-Recovery/PUBLIC%20PAGES/confirmverification.php"
. "?reset_id=" . urlencode((string) $resetId)
. "&username=" . urlencode($username);

$mail = new PHPMailer(true);

try {

$mail->isSMTP();

$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;

$mail->Username = 'lizwifaith08@gmail.com';
$mail->Password = 'arnp vlny vxqw hwpf';

$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

$mail->setFrom(
'lizwifaith08@gmail.com',
'M-LINK'
);

$mail->addAddress($username);

$mail->isHTML(true);

$mail->Subject = 'Your M-LINK Administrator Account';

$safeFirstName = htmlspecialchars($firstName);
$safeUsername = htmlspecialchars($username);
$safeLink = htmlspecialchars($resetLink);

$mail->Body = "
<h2>Welcome to M-LINK</h2>

<p>Hello $safeFirstName,</p>

<p>An administrator account has been created for you
using this email address ($safeUsername).</p>

<p>For your security, no password has been sent by
email. Use the verification code below to confirm it's
you, then set your own password:</p>

<h1>$verificationCode</h1>

<p><a href=\"$safeLink\">Click here to set your password</a></p>

<p>This code and link expire in 10 minutes. If they
expire, use \"Forgot Password\" on the login page to
request a new code.</p>

<p>If you were not expecting this account, please
contact your system administrator immediately.</p>

<p>Regards,<br>M-LINK</p>
";

$mail->AltBody =
"An M-LINK administrator account has been created for "
. $username
. ". Your verification code is "
. $verificationCode
. ". Visit "
. $resetLink
. " to set your password. This expires in 10 minutes.";

$mail->send();

$resetEmailSent = true;

} catch (Exception $e) {

$resetEmailError = "the account was created, but the setup email could not be sent.";

}

} else {

$insertCodeStmt->close();
$resetEmailError = "the account was created, but a verification code could not be generated.";

}

}

}

if ($resetEmailSent) {

$message = "Administrator account created successfully. A setup email with a verification code and reset link has been sent to $username.";

} else {

$message = "Administrator account created, but " . $resetEmailError . " Ask them to use \"Forgot Password\" on the login page instead.";

}

$messageType = "success";

} catch (Exception $e) {

$conn->rollback();

$message = $e->getMessage();
$messageType = "error";

}

}

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

<title>M-Link | Add Admin</title>

<style>

* {
margin: 0;
padding: 0;
box-sizing: border-box;
}

body {
font-family: Arial, Helvetica, sans-serif;
background: #f4f0e7;
color: #1f2933;
min-height: 100vh;
}

.pageContainer {
width: 100%;
flex: 1;
padding: 24px 43px 50px;
}

.pageHeader {
width: 100%;
display: flex;
justify-content: space-between;
align-items: center;
margin-bottom: 25px;
}

.pageTitle {
margin: 0;
font-size: 28px;
font-weight: 700;
color: #263238;
}

.adminFormCard {
width: 100%;
background: #ffffff;
border: 1px solid #ddd7cc;
border-radius: 7px;
padding: 30px;
}

.adminFormCard h2 {
font-size: 22px;
color: #1f2933;
margin-bottom: 7px;
}

.adminFormCard .description {
color: #666666;
font-size: 14px;
margin-bottom: 28px;
}

.message {
width: 100%;
padding: 13px 15px;
margin-bottom: 22px;
border-radius: 5px;
font-size: 14px;
}

.message.success {
background: #d4edda;
color: #155724;
border: 1px solid #c3e6cb;
}

.message.error {
background: #f8d7da;
color: #721c24;
border: 1px solid #f5c6cb;
}

.formGrid {
display: grid;
grid-template-columns: 1fr 1fr;
gap: 20px 25px;
}

.formGroup {
width: 100%;
}

.formGroup.fullWidth {
grid-column: 1 / -1;
}

.formGroup label {
display: block;
margin-bottom: 8px;
font-size: 14px;
font-weight: 600;
color: #222222;
}

.formGroup input {
width: 100%;
height: 43px;
padding: 0 13px;
border: 1px solid #cfd1d3;
border-radius: 5px;
background: #ffffff;
color: #222222;
font-size: 14px;
outline: none;
}

.formGroup input:focus {
border-color: #5f7022;
box-shadow: 0 0 0 2px rgba(95, 112, 34, 0.10);
}

.formActions {
margin-top: 28px;
padding-top: 22px;
border-top: 1px solid #e0dbd1;
display: flex;
justify-content: flex-end;
}

.createAdminButton {
min-width: 180px;
height: 42px;
padding: 0 22px;
border: none;
border-radius: 5px;
background: #5f7022;
color: #ffffff;
font-size: 13px;
font-weight: 600;
cursor: pointer;
}

.createAdminButton:hover {
background: #4b591a;
}

@media (max-width: 750px) {

.pageContainer {
padding: 20px;
}

.pageHeader {
align-items: flex-start;
}

.formGrid {
grid-template-columns: 1fr;
}

.formGroup.fullWidth {
grid-column: auto;
}

}

@media (max-width: 500px) {

.pageHeader {
flex-direction: column;
gap: 15px;
}

.pageTitle {
font-size: 24px;
}

.adminFormCard {
padding: 22px;
}

.createAdminButton {
width: 100%;
}

}

</style>

<!-- FONTS + ICONS USED BY THE SHARED ADMIN SIDEBAR / TOPBAR -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Ubuntu:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<!-- SHARED ADMIN SIDEBAR / TOPBAR / FOOTER STYLES (keep this LAST in the head) -->
<link rel="stylesheet" href="adminLayout.css">

</head>

<body>

<?php
$adminActivePage = "addadmin";
require __DIR__ . "/adminHeader.php";
?>

<div class="pageContainer">

<header class="pageHeader">
<h1 class="pageTitle">
Add Administrator
</h1>
</header>

<div class="adminFormCard">

<h2>
Add Administrator
</h2>

<p class="description">
Create a new administrator account.
</p>

<?php if ($message !== ""): ?>

<div class="message <?= htmlspecialchars($messageType) ?>">

<?= htmlspecialchars($message) ?>

</div>

<?php endif; ?>

<form
method="POST"
action="addAdmin.php"
>

<div class="formGrid">

<div class="formGroup">

<label for="first_name">
First Name
</label>

<input
type="text"
id="first_name"
name="first_name"
maxlength="100"
title="Letters, spaces, hyphens and apostrophes only"
value="<?= htmlspecialchars(
$_POST["first_name"] ?? ""
) ?>"
required
>

</div>

<div class="formGroup">

<label for="surname">
Surname
</label>

<input
type="text"
id="surname"
name="surname"
maxlength="100"
title="Letters, spaces, hyphens and apostrophes only"
value="<?= htmlspecialchars(
$_POST["surname"] ?? ""
) ?>"
required
>

</div>

<div class="formGroup fullWidth">

<label for="username">
Email Address
</label>

<input
type="email"
id="username"
name="username"
maxlength="50"
pattern="(?!.*\.\.)[A-Za-z][A-Za-z0-9._%+\-]{0,63}@(?:[A-Za-z0-9](?:[A-Za-z0-9\-]*[A-Za-z0-9])?\.)+[Cc][Oo][Mm]"
title="Must start with a letter and end with .com (for example name@example.com)"
autocapitalize="none"
spellcheck="false"
value="<?= htmlspecialchars(
$_POST["username"] ?? ""
) ?>"
required
>

</div>

<div class="formGroup fullWidth">

<label for="contact">
Contact Number
</label>

<input
type="text"
id="contact"
name="contact"
maxlength="10"
pattern="[0-9]{10}"
value="<?= htmlspecialchars(
$_POST["contact"] ?? ""
) ?>"
required
>

</div>

<div class="formGroup">

<label for="password">
Password
</label>

<input
type="password"
id="password"
name="password"
maxlength="72"
title="8-72 characters with upper and lower case letters, a number and a special character"
required
>

</div>

<div class="formGroup">

<label for="confirm_password">
Confirm Password
</label>

<input
type="password"
id="confirm_password"
name="confirm_password"
maxlength="72"
required
>

</div>

</div>

<div class="formActions">

<button
type="submit"
class="createAdminButton"
>
Create Administrator
</button>

</div>

</form>

</div>

</div>

<?php require __DIR__ . "/adminFooter.php"; ?>

</body>

</html>
