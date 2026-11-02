<?php
/*
 * errorHandling.php
 * ---------------------------------------------------------------------
 * Keeps technical error details away from visitors.
 *
 *  - PHP warnings, notices and uncaught errors are written to the server's
 *    error log instead of being shown on the page.
 *  - dbErrorNotice() logs the real database error and hands back a short,
 *    safe message that can be shown to the visitor.
 *  - safeExceptionMessage() does the same for exceptions: database
 *    exceptions are logged and replaced by a safe message, other
 *    exceptions (your own validation messages) are shown unchanged.
 *
 * Where to read the real errors: the PHP / Apache error log
 * (XAMPP: xampp/apache/logs/error.log). Search for "[M-LINK]".
 *
 * Every page includes this file first (before Error404Connection.php).
 * ---------------------------------------------------------------------
 */

ini_set("display_errors", "0");
ini_set("display_startup_errors", "0");
ini_set("log_errors", "1");
error_reporting(E_ALL);


if (!function_exists("dbErrorNotice")) {

    /**
     * Log a database error and return the safe text to show instead.
     *   $context = what was being attempted, e.g. "Could not load tickets"
     *   $detail  = the raw error ($conn->error, $stmt->error, ...)
     */
    function dbErrorNotice($context, $detail = "")
    {
        $context = rtrim(trim((string)$context), " :");

        error_log("[M-LINK] " . $context . " | " . (string)$detail);

        return $context . ". Please try again later.";
    }

}


if (!function_exists("safeExceptionMessage")) {

    /**
     * Message that is safe to show for a caught exception.
     * Database exceptions may contain table names, column names or SQL,
     * so they are logged and replaced.
     */
    function safeExceptionMessage($exception)
    {
        if ($exception instanceof \mysqli_sql_exception) {

            error_log(
                "[M-LINK] Database exception: " .
                $exception->getMessage()
            );

            return "Sorry, something went wrong on our side. Please try again later.";

        }

        return $exception->getMessage();
    }

}


// Anything that is still uncaught shows a plain message, not a stack trace.
set_exception_handler(function ($exception) {

    error_log(
        "[M-LINK] Uncaught " . get_class($exception) . ": " .
        $exception->getMessage() . " in " .
        $exception->getFile() . ":" . $exception->getLine()
    );

    if (!headers_sent()) {

        http_response_code(500);

    }

    echo "Sorry, something went wrong on our side. Please try again later.";

});


// Fatal errors end the script; show the same plain message.
register_shutdown_function(function () {

    $error = error_get_last();

    if (
        $error !== null &&
        in_array(
            $error["type"],
            [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR],
            true
        )
    ) {

        error_log(
            "[M-LINK] Fatal error: " . $error["message"] . " in " .
            $error["file"] . ":" . $error["line"]
        );

        if (!headers_sent()) {

            http_response_code(500);

        }

        echo "Sorry, something went wrong on our side. Please try again later.";

    }

});