<?php
/*
 * inputValidation.php
 * ####################################################################
 * Server-side input checks shared by the public pages
 * (login_process.php now, urreg.php and the organisation registration
 * handler next).
 *
 * Browser-side checks (HTML attributes / JavaScript) only guide honest
 * users. These functions are the checks that actually protect the
 * system, because they run on the server where they cannot be bypassed.
 *
 * NOTE: these functions validate. They do NOT replace prepared
 * statements. Always keep using $conn->prepare() + bind_param() for
 * every query that contains user input.
 * #####################################################################
 */


/**
 * Registration username (an email address) rules:
 *  - must start with a letter (not a number or symbol)
 *  - local part may only contain letters, digits and . _ % + -
 *  - no two dots in a row, no dot directly before the @
 *  - must end with ".com" (any letter case)
 *  - maximum 50 characters (the `username` column is VARCHAR(50);
 *    longer values would be rejected or, on a lenient server,
 *    silently cut off, which could make two accounts collide)
 */
function isValidRegistrationUsername($value)
{
    if (!is_string($value)) {

        return false;

    }

    if ($value === "" || strlen($value) > 50) {

        return false;

    }

    $pattern =
        '/\A(?!.*\.\.)'
        . '[A-Za-z][A-Za-z0-9._%+\-]{0,63}(?<!\.)'
        . '@'
        . '(?:[A-Za-z0-9](?:[A-Za-z0-9\-]*[A-Za-z0-9])?\.)+'
        . 'com\z/i';

    return preg_match($pattern, $value) === 1;
}


/**
 * Person name rules (first name / surname):
 *  - letters (any language), spaces, hyphens and apostrophes only
 *  - must start and end with a letter
 *  - 1 to 100 characters (the columns are VARCHAR(100))
 * Allows names such as O'Brien, Mary-Jane and Van der Merwe.
 */
function isValidPersonName($value)
{
    if (!is_string($value) || $value === "") {

        return false;

    }

    if (preg_match('//u', $value) !== 1) {

        return false;

    }

    if (preg_match_all('/./us', $value) > 100) {

        return false;

    }

    return preg_match(
        '/\A\p{L}(?:[\p{L}\p{M}\x{2019}\' \-]*[\p{L}\p{M}])?\z/u',
        $value
    ) === 1;
}


/**
 * Organisation name rules:
 *  - letters and digits, spaces and the symbols & ' . -
 *  - must start with a letter or digit
 *  - 2 to 100 characters
 * (same characters the organisation form already allows)
 */
function isValidOrganisationName($value)
{
    if (!is_string($value)) {

        return false;

    }

    return preg_match(
        '/\A[A-Za-z0-9][A-Za-z0-9&\'. \-]{1,99}\z/',
        $value
    ) === 1;
}


/**
 * Address rules (optional field, so an empty string is accepted):
 *  - letters (any language), digits, spaces, line breaks and , . - ' / #
 *  - maximum 255 characters (the column is VARCHAR(255))
 */
function isValidAddress($value)
{
    if (!is_string($value)) {

        return false;

    }

    if ($value === "") {

        return true;

    }

    if (preg_match('//u', $value) !== 1) {

        return false;

    }
    if (preg_match_all('/./us', $value) > 255) {

        return false;

    }
    return preg_match(
        '/\A[\p{L}\p{M}0-9 \r\n,.\-\x{2019}\'\/#]+\z/u',
        $value
    ) === 1;
}


/**
 * Contact number rules: exactly 10 digits (0-9) and nothing else.
 * \z (not $) is used so a trailing newline is not accepted.
 */
function isValidContactNumber($value)
{
    if (!is_string($value)) {

        return false;

    }

    return preg_match('/\A[0-9]{10}\z/', $value) === 1;
}


