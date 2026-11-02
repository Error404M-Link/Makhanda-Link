<?php
/*
 * setupMessageEncryption.php  -  RUN ONCE, THEN DELETE THIS FILE
 * ---------------------------------------------------------------------
 * 1. creates the encryption key file (only if it does not exist yet -
 *    an existing key is never overwritten),
 * 2. widens internal_messages.subject so the encrypted subject fits,
 * 3. encrypts every message that is still plain text.
 *
 * Safe to run more than once: rows that are already encrypted are skipped.
 *
 * BEFORE YOU RUN IT: take a backup of the database (Workbench: Server >
 * Data Export). AFTER: back up the key file too - without it the
 * encrypted messages cannot be read.
 *
 * HOW TO RUN: open http://localhost/<your-folder>/setupMessageEncryption.php
 * in a browser on the server, or run  php setupMessageEncryption.php
 * It refuses to run for visitors on other computers.
 * ---------------------------------------------------------------------
 */

$isCli = (PHP_SAPI === "cli");

$isLocal = in_array(
    $_SERVER["REMOTE_ADDR"] ?? "",
    ["127.0.0.1", "::1"],
    true
);

if (!$isCli && !$isLocal) {

    http_response_code(403);
    exit("Forbidden: this script can only be run on the server itself.");

}

if (!$isCli) {

    header("Content-Type: text/plain; charset=UTF-8");

}

require_once "Error404Connection.php";
require_once "messageCrypto.php";

// ---- 1. key ---------------------------------------------------------
if (getenv("MLINK_MESSAGE_KEY")) {

    echo "Key: using the MLINK_MESSAGE_KEY environment variable.\n";

} elseif (messageKeyFile() !== null) {

    echo "Key: existing key file found (" . messageKeyFile() . ") - keeping it.\n";

} else {

    $hex = bin2hex(random_bytes(32));

    $contents =
        "<?php\n" .
        "// M-LINK message encryption key. KEEP SECRET. BACK UP. NEVER COMMIT.\n" .
        "if (!defined(\"MLINK_KEY_LOADER\")) { http_response_code(403); exit; }\n" .
        "return \"" . $hex . "\";\n";

    if (file_put_contents(MESSAGE_KEY_FILE, $contents, LOCK_EX) === false) {

        exit("Could not create the key file at " . MESSAGE_KEY_FILE . " (check folder permissions).\n");

    }

    @chmod(MESSAGE_KEY_FILE, 0600);

    echo "Key: created " . MESSAGE_KEY_FILE . "\n";
    echo "     BACK THIS FILE UP NOW and keep it out of GitHub.\n";

}

try {

    messageKey();

} catch (Throwable $error) {

    exit("Key problem: " . $error->getMessage() . "\n");

}

// ---- 2. widen the subject column -----------------------------------
try {

    if (!$conn->query("ALTER TABLE internal_messages MODIFY subject VARCHAR(1000) NOT NULL")) {

        throw new RuntimeException($conn->error);

    }

    echo "Column: subject is now VARCHAR(1000).\n";

} catch (Throwable $error) {

    exit("Could not widen the subject column: " . $error->getMessage() . "\nNothing was encrypted.\n");

}

// ---- 3. encrypt the existing plain-text rows -----------------------
$rows = $conn->query("SELECT message_id, subject, message_body FROM internal_messages");

if (!$rows) {

    exit("Could not read internal_messages.\n");

}

$update = $conn->prepare(
    "UPDATE internal_messages SET subject = ?, message_body = ? WHERE message_id = ?"
);

$encrypted = 0;
$skipped   = 0;

while ($row = $rows->fetch_assoc()) {

    if (isEncryptedText($row["subject"]) && isEncryptedText($row["message_body"])) {

        $skipped++;
        continue;

    }

    $subject = isEncryptedText($row["subject"]) ? $row["subject"] : encryptText($row["subject"]);
    $body    = isEncryptedText($row["message_body"]) ? $row["message_body"] : encryptText($row["message_body"]);
    $id      = (int)$row["message_id"];

    $update->bind_param("ssi", $subject, $body, $id);
    $update->execute();

    $encrypted++;

}

$update->close();

echo "Messages encrypted now:      " . $encrypted . "\n";
echo "Already encrypted (skipped): " . $skipped . "\n";
echo "\nDone. Delete setupMessageEncryption.php from the server now.\n";