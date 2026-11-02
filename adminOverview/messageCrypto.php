<?php
/*
 * messageCrypto.php
 * ---------------------------------------------------------------------
 * Encrypts / decrypts the text of internal messages (subject and body)
 * with AES-256-GCM (OpenSSL). GCM is "authenticated" encryption: if the
 * stored value is edited in the database, decryption fails instead of
 * returning altered text.
 *
 *   encryptText($plain)   -> "enc:v1:" + base64(iv + tag + ciphertext)
 *   decryptText($stored)  -> the original text
 *
 * Rows saved before encryption was switched on are plain text (no
 * "enc:v1:" prefix). decryptText() returns those unchanged, so old
 * messages keep displaying until setupMessageEncryption.php converts them.
 *
 * THE KEY
 *   32 random bytes, kept as 64 hex characters. It is read from:
 *     1. the environment variable MLINK_MESSAGE_KEY, or
 *     2. the file below (messageKey.php, created by
 *        setupMessageEncryption.php).
 *   ONE key is used by every folder that shows messages (see
 *   messageKeyFile() below for where it is looked for). Best practice: move
 *   messageKey.php to a folder ABOVE the web root and point
 *   MESSAGE_KEY_FILE there. Never commit it to GitHub.
 *   BACK THE KEY UP: without it, encrypted messages cannot be read again.
 * ---------------------------------------------------------------------
 */

// Where setupMessageEncryption.php creates the key file.
const MESSAGE_KEY_FILE = __DIR__ . "/messageKey.php";


/**
 * Finds the key file. Every folder that shows messages uses the SAME key,
 * so the file is looked for in this order:
 *   1. the same folder as this file
 *   2. the parent folder (one shared copy for all folders)
 *   3. the AdminOverview folder next to this one
 * Returns the path, or NULL when there is no key file.
 */
function messageKeyFile()
{
    $candidates = [
        MESSAGE_KEY_FILE,
        dirname(__DIR__) . "/messageKey.php",
        dirname(__DIR__) . "/AdminOverview/messageKey.php",
    ];

    foreach ($candidates as $path) {

        if (is_file($path)) {

            return $path;

        }

    }

    return null;
}

const MESSAGE_CIPHER = "aes-256-gcm";
const MESSAGE_PREFIX = "enc:v1:";
const MESSAGE_IV_BYTES = 12;
const MESSAGE_TAG_BYTES = 16;


/**
 * Returns the 32-byte key, or throws if it is missing / malformed.
 */
function messageKey()
{
    static $key = null;

    if ($key !== null) {

        return $key;

    }

    $hex = getenv("MLINK_MESSAGE_KEY");

    if (!is_string($hex) || $hex === "") {

        $keyFile = messageKeyFile();

        if ($keyFile === null) {

            throw new RuntimeException("Message encryption key file is missing.");

        }

        defined("MLINK_KEY_LOADER") || define("MLINK_KEY_LOADER", true);

        $hex = include $keyFile;

    }

    if (
        !is_string($hex) ||
        strlen($hex) !== 64 ||
        !ctype_xdigit($hex)
    ) {

        throw new RuntimeException("Message encryption key is not valid.");

    }

    $key = hex2bin($hex);

    return $key;
}


function isEncryptedText($value)
{
    return is_string($value) && strpos($value, MESSAGE_PREFIX) === 0;
}


function encryptText($plain)
{
    if (!function_exists("openssl_encrypt")) {

        throw new RuntimeException("The OpenSSL extension is not enabled.");

    }

    $iv  = random_bytes(MESSAGE_IV_BYTES);
    $tag = "";

    $cipher = openssl_encrypt(
        (string)$plain,
        MESSAGE_CIPHER,
        messageKey(),
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        "",
        MESSAGE_TAG_BYTES
    );

    if ($cipher === false || strlen($tag) !== MESSAGE_TAG_BYTES) {

        throw new RuntimeException("Encryption failed.");

    }

    return MESSAGE_PREFIX . base64_encode($iv . $tag . $cipher);
}


/**
 * Decrypts a stored value. Plain-text (older) rows are returned as they
 * are. A value that cannot be decrypted shows a placeholder and is logged.
 */
function decryptText($stored)
{
    if (!isEncryptedText($stored)) {

        return (string)$stored;

    }

    $placeholder = "[message could not be decrypted]";

    try {

        $raw = base64_decode(substr($stored, strlen(MESSAGE_PREFIX)), true);

        if ($raw === false || strlen($raw) < MESSAGE_IV_BYTES + MESSAGE_TAG_BYTES) {

            return $placeholder;

        }

        $iv     = substr($raw, 0, MESSAGE_IV_BYTES);
        $tag    = substr($raw, MESSAGE_IV_BYTES, MESSAGE_TAG_BYTES);
        $cipher = substr($raw, MESSAGE_IV_BYTES + MESSAGE_TAG_BYTES);

        $plain = openssl_decrypt(
            $cipher,
            MESSAGE_CIPHER,
            messageKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return $plain === false ? $placeholder : $plain;

    } catch (\Throwable $error) {

        error_log("[M-LINK] Message decryption problem: " . $error->getMessage());

        return $placeholder;

    }
}