/**
 * Safely read a text field from $_POST.
 *
 * Returns the cleaned string, or NULL when the value is not acceptable:
 *  - the field is missing, or is not a plain string (e.g. username[]=x)
 *  - it is longer than $maxLength bytes
 *  - it is not valid UTF-8
 *  - it contains control characters (NUL, escape codes, ...)
 *    and, unless $allowNewlines is true, tabs / line breaks as well
 *
 * $trim = false must be used for passwords so spaces are not removed.
 */
function postString($key, $maxLength = 255, $trim = true, $allowNewlines = false)
{
    if (!isset($_POST[$key]) || !is_string($_POST[$key])) {

        return null;

    }

    $value = $_POST[$key];

    if ($trim) {

        $value = trim($value);

    }

    if (strlen($value) > $maxLength) {

        return null;

    }

    if (preg_match('//u', $value) !== 1) {

        return null;

    }

    $controlCharacters =
        $allowNewlines
            ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/'
            : '/[\x00-\x1F\x7F]/';

    if (preg_match($controlCharacters, $value) === 1) {

        return null;

    }

    return $value;
}


/**
 * Password strength check for NEW passwords.
 * Returns an error message (string) when the password is too weak, or
 * NULL when it is acceptable.
 *
 * Rules:
 *  - 8 to 72 characters (72 bytes is the most bcrypt uses)
 *  - at least one lowercase letter, one uppercase letter, one number
 *    and one special character (for example ! @ # $ % & *)
 *  - at least 5 different characters (so "Aa1!Aa1!" is not enough)
 *  - not a common password (password, qwerty, 12345678, ...)
 *  - must not contain the email name, first name or surname
 *
 * $username = the email address, $names = extra words it must not contain.
 */
function passwordStrengthError($password, $username = "", $names = [])
{
    if (!is_string($password)) {

        return "Please enter a valid password.";

    }

    if (strlen($password) < 8) {

        return "Password must be at least 8 characters.";

    }

    if (strlen($password) > 72) {

        return "Password must be 72 characters or fewer.";

    }

    if (
        preg_match('/\p{Ll}/u', $password) !== 1 ||
        preg_match('/\p{Lu}/u', $password) !== 1 ||
        preg_match('/[0-9]/', $password) !== 1 ||
        preg_match('/[^\p{L}\p{N}\s]/u', $password) !== 1
    ) {

        return "Password must contain an uppercase letter, a lowercase letter, a number and a special character (for example ! @ # $ %).";

    }

    $characters = preg_split('//u', $password, -1, PREG_SPLIT_NO_EMPTY);

    if (count(array_unique($characters)) < 5) {

        return "Password must contain at least 5 different characters.";

    }

    $lower = mb_strtolower($password, "UTF-8");

    $commonWords = [
        "password", "passw0rd", "p@ssword", "qwerty", "asdfgh", "zxcvbn",
        "123456", "12345678", "111111", "letmein", "welcome", "iloveyou",
        "admin", "abc123", "monkey", "dragon", "football", "sunshine",
        "changeme", "mlink", "m-link", "makhanda", "makana"
    ];

    foreach ($commonWords as $word) {

        if (strpos($lower, $word) !== false) {

            return "That password is too easy to guess. Please choose a less common password.";

        }

    }

    // each name such as "Van der Merwe" is checked whole and word by word 
    $forbidden = [];

    foreach ($names as $fullName) {

        if (!is_string($fullName)) {

            continue;

        }

        $forbidden[] = $fullName;

        foreach (preg_split('/[\s\-\x{2019}\']+/u', $fullName, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {

            $forbidden[] = $part;

        }

    }

    if (is_string($username) && $username !== "") {

        $forbidden[] = strstr($username, "@", true) ?: $username;

    }

    foreach ($forbidden as $word) {

        if (!is_string($word)) {

            continue;

        }

        $word = mb_strtolower(trim($word), "UTF-8");

        if (mb_strlen($word, "UTF-8") >= 3 && strpos($lower, $word) !== false) {

            return "Password must not contain your name or email address.";

        }

    }

    return null;
